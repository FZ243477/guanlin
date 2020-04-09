<?php
/**
* 	配置账号信息
*/

class WxPayConf_pub
{
//1237853402@1237853402

	//=======【基本信息设置】=====================================
	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	// const APPID = '';
	// //受理商ID，身份标识
	// const MCHID = '';
	// //商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	// const KEY = '';
	// //	const KEY = '';
	// //JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	// const APPSECRET = '';

    const APPID = 'wxb986befacf10fa02';     //https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=372361504&lang=zh_CN
    const MCHID = '1486876832';				// https://mp.weixin.qq.com/misc/pluginloginpage?pluginuin=10010&token=372361504&lang=zh_CN
    const KEY = 'e96b4335899b11e7a807b08387654321'; //
    const APPSECRET = 'd1c270f61e609b76588d012f2fb19453';
	
	//=======【JSAPI路径设置】===================================
	//获取access_token过程中的跳转uri，通过跳转将code传入jsapi支付页面
    const JS_API_CALL_URL = '/WeChat/Pay/wxpay';
    const JS_API_CALL_URL2 = '/WeChat/Pay/wxwallet';
	
	//=======【证书路径设置】=====================================
    //证书路径,注意应该填写绝对路径
    const SSLCERT_PATH = 'http://lvcheng.hzjiuyu.cn/ThinkPHP/Library/Vendor/Wxpay/WxPayPubHelper/cacert/apiclient_cert.pem';
    const SSLKEY_PATH =  'http://lvcheng.hzjiuyu.cn/ThinkPHP/Library/Vendor/Wxpay/WxPayPubHelper/cacert/apiclient_key.pem';
	
	//=======【异步通知url设置】===================================
	//异步通知url，商户根据实际开发过程设定
	//const NOTIFY_URL = 'http://goead.ysxdgy.com/Index/notify_url1';

	//=======【curl超时设置】===================================
	//本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
	const CURL_TIMEOUT = 30;
	
}
	
?>