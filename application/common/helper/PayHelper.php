<?php


namespace app\common\helper;


trait PayHelper
{

    private function aliPayConfig()
    {
        //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        //合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
        $alipay_config['partner']       = getSetting('alipay.partner');

        //收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
        $alipay_config['seller_id'] = getSetting('alipay.seller_id');

        //收款支付宝帐户
        $alipay_config['seller_email']  = getSetting('alipay.seller_email');

        // MD5密钥，安全检验码，由数字和字母组成的32位字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
        $alipay_config['key']           = getSetting('alipay.key');
        // if ($type == 0) {//充值回调
        //     // 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
        //     $alipay_config['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/Home/Pay/alipayNotify";

        //     // 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
        //     $alipay_config['return_url'] = "http://".$_SERVER['HTTP_HOST']."/Home/Pay/alipayCallBack";
        // } elseif ($type == 1) {//购物回调
        // }
        // 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
        $alipay_config['notify_url'] = getSetting('system.host')."/api/Pay/aliPayNotifyUrl";

        // 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
        $alipay_config['return_url'] =  getSetting('system.host')."/Pay/paySuccess";

        //签名方式
        $alipay_config['sign_type']    = strtoupper('MD5');

        //字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config['input_charset']= strtolower('utf-8');

        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $alipay_config['cacert']    = getcwd().'\\cacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config['transport']    = getSetting('alipay.transport');

        // 支付类型 ，无需修改
        $alipay_config['payment_type'] = "1";

        // 产品类型，无需修改
        $alipay_config['service'] = "create_direct_pay_by_user";

        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        //↓↓↓↓↓↓↓↓↓↓ 请在这里配置防钓鱼信息，如果没开通防钓鱼功能，为空即可 ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        // 防钓鱼时间戳  若要使用请调用类文件submit中的query_timestamp函数
        $alipay_config['anti_phishing_key'] = "";
        // 客户端的IP地址 非局域网的外网IP地址，如：221.0.0.1
        $alipay_config['exter_invoke_ip']   = "";
        //↑↑↑↑↑↑↑↑↑↑请在这里配置防钓鱼信息，如果没开通防钓鱼功能，为空即可 ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        return $alipay_config;
    }

    private function unionPayKey($icbc_config)
    {
        $keyfile = '.'.$icbc_config['key'];
        $contents = '';
        if (file_exists($keyfile)) {
            //read private key from file
            //$fd = "pri-CEA.key";
            $fp = fopen($keyfile,"rb");
            if($fp == NULL)
            {
                echo "open file error<br/>";
                exit();
            }
            fseek($fp,0,SEEK_END);
            $filelen=ftell($fp);
            fseek($fp,0,SEEK_SET);
            $contents = fread($fp,$filelen);
            fclose($fp);
        }
        $icbc_config['private_key'] = base64_encode($contents);
        $keyfile = '.'.$icbc_config['crt'];
        $contents = '';
        if (file_exists($keyfile)) {
            //read private key from file
            //$fd = "pri-CEA.key";
            $fp = fopen($keyfile,"rb");
            if($fp == NULL)
            {
                echo "open file error<br/>";
                exit();
            }
            fseek($fp,0,SEEK_END);
            $filelen=ftell($fp);
            fseek($fp,0,SEEK_SET);
            $contents = fread($fp,$filelen);
            fclose($fp);
        }
        $icbc_config['ca'] = base64_encode($contents);
        return $icbc_config;
    }

