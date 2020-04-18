<?php


namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class ManagerLog extends Model
{

    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //获取器 用于读取字段值的修改
    protected function getManagerNameAttr($value, $data)
    {
        if ($data['manager_id']) {
            $manager_name = model('Manager')->where(['is_del' => 0, 'id' => $data['manager_id']])->value('manager_name');
        } else {
            $manager_name = '';
        }

        return $manager_name;
    }
}