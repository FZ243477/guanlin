<?php


namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class User extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //获取器 用于读取字段值的修改
    protected function getLevelNameAttr($value, $data)
    {
        if ($data['user_level']) {
            $level_name = model('user_level')->where(['user_level_id' => $data['user_level']])->value('level_name');
        } else {
            $level_name = '';
        }

        return $level_name;
    }
}