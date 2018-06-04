<?php

namespace App\Http\Controllers\MiniApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CarPlateNumber;
use App\Models\CarQrCode;

class CarController extends Controller
{
    /*
     *  小程序扫描查询车牌号
     * */
    public function getPlateNumber()
    {
        // 参数验证
        $error = Validator::make(request()->all(), [
            'keywords' => 'required', // 二维码标识
        ], [
                'keywords.required' => '二维码标识传递有误',
            ]
        );

        // 参数有错误 直接返回错误信息
        if($error->fails()){
            return responce(400,$error->errors()->first());
        }
        //获取参数
        $key = request('keywords','');
        Log::info('查询车牌号为的关键字是： '.$key);
        //判断是链接还是 sence_id
        if(strpos($key,'http') > -1){
            $data = CarQrCode::where('url',$key)->first();
            if($data){
                $key = $data->secen_id;
            }else{
                Log::error("没有找到对应该链接的车牌信息!!! 查询结果是 == ".$data);
                return responce(400,'没有找到对应该链接的车牌信息!!!');
            }
        }
        $data = CarPlateNumber::where('secen_id',$key)->first();
        if($data){
            Log::info('车牌号查询结果 === '.$data->plate_number);
            return responce(200,'获取成功',$data->plate_number);
        }else{
            Log::error('没有找到车牌号');
            return responce(404,'没有对应的车牌号');
        }
    }

    /*
     * 生成车辆二维码
     * */
    public function createCarQrCode()
    {
        $plate = request('plate','京Q5KK81');//$request->input('plate','京Q5KK81');
        $secen_id = time();
        $res = CarPlateNumber::create(['plate_number'=>$plate,'secen_id'=>$secen_id]);
        //生成二维码
        $data = $this->createQrCode($secen_id);
        if($res){
            $time = date('m/d/Y H:i:s',time());
            $res = CarQrCode::create(['url'=>$data['url'],'secen_id'=>$secen_id,'ticket'=>$data['ticket'],'status'=>1,'user_id'=>88888,'create_time'=>$time]);
        }
        var_dump($data);
        dd($res);
    }

    /*
     * 生成公众号二维码
     * @ $sence_id 二维码参数
     * */
    private function createQrCode($sence_id)
    {
        //生成永久二维码
        $app = app('wechat.official_account');
        $result = $app->qrcode->forever($sence_id);
        return $result;//返回数组
    }
}
