<?php

namespace App\Http\Controllers\Dadao;

use App\Http\Controllers\Controller;
use BaiduFace\Api\AipFace;
use Godruoyi\LaravelOCR\OCR;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Log;

class TestController extends Controller
{
    public function test()
    {

        echo "<img src='http://testincar.bj.bcebos.com/15278415428965' width='80' />";

        echo "<img src='http://testincar.bj.bcebos.com/15278413397906' width='80' />";
        die;

        $images = [
            file_get_contents('http://online-incar.bj.bcebos.com/23427227653161'),
            file_get_contents('http://online-incar.bj.bcebos.com/23427227653206'),
        ];

        $images = [
            file_get_contents('http://testincar.bj.bcebos.com/15278415428965'),
            file_get_contents('http://testincar.bj.bcebos.com/15278413397906'),
        ];

        $re = $this->faceMetch($images);
        dump($re);

        die;

//        $data = Cache::get('12456_ident');
//        dump($data);
//        $re = Cache::pull('12456_ident');
//        dump($re);
//        dump($data);
//        die;

//        $arr = [
//            'aa' => 'aa',
//            'bb' => 'bb',
//            'cc' => 'cc',
//        ];
//
//        var_dump($arr);


        $appId = config('ai.appId');
        $appKey = config('ai.apiKey');
        $appSecret = config('ai.apiSecret');
        $client = new AipFace($appId, $appKey, $appSecret);

        $image = file_get_contents('http://online-incar.bj.bcebos.com/23453227823919');
        $image = file_get_contents('http://online-incar.bj.bcebos.com/23453227823919');
//        $image = file_get_contents('http://online-incar.bj.bcebos.com/23453227823873');
//        $image = file_get_contents('http://online-incar.bj.bcebos.com/23453227824039');
//        $image = file_get_contents('http://online-incar.bj.bcebos.com/23453227823886');
//        $image = file_get_contents('data:image/jpeg;base64,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');
//        $images = [
//            file_get_contents('http://online-incar.bj.bcebos.com/23427227653161'),
//            file_get_contents('http://online-incar.bj.bcebos.com/23427227653206'),
//        ];
//        $image = file_get_contents(public_path().'/images/a.jpg');

        $options["max_face_num"] = 1;
        $options["face_fields"] = "age,gender";
        // 调用人脸检测
        $data = $client->detect($image);
        return responce(200,'检测成功',$data['result'][0]['face_probability']);
//        var_dump($data);
        die;

        $res = Cache::get('real');
        $content = file_get_contents($res);
        dump($res);
        dump($content);
        die;
        $file = request()->file('mydadao');
        if($file->isValid()){
            // 临时绝对路径
            $name = time();
            $realPath = $file->getRealPath();
            $re = Storage::disk('public')->put($name,file_get_contents($realPath));
            if($re){
                Cache::forever('real',$realPath);
                return responce(200,'上传成功',$name.'-'.$realPath);
            }else{
                Log::error("文件上传失败");
                return responce(400,'上传失败');
            }
        }
        die;


        $userName = '刘淑华'; // 用户名
        $identityCardNum = '370202196502083948'; // 身份证号码
        $img = "http://online-incar.bj.bcebos.com/23427227653161";
        $identityPositivePictureInfo = $this->idcard($img,'back');
        if($identityPositivePictureInfo){
            $userNameCheck = $identityPositivePictureInfo['姓名']['words'];
            $identityCardNumCheck = $identityPositivePictureInfo['公民身份号码']['words'];
            if($userName != $userNameCheck || $identityCardNum != $identityCardNumCheck){
                return responce(400,'姓名或身份证号码不匹配');
            }
        }else{
            return responce(400,'请上传身份证反面照片');
        }
        return response()->json($identityPositivePictureInfo,200,[],JSON_UNESCAPED_UNICODE);
    }

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
                return responce(400,'请上传身份证照片');
            }
        }else{
            return $identityPositivePictureInfo['words_result'];
        }
    }

    /*
     *  对图片进行人脸识别
     * */
    public function faceMetch($images)
    {
        $appId = config('ai.appId');
        $appKey = config('ai.apiKey');
        $appSecret = config('ai.apiSecret');
        $client = new AipFace($appId, $appKey, $appSecret);
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
}
