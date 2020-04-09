<?php
namespace app\common\helper;
use app\common\constant\EncryptionConstant;

trait WeChatHelper
{
    //微信服务号开发 对接 微信服务号接口

//    private $we_chat_appid  = null;
//    private $we_chat_secret = null;
    //微信的appid
    private $we_chat_appid;
    //微信secret
    private $we_chat_secret;
    //LXCurl 调用
    protected $lx_curl = null;

    private function __construct()
    {
        $this->we_chat_appid = getSetting('wechat.we_chat_appid');
        $this->we_chat_secret = getSetting('wechat.we_chat_secret');
    }

    //微信 获取 access_token
    protected function GetAccessToKen($name='serve_access_token'){
        //缓存redis
        $access_token = $this->GetRedis($name);
        if(!empty($access_token)){
            return array('status'=>1,'access_token'=>$access_token);
        }
        //缓存不存在
        $this->lx_curl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->we_chat_appid.'&secret='.$this->we_chat_secret;
        $result = $this->LXCurl();

        if($result['status'] == 1){
            //设置缓存
            $redis_result = $this->SetRedis(
                    $name,
                    $result['result']['access_token'],
                    $result['result']['expires_in']-10
                );
            return array('status'=>1,'access_token'=>$result['result']['access_token']);
        }else{
            return array('status'=>0,'info'=>$result['info']);
        }
    }

