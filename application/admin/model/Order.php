<?php


namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Order extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $updateTime = 'update_at';

    //获取器 用于读取字段值的修改
    protected function getOrderTimeAttr($value)
    {
        if ($value) {
            $value = date('Y-m-d H:i:s', $value);
        }

        return $value;
    }
}