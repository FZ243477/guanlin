<?php

namespace app\common\helper;
/**
 * 酷家乐接口 by cx
 * Class kujiale
 */
trait KujialeHelper
{
    private $appkey;
    private $appsecret;

    /***
     * 酷家乐接口
     * @param $url
     * @param array $post_data Request Body
     * @param string $is_post 传输方式 post  get
     * @param array $get_data  URL Query Param
     * @return array|bool|mixed|string
     */
    private function backDataInfo($url, $post_data = array(), $is_post = 'get', $get_data = array())
    {
        $this->appkey = getSetting('kujiale.appkey');
        $this->appsecret = getSetting('kujiale.appsecret');
        $timestamp = self::getMillisecond();

        $appuidPath = '';
        $signPath = '';
        if ($get_data) {
            $appuid = isset($get_data['appuid']) ? $get_data['appuid'] : '';
            
            //因同步清单接口签名不能加入appuid，故用appuid_special参数代替，appuid
            $appuid_special = isset($get_data['appuid_special']) ? $get_data['appuid_special'] : '';
            if ($appuid_special) {
                $get_data['appuid'] = $appuid_special;
            }

            foreach ($get_data as $k=>$v)
            {
                $appuidPath .= "&"."$k=".urlencode($v);
            }
	    
	    //如果存在appuid，签名中需要添加appuid
            if ($appuid) {
                $signPath .= $appuid;
            }

        }
        session(array('name'=>'session_id','expire'=>3600));
        $sign = MD5($this->appsecret.$this->appkey.$signPath.$timestamp);

        $url = $url."appkey={$this->appkey}&timestamp={$timestamp}&sign={$sign}".$appuidPath;

        if ($is_post == 'get') {
            $result = self::curlSend($url);
            $result = json_decode($result, true);
        } else if ($is_post == 'post') {
            $result = self::curlSend($url, json_encode($post_data));
            $result = json_decode($result, true);
        } else {
            $result = array('c' => 100011, 'm' => 'method not supported', 'd' => null);
        }
        return $result;
    }

    /***
     * curl提交
     */
    private function curlSend($url, $post_data = "")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $headers = array(
            'Accept:application/json;',
            'Content-Type:application/json; charset=UTF-8',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($post_data != "") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /***
     * 获取毫秒时间戳
     */
    private function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    private function getDiyCityList()
    {
        $keyfile = './static/index/js/city.json';
        $contents = '';
        if (file_exists($keyfile)) {
            $fp = fopen($keyfile,"r");
            if($fp == NULL) {
               return false;
            }
            fseek($fp,0,SEEK_END);
            $filelen=ftell($fp);
            fseek($fp,0,SEEK_SET);
            $contents = fread($fp,$filelen);
            fclose($fp);
        }
        $city_list = json_decode($contents, true);
        return $city_list;
    }
}

