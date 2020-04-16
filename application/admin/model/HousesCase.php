<?php


namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class HousesCase extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = true;


    //获取器 用于读取字段值的修改
    protected function getTypeNameAttr($value, $data)
    {
        $type = '';
        if (isset($data['type'])) {
            if ($data['type'] == 1) {
                $type = 'VR';
            } else if ($data['type'] == 2) {
                $type = '实景案例';
            } else if ($data['type'] == 3) {
                $type = '文章';
            }
        }
        return $type;
    }
}