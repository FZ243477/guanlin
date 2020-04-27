<?php


namespace app\api\controller;

use app\common\constant\IntegralConstant;
use app\common\constant\SystemConstant;
use app\common\constant\UserConstant;
use app\common\helper\EncryptionHelper;
use app\common\helper\MessageHelper;
use app\common\helper\UserHelper;
use app\common\helper\VerificationHelper;
use app\common\helper\CurlHelper;
use app\common\helper\TokenHelper;
use think\Db;

class Login extends Base
{
    use TokenHelper;
    use VerificationHelper;
    use CurlHelper;
    use EncryptionHelper;
    use UserHelper;
    use MessageHelper;

    /**
     * @title 小程序登录
     * @description 小程序登录
     * @param name:code type:string require:1 default: other: desc:
     * @param name:encryptedData type:string require:1 default: other:
     * @param name:iv type:string require:1 default: other: desc:
     * @return token:5609efd652c5e57731f430a094545ca7
     * @author LnC
     * @url /api/Login/wxLogin
     * @method Post
     */
    public function wxLogin()
    {
        $appid = getSetting('wechat.we_cgi_appid');
        $secret = getSetting('wechat.we_cgi_secret');

        $code = request()->post('code');
        $encryptedData = request()->post('encryptedData');
        $iv = request()->post('iv');

        $query = array(
            'appid' => $appid,
            'secret' => $secret,
            'js_code' => $code,
            'grant_type' => 'authorization_code',
        );
        $url = 'https://api.weixin.qq.com/sns/jscode2session?' . http_build_query($query) . '#wechat_redirect';
        $output = $this->curt($url);
        $result = json_decode($output, true);
        if (!isset($result['session_key'])) {
            ajaxReturn($result);
        }
        $session_key = $result['session_key'];
        if ($result && isset($result['session_key'])) {
            $result = $this->get_access_token($encryptedData, $iv, $session_key);
            if ($result) {
                $info = model('user_access_token')->where(['open_id' => $result['openId']])->find();
                $img = $result['avatarUrl'];
                $name = $this->filter($result['nickName']);
                $sex = $result['gender'];
                $data = array(
                    'sex' => $sex,
                    'head_img' => $img,
                    'nickname' => $name,
                    'create_time' => time(),
                    'last_login_time' => time(),
                    'login_num' => 1,
                );
                if ($info) {
                    $user = model('user')->where(['id' => $info['user_id']])->find();
                } else {
                    $user = false;
                }
                if ($info) {
                    if ($user) {
                        $user_id = $user['id'];
                        unset($data['create_time']);
                        $data['update_time'] = time();
                        $data['login_num'] = $user['login_num'] + 1;
                        model('user')->save($data, ['id' => $user['id']]);
                        if ($user['telephone']) {
                            $is_binging = 0;
                        } else {
                            $is_binging = 1;
                        }
                    } else {
                        $is_binging = 1;
                        $user_id = model('user')->insertGetId($data);
                        model('user_access_token')->save(['user_id' => $user_id], ['id' => $info['id']]);
                    }
                } else {
                    $is_binging = 1;
                    $user_id = model('user')->insertGetId($data);
                    model('user_access_token')->save([
                        'open_id' => $result['openId'],
                        'nickname' => $name,
                        'avatarurl' => $img,
                        'user_id' => $user_id,
                        'gender' => $sex,
//                        'union_id' => $unionId,
                        'create_time' => time()
                    ]);
                }
                $token = $this->setTokenByUserInfo($user_id, UserConstant::REG_SOURCE_CGI);
                $data = ['token' => $token, 'is_binding' => $is_binging];
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
        }
    }

    /**
     * 绑定手机号
     */
    public function editTelephone()
    {
        $token = request()->post('token');

        $telephone = request()->post('telephone');

        if (!$token) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
        }
        $user_id = $this->getUserInfoByToken($token);
        if (!$user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请重新登录', 'data' => []]);
        }

        if (!$telephone) {
            ajaxReturn(['status' => 0, 'msg' => '请填写手机号', 'data' => []]);
        }
        if (!$this->VerifyTelephone($telephone)) {
            ajaxReturn(['status' => 0, 'msg' => '手机号格式不正确', 'data' => []]);
        }


        $code = request()->post('code');
        if (!$code) {
            ajaxReturn(['status' => 0, 'msg' => '请填写验证码', 'data' => []]);
        }
        //验证短信
        $res = $this->checkMessage($telephone, $code, 1);
        if ($res['status'] != 1) {
            ajaxReturn(['status' => 0, 'msg' => $res['msg'], 'data' => []]);
        }


        $pid = model('user')->where(['telephone' => $telephone])->value('id');
        if ($pid) {
            ajaxReturn(['status' => 0, 'msg' => '该手机号已被绑定', 'data' => []]);
        }
        model('user')->save(['telephone' => $telephone], ['id' => $user_id]);
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);

    }

    public function bindingTelephone()
    {
        $appid = getSetting('wechat.we_cgi_appid');
        $secret = getSetting('wechat.we_cgi_secret');

        $code = request()->post('code');
        $encryptedData = request()->post('encryptedData');
        $iv = request()->post('iv');

        $query = [
            'appid'     => $appid,
            'secret'    => $secret,
            'js_code'      => $code,
            'grant_type'=> 'authorization_code',
        ];
        $url = 'https://api.weixin.qq.com/sns/jscode2session?'.http_build_query($query).'#wechat_redirect';
        $output = $this->curt($url);
        $result = json_decode($output, true);
        if (!isset($result['session_key'])) {
            ajaxReturn($result);
        }
        $result = $this->get_access_token($encryptedData, $iv, $result['session_key']);
        model('user')->save(['telephone' => $result['phoneNumber']], ['id' => $this->user_id]);
        ajaxReturn(['status' => 1, 'msg' => 'success']);

    }

    public function curt($url, $data = [])
    {
        $curl = curl_init();
        $header[] = 'Accept-Charset: utf-8';
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        $output = curl_exec($curl);
        $error = curl_errno($curl);
        curl_close($curl);
        return $output;
    }

    //微信过滤昵称中的特殊字符
    public function filter($str)
    {
        if ($str) {
            $nickname = $str;
            $nickname = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $nickname);

            $nickname = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $nickname);

            $nickname = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $nickname);

            $nickname = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $nickname);

            $nickname = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $nickname);

            $nickname = str_replace(array('"', '\''), '', $nickname);
            $return = addslashes(trim($nickname));
        } else {
            $return = '';
        }
        return $return;
    }


    public function checkMessage($phone, $identify, $type=false)
    {
        //这里判断  短信验证码
        if ($identify == 666){ // 验证码正确,修改验证码状态
            //model('user_verify')->save(['status'=>1],['id'=>$res['id']]);
            return ['status'=>'1', 'msg'=>'验证码正确'];
        }else {     // 验证码错误
            return ['status'=>'0', 'msg'=>'验证码错误'];
        }
        if (!$phone || !$identify) {
            return ['status' => 0, 'msg' => '参数错误'];
        }

        $data = ['telephone' => $phone];

        if($type){
            $data['type'] = $type;
        }

        $res =  model('user_verify')->where($data)->order('add_time desc')->find();
        if(!$res){
            return ['status' =>'0', 'msg' =>'验证码错误'];
        }

        $time = strtotime($res['add_time']);
        $over_time = getSetting('sms.over_time');
        if (time() < ($time + $over_time)){
            $code = $res['sms_code'];
            if ($code == $identify){ // 验证码正确,修改验证码状态
                //model('user_verify')->save(['status'=>1],['id'=>$res['id']]);
                return ['status'=>'1', 'msg'=>'验证码正确'];
            }else {     // 验证码错误
                return ['status'=>'0', 'msg'=>'验证码错误'];
            }
        }else{      //验证码过期
            model('user_verify')->save(['status' => 0], ['id'=>$res['id']]);
            return ['status'=>'0','msg'=>'验证码过期'];
        }
    }
    /**
     * 发送短信验证码
     */
    public function sendMessage(){

        $telephone = input('post.telephone');
        $type = input('post.type', 1);
        $data['telephone'] = $telephone;
        $data['status']    = 0;
        $data['type']      = $type;
        $res =  model('user_verify')->where($data)->order('add_time desc')->find();
        $time = strtotime($res['add_time']);
        $over_time = getSetting('sms.over_time');
        if (time() < ($time + 60)){
            ajaxReturn(['status'=>0, 'msg'=>'请勿重复发送']);
        }

        if(!$telephone){
            ajaxReturn(['status'=>0, 'msg'=>'请填写手机号']);
        }

        if(!$this->VerifyTelephone($telephone)){
            ajaxReturn(['status'=>0, 'msg'=>'手机号码格式错误']);
        }
        $is_close = 1;
        if ($is_close == 1) {
            ajaxReturn(['status'=>0, 'msg'=>'功能关闭']);
        }
        //验证码
        $code = '';
        $strings = '0123456789';
        for($i = 0; $i < 6; $i++)
        {
            srand();
            $rnd = mt_rand(0, 9);
            $code = $code . $strings[$rnd];
        }
        $msg = getSetting('sms.code_template');
        $msg = str_replace('{code}', $code, $msg);

        $arr = $this->smsMessage($telephone, $msg);
        if($arr && $arr['respstatus'] ==  0){
            $data = [];
            $data['telephone'] = $telephone;
            $data['sms_code']  = $code;
            $data['status']    = 0;
            $data['add_time']  = date('Y-m-d H:i:s',time());
            $data['type']      = $type;
            $data['msg']       = $msg;
            model('user_verify')->save($data);
            ajaxReturn(['status'=>1, 'msg'=>'验证码发送成功', 'data' => []]);
        }elseif($arr['respstatus'] == 103){
            ajaxReturn(['status'=>0, 'msg'=>'您发送频率过于频繁，歇会儿再来试试吧！']);
        }elseif($arr['respstatus'] == 104){
            ajaxReturn(['status'=>0, 'msg'=>'系统繁忙，请稍后再试！']);
        }else{
            ajaxReturn(['status'=>0, 'msg'=>'验证码发送失败']);
        }

    }

}