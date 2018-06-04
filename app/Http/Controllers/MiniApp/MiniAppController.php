<?php

namespace App\Http\Controllers\Miniapp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use EasyWeChat\Kernel\Messages\Text;
use Log;

class MiniAppController extends Controller
{
    private $app;
    public function __construct(Request $request)
    {
        //初始化
        $this->app = app('wechat.mini_program');
    }
    // 接收微信小程序客服消息
    public function serve(Request $request){
        //获取请求方式
        $method = $request->method();
        if($method == "POST"){//接收用户回复
            //获取小程序时间对象实例
            $message = $this->app->server->getMessage();
            $openId = $message['FromUserName'];
            // 根据定义关键字回复
            if($message['Content'] == "芝麻信用" || $message['Content'] == 1){
                //回复内容
                $zhima_url = config('wechat_parameter.zhima_url');
                $content = "你好，大道用车为你服务，绑定芝麻信用请点击链接:".$zhima_url;
                $text = new Text($content);
                $this->app->customer_service->message($text)->to($openId)->send();
            }else{
                $text = '';
                $this->app->customer_service->message($text)->to($openId)->send();
            }
        }elseif ($method == "GET") {
            $this->app->server->push(function ($message) {//首次token验证
                //回复内容
                $zhima_url = config('wechat_parameter.zhima_url');
                $content = "你好，大道用车为你服务，绑定芝麻信用请点击链接:".$zhima_url;
                return $content;
            });
        }
        return $this->app->server->serve();
    }
}