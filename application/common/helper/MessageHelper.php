<?php
namespace app\common\helper;


trait MessageHelper
{

    /**
     * 发送短信验证码
     * @param $telephone
     * @param $msg
     * @return bool|mixed
     */
    private function smsMessage($telephone, $msg)
    {
        if (!$telephone) {
            return false;
        }

        $post_data = [];
        $post_data['name'] = getSetting('sms.user');   //帐号
        $post_data['pswd'] = getSetting('sms.pwd');  //密码
        $post_data['mobile'] = $telephone; //手机号码， 多个用英文状态下的 , 隔开
        $post_data['msg'] = $msg; //短信内容需要用utf8编码下
        $post_data['needstatus']='false'; //是否需要状态报告，需要true，不需要false
        $post_data['sender'] = ''; //扩展码，用户定义扩展码，2 位，默讣空 不用填写
        $post_data['type']='json';  //扩展码   不用填写
        $url = getSetting('sms.url');
        $o='';
        foreach ($post_data as $k=>$v)
        {
            $o.=$k.'='.urlencode($v).'&';
        }

        $post_data=substr($o,0,-1);
        $result = $this->curPost($url, $post_data);
        $arr = json_decode($result,true);
        return $arr;
    }
}