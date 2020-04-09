<?php


namespace app\api\model;

use think\Model;


class Express  extends Model
{
    protected function getExpressLogoAttr($value)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }
        return $value;
    }
}