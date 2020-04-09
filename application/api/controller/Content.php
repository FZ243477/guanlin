<?php


namespace app\api\controller;


use app\common\constant\BannerConstant;
use app\common\constant\ContentConstant;
use app\common\constant\SystemConstant;

class Content
{
   public function about()
   {


           $content_model = model('content');
         /*  $banner = model('banner')
               ->field('banner_name,banner_describe,banner_pic,link_type,link_url,goods_id')
               ->where(['banner_cate_id' => BannerConstant::BANNER_TYPE_ABOUT])
               ->order('sort desc')
               ->find()*/
           $about_1 = $content_model->field('title,gai,pic,content')->where(['class_id' => ContentConstant::CONTENT_ABOUT_CLASS_ONE])->find();
           $about_2 = $content_model->field('title,gai,pic')->where(['class_id' => ContentConstant::CONTENT_ABOUT_CLASS_TWO])->order('sort desc')->find();
           $about_3 = $content_model->field('title,gai')->where(['class_id' => ContentConstant::CONTENT_ABOUT_CLASS_TREE])->order('sort desc')->select();
           $about_4 = $content_model->field('title,gai')->where(['class_id' => ContentConstant::CONTENT_ABOUT_CLASS_FOUR])->select();
           $about_5 = $content_model->field('title,pic,gai,gai1,gai2,content')->where(['class_id' => ContentConstant::CONTENT_ABOUT_CLASS_FIVE])->find();

           $data = [
//               'banner' => $banner,
               'about_1' => $about_1,
               'about_2' => $about_2,
               'about_3' => $about_3,
               'about_4' => $about_4,
               'about_5' => $about_5,
           ];
           $json_arr =  ["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
           ajaxReturn($json_arr);

   }
}