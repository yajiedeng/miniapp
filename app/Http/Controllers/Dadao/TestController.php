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

//        echo "<img src='http://testincar.bj.bcebos.com/15280928995481'/>";
//        die;
//
//        echo "<img src='http://testincar.bj.bcebos.com/15278413397906' width='80' />";
//        die;
//
//        $images = [
//            file_get_contents('http://online-incar.bj.bcebos.com/23427227653161'),
//            file_get_contents('http://online-incar.bj.bcebos.com/23427227653206'),
//        ];
//
//        $images = [
//            file_get_contents('http://testincar.bj.bcebos.com/15278415428965'),
//            file_get_contents('http://testincar.bj.bcebos.com/15278413397906'),
//        ];
//
//        $re = $this->faceMetch($images);
//        dump($re);
//
//        die;
//
////        $data = Cache::get('12456_ident');
////        dump($data);
////        $re = Cache::pull('12456_ident');
////        dump($re);
////        dump($data);
////        die;
//
////        $arr = [
////            'aa' => 'aa',
////            'bb' => 'bb',
////            'cc' => 'cc',
////        ];
////
////        var_dump($arr);
//
//
//        $appId = config('ai.appId');
//        $appKey = config('ai.apiKey');
//        $appSecret = config('ai.apiSecret');
//        $client = new AipFace($appId, $appKey, $appSecret);
//
//        $image = file_get_contents('http://online-incar.bj.bcebos.com/23453227823919');
//        $image = file_get_contents('http://online-incar.bj.bcebos.com/23453227823919');
////        $image = file_get_contents('http://online-incar.bj.bcebos.com/23453227823873');
////        $image = file_get_contents('http://online-incar.bj.bcebos.com/23453227824039');
////        $image = file_get_contents('http://online-incar.bj.bcebos.com/23453227823886');
////        $images = [
////            file_get_contents('http://online-incar.bj.bcebos.com/23427227653161'),
////            file_get_contents('http://online-incar.bj.bcebos.com/23427227653206'),
////        ];
////        $image = file_get_contents(public_path().'/images/a.jpg');
//
//        $options["max_face_num"] = 1;
//        $options["face_fields"] = "age,gender";
//        // 调用人脸检测
//        $data = $client->detect($image);
//        return responce(200,'检测成功',$data['result'][0]['face_probability']);
////        var_dump($data);
//        die;
//
//        $res = Cache::get('real');
//        $content = file_get_contents($res);
//        dump($res);
//        dump($content);
//        die;
        $file = request()->file('mydadao');
        if($file->isValid()){
            // 临时绝对路径
            $name = time();
            $realPath = $file->getRealPath();
            $re = Storage::disk('bos')->put($name,file_get_contents($realPath));
            if($re){
                Cache::forever('real',$realPath);
                return responce(200,'上传成功',$name);
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
