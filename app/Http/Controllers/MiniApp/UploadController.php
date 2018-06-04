<?php

namespace App\Http\Controllers\MiniApp;

use App\Http\Controllers\Controller;
use BaiduFace\Api\AipFace;
use Godruoyi\LaravelOCR\OCR;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Validator;
use Log;

class UploadController extends Controller
{
    private $degree; // 人脸对比结果
    private $identityCardNum; // 身份证上传后的临时路径
    private $selfie; // 自拍照上传后的临时路径

    private $appId;
    private $appKey;
    private $appSecret;

    public function __construct()
    {
        $this->appId = config('ai.appId');
        $this->appKey = config('ai.apiKey');
        $this->appSecret = config('ai.apiSecret');
    }

    // 上传图片到 bos
    public function upload()
    {
        //接收上传文件
        $file = request()->file('mydadao');
        if(!$file){
            return responce(400,'没有收到您上传的图片');
        }
        // 参数验证
        $error = Validator::make(request()->all(), [
            'userName' => 'required', // 姓名
            'identityCardNum' => 'required', // 身份证号码
            'imgType' => 'required', // 图片类型参数
            'userId' => 'required', // 图片类型参数
        ], [
                'userName.required' => '姓名传递有误',
                'identityCardNum.required' => '身份证传递有误',
                'imgType.required' => '图片类型传递有误',
                'userId.required' => '用户标识传递有误',
           ]
        );

        // 参数有错误 直接返回错误信息
        if($error->fails()){
            return responce(400,$error->errors()->first());
        }

        $userName = request('userName'); // 用户名
        $identityCardNum = request('identityCardNum'); // 身份证号码
        $user_id = request('userId'); // 用户id
        $realPath = $file->getRealPath(); // 图片临时路径
        $imageContent = file_get_contents($realPath); // 图片二进制流
        // 判断是否是图片
        if(!in_array( strtolower($file->extension()),['jpeg','jpg','gif','gpeg','png'])){
            return responce(400,'您上传的图片类型有误');
        }
        //接收上传文件
        $imgType = request('imgType');
        if($imgType == 1){ // 身份证正面上传
            // 人脸检测
            $res = $this->detect($realPath);
            if($res == false || $res < 1){
                return responce(400,'请拍摄本人身份证正面');
            }
            // 身份证文字识别校验
            $identityPositivePictureInfo = $this->idcard($realPath,'front');
            if($identityPositivePictureInfo){
                $userNameCheck = $identityPositivePictureInfo['words_result']['姓名']['words'];
                $identityCardNumCheck = $identityPositivePictureInfo['words_result']['公民身份号码']['words'];
                if($userName != $userNameCheck || $identityCardNum != $identityCardNumCheck){
                    return responce(400,'请拍摄本人身份证正面');
                }
                Cache::forever($user_id.'_identfront',$identityPositivePictureInfo); // 存储身份证文字识别结果
            }else{
                return responce(400,'请拍摄本人身份证正面');
            }
        }elseif ($imgType == 2){ // 身份证反面
            $identityOppositePicturenfo = $this->idcard($realPath,'back');
            if($identityOppositePicturenfo){
                // 获取到的身份证反面存入缓存
                Cache::forever($user_id.'_identback',$identityOppositePicturenfo);
            }else{
                return responce(400,'请拍摄本人身份证背面');
            }
        }elseif ($imgType == 3){ // 驾驶证正面
            // 人脸检测
            $res = $this->detect($realPath);
            if($res == false || $res < 1){
                return responce(400,'请拍摄本人驾驶正本');
            }
            // 驾驶证文字识别
            $licensePictureInfo = $this->driving($realPath);
            if(!$licensePictureInfo){ // 识别失败
                return responce(400,'请拍摄本人驾驶正本');
            }
            // 验证驾驶证姓名与身份证号码
            $userNameCheck = $licensePictureInfo['words_result']['姓名']['words'];
            $identityCardNumCheck = $licensePictureInfo['words_result']['证号']['words'];
            if($userName != $userNameCheck || $identityCardNum != $identityCardNumCheck){
                return responce(400,'请拍摄本人驾驶正本');
            }
            // 获取到的身份证反面存入缓存
            Cache::forever($user_id.'_license',$licensePictureInfo);
        }elseif ($imgType == 4){
            // 驾驶证文字识别
            $licensePictureInfo = $this->driving($realPath);
            if(!$licensePictureInfo){
                return responce(400,'请拍摄本人驾驶副本');
            }
            // 验证驾驶证副本姓名与身份证号码
            $userNameCheck = $licensePictureInfo['words_result']['姓名']['words'];
            $identityCardNumCheck = $licensePictureInfo['words_result']['证号']['words'];
            if($userName != $userNameCheck || $identityCardNum != $identityCardNumCheck){
                return responce(400,'请拍摄本人驾驶副本');
            }
        }elseif ($imgType == 5){ // 自拍照
            // 人脸检测
            $res = $this->detect($realPath);
            if($res == false || $res < 1){
                return responce(400,'请拍摄本人自拍');
            }
        }

        // 文件是否上传成功
        if($file->isValid()){
            // 临时绝对路径
            $fileName = time().mt_rand(1234,9876);
            $realPath = $file->getRealPath();
            $re = Storage::disk('bos')->put($fileName,$imageContent);
            if($re){
                return responce(200,'上传成功',$fileName);
            }else{
                Log::error("文件上传到百度bos失败");
                return responce(400,'上传失败');
            }
        }else{
            Log::error("文件上传失败");
        }
    }