    /*
    * 2017-12-07
    */
    private function AccessToKen($name='serve_access_token'){
        $res = $this->GetAccessToKen($name);
        if($res['status'] == 0){
            return array('status'=>0,'info'=>$res['info']);
        }
        return array('status'=>1,'access_token'=>$res['access_token']);
    }
    //客服接口-发消息
    /*
     *  客服接口-发消息
        接口调用请求说明
        http请求方式: POST
        https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=ACCESS_TOKEN
        各消息类型所需的JSON数据包如下：
        发送文本消息
        {
            "touser":"OPENID",
            "msgtype":"text",
            "text":
            {
                 "content":"Hello World"
            }
        }
        发送图片消息
        {
            "touser":"OPENID",
            "msgtype":"image",
            "image":
            {
              "media_id":"MEDIA_ID"
            }
        }
        发送语音消息
        {
            "touser":"OPENID",
            "msgtype":"voice",
            "voice":
            {
              "media_id":"MEDIA_ID"
            }
        }
        发送视频消息
        {
            "touser":"OPENID",
            "msgtype":"video",
            "video":
            {
              "media_id":"MEDIA_ID",
              "thumb_media_id":"MEDIA_ID",
              "title":"TITLE",
              "description":"DESCRIPTION"
            }
        }
        发送音乐消息
        {
            "touser":"OPENID",
            "msgtype":"music",
            "music":
            {
              "title":"MUSIC_TITLE",
              "description":"MUSIC_DESCRIPTION",
              "musicurl":"MUSIC_URL",
              "hqmusicurl":"HQ_MUSIC_URL",
              "thumb_media_id":"THUMB_MEDIA_ID"
            }
        }
        发送图文消息（点击跳转到外链） 图文消息条数限制在8条以内，注意，如果图文数超过8，则将会无响应。
        {
            "touser":"OPENID",
            "msgtype":"news",
            "news":{
                "articles": [
                 {
                     "title":"Happy Day",
                     "description":"Is Really A Happy Day",
                     "url":"URL",
                     "picurl":"PIC_URL"
                 },
                 {
                     "title":"Happy Day",
                     "description":"Is Really A Happy Day",
                     "url":"URL",
                     "picurl":"PIC_URL"
                 }
                 ]
            }
        }
        发送图文消息（点击跳转到图文消息页面） 图文消息条数限制在8条以内，注意，如果图文数超过8，则将会无响应。
        {
            "touser":"OPENID",
            "msgtype":"mpnews",
            "mpnews":
            {
                 "media_id":"MEDIA_ID"
            }
        }
        发送卡券
        {
          "touser":"OPENID",
          "msgtype":"wxcard",
          "wxcard":{
                   "card_id":"123dsdajkasd231jhksad"
                    },
        }
     */
    /*
     *  msgtype     名称
     *    text      文本
     *    image     图片
     *    voice     语音
     *    video     视频
     *    music     音乐
     *    news      图文 （点击跳转到外链）
     *    mpnews    图文 （点击跳转到图文消息页面）
     *    wxcard    发送卡券
     */
    protected function CustomNews($content){
        $a_t_result = $this->GetAccessToKen();
        if($a_t_result['status'] == 0){
            return array('status'=>0,'info'=>'服务器繁忙,请稍后再试!','real'=>'获取token失败!');
        }
        $this->lx_curl = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$a_t_result['access_token'];
        $result = $this->LXCurl($content);
        return $result;
    }
    protected function concurrence($content){
        $a_t_result = $this->GetAccessToKen();
        if($a_t_result['status'] == 0){
            return array('status'=>0,'info'=>'服务器繁忙,请稍后再试!','real'=>'获取token失败!');
        }
        $this->lx_curl = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$a_t_result['access_token'];
        $result = $this->LinCurl($content);
        return array('status'=>1);
//        return $result;
    }
    //上传图文消息素材【订阅号与服务号认证后均可用】
    /*
     *  接口调用请求说明
        http请求方式: POST
        https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token=ACCESS_TOKEN
        POST数据说明
        POST数据示例如下：
        {
           "articles": [
                 {
                     "thumb_media_id":"qI6_Ze_6PtV7svjolgs-rN6stStuHIjs9_DidOHaj0Q-mwvBelOXCFZiq2OsIU-p",
                     "author":"xxx",
                     "title":"Happy Day",
                     "content_source_url":"www.qq.com",
                     "content":"content",
                     "digest":"digest",
                     "show_cover_pic":1
                 },
                 {
                     "thumb_media_id":"qI6_Ze_6PtV7svjolgs-rN6stStuHIjs9_DidOHaj0Q-mwvBelOXCFZiq2OsIU-p",
                     "author":"xxx",
                     "title":"Happy Day",
                     "content_source_url":"www.qq.com",
                     "content":"content",
                     "digest":"digest",
                     "show_cover_pic":0
                 }
           ]
        }
        参数	是否必须	说明
        Articles	是	图文消息，一个图文消息支持1到8条图文
        thumb_media_id	是	图文消息缩略图的media_id，可以在基础支持-上传多媒体文件接口中获得
        author	否	图文消息的作者
        title	是	图文消息的标题
        content_source_url	否	在图文消息页面点击“阅读原文”后的页面，受安全限制，如需跳转Appstore，可以使用itun.es或appsto.re的短链服务，并在短链后增加 #wechat_redirect 后缀。
        content	是	图文消息页面的内容，支持HTML标签。具备微信支付权限的公众号，可以使用a标签，其他公众号不能使用，如需插入小程序卡片，可参考下文。
        digest	否	图文消息的描述，如本字段为空，则默认抓取正文前64个字
        show_cover_pic	否	是否显示封面，1为显示，0为不显示
     */
    //弃用 UploadNews MassImageText
    /*protected function UploadNews(){
        $a_t_result = $this->GetAccessToKen();
        if($a_t_result['status'] == 0){
            return array('status'=>0,'info'=>'服务器繁忙,请稍后再试!','real'=>'获取token失败!');
        }
        $this->lx_curl = 'https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token='.$a_t_result['access_token'];
    }
    //根据OpenID列表群发 todo 【订阅号不可用，服务号认证后可用】
    protected function MassImageText(){
    }*/
    //回复图文消息
    /*
     *  <xml>
            <ToUserName><![CDATA[toUser]]></ToUserName>
            <FromUserName><![CDATA[fromUser]]></FromUserName>
            <CreateTime>12345678</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <ArticleCount>2</ArticleCount>
            <Articles>
                <item>
                    <Title><![CDATA[title1]]></Title>
                    <Description><![CDATA[description1]]></Description>
                    <PicUrl><![CDATA[picurl]]></PicUrl>
                    <Url><![CDATA[url]]></Url>
                </item>
                <item>
                    <Title><![CDATA[title]]></Title>
                    <Description><![CDATA[description]]></Description>
                    <PicUrl><![CDATA[picurl]]></PicUrl>
                    <Url><![CDATA[url]]></Url>
                </item>
            </Articles>
        </xml>
        参数	              是否必须	         说明
        ToUserName	        是	        接收方帐号（收到的OpenID）
        FromUserName	    是	        开发者微信号
        CreateTime	        是	        消息创建时间 （整型）
        MsgType	            是	        news
        ArticleCount	    是	        todo 图文消息个数，限制为8条以内
        Articles	        是	        多条图文消息信息，默认第一个item为大图,注意，如果图文数超过8，则将会无响应
        Title	            是	        图文消息标题
        Description	        是	        图文消息描述
        PicUrl	            是	        todo 图片链接，支持JPG、PNG格式，较好的效果为大图360*200，小图200*200 店铺的商城图片必须处理成功 JPG、PNG
        Url	                是	        点击图文消息跳转链接
     */
    protected function ReplyNews($news,$w_return_message){
        $xml  =  '<xml>';
        $xml .=  '<ToUserName><![CDATA['.$w_return_message['FromUserName'].']]></ToUserName>';
        $xml .=  '<FromUserName><![CDATA[gh_b932812712b9]]></FromUserName>';
        $xml .=  '<CreateTime><![CDATA['.$this->timestamp().']]></CreateTime>';
        $xml .=  '<MsgType><![CDATA[news]]></MsgType>';
        $xml .=  '<ArticleCount><![CDATA['.count($news).']]></ArticleCount>';
        $xml .=  '<Articles>';
        foreach ( $news as $k => $v ){
            $xml .= '<item>';
            $xml .= '<Title><![CDATA['.$v['title'].']]></Title>';
            $xml .= '<Description><![CDATA['.$v['description'].']]></Description>';
            $xml .= '<PicUrl><![CDATA['.$v['img'].']]></PicUrl>';
            $xml .= '<Url><![CDATA['.$v['url'].']]></Url>';
            $xml .= '</item>';
        }
        $xml .=  '</Articles>';
        $xml .=  '</xml>';
        return $xml;
    }
    //解析 带参数二维码的参数
    /* 生成二维码时请考虑月这一边同步
     * 参数定义
     * type
     *  1 门店二维码
     * 案例 type=1&store_id=2
     * 注意点 scene_str	场景值ID（字符串形式的ID），字符串类型，长度限制为1到64
     */
    protected function AnalysisParameter($event_key){
        // Event 与 EventKey 有区别
        //type=1&store_id=2
        //array('type'=>1,'store_id'=>2);
        $event_key = explode('&',$event_key);
        //拆分之后 array('0'=>type=1,'1'=>store_id=2);
        //再拆分
        $new_event_key = array();
        foreach ($event_key as $k => $v){
            $info = explode('=',$v);
            $new_event_key[$info[0]] = $info[1];
        }
        return $new_event_key;
    }
    //微信分享类
    private function WShareDeploy(){
        $result = $this->GetJsApiTicket();
        if($result['status'] == 0){
            return $result;
        }
        $url = $this->getUrl();  // 注意 URL 一定要动态获取，不能 hardcode.
        $nonceStr = $this->CreateNonceStr();
        $string = 'jsapi_ticket='.$result['ticket'].'&noncestr='.$nonceStr.'&timestamp='. $this->Timestamp() .'&url='.$url;  // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $signature = sha1($string);
        $signPackage = array(
            "appId"     => $this->we_chat_appid,
            "nonceStr"  => $nonceStr,
            "timestamp" => $this->Timestamp(),
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return array('status'=>1,'share_deploy'=>$signPackage);
    }
    //获取微信 jsapi_ticket
    protected function GetJsApiTicket(){
        $ticket = $this->GetRedis('js_api_ticket');
        if(!empty($ticket)){
            return array('status'=>1,'ticket'=>$ticket);
        }
        $result = $this->GetAccessToKen();
        if($result['status'] == 0){
            return $result;
        }
        $this->lx_curl = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$result['access_token'].'&type=jsapi';
        $result = $this->LXCurl();
        if($result['status'] == 0){
            return $result;
        }
        $redis_result = $this->SetRedis(
            'js_api_ticket',
            $result['result']['ticket'],
            $result['result']['expires_in']-10
        );
        return array('status'=>1,'ticket'=>$result['result']['ticket']);
    }
    //动态获取url
    protected function getUrl()
    {   //获取当前完整URl
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self     = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info    = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    }
    //随机字符
    private function CreateNonceStr($length = 16)
    {   //16位随机数
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    //获取openid
    private function GetOpenId(){
        if (!isset($_GET['code']))
        {
            $query = array(
                'appid'         => $this->we_chat_appid,
                'redirect_uri'  => $this->getUrl(),
                'response_type' => 'code',
                'scope'         => 'snsapi_userinfo',
                'state'         => 'oauth',
            );
            header('Location:'.'https://open.weixin.qq.com/connect/oauth2/authorize?'.http_build_query($query).'#wechat_redirect');
            exit();
        }
        if (isset($_GET['code'])&&isset($_GET['state'])&&isset($_GET['state'])=='oauth')
        {
            $query = array(
                'appid'     => $this->we_chat_appid,
                'secret'    => $this->we_chat_secret,
                'code'      => $_GET['code'],
                'grant_type'=> 'authorization_code',
            );
            $this->lx_curl = 'https://api.weixin.qq.com/sns/oauth2/access_token?'.http_build_query($query);
            $result = $this->LXCurl();
            if($result['status'] ==1)
            {
                $data = array();
                $data['expire_time'] = $this->Timestamp() + $result['result']['expires_in'];
                $data['access_token'] = $result['result']['access_token'];
                $data['refresh_token'] = $result['result']['refresh_token'];
                session('WeChatWeb_access_token',json_encode($data));
                return $result; exit();
            }else{
                return $result;exit();
            }
        }
    }
    //获取微信用户信息
    private function GetWeChatUserInfo($open_id,$access_token){
        $query = array(
            'access_token'=>$access_token,
            'openid'      =>$open_id,
            'lang'        =>'zh_CN',
        );
        $this->lx_curl = 'https://api.weixin.qq.com/sns/userinfo?'.http_build_query($query);
        $result = $this->LXCurl();
        return $result;
    }
    //小程序获取用户信息
    private function GetWeChatAppsUserInfo($code){
        $query = array(
            'appid' => $this->we_chat_appid,
            'secret'=> $this->we_chat_secret,
            'js_code'=>$code,
            'grant_type'=>'authorization_code'
        );
        $this->lx_curl = 'https://api.weixin.qq.com/sns/jscode2session?'.http_build_query($query);
        $result = $this->LXCurl();
        return $result;
    }
    //推送模板消息
    private function MessagePush($data){
        $result = $this->GetAccessToKen();
        $this->lx_curl = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$result['access_token'];
        $data = json_encode($data);
        return $this->LXCurl($data);
    }
    //解密微信数据
    private function decryptData($sessionKey,$encryptedData,$iv){
        if (strlen($sessionKey) != 24) {
            return 41001;
        }
        $aesKey=base64_decode($sessionKey);
        if (strlen($iv) != 24) {
            return 41002;
        }
        $aesIV=base64_decode($iv);
        $aesCipher=base64_decode($encryptedData);
        $result = $this->decrypt($aesCipher,$aesIV,$aesKey);
        if ($result[0] != 0) {
            return $result[0];
        }
        $dataObj=json_decode( $result[1] );
        if( $dataObj  == NULL )
        {
            return 41003;
        }
        if( $dataObj->watermark->appid != $this->we_chat_appid )
        {
            return 41003;
        }
        $data = $result[1];
        return $data;
    }
    private function decrypt( $aesCipher, $aesIV ,$aesKey)
    {
        try {


            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            mcrypt_generic_init($module, $aesKey, $aesIV);
            //解密
            $decrypted = mdecrypt_generic($module, $aesCipher);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);


        } catch (Exception $e) {
            return array(41003, null);
        }
        try {
            //去除补位字符
            $result = $this->decode($decrypted);
        } catch (Exception $e) {
            //print $e;
            return array(41003, null);
        }
        return array(0, $result);
    }
    private function decode($text)
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }
    //解密微信数据 !
    //判断是否关注过微信关系
    private function IsAttention($openid){
        $result = $this->GetAccessToKen();
        $this->lx_curl = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$result['access_token']."&openid=".$openid."&lang=zh_CN";
        $result = $this->LXCurl();
        return $result;
    }
    private function public_Curl($data){
        $result = $this->LXCurl($data);
        return $result;
    }

