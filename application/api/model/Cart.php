<?php


namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class Cart extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //获取器 用于读取字段值的修改
    protected function getGoodsCateNameAttr($value, $data)
    {
        if ($data['goods_cate_id']) {
            $goods_cate_name = model('GoodsCate')->where(['goods_cate_id' => $data['goods_cate_id']])->value('goods_cate_name');
        } else {
            $goods_cate_name = '';
        }

        return $goods_cate_name;
    }

   /* protected function getGoodsPicAttr($value)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }
        return $value;
    }*/

    protected function getGoodsBannerPicAttr($value)
    {
        if ($value) {
            $value = explode(',', $value);
            foreach ($value as &$v) {
                $v = picture_url_dispose($v);
            }
        }
        return $value;
    }
}