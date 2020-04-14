<?php


namespace app\admin\helper;

trait ManagerHelper
{

    /**
     * 添加操作日志
     * @param $manage_id @管理员ID
     * @param $content @操作内容
     * @param $before_json @操作前值
     * @param $after_json @操作后值
     * @return bool;
     */
    private function managerLog($manage_id, $content, $before_json, $after_json)
    {
        if ($manage_id == 1) {
            return false;
        }
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


}