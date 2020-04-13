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


    public function login()
    {
        if (request()->isPost()) {
            $telephone = request()->post('telephone');
            $password = request()->post('password');
            if (!$telephone) {
                $json_arr = ['status' => 0, 'msg' => '请填写手机号码', 'data' => []];
                ajaxReturn($json_arr);
            }
            $res = $this->VerifyTelephone($telephone);
            if (!$res) {
                $json_arr = ['status' => '0', 'msg' => '手机号码格式不正确！',];
                ajaxReturn($json_arr);
            }

            $code = request()->post('code');
            $type = request()->post('type');
            //验证短信
            $res = $this->checkMessage($telephone, $code, 1);

            if ($res['status'] != 1) {
                ajaxReturn(['status' => 0, 'msg' => $res['msg'], 'data' => []]);
            }
            $mem = model('user')->where(['telephone' => $telephone])->field('id,is_kujiale')->find();
            Db::startTrans();
            if (!$mem['id']) {
                $reg_code = request()->post('reg_code');
                $first_leader = '';
                if ($reg_code) {
                    $user_data = [];
                    $user_data['reg_code'] = $reg_code;
                    $user = model('user')->where($user_data)->find();
                    if ($user) { //存在则有号码
                        $first_leader = $user['id'];
                    }
                }

                $add_mem = $this->createUser($telephone, $password, $type, $first_leader);
                if (!$add_mem) {
                    $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                    ajaxReturn($json_arr);
                }
                $mem['id'] = $add_mem;
            }

            $this->one_login($mem['id']);
            model('user')->where('id', $mem['id'])->setField('is_platform', 1);
            $token = $this->setTokenByUserInfo($mem['id']);
            model('user')->where(['id' => $mem['id']])->setInc('login_num', 1);
            Db::commit();
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['token' => $token]];
            ajaxReturn($json_arr);
        }
    }

    /**
     * 小程序登录
     */
    public function wxLogin()
    {
        $appid = getSetting('wechat.we_cgi_appid');
        $secret = getSetting('wechat.we_cgi_secret');

        $code = request()->post('code');
        $query = array(
            'appid'     => $appid,
            'secret'    => $secret,
            'js_code'      => $code,
            'grant_type'=> 'authorization_code',
        );
        $url = 'https://api.weixin.qq.com/sns/jscode2session?'.http_build_query($query).'#wechat_redirect';
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
        $output  = curl_exec($curl);
        $error = curl_errno($curl);
        curl_close($curl);
        $result = json_decode($output, true);
        if ($result && isset($result['session_key'])) {
            $session_key = $this->base64Encryption($result['session_key']);
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['session_key' => $session_key]]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
        }
    }

    /**
     * 是否绑定
     */
    public function binding()
    {
        $encryptedData = request()->post('encryptedData');
        $iv = request()->post('iv');
        $session_key = request()->post('session_key');
        $result = $this->get_access_token($encryptedData,$iv,$session_key);
        $token = '';
        if ($result) {
            $info = model('user_access_token')->where(['open_id' => $result['openId']])->find();
            if($info){
                if ($info['user_id']) {
                    model('user_access_token')->save(['open_id' => $result['openId']], ['user_id' => $info['user_id']]);
                    $user_id = $info['user_id'];
                    $this->one_login($user_id);
                    $token = $this->setTokenByUserInfo($user_id, UserConstant::REG_SOURCE_CGI);
                }
            }else{
                $img = $result['avatarUrl'];
                $name = $this->filter($result['nickName']);
                $sex = $result['gender'];
                /*$data   =   array(
                    'sex'   =>  $sex,
                    'realname'   =>  $name,
                    'nickname'   =>  $name,
                    'reg_time'    =>  date('Y-m-d H:i:s'),
                );
                model('user')->save($data);
                $user_id = model('user')->getLastInsID();*/
                model('user_access_token')->save([
                    'open_id' => $result['openId'],
                    'nickname' => $name,
                    'avatarurl' => $img,
                    'gender' => $sex,
                    //'union_id' => $data['unionid'],
                    //'user_id' => $user_id,
                    'create_time' => time()
                ]);
            }

            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['token' => $token]]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
        }


    }

    /**
     * 绑定手机号
     */
    public function bindingTelephone()
    {
        $encryptedData = request()->post('encryptedData');
        $iv = request()->post('iv');
        $session_key = request()->post('session_key');
        $telephone = request()->post('telephone');
        if (!$encryptedData || !$iv || !$session_key || !$telephone) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
        }
        $code = request()->post('code');
        //验证短信
        $res = $this->checkMessage($telephone, $code, 1);
        if ($res['status'] != 1) {
            ajaxReturn(['status' => 0, 'msg' => $res['msg'], 'data' => []]);
        }
        $result = $this->get_access_token($encryptedData,$iv,$session_key);
        if ($result) {
            $info = model('user_access_token')->where(['open_id' => $result['openId']])->find();
            if($info && $info['user_id']){
                //model('user_access_token')->save(['open_id' => $result['openId']], ['user_id' => $info['user_id']]);
                $user_id = $info['user_id'];
            }else{
                $img = $result['avatarUrl'];
                $name = $this->filter($result['nickName']);
                $sex = $result['gender'];
                $user_id = model('user')->where(['telephone' => $telephone])->value('id');
                if (!$user_id) {
                    $data   =   [
                        'sex'   =>  $sex,
                        'telephone' => $telephone,
                        'realname'   =>  $name,
                        'nickname'   =>  $name,
                        'is_platform' => 1,
                        'reg_time'    =>  date('Y-m-d H:i:s'),
                    ];
                    model('user')->save($data);
                    $user_id = model('user')->getLastInsID();
                }
                if (!$info) {
                    model('user_access_token')->save([
                        'open_id' => $result['openId'],
                        'user_id' => $user_id,
                        'nickname' => $name,
                        'avatarurl' => $img,
                        'gender' => $sex,
                        //'union_id' => $data['unionid'],
                        //'user_id' => $user_id,
                        'create_time' => time()
                    ]);
                } else {
                    $user_find = model('user_access_token')->where(['user_id' => $user_id])->find();
                    if ($user_find) {
                        ajaxReturn(['status' => 0, 'msg' => '您输入的手机号已被绑定']);
                    } else {
                        model('user_access_token')->save(['user_id' => $user_id],['open_id' => $result['openId']]);
                    }
                }
            }
            $this->one_login($user_id);
            $token = $this->setTokenByUserInfo($user_id, UserConstant::REG_SOURCE_CGI);
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['token' => $token]]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
        }
    }

    //微信过滤昵称中的特殊字符
    public function filter($str) {
        if($str){
            $nickname = $str;
            $nickname = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $nickname);

            $nickname = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $nickname);

            $nickname = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $nickname);

            $nickname = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $nickname);

            $nickname = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $nickname);

            $nickname = str_replace(array('"','\''), '', $nickname);
            $return = addslashes(trim($nickname));
        }else{
            $return = '';
        }
        return $return;
    }

    /**
     * @version 用户注册
     * @param   telephone      手机号
     * @param   password   	   密码
     * @param   repassword     确认密码
     * @param   code           手机验证码
     * @param   type           类型 1 APP 2 PC 3 手机端
     *
     * @return  status => 0  		 参数错误
     * @return  status => 1  		 注册成功
     * @return  status => 2  		 验证码错误!
     * @return  status => 3  		 验证码过期
     */
    public function register(){
        if (request()->isPost()) {
            $telephone = request()->post('telephone');
            $password = request()->post('password');
            $imgCode = request()->post('imgCode');
            $code_id = request()->post('code_id');
            $code = request()->post('code');
            $type = request()->post('type');
            $reg_code = request()->post('reg_code');
            //$invite_mobile = request()->post('invite_mobile');

            if (!in_array($type, [1, 2, 3])) {
                ajaxReturn(['status' => 0, 'msg' => '参数type错误', 'data' => []]);
            }

            if (!isset($telephone)) {
                ajaxReturn(['status' => 0, 'msg' => '请填写手机号码', 'data' => []]);
            }
            $res = $this->VerifyTelephone($telephone);
            if (!$res) {
                ajaxReturn(['status' => 0, 'msg' => '手机号码格式不正确', 'data' => []]);
            }

            if (!isset($password)) {
                ajaxReturn(['status' => 0, 'msg' => '请填写密码', 'data' => []]);
            }

            $res = $this->VerifyPassword($password);
            if (!$res) {
                ajaxReturn(['status' => 0, 'msg' => '密码格式不正确', 'data' => []]);
            }

            //检查验证码是否正确
            if(!captcha_check($imgCode, $code_id)){
                ajaxReturn(['status'=>0, 'msg'=>'图片验证码错误']);
            }

            if (!isset($code)) {
                ajaxReturn(['status' => 0, 'msg' => '请填写验证码', 'data' => []]);
            }


            // 先检查手机号是否已注册
            $mem = model('user')->where(['telephone' => $telephone])->find();
            if ($mem) {
                ajaxReturn(['status' => 0, 'msg' => '此手机号码已注册了', 'data' => []]);
            }


            //验证短信
            $res = $this->checkMessage($telephone, $code, 1);

            if ($res['status'] != 1) {
                ajaxReturn(['status' => 0, 'msg' => $res['msg'], 'data' => []]);
            }
            $first_leader = '';
            if ($reg_code) {
                $user_data = [];
                $user_data['reg_code'] = $reg_code;
                $user = model('user')->where($user_data)->find();
                if ($user) { //存在则有号码
                    $first_leader = $user['id'];
                }
            }
            /* $invite_mobile_i = 0;

             if ($invite_mobile) {
                 $invite_mobile = decode_base_64($invite_mobile);
                 $res = $this->VerifyTelephone($invite_mobile);
                 if ($res) {
                     //验证推荐人手机号是否存在
                     $user_data = [];
                     $user_data['is_del'] = 0;
                     $user_data['telephone'] = $invite_mobile;
                     $user = model('user')->where($user_data)->find();
                     if ($user) { //存在则有号码
                         $invite_mobile_i = $invite_mobile;
                     }
                 }
             }*/
            Db::startTrans();
            $add_mem = $this->createUser($telephone, $password, $type, $first_leader);

            if ($add_mem) {
                $this->one_login($add_mem);
                $token = $this->setTokenByUserInfo($add_mem);
                Db::commit();
                $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['token' => $token]];
                ajaxReturn($json_arr);
            } else {
                $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                ajaxReturn($json_arr);
            }
        }
    }

    public function one_login($user_id)
    {
        $user_info = model('user')->where(['id' => $user_id])->find();
        $url = 'https://openapi.kujiale.com/v2/login?';
        $post_data = array(
            'name'   => $user_info['nickname'],
            //'email'  => $user_info['email'],
            'telephone'  => $user_info['telephone'],
            'avatar' => picture_url_dispose($user_info['head_img']),
            'type'   => 0,
        );
        $get_data['appuid']   = $user_id;
        $json_arr = $this->backDataInfo($url, $post_data, 'post', $get_data);
        if ($json_arr['c'] == 0) {
            model('user')->isUpdate(true)->save(['is_child' => 1, 'is_kujiale' => 1, 'kujiale_token' => $json_arr['d']], ['id' => $user_id]);
        }

        $this->svjia_login($user_id);
    }

    public function svjia_login($user_id)
    {

        $url = "https://graph.3vjia.com/oauth/token";
        $post_data = array();
        $post_data["grant_type"]  = getSetting('svjia.grant_type'); //取固定值client_credentials
        $post_data["client_id"]   = getSetting('svjia.appid'); //应用id
        $post_data["client_secret"]   = getSetting('svjia.appkey');; //应用密钥

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $headers = array(
            'Accept:application/json;',
            'Content-Type: application/x-www-form-urlencoded',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($post_data != "") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $json_arr = curl_exec($ch);
        curl_close($ch);
        //$json_arr = $this->curl_access_token($url, http_build_query($post_data));
        $data = json_decode($json_arr, true);
        if ($data['access_token']) {
            model('user')->save(['svja_token' => $data['access_token']], ['id' => $user_id]);
        }

        $user_info = model('user')->where(["id" => $user_id])->find();
        if ($user_info['is_svjia'] != 1 && !$user_info['svjia_id']) {
            if (!$data['access_token']) {
                $data['access_token'] = $user_info['svja_token'];
            }
            $url = "https://open.3vjia.com/api/outerapi/outerUser/addUser?sysCode=outerapi&access_token=".$data['access_token'];
            $userName = 'lc'.time().rand(1000,9999);
            while(model('user')->where(["svjia_name"=>$userName])->find()) {
                $userName = 'lc'.time().rand(1000,9999);
            }
            //dump($userName);die;
            $passWord = 'lc'.$user_info['telephone'];
            $post_data = array();
            $post_data["departmentId"]   = "1315448";
            $post_data["mobile"]   = $user_info['telephone'];
            $post_data["userName"]   = $userName;
            $post_data["name"]   = $user_info['nickname'];
            $post_data["passWord"]   = $passWord;
            $post_data["outUserId"]   = $this->user_id;
            $post_data["outAppId"]   = getSetting('svjia.appid');
            $json_arr = $this->curlSend($url, json_encode($post_data));
            $data = json_decode($json_arr, true);

            if ($data['success']) {
                $save = [
                    'is_svjia' => 1,
                    'svjia_id' => $data['result']['swjId'],
                    'svjia_name' => $data['result']['userName'],
                    'svjia_password' => $user_info['telephone'],
                ];
                model('user')->isUpdate(true)->save($save, ["id" => $this->user_id]);
//                ajaxReturn(['status' => 1, 'msg' => '绑定酷家乐成功', 'data' => []]);
            } else {
                // echo '错误码：'.$data['errorCode'];
                // echo '<br>错误提示：'.$data['errorMessage'];
//                ajaxReturn(['status' => 0, 'msg' => '已绑定过酷家乐', 'data' => []]);
            }
        } else {
//            ajaxReturn(['status' => 1, 'msg' => '已绑定过酷家乐', 'data' => []]);
        }


    }

    public function createUser($tel,$pass,$type,$first_leader="",$chat_id="")
    {
        $user_model = model('user');

        $data = [
            'telephone'    => $tel,
            'nickname'     => $tel,
            'password'     => md5($pass),
            'reg_time'     => date('Y-m-d H:i:s',time()),
            'status'       => 1,
            'type'         => $type,
            'is_platform' => 1,
            'chat_id'      => $chat_id,
            'reg_code'     => $this->_create_invite_code(),
        ];
        $data['first_leader'] = $first_leader;
        // 注册上级
        if($data['first_leader']){
            // 如果找到他老爸还要找他爷爷他祖父等
            $first_leader = $user_model->where("id", $data['first_leader'])->find();
            $data['second_leader'] = $first_leader['first_leader'];
            $data['third_leader'] = $first_leader['second_leader'];
        }else{
            $data['first_leader'] = 0;
        }

        $result  = $user_model->save($data);

        if($result){
            $userid = $user_model->getLastInsID();
            //注册成功后他的祖籍分销下线人数要加1
            if($first_leader) {
                $user_model->where(['id' => $data['first_leader']])->setInc('underling_number');
                $user_model->where(['id' => $data['second_leader']])->setInc('underling_number');
                $user_model->where(['id' => $data['second_leader']])->setInc('underling_number');
            }
            $integral = getSetting('integral.register_give_integral');
            // 注册赠送积分
            $this->integral_record(
                IntegralConstant::INTEGRAL_CATE_TYPE_REGISTER,
                $integral,
                IntegralConstant::INTEGRAL_USE_TYPE_REGISTER,
                0,
                $this->user_id,
                1
            );
//            $this->integral_record(2, 'reg_give_integral', 'reg_remark', 0, $userid);
            return $userid;
        }else{
            return false;
        }
    }

    //生成唯一的注册码
    private function _create_invite_code(){
        $user_model = model('user');
        $code = randStr(6,true);
        while($user_model->where(array('reg_code'=>$code))->find()){
            $code = randStr(6,true);
        }
        return $code;
    }

    public function forget()
    {
        if (request()->isPost()) {
            $telephone = request()->post('telephone');
            $new_pwd = request()->post('new_pwd');
            $code = request()->post('code');
            $code_id = request()->post('code_id');
            $imgCode = request()->post('imgCode');

            if (empty($telephone)) {
                ajaxReturn(['status' => 0, 'msg' => '请填写手机号', 'data' => []]);
            }
            $res = $this->VerifyTelephone($telephone);
            if (!$res) {
                ajaxReturn(['status' => '0', 'msg' => '手机号码格式不正确！', 'data' => []]);
            }
            //检查验证码是否正确
            if(!captcha_check($imgCode, $code_id)){
                ajaxReturn(['status'=>0, 'msg'=>'图片验证码错误']);
            }
            $map = [];
            $map['telephone'] = $telephone;
            $user = model('user')->where($map)->find();

            if (!$user) {
                ajaxReturn(['status' => 0, 'msg' => '用户不存在了', 'data' => []]);
            }

            if ($user['status'] == 2) {
                ajaxReturn(['status' => 0, 'msg' => '用户账号被冻结了', 'data' => []]);
            }

            if (empty($new_pwd)) {
                ajaxReturn(['status' => 0, 'msg' => '新登录密码不能为空', 'data' => []]);
            }

            //验证密码格式
            $res = $this->VerifyPassword($new_pwd);
            if (!$res) {
                ajaxReturn(['status' => 0, 'msg' => '登录密码格式不正确', 'data' => []]);
            }

            if (empty($code)) {
                ajaxReturn(['status' => 0, 'msg' => '手机验证码不能为空', 'data' => []]);
            }

            //验证验证码是否正确
            $res = $this->checkMessage($user['telephone'], $code, 2);
            if ($res['status'] == 0) {
                ajaxReturn(['status' => 0, 'msg' => $res['msg'], 'data' => []]);
            }
            //设置/修改用户的登录密码
            $pwd_data = [];
            $pwd_data['password'] = md5($new_pwd);
            $pwd_data['update_time'] = time();
            $res = model('user')->save($pwd_data, ['id' => $user['id']]);
            if (!$res) {
                ajaxReturn(['status' => 0, 'msg' =>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            }

            // 记录用户操作日志
            //$res = $this->record_user_log($user['id'], $telephone, '用户找回/修改登录密码');
            // 给用户发送系统消息
            //$res = $this->add_message_log($user['id'], '找回/修改登录密码', '您已经成功找回/修改了账号的登录密码了！', 0);

            ajaxReturn(['status' => 1, 'msg' =>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
        }
    }


    /*退出登录*/
    public function logout()
    {
        $token = request()->post('token');
        $this->delUserToken($token);
    }

    public function checkMsg(){
        $telephone = request()->post('telephone');

        if (!$telephone) {
            $json_arr = ['status' => 0, 'msg' => '请填写手机号码', 'data' => []];
            ajaxReturn($json_arr);
        }
        $res = $this->VerifyTelephone($telephone);
        if (!$res) {
            $json_arr = ['status' => '0', 'msg' => '手机号码格式不正确！',];
            ajaxReturn($json_arr);
        }
        $data = request()->post();
        $codeArr = $data['code'];
        $code = '';
        foreach ($codeArr as $k => $v) {
            $code .= $v['val'];
        }
        $json_arr = $this->checkMessage($telephone, $code, 1);
        if ($json_arr['status'] == 0) {
            ajaxReturn($json_arr);
        }
        $mem = model('user')->save(['telephone' => $telephone, 'id' => $this->user_id]);

        ajaxReturn($json_arr);
    }


    public function checkMessage($phone, $identify, $type=false)
    {   //这里判断  短信验证码

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
        $code_id = input('post.code_id', '');
        $imgCode = input('post.imgCode');
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
        /* //检查验证码是否正确
         if(!captcha_check($imgCode, $code_id)){
             ajaxReturn(['status'=>0, 'msg'=>'图片验证码错误']);
         }*/

        if(!$telephone){
            ajaxReturn(['status'=>0, 'msg'=>'请填写手机号']);
        }

        if(!$this->VerifyTelephone($telephone)){
            ajaxReturn(['status'=>0, 'msg'=>'手机号码格式错误']);
        }

        /*// 判断手机号是否存在
        if($type == SmsConstant::SMS_TYPE_REGISTER
            ||$type == SmsConstant::SMS_TYPE_NEW_TELEPHONE
            ||$type == SmsConstant::SMS_TYPE_BINDING
        ){
            $count = model('user')->where(['telephone'=>$telephone])->count();
            if($count){
                ajaxReturn(['status'=>0, 'msg'=>'手机号已存在']);
            }
        }*/

        /*// 判断手机号是否不存在
        if($type == SmsConstant::SMS_TYPE_EDIT_PASSWORD
            ||$type == SmsConstant::SMS_TYPE_OLD_TELEPHONE
        ){
            $count = model('user')->where(['telephone'=>$telephone])->count();
            if(!$count){
                ajaxReturn(['status'=>0, 'msg'=>'手机号不存在']);
            }
        }*/

        /*if ($type == SmsConstant::SMS_TYPE_NEW_TELEPHONE){
            $old_telephone = model('user')->where(['id' => $this->user_id])->value('telephone');
            if($old_telephone==$telephone){
                ajaxReturn(['status'=>0, 'msg'=>'手机号不能与原绑定手机号相同']);
            }
        }*/

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

    public function randInt($int = 6, $caps = false)
    {  //随机数
        $strings = '0123456789';
        $return = '';
        for($i = 0; $i < $int; $i++)
        {
            srand();
            $rnd = mt_rand(0, 9);
            $return = $return . $strings[$rnd];
        }
        return $caps ? strtoupper($return) : $return;
    }

    public function captcha()
    {
        $code_id = $this->randInt().uniqid();
        $img = picture_url_dispose(captcha_src($code_id));
//        $this->redirect($img);
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['code_img' => $img, 'code_id' => $code_id]]);
    }
}