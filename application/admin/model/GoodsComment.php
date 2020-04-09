<?php


namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class GoodsComment extends Model
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
    protected function getSlideImgAttr($value)
    {
        if ($value) {
            $value = explode(',', $value);
            $new_value = [];
            foreach ($value as $kk => $vv) {
                $new_value[$kk]['pic'] = picture_url_dispose($vv);
            }
        } else {
            $new_value = [];
        }
        return $new_value;
    }

}