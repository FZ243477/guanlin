<?php


namespace app\api\model;

use think\Model;

class GoodsImages extends Model
{
    protected function getLogoAttr($value)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }
        return $value;
    }
}