    /*
     *  身份证文字识别
     * */
    private function idcard($img,$type = 'front')
    {
        $identityPositivePictureInfo = OCR::baidu()->idcard($img,[
            'detect_direction'      => false,      //是否检测图像朝向
            'id_card_side'          => $type,    //front：身份证正面；back：身份证背面 （注意，该参数必选）
            'detect_risk'           => false,      //是否开启身份证风险类型功能，默认false
        ]);
        if(array_key_exists('error_code',$identityPositivePictureInfo)){
            if($type == 'back'){
                Log::info('身份证泛反面识别失败 '.json_encode($identityPositivePictureInfo,JSON_UNESCAPED_UNICODE));
                return false;
            }else{
                Log::info('身份证正面识别失败 '.json_encode($identityPositivePictureInfo,JSON_UNESCAPED_UNICODE));
                return false;
            }
        }
        return $identityPositivePictureInfo;
    }

    /*
     *  驾驶证文字识别
     * */
    private function driving($realPath)
    {
        $licensePictureInfo = OCR::baidu()->drivingLicense($realPath);
        if(array_key_exists('error_code',$licensePictureInfo)){
            Log::info('驾驶证上传失败 '.$licensePictureInfo);
            return false;
        }
        return $licensePictureInfo;
    }

    /*
     *  人脸检测
     * */
    private function detect($image)
    {
        $client = new AipFace($this->appId, $this->appKey, $this->appSecret);
        $image = file_get_contents($image);
        // 调用人脸检测
        $data = $client->detect($image);

        Log::info('人脸检测结果 === '.json_encode($data));
        if(array_key_exists('error_codde',$data) || $data['result_num'] < 1){
            Log::info('没有检测到人脸');
            return false;
        }else{
            return $data['result'][0]['face_probability'];
        }

    }

    /*
     *  人脸对比
     * */
    private function faceMetch($images)
    {
        $client = new AipFace($this->appId, $this->appKey, $this->appSecret);
        // 调用人脸检测
        $data = $client->match($images);
        if(!array_key_exists('error_code',$data)){
            Log::info("人脸对比结果 === ".$data['result'][0]['score']);
            return $data['result'][0]['score']; // 人脸比对结果
        }else{
            Log::info("人脸对比失败 === ".json_encode($data));
            return false;
        }
    }

    public function submitImage()
    {
        // 参数验证
        $error = Validator::make(request()->all(), [
            'userName' => 'required', // 姓名
            'identityCardNum' => 'required', // 身份证号码
            'userId' => 'required', // 用户id
            'identityPositivePicture' => 'required', // 身份证正面图片 id
            'identityOppositePicture' => 'required', // 身份证反面图片 id
            'licensePicture' => 'required', // 驾驶证图片 id
            'licensePictureCopy' => 'required', // 驾驶证副本图片 id
            'selfie' => 'required', // 自拍照图片 id
        ], [
                'userName.required' => '姓名传递有误',
                'identityCardNum.required' => '身份证传递有误',
                'userId.required' => '用户标识传递有误',
                'identfront.required' => '身份证正面图片id传递有误',
                'identback.required' => '身份证反面图片id传递有误',
                'license.required' => '驾驶证正面图片id传递有误',
                'licenseback.required' => '驾驶证副本图片id传递有误',
                'selfie.required' => '用户标识传递有误',
            ]
        );
        // 参数有错误 直接返回错误信息
        if($error->fails()){
            return responce(400,$error->errors()->first());
        }

        //接收参数
        $userName = request('userName'); // 用户名
        $identityCardNum = request('identityCardNum'); // 身份证号码
        $user_id = request('userId'); // 用户id
        $selfie = request('selfie'); // 自拍照图片id
        $identityPositivePicture = request('identityPositivePicture'); // 身份证正面图片 id

        $identfrontText = Cache::get($user_id.'_identfront'); // 身份证正面文字识别结果
        $identbackText = Cache::get($user_id.'_identback'); // 身份证反面文字识别结果
        $licenseText = Cache::get($user_id.'_license'); // 驾驶证文字识别结果

        $identfrontUrl = Storage::disk('bos')->url($identityPositivePicture); // 身份证正面图片链接
        $selfieUrl = Storage::disk('bos')->url($selfie); // 身份证正面图片链接

        // 进行人脸对比
        $images = [
            file_get_contents($identfrontUrl),
            file_get_contents($selfieUrl),
        ];
        $degree = $this->faceMetch($images); // 人脸对比相似度结果
        if(!$degree){
            return responce(400,'人脸对比失败');
        }

        $status = 1;
        $datas = request()->all(); // 绑定参数到变量
        $datas['status'] = $status;
        $datas['degree'] = $degree;
        $datas['identityPositiveInfoJsonStr'] = $identfrontText;
        $datas['identityOppositeInfoJsonStr'] = $identbackText;
        $datas['licenseInfoJsonStr'] = $licenseText;
        $datas['platform'] = 3; // 平台类型 3是小程序
        // licensePicture
        unset($datas['userId']);
        dump($datas);

        $apiUrl = "https://xcx-dev2.mydadao.com/v/user/upload-user-info";
        $data = curl_post($apiUrl,$datas);
        dump($data);

        return responce(200,'成功',$datas);

    }



}
