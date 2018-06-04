<?php
/**
 * @param string $url get请求地址
 * @param int $httpCode 返回状态码
 * @return mixed 结果集
 */
function curl_get($url, &$httpCode = 0)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //不做证书校验,部署在linux环境下请改为true
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $file_contents = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $file_contents;
}

/**
 * @param string $url post请求地址
 * @param array $params 请求参数
 * @return mixed 结果集
 */
function curl_post($url, $params = array())
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt(
        $ch, CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json'
        )
    );
    $data = curl_exec($ch);
    curl_close($ch);
    return ($data);
}

function request_post($url = '', $param = '')
{
    if (empty($url) || empty($param)) {
        return false;
    }

    $postUrl = $url;
    $curlPost = $param;
    // 初始化curl
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $postUrl);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    // 要求结果为字符串且输出到屏幕上
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    // post提交方式
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
    // 运行curl
    $data = curl_exec($curl);
    curl_close($curl);

    return $data;
}

/*
 * 定义接口返回格式
 * */

function responce($code='200',$msg='ok',$result='')
{
    if($result){
        $data = [
            'msg_code' => (string)$code,
            'message'  => (string)$msg,
            'data' => $result
        ];
    }else{
        $data = [
            'msg_code' => (string)$code,
            'message'  => (string)$msg,
            'data' => (string)$result
        ];
    }

//    if($result === null){
//        unset($data['data']);
//    }
    return response()->json($data,200,[],JSON_UNESCAPED_UNICODE);
}


/*
 * 获取当前域名
 * */

function getUrl()
{
    $url = url()->previous();
    return $url;
}

/*
 *  小程序验证用户证件 转存
 * */

function writeImg($imgContent,$fileName)
{
    $baidu_bos_url = config('wechat_parameter.bcebos_url');
    //读取图片内容
//    $imgContent = curl_get($baidu_bos_url.$imgUrl);
    //写入文件
    $filePath = storage_path().'/app/public/';
    $fileName = $fileName.".jpg";
    $path = $filePath.$fileName;
    $ifp = fopen( $path, "wb" );
    $res = fwrite( $ifp, $imgContent );
    fclose( $ifp );
    return $res;
}