<?php


namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class Goods extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //获取器 用于读取字段值的修改
    protected function getGoodsCateNameAttr($value, $data)
    {
        if ($data['cate_id']) {
            $goods_cate_name = model('GoodsCate')->where(['id' => $data['cate_id']])->value('classname');
        } else {
            $goods_cate_name = '';
        }

        return $goods_cate_name;
    }

    protected function getGoodsLogoAttr($value, $data)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }
        return $value;
    }


    protected function getGoodsPicAttr($value)
    {
        if ($value) {
            $value = picture_url_dispose($value);
        }
        return $value;
    }

    protected function getGoodsBigBannerAttr($value)
    {
        if ($value) {
            $value = explode(',', $value);
            $goods_banner = [];
            foreach ($value as $v) {
                $goods_banner[]['pic'] = picture_url_dispose($v);
            }
        } else {
            $goods_banner = [];
        }
        return $goods_banner;
    }
    protected function getGoodsDetailPicAttr($value)
    {
        if ($value) {
            $value = explode(',', $value);
            $goods_banner = [];
            foreach ($value as $v) {
                $goods_banner[]['pic'] = picture_url_dispose($v);
            }
        } else {
            $goods_banner = [];
        }
        return $goods_banner;
    }
    protected function getGoodsParamAttr($value)
    {
        if ($value) {
            $goods_param_r = rtrim($value,';');
            $goods_param_arr = explode(';', $goods_param_r);
            $goods_attr = [];
            foreach ($goods_param_arr as $v) {
                $param_kv = explode(':', $v);
                if (!empty($param_kv[0]) && !empty($param_kv[1])) {
                    $goods_attr[] = [
                        'key' => $param_kv[0],
                        'value' => $param_kv[1],
                    ];
                }
            }
        } else {
            $goods_attr = [];
        }
        return $goods_attr;
    }
}