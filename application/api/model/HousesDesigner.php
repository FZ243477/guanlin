<?php


namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class HousesDesigner extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';


    protected function getDesignerLogoAttr($value)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }
        return $value;
    }

    protected function getBackgroundAttr($value)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }
        return $value;
    }
}