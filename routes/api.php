<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//微信小程序路由组
Route::group(['namespace' => 'MiniApp'], function(){
    Route::any('/miniapp','MiniAppController@serve'); // 小程序接收消息 & 事件 地址
    Route::post('/miniapp/openid','UserController@getOpenid'); // 小程序获取 openid
    Route::post('/car/qrcode/add','CarController@createCarQrCode'); // 小程序生成车辆二维码
    Route::post('/car/plates','CarController@getPlateNumber'); // 小程序获取车牌号
    Route::post('/miniapp/upload','UploadController@upload'); // 小程序上传证件照片
    Route::post('/miniapp/upload/submit','UploadController@submitImage'); // 小程序上传证件照片提交
});

Route::any('/test','Dadao\TestController@test');
