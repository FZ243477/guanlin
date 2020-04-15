<?php


namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Banner extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //获取器 用于读取字段值的修改
    protected function getBannerCateNameAttr($value, $data)
    {
        if ($data['banner_cate_id']) {
            $banner_cate_name = model('BannerCate')->where(['banner_cate_id' => $data['banner_cate_id']])->value('banner_cate_name');
        } else {
            $banner_cate_name = '';
        }

        return $banner_cate_name;
    }
}