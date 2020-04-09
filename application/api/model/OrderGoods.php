<?php


namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class OrderGoods  extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
	
	 protected function getGoodsPicAttr($value)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }
        return $value;
    }
}