    /**
     * @param array $data 多线程
     */
    protected function LinCurl($data){
        $mh = curl_multi_init();
        $conn = array();
        $header = 'Accept-Charset: utf-8';
        foreach ($data as $k => $v){
            $conn[$k] = curl_init();
            curl_setopt($conn[$k], CURLOPT_URL, $this->lx_curl);
            curl_setopt($conn[$k], CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($conn[$k], CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($conn[$k], CURLOPT_HTTPHEADER, $header);
            if (!empty($v)) {
                curl_setopt($conn[$k], CURLOPT_POST, 1);
                curl_setopt($conn[$k], CURLOPT_POSTFIELDS, $v);
            }
            curl_setopt($conn[$k], CURLOPT_TIMEOUT, 30);
            curl_multi_add_handle($mh,$conn[$k]);
        }
        $active = null;
        do { curl_multi_exec($mh,$active); } while ($active);
        foreach ($data as $i => $url) {
            curl_multi_remove_handle($mh, $conn[$i]);
        }
        curl_multi_close($mh);
        return array('status'=>1);
    }
    //判断是否关注过微信关系
    //请求
    protected function LXCurl( $data=array() ){
        $curl = curl_init();
        $header = 'Accept-Charset: utf-8';
        curl_setopt($curl, CURLOPT_URL, $this->lx_curl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        $output  = curl_exec($curl);
        $error = curl_errno($curl);
        // dump($output);
        // dump($error);
        curl_close($curl);
        if($error){
            //发生错误
//            $this->ErrorLog('get_weChat_log',array(
//                'type'=>1,
//                'msg'=>'获取微信信息请求发生错误',
//                'error'=>$error
//            ));
            return array(
                'status'=>0,
                'info'=>'服务器繁忙,请稍后再试!',
            );
        }
        $result = json_decode($output, true);
        // var_dump($result);
        if($result['errcode']){
//            $this->ErrorLog('get_weChat_log',array(
//                'type'=>1,
//                'msg'=>'获取微信信息,微信返回错误信息',
//                'errcode'=>$result['errcode'],
//                'errmsg'=>$result['errmsg'],
//            ));
            return array(
                'status'=>0,
                'info'=>'服务器繁忙,请稍后再试!',
                'result'=>$result,
            );
        }
        return array('status'=>1,'result'=>$result);
    }
}