    //支付宝支付
    /**
     * 购物--支付完成异步回调控制器————支付宝
     */
    private function aliShoppingNotify()
    {
        // 进行回调的异步处理
        import('Alipay.alipay_notify', EXTEND_PATH,'.php');
        $alipay_config = self::aliPayConfig();
        //计算得出通知验证结果
        $alipayNotify  = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
//         file_put_contents("./alipay.txt", (string)$verify_result);
        if($verify_result) {
            //验证成功
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            //解析notify_data
            //注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。建议本地调试时使用PHP开发软件
            //商户订单号
            $order_no = $_POST["out_trade_no"];
            //支付宝交易号
            $trade_no     = $_POST["trade_no"];
            //交易状态
            $trade_status = $_POST["trade_status"];
            //支付金额
            $total_fee    = $_POST["total_fee"];

            if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {

                // $this->shoppingCallback($order_no,$total_fee,3);
                //echo "success";		//请不要修改或删除
                return ['status' => 1, 'msg' => 'success', 'data' => $_POST];
            }else{
                return ['status' => 0, 'msg' => 'fail', 'data' => $_POST];
            }
        }
        else {
            //验证失败
            return ['status' => 0, 'msg' => 'fail', 'data' => $_POST];
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    //微信支付
    /**
     * 发起订单
     * @param float $totalFee 收款总费用 单位元
     * @param string $outTradeNo 唯一的订单号
     * @param string $orderName 订单名称
     * @param string $notifyUrl 支付结果通知url 不要有问号
     * @param string $timestamp 订单发起时间
     * @return array
     */
    private function createJsBizPackage($totalFee, $outTradeNo, $orderName, $notifyUrl, $timestamp, $user_id, $trade_type = 'NATIVE', $config)
    {
        $scene_info ='{"h5_info":{"type":"Wap","wap_url":' .'"/","wap_name":"支付"}}';//场景信息 必要参数
        //$orderName = iconv('GBK','UTF-8',$orderName);
        $open_id = model('user_access_token')->where(['user_id' => $user_id])->value('open_id');
        $unified = array(
            'appid' => $config['appid'],
            'attach' => 'pay',
            'body' => $orderName,
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::createNonceStr(),
            'notify_url' => $notifyUrl,
            'scene_info' => $scene_info,
            'out_trade_no' => $outTradeNo,
            'spbill_create_ip' => $_SERVER["REMOTE_ADDR"],
            'total_fee' => intval($totalFee),
            'trade_type' => $trade_type,
        );
        if ($trade_type == 'JSAPI') {
            $unified['openid'] = $open_id;
        }

        $unified['sign'] = self::getSign($unified, $config['key']);
        $responseXml = self::curlPost('https://api.mch.weixin.qq.com/pay/unifiedorder', self::arrayToXml($unified));
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($unifiedOrder === false) {
            die('parse xml error');
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            die($unifiedOrder->return_msg);
        }
        if ($unifiedOrder->result_code != 'SUCCESS') {
            die($unifiedOrder->err_code);
        }
        $codeUrl = (array)($unifiedOrder->code_url);
        !isset($codeUrl[0])?$codeUrl[0] = '':false;
//        if(!isset($codeUrl[0])) {
            //exit('get code_url error');
//        }
        $arr = array(
            "appId" => $config['appid'],
            "timeStamp" => $timestamp,
            "nonceStr" => self::createNonceStr(),
            "package" => "prepay_id=" . $unifiedOrder->prepay_id,
            "signType" => 'MD5',
            "code_url" => $codeUrl[0],
        );
        $arr['paySign'] = self::getSign($arr, $config['key']);
        return $arr;
    }


    /**
     * 提现
     * @param float $totalFee 收款总费用 单位元
     * @param string $outTradeNo 唯一的订单号
     * @param string $orderName 订单名称
     * @param string $notifyUrl 支付结果通知url 不要有问号
     * @param string $timestamp 订单发起时间
     * @return array
     */
    public function withdrawCreateJsBizPackage($totalFee, $outTradeNo, $orderName, $timestamp, $open_id)
    {
        $config = [
            'mch_id' => getSetting('wechat.we_chat_mch_id'),
            'appid' => getSetting('wechat.we_chat_appid'),
            'key' => getSetting('wechat.we_chat_key'),
        ];
        $unified = array(
            'mch_appid' => $config['appid'],
            'mchid' => $config['mch_id'],
            'nonce_str' => self::createNonceStr(),
            'partner_trade_no' => $outTradeNo,
            'openid' => $open_id,
            'check_name' => "NO_CHECK",
            'amount' => intval($totalFee * 100),
            'desc' => $orderName,
            'spbill_create_ip' => $_SERVER["REMOTE_ADDR"],
        );
        $unified['sign'] = self::getSign($unified, $config['key']);

        $responseXml = self::curSSLlPost('https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers', self::arrayToXml($unified));
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($unifiedOrder === false) {
            die('parse xml error');
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            die($unifiedOrder->return_msg);
        }
        if ($unifiedOrder->result_code != 'SUCCESS') {
            die($unifiedOrder->err_code);
        }
        $codeUrl = (array)($unifiedOrder->code_url);
//        if(!$codeUrl[0]) exit('get code_url error');
        !isset($codeUrl[0])?$codeUrl[0] = '':false;
        $arr = array(
            "appId" => $config['appid'],
            "timeStamp" => $timestamp,
            "nonceStr" => self::createNonceStr(),
            "package" => "prepay_id=" . $unifiedOrder->prepay_id,
            "signType" => 'MD5',
            "code_url" => $codeUrl[0],
        );
        $arr['paySign'] = self::getSign($arr, $config['key']);
        return $arr;
    }

    private function notify()
    {
        $config = array(
            'mch_id' => getSetting('wechat.we_chat_mch_id'),
            'appid' => getSetting('wechat.we_chat_appid'),
            'key' => getSetting('wechat.we_chat_key'),
        );
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($postObj === false) {
            return ['status' => 0, 'msg' => 'parse xml error'];
        }
        if ($postObj->return_code != 'SUCCESS') {
            return ['status' => 0, 'msg' => $postObj->return_msg];
        }
        if ($postObj->result_code != 'SUCCESS') {
            return ['status' => 0, 'msg' => $postObj->err_code];
        }
        $arr = (array)$postObj;
        unset($arr['sign']);
        if (self::getSign($arr, $config['key']) == $postObj->sign) {
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $postObj];

        }
    }

    private static function curSSLlPost($url = '', $postData = '', $options = array())
    {
        try {
            if (is_array($postData)) {
                $postData = http_build_query($postData);
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            if (!empty($options)) {
                curl_setopt_array($ch, $options);
            }

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            //默认格式为PEM，可以注释
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            //curl_setopt($ch,CURLOPT_SSLCERT, WxPayConf_pub::SSLCERT_PATH);
            curl_setopt($ch,CURLOPT_SSLCERT, EXTEND_PATH.'Wxpay'.DS.'WxPayPubHelper'.DS.'cacert'.DS.'apiclient_cert.pem');
            //默认格式为PEM，可以注释
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            //curl_setopt($ch,CURLOPT_SSLKEY, WxPayConf_pub::SSLKEY_PATH);
            curl_setopt($ch,CURLOPT_SSLKEY, EXTEND_PATH.'Wxpay'.DS.'WxPayPubHelper'.DS.'cacert'.DS.'apiclient_key.pem');
            $data = curl_exec($ch);
            if (FALSE === $data) {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            } else {
                curl_close($ch);
                return $data;
            }
            // ...process $content now
        } catch(\Exception $e) {
            trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
        }

    }

    private static function curlPost($url = '', $postData = '', $options = array())
    {
        try {
            if (is_array($postData)) {
                $postData = http_build_query($postData);
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            if (!empty($options)) {
                curl_setopt_array($ch, $options);
            }

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
            // ...process $content now
        } catch(Exception $e) {

            trigger_error(sprintf(
                'Curl failed with error #%d: %s',
                $e->getCode(), $e->getMessage()),
                E_USER_ERROR);

        }

    }

    private static function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }
    /**
     * 获取签名
     */
    private static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }

    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /**
     * 微信退款接口
     * $out_trade_no    商户订单号
     * $trade_no        微信交易号
     * $refund_money    退款金额
     * $refund_reason   退款原因
     * */
    private function WeixinRefund($out_trade_no,$trade_no,$refund_money,$refund_reason,$refund_order_id){
        if(!$out_trade_no && !$trade_no && !$refund_money && !$refund_reason){
            return array('status' => 0, 'msg' => '退款失败');
        }
        if($refund_money<0.01){
            return array('status' => 0, 'msg' => '退款失败');
        }

        $tofee_price = model("order")->where(array("out_trade_no"=>$out_trade_no))->find();
        //$count = model("order_goods")->where(array("order_no"=>$out_trade_no, 'is_refund' => 1))->count();

        /*if (!$tofee_price['out_request_no'] && $count > 1) {
            $tofee_price['out_request_no'] = date('YmdHis', time()).rand(1111, 9999);
            model('order')->where(array("order_no"=>$out_trade_no))->save(array('out_request_no' => $tofee_price['out_request_no']));
        }*/

        $new_wx_oder_no = $this->wx_cxno($tofee_price["trade_no"]);
        if($new_wx_oder_no != "no"){
            $out_trade_no = $new_wx_oder_no;
        }

        $appid = getSetting('wechat.we_chat_appid');
        $mch_id = getSetting('wechat.we_chat_mch_id');
        $key = getSetting('wechat.we_chat_key');

        $out_refund_no = $trade_no.$refund_order_id;
        $total_fee = $tofee_price["total_fee"];
        $refund_fee = $refund_money;
        $nonce_str = md5($out_trade_no);
        $refund_fee = $refund_fee * 100;
        $total_fee = $total_fee * 100;
        $ref = strtoupper(MD5("appid=$appid&mch_id=$mch_id&nonce_str=$nonce_str&out_refund_no=$out_refund_no&out_trade_no=$out_trade_no&refund_fee=$refund_fee&total_fee=$total_fee&key=$key")); //sign加密MD5
        $refund = array(
            'appid' =>$appid,
            'mch_id' => $mch_id,
            'nonce_str' => $nonce_str,  //随机
            'out_refund_no' => $out_refund_no, //商户内部唯一退款单号
            'out_trade_no' => $out_trade_no, //商户订单号
            'refund_fee' => $refund_fee, //退款金额
            'total_fee' => $total_fee, //总金额
            'sign' => $ref,//签名
        );
        /*if ($count > 1) {
            $refund['out_request_no'] = $tofee_price['out_request_no'];
        }*/
        $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
        $apiclient_cert = getcwd()."/private/wxpay/cert/apiclient_cert.pem";
        $apiclient_key = getcwd()."/private/wxpay/cert/apiclient_key.pem";
        $xml = $this->arrayToXml($refund);
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
        curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $xml );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );

        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'pem');
        curl_setopt($ch, CURLOPT_SSLCERT, $apiclient_cert);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'pem');
        curl_setopt($ch, CURLOPT_SSLKEY, $apiclient_key);
        $result = curl_exec($ch);
        if ($result) {
            curl_close($ch);
            $postStr = $result;
            $msg = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            if($msg["return_code"] == "SUCCESS" && $msg["return_msg"] == "OK" && $msg['result_code'] == 'SUCCESS'){
                return array('status' => 1, 'msg' => $msg["return_msg"]);
            } else {
                return array('status' => 0, 'msg' => $msg["return_msg"]);
            }
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            $result['errNum'] = $error;
            return array('status' => 0, 'msg' => '退款失败');
        }
    }
    public function wx_cxno($cr_order_no){
        $shop_order_no = $cr_order_no;
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        $appid = getSetting('wechat.we_chat_appid');
        $mch_id = getSetting('wechat.we_chat_mch_id');
        $transaction_id  = $shop_order_no;
        $nonce_str = strtoupper(md5($shop_order_no));
        $key = getSetting('wechat.we_chat_key');
        $sign= strtoupper(MD5("appid=$appid&mch_id=$mch_id&nonce_str=$nonce_str&transaction_id=$transaction_id&key=$key"));
        $param ="<xml>
               <appid>$appid</appid>
			   <mch_id>$mch_id</mch_id>
			   <nonce_str>$nonce_str</nonce_str>
			   <transaction_id>$transaction_id</transaction_id>
			   <sign>$sign</sign>
		  </xml>";
        $msg = $this->curl_post($url,$param);
        if($msg["return_code"] == "SUCCESS" && $msg["trade_state"] == "SUCCESS"){
            $out_trade_no = $msg["out_trade_no"];
            return $out_trade_no;
        }else{
            return "no";
        }
    }
    private function aliRefund($out_trade_no,$trade_no,$refund_money,$refund_reason,$refund_order_id)
    {
        //商户订单号,支付宝交易号,金额,退款理由,订单ID
        if(!($out_trade_no && $trade_no && $refund_money&& $refund_reason && $refund_order_id)){    //判断参数
            return false;
        }
        $setbizstring=array(
            'out_trade_no' =>$out_trade_no,              //订单支付时传入的商户订单号,不能和 trade_no同时为空。
            'trade_no'     =>$trade_no,             //支付宝交易号，和商户订单号不能同时为空
            'refund_amount'=>$refund_money,                //退款金额
            'refund_reason'=>$refund_reason,        //退款原因
            'out_request_no'=>$out_trade_no.'lclxj',   //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传。
        );
        import('Alipay.aop.AopClient', EXTEND_PATH,'.php');
        import('Alipay.aop.request.AlipayTradeRefundRequest', EXTEND_PATH,'.php');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2017082808427128';
        $aop->rsaPrivateKey = "MIIEpQIBAAKCAQEAwRJU0AhZVS6eXmATPoZSKuVgfMHC2fwZHhTnYYeR6gaJClMdxy07L+Usyu3ZrFlOZ075WIQE8LeHLKesV2CkUMbIPIR6SlIwgqKhXpGIxEtLFciqD3ugr98493xCn1dEwGtMRhkN5igDgl9U8tkx6n6jTPPxdlw8FBcnINNy5u134OjRVdHraIBZFPtqgpnXvmp4OSVW358SB0N2JIiKzAmvzLnnfcke7pM/jQGs6AiIQkN5x9SDKQ8coCQBkuk855It0DG3UzDKqT4kFWERxyfuqaJBzQ9CMO+q6rUYmAvywNcabp9KEfBIA9u5ya/qXCQjRGB1b+joJsJ3nbXaywIDAQABAoIBABlV5ntGHTLoYy/PO7dAuReX8gltA7zHMCSaaaKKv8MOCH2qWoYAkXu/fxlCDQZo0bkMy/upl1xfOQXLGvp8XBOdgjkuyT1ne6Yo4TQftOrNVpUNOAPAHV321OrlC3kNYqA9dEzejMxfA9bf263igoJbC+LVQCJgCCI6x4+vL6zN0iZToAuIAHRXi3EfC+pCt9hlgbnR43cDKappTSP7RhjM7WR63d1gPmshV2EjbVr/9De63X7XV6eE/SiHknPm3h2s5LFujGnJ8GKBbjgphzcfVlx6yYDdQSC0yYXOrqN184eHmcG+POz/HsEFSoLmNyMB3OiS4nqIjaHhlqs7ngECgYEA7rnkCwCN56kctvFAKPDXWe3IXv2KlgEeZH06wB4v8Uda5YDIRvVmySlS8RVMHvyjddp+hl4B35uAz+HqleOuK02frg+RhemWnsj33gz8SjVJR0UutHXENAl6cPLWfQO3PfzlD4guvJEVzToymAibfY3Oxi5cFC5b8tY1S+T/eCECgYEAzwq/DF8HK6AxZHENgKMvQi/3w/+5UOqwSR3UkmJ+ZulTZf9+TDlgyxKnWKJArDXGQZD+qccHjPas4Wp/G1P75tbSR5nzXiw2FoyiLIEjT0o7mmtSv7fG1BvZ5KMV9j8trmkfAz1cWbsTwnaq7oxt56ZL1y3WU/9B8Hq5r77WBWsCgYEAzeUuNf8IZSGKTo7M04LFeh6HjsYGXVIhsHIB1ekzWFo+n2rvUaQePqmRi7TermsfYGpObf1uiDlKZAFpnlV8xoRwkGOFE4ZgDhsvDSkN/8LtrLaSjbp0upziKcCIFdK6nJAdz8OY67IGp7bmJBJoaWWBTZR2fkFttIfj981OOYECgYEAl8aN9Ri4ne+KJdKGjnWSEFgvrnwJstrIrmDy0vjnJrQHEi+wu1oYreWXze7rsBKfqrMLLRSdYWX3qCu00CjJ8hgrAJhIAxIv+GnR/QQSCW8msHXarHahiB5+phAz6le4OjIPrQVPbOwqeRUbC1Lgwr9yu2R7yQnHoe2lr8MbC88CgYEAtK66NZPmlwEU6Cd6wAIKvtRm2TTxawVbHoK3UjPq61gpmq92VvY2J5zkmFOSEJa7pWsQKJHW1kuJ2Io4vwkGBAJgCBn72mLIrjVB2H1337HieI/OTvRfMQr6rQNBinZFeUyzOQwaAbZWZMy9CWPuI3+QuOHEt3ksj87Rj9ZnyxQ=";
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAq6FuwPoHKHhKpHTJZgicVRunfVa2euvv4NQY7TuKb3bSsN1A4JaHL2imuWLtER5UFk9So2T1m1xZL2Cvgta+eSzdNNsbakdWGTN1fEdp7e62ZF2OAgEp3eEMQvrhlNfFj7RCNln9SqhrrQgBvNisy3FjD+o2It9yuvj1TwsYZygQQxa9MoV6Bl/JuP1gJcBV4SONX8Y+G+hlmGhsiEQ9SjveBslN6+GzLgJv4+lBkdaM4bQYfDKcZ6ULnGGNX8OgJitGWrqRM8Ei74JDhn+hf4HR0BSymYqcX3u+neaWIbKVMcAnNsEdn5oWJU5N3Y6K+8gaqSGmn3QB4biG/84dRwIDAQAB';
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayTradeRefundRequest ();

        $request->setBizContent(json_encode($setbizstring));
        $result = $aop->execute ($request);


        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){   //退款成功
            return array('status'=>1,'msg'=>'退款成功');
        }
        file_put_contents("./logos/AlipayRefund.txt",print_r(array($result,date('Y-m-d H:i:s',time())),true).PHP_EOL,FILE_APPEND);
        return array('status'=>0,'msg'=>'退款失败');

    }

    private function unionRefund()
    {
        $icbc_config = getSetting('unionPay');
        $icbc_config = $this->unionPayKey($icbc_config);
        date_default_timezone_set("PRC");
        require_once(EXTEND_PATH.'unionPay/DefaultIcbcClient.php');
        require_once(EXTEND_PATH.'unionPay/UiIcbcClient.php');
        require_once(EXTEND_PATH.'unionPay/IcbcConstants.php');
//        import('unionPay.DefaultIcbcClient', EXTEND_PATH,'.php');
//        import('unionPay.UiIcbcClient', EXTEND_PATH,'.php');
        $time = time();
        $order_no = request()->param('order_no');
        $order = model("order")->where(['out_trade_no|order_no'=>$order_no])->find();
        if (!$order) {
            $this->error('订单不存在，请重试');
            return;
        }
        if ($order && ($order['pay_status'] == 1)) {
            $this->error('订单已支付');
            return;
        }
        $order_amount = (int)($order['total_amount'] * 100);
        $order_goods = model('order_goods')->where(['order_id' => $order['id']])->find();
        $request = array(
            "serviceUrl" => 'https://gw.open.icbc.com.cn/api/enterprise/merctserv/refund/V1',
            "method" => 'POST',
            "isNeedEncrypt" => false,
            "biz_content" => array(
                "custom" => array(
                    "verify_join_flag" => "0", //联名校验标志，手机银行订单必输0，不校验
                    "language" => "zh_CN",//语言版本，默认为中文版，目前只支持中文版 取值：“zh_CN”
                ),
                "message" => array(
                    "goods_id" => (string)$order_goods['goods_id'],//商品编号，最大长度10
                    "goods_name" => base64_encode(iconv("UTF-8","gbk//TRANSLIT", $order_goods['goods_name'])),//商品名称(base64加密)
                    "goods_num" => "3",//商品数量
                    "carriage_amt" => "0",//已含运费金额，选输，以分为单位
                    "mer_hint" => base64_encode(iconv("UTF-8","gbk//TRANSLIT","商品支付")),//商城提示(base64加密)
                    "remark1" => base64_encode(iconv("UTF-8","gbk//TRANSLIT","工商银行支付")),//备注1(base64加密)
                    "remark2" => base64_encode(date('YmdHis', $time+60*60*2)),//该字段目前由商户上送订单失效时间，控制线上交易，格式yyyyMMddHHmmss
                    "credit_type" => "2",//移动端送空，pc端支付必输。默认“2”。取值范围为0、1、2，其中0表示仅允许使用借记卡支付，1表示仅允许使用信用卡支付，2表示借记卡和信用卡都能对订单进行支付 |
                    "mer_reference" => "*.gtdreamlife.com",//移动端送空，pc端支付选输。上送商户网站域名（支持通配符，例如“*.某B2C商城.com”），如果上送，工行会在客户支付订单时，校验商户上送域名与客户跳转工行支付页面之前网站域名的一致性 |
                    "mer_custom_ip" => "",//移动端送空，pc端支付选输。工行在支付页面显示该信息。使用IPV4格式。当商户reference项送空时，该项必输
                    "goods_type" => "0",//
                    "mer_custom_id" => "",//
                    "mer_custom_phone" => "",//
                    "goods_address" => "",
                    "mer_order_remark" => "",
                    "mer_var" => "",
                    "notify_type" => "HS",//通知类型，在交易转账处理完成后把交易结果通知商户的处理模式。取值“HS”：在交易完成后实时将通知信息以HTTP协议POST方式，主动发送给商户，发送地址为商户端随订单数据提交的接收工行支付结果的URL；取值“AG”：在交易完成后不通知商户。商户需要使用浏览器登录工行的B2C商户服务网站，或者使用工行提供的客户端程序API主动获取通知信息。取值“LS”：在交易完成后实时将通知信息以HTTP协议POST方式，主动发送给商户，发送地址为商户端随订单数据提交的接受工行支付结果的URL，即表单中的notify_url字段，商户响应银行通知时返回取货链接给工行，如工行未收到商户响应则重复发送通知消息，发送次数由工行参数配置 | HS
                    "result_type" => "0",//结果发送类型，取值“0”：无论支付成功或者失败，银行都向商户发送交易通知信息；取值“1”，银行只向商户发送交易成功的通知信息。只有通知方式为HS时此值有效，如果使用AG方式，可不上送此项，但签名数据中必须包含此项，取值可为空。注：如商户同时支持线上支付和线下扫码支付则该字段取值方式只支持：1-银行只向商户发送交易成功的通知信息 | 0
                    "orderFlag_ztb" => "0",
                    "o2o_mer_id" => "120208999396",
                    "elife_mer_id" => "12020060679",
                    "pay_expire" => "300",
                    "return_url" => getSetting('system.host').'/Pay/paySuccess?out_trade_no='.$order['order_no'].'&total_fee='.$order['total_amount'],
                    "auto_refer_sec" => "5",
                    "backup1" => "",
                    "backup2" => "",
                    "backup3" => "",
                    "backup4" => ""
                ),
                "order_info" => array(
                    "order_date" => date('YmdHis', strtotime($order['order_time'])),//订单日期
                    "order_id" => $order['out_trade_no'], //订单号
                    "amount" => "$order_amount", //金额
                    "installment_times" => "1",//分期付款期数，每笔订单一个；取值：1、3、6、9、12、18、24；1代表全额付款，必须为上述值，否则订单校验不通过。B2CPay目前仅支持全额付款
                    "cur_type" => "001",//币种，目前工行只支持使用人民币（001）
                    "mer_id" => $icbc_config['mer_id'],//商户代码
                    "mer_acct" => $icbc_config['mer_acct']//商户账号
                )
            ),
            "extraParams" => array(
//                 'app_id' => 10000000000001400501,
//                 'sign_type' => 'CA',
//                 'charset' => 'UTF-8',
//                 'format' => 'json',
//                 'msg_id' => 'msid' . $time,
                'timestamp' => date('Y-m-d H:i:s', $time),
//                 'sign' => '',
                "notify_url" => getSetting('system.host').'/api/Pay/icbc_notify',
                "interfaceName" => 'ICBC_PERBANK_B2C',
                "interfaceVersion" => "1.0.0.11",//new
//                 "clientType" => "0"//new
            )
        );

        $client = new \UiIcbcClient($icbc_config['appId'],
            $icbc_config['private_key'],
            \IcbcConstants::$SIGN_TYPE_CA,
            '',
            '',
            '',
            '',
            '',
            $icbc_config['ca'],
            $icbc_config['password']);
        try {
            $resp = $client->buildPostForm($request, 'msid' . $time . mt_rand(10000, 99999), ''); //执行调用
            echo $resp;
            //ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['form' => $resp]]);
        } catch (\Exception $e) {
            //捕获异常
            file_put_contents(RUNTIME_PATH . '/icbc_req.log', date('Y-m-d H:i:s') . 'Exception:' . $e->getMessage() . "\n", FILE_APPEND);
            ajaxReturn(['status' => 0, 'msg' => '请求失败，请重试', 'data' => []]);
        }
        // TODO: Implement unionPay() method.
    }
    function curl_post($url,$param){
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
        curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $param );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        $result = curl_exec($ch);
        curl_close($ch);
        $msg = array();
        $postStr = $result;
        $msg = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $msg;
    }

    //微信支付记录日志的方法
    private function  log_result($file,$word, $arr = [])
    {

		$txtname = $file?$file:'pcwxpay_log.txt';
        $time = date('Y-m-d H:i:s');
        file_put_contents($txtname,print_r(array("$time"=>$arr, 'msg' => $word),true).PHP_EOL,FILE_APPEND);
    }

    //银联支付
}