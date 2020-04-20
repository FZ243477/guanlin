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

    protected function getStateAttr($value)
    {
        if ($value ==0) {
            $value = "待付款";
        }
        if ($value ==1) {
            $value = "待发货";
        }
        if ($value ==2) {
            $value = "已发货";
        }
        if ($value ==3) {
            $value = "已签收";
        }
        if ($value ==4) {
            $value = "已完成";
        }

        return $value;
    }
}