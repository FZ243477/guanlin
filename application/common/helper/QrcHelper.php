<?php


namespace app\common\helper;

use Endroid\QrCode\QrCode;

trait QrcHelper
{

    private function https_request($url, $data=null, $type=''){
        if(function_exists('curl_init')) {

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            if (!empty($data)) {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            if ($type == 'json') {//json $_POST=json_decode(file_get_contents('php://input'), TRUE);
                $headers = array(
                    'Accept:application/json;',
                    'Content-Type:application/json; charset=UTF-8',
                );
                /*$headers = [
                    "Content-type: application/json;charset=UTF-8",
                    "Accept: application/json",
                    "Cache-Control: no-cache", "Pragma: no-cache"
                ];*/
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            }
            $output = curl_exec($curl);
            curl_close($curl);
            return $output;
        } else {
            return false;
        }
    }

    // 发送access_token
    private function getAccessToken($appid,$secret,$grant_type){
        if (empty($appid)||empty($secret)||empty($grant_type)) {
            return ['status' => 0, 'msg' => '参数错误'];
        }
        // https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type={$grant_type}&appid={$appid}&secret={$secret}";
        if (cache('wx_token')) {
            $token = cache('wx_token');
            return ['status' => 1, 'msg' => 'success', 'data' => $token];
        }
        $json = $this->https_request($url);
        $data=json_decode($json,true);
        if (empty($data['access_token'])) {
            return ['status' => 0, 'msg' => '请求失败', 'data' => $data];
        }
        cache('wx_token',$data['access_token'],300);
        return ['status' => 1, 'msg' => 'success', 'data' => $data['access_token']];
    }
    // 获取带参数的二维码
    // 获取小程序码，适用于需要的码数量极多的业务场景。通过该接口生成的小程序码，永久有效，数量暂无限制。
    private function getWXACodeUnlimit($access_token,$path='',$width=430){
        if (empty($access_token)||empty($path)) {
            return ['status' => 0, 'msg' => '参数错误'];
        }
        // https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=ACCESS_TOKEN
        //$url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token={$access_token}";
        $url = "https://api.weixin.qq.com/wxa/getwxacode?access_token={$access_token}";
        $data = array();
        $data['path'] = $path;
        //最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）
        $data['width'] = $width;
        //$data['auth_color'] = true;
        //$data['line_color'] = '';
        //二维码的宽度，默认为 430px
        $json = $this->https_request($url,json_encode($data), 'json');
        return ['status' => 1, 'msg' => 'success', 'data' => $json];
    }

    private function qrcode($savePath, $path, $width=430){
        $appid = getSetting('wechat.we_cgi_appid');
        $secret = getSetting('wechat.we_cgi_secret');
        $res = $this->getAccessToken($appid, $secret,'client_credential');
        if ($res['status'] == 1) {
            $access_token = $res['data'];
        }else{
            return ['status' => 0, 'msg' => '请求失败', 'data' => $res['data']];
//            ajax_return(false,$res);
        }
        if (empty($access_token)) {
            return ['status' => 0, 'msg' => 'access_token为空，无法获取二维码', 'data' => []];
//            ajax_return(false,'access_token为空，无法获取二维码');
        }
//        $path = 'pages/index/index?super='.$superId;
//        $width = 430;
        $res2 = $this->getWXACodeUnlimit($access_token,$path,$width);
        if ($res2['status'] == 0) {
            return ['status' => 0, 'msg' => '请求失败'];
        }
        // var_dump($res2);
        //将生成的二维码保存到本地
        // $file ="/Uploads/".substr($path,strripos($path,"?")+1).".jpg";
        file_put_contents($savePath,$res2['data']);
        if (file_exists($savePath)) {
            return ['status' => 1, 'msg' => 'success'];
//            ajax_return(true,'','/'.$file);
        }else{
            return ['status' => 0, 'msg' => '请求失败'];
//            ajax_return(false);
        }
    }

}