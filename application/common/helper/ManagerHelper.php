<?php


namespace app\common\helper;
use app\common\constant\EncryptionConstant;

trait ManagerHelper
{

    /**
     * 添加操作日志
     * @param $manage_id 管理员ID
     * @param $content 操作内容
     * @param $before_json 操作前值
     * @param $after_json 操作后值
     */
    private function managerLog($manage_id,$content, $before_json, $after_json)
    {
        $data = [];
        $data['manager_id'] = $manage_id;
        $data['content'] = $content;
        $data['add_time'] = date('Y-m-d H:i:s', time());
        $data['login_ip'] = request()->ip();
        $data['control'] = request()->controller();
        $data['act'] = request()->action();
        $data['after_json'] = json_encode((array)$after_json, JSON_UNESCAPED_UNICODE);
        $data['before_json'] = json_encode((array)$before_json, JSON_UNESCAPED_UNICODE);
        model('ManagerLog')->create($data);
    }

    /**
     * 设置用户token的方法
     * @param $manager_id
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function setTokenByUserInfo($manager_id)
    {
        if (!$manager_id) {
            return false;
        }
        $user  = model('manager')->where(['id'=>$manager_id])->find();
        if (!$user) {
            return false;
        }
        $time  = time();
        $token = md5(EncryptionConstant::MD5_KEY.$time.$manager_id);
        $manager_token  = model('manager_token')->where(['manager_id' => $manager_id])->find();
        if ($manager_token) {
            $data = [
                'token' => $token,
                'login_time' => $time,
            ];
            model('manager_token')->save($data, ['manager_id' => $manager_id]);
        } else {
            $data = [
                'token' => $token,
                'login_time' => $time,
                'manager_id' => $manager_id,
            ];
            model('manager_token')->save($data);
        }
        return $token;
    }

    /**
     * 获取用户的manager_id信息
     * @param $token
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfoByToken($token){
        if (!$token) {
            return false;
        }
        $manager_token  = model('manager_token')->where(['token' => $token])->field('manager_id')->find();

        if(empty($manager_token)){
            return false;
        }

        return $manager_token['manager_id'];
    }
}