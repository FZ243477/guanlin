<?php


namespace app\api\model;

use think\Model;

class OrderGoods  extends Model
{
	 protected function getGoodsLogoAttr($value)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }
        return $value;
    }
}