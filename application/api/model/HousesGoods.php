<?php


namespace app\api\model;

use think\Model;

class HousesGoods extends Model
{
    protected function getGoodsLogoAttr($value)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }
        return $value;
    }
}