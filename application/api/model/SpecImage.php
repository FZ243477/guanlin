<?php


namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class SpecImage extends Model
{
    protected $deleteTime = 'delete_time';

    protected function getSrcAttr($value)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }
        return $value;
    }

}