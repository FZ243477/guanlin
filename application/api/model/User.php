<?php


namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class User extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';


    //获取器 用于读取字段值的修改
    protected function getHeadImgAttr($value)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }

        return $value;
    }
}