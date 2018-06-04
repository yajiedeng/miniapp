<?php

namespace App\Http\Controllers\MiniApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Log;
use Validator;

class UserController extends Controller
{
    private $app;
    public function __construct(Request $request)
    {
        //初始化
        $this->app = app('wechat.mini_program');
    }
    //小程序用户获取openid
    public function getOpenid(Request $request){
        $method = $request->method();
        if($method != "POST"){
            return responce('502','The server rejected your request');
        }

        // 参数验证
        $error = Validator::make(request()->all(), [
            'user_id' => 'required', // 用户id
            'code' => 'required', // 获取 openid 使用的 code
        ], [
                'user_id.required' => '用户标识传递有误',
                'code.required' => 'code传递有误',
            ]
        );

        // 参数有错误 直接返回错误信息
        if($error->fails()){
            return responce(400,$error->errors()->first());
        }


        //接收参数
        $user_id = request('user_id');
        $code = request('code');
        $user = User::where('user_id', $user_id)->first(); // 查看是否已经存在
        //查询是否有该用户信息
        if($code == -1){//获取openid
            if($user){//用户信息存在返回openid
                //记录返回 openid 日志
                Log::info('用户id为 '.$user_id.' 的openid是 '.$user->openid);
                return responce(200,'获取成功',$user->openid);
            }else{
                Log::info('没有找到用户 '.$user_id.' 的openid');
                return responce(400,'没有找到该用户的openid','');
            }
        }else{//存储openid
            //获取用户openid unideid session_key
            $result = $this->app->auth->session($code);
            //记录用户获取 openid 结果到日志
            Log::info("用户 ".$user_id." 获取openid 的返回参数是".json_encode($result));
            // 获取 openid 返回错误信息直接输出
            if(array_key_exists('errcode',$result)){
                return responce($result['errcode'],$result['errmsg']);
            }
            //判断该用户openid是否存在
            if($user && $user->openid != 0){//openid 已存在
                return responce(200,'获取成',$user->openid);
            }elseif ($user && $user->openid == 0){//上次存储失败 重新存储
                $user->user_id = $user_id;
                $user->openid = $result['openid'];
                $user->session_key = $result['session_key'];
                if(array_key_exists('unionid',$result) && !empty($result['unionid'])){
                    $user->unionid = $result['unionid'];
                }
                if($user->save()){
                    return responce(200,'获取成功',$result['openid']);
                }
            }elseif(!$user){
                $datas['user_id'] = $user_id;
                $datas['openid'] = $result['openid'];
                $datas['session_key'] = $result['session_key'];
                if(array_key_exists('unionid',$result) && !empty($result['unionid'])){
                    $datas['unionid'] = $result['unionid'];
                }
                $user = User::create($datas);
                if($user){
                    return responce(200,'获取成功',$result['openid']);
                }
            }
        }
    }
}
