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
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	private function setTokenByUserInfo($user_id, $login_type = UserConstant::REG_SOURCE_PC){
	    if (!$user_id) {
	        return '';
        }
		$time  = time();
		$token = md5(EncryptionConstant::MD5_KEY.$time.$user_id);
		$user   = model('user')->where(['id'=>$user_id])->field('id')->find();
		$time = time();
		if($user){
            $data = [
                'user_id' => $user['id'],
                'token' => $token,
                'add_time' => $time,
                'end_time' => $time + 3600*24*100,
                'login_type' => $login_type,
            ];
			$res2 = model('user_token')->save($data);
		}else{
			$res2 = '';
		}
		return $res2?$token:'';
	}

    /**
     * 通过token，获取用户的信息
     * @param $token
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	private function getUserInfoByToken($token){
        if (!$token) {
            return 0;
        }
        $user = model('user_token')->where(['token' => $token])->field('user_id, end_time')->order('add_time desc')->find();
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
    