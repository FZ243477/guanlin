<?php


namespace app\common\helper;
use app\common\constant\EncryptionConstant;
use app\common\constant\UserConstant;
use think\Db;

trait TokenHelper
{

    /**
     * 设置用户token的方法
     * @param $user_id
     * @param int $login_type
     * @param int $is_refresh
     * @return string
     */
    private function setTokenByUserInfo($user_id, $login_type = UserConstant::REG_SOURCE_CGI, $is_refresh = 0){
        if (!$user_id) {
            return '';
        }
        $user   = model('user')->where(['id'=>$user_id])->field('id')->find();
        if($user){
            $user_token = model('user_token')->where([
                'user_id' => $user['id'],
                'login_type' => $login_type,
            ])->find();
            if ($user_token && $user_token['end_time'] > time() && $is_refresh == 0) {
                $token = $user_token['token'];
            } else {
                $time = time();
                $token = md5(EncryptionConstant::MD5_KEY.$time.$user_id);
                $data = [
                    'user_id' => $user['id'],
                    'token' => $token,
                    'create_time' => $time,
                    'update_time' => $time,
                    'end_time' => $time + 3600*24*100,
                    'login_type' => $login_type,
                ];
                $res2 = model('user_token')->save($data);
                $res2 ? false : $token = '';
            }
        }else{
            $token = '';
        }
        return $token;
    }

    /**
     * 通过token，获取用户的信息
     * @param $token string
     * @return int
     */
    private function getUserInfoByToken($token){
        if (!$token) {
            return 0;
        }
        $user = model('user_token')->where(['token' => $token])->field('user_id, end_time')->find();
        if ($user) {
            if ($user['end_time'] < time()) {
                return 0;
            } else {
                $user_id = $user['user_id'];
                return $user_id;
            }
        } else {
            return 0;
        }
    }

    private function delUserToken($token) {
        model('user_token')->where(['token' => $token])->setField('end_time', time());
    }

}
    