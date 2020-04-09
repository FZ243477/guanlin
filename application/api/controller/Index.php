<?php
namespace app\api\controller;
use app\common\constant\ContentConstant;
use app\common\constant\UserConstant;
use app\common\constant\BannerConstant;
use app\common\constant\SystemConstant;
use app\common\constant\PreferentialConstant;
use app\common\helper\PreferentialHelper;
use app\common\helper\GoodsHelper;
use app\common\helper\QrcHelper;
use app\common\helper\UserHelper;
use app\common\helper\VerificationHelper;

class Index extends Base
{
    use UserHelper;
    use GoodsHelper;
    use QrcHelper;
    use VerificationHelper;
    use PreferentialHelper;
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        return $this->fetch();
    }

    /**
     * 首页内容接口
     * http://localhost/Index/homePage
     */
    public function homePage()
    {
        if (request()->isPost()) {
            //访问记录
            $this->add_access($this->user_id, UserConstant::USER_ACCESS_HOME_PAGE);

            //banner图
            $banner = model('banner')
                ->field('banner_name,banner_describe,banner_pic,phone_pic,gic_link,show_type,link_type,link_url,goods_id')
                ->where(['banner_cate_id' => BannerConstant::BANNER_TYPE_HOME_BANNER, 'is_display' => 1])
                ->order('sort desc')
                ->select();
				foreach($banner as $k => $v) {
					$banner[$k]['gic_link'] =  '/'.ltrim(str_replace('.html','',$v['gic_link']), '/'); 
				}
            $popups_banner = model('banner')
                ->field('banner_name,banner_describe,banner_pic,show_type,link_type,link_url,goods_id')
                ->where(['banner_cate_id' => BannerConstant::BANNER_TYPE_HOME_POPUPS, 'is_display' => 1])
                ->order('sort desc')
                ->find();


            /* //限时特惠
             $where = ['status' => 1,'start_time' => ['lt', time()], 'end_time' => ['gt', time()]];
             $limit_special = model('limit_special')->where($where)->field('special_id,end_time')->find();
             if ($limit_special) {
                 $where = ['a.status' => 1, 'special_id' => $limit_special['special_id']];

                 $limit_sales = model('limit_sales')
                     ->alias('a')
                     ->join('tb_goods b','a.goods_id = b.id','left')
                     ->field('limit_sales_id,a.goods_id,goods_name, goods_desc, goods_logo, price, spec_price')
                     ->order('a.sort asc')
                     ->where($where)
                     ->limit(5)->select();
             } else {
                 $limit_sales = [];
             }

             //热门单品
             $where = ['a.status' => 1];
             $new_goods = model('new_goods')
                 ->alias('a')
                 ->join('tb_goods b','a.goods_id = b.id','left')
                 ->field('new_goods_id,a.goods_id,goods_name, goods_logo, price, spec_price')
                 ->order('a.sort asc')
                 ->where($where)
                 ->limit(5)->select();

             //新品推荐
             $where = ['a.status' => 1];
             $popular = model('popular')
                 ->alias('a')
                 ->join('tb_goods b','a.goods_id = b.id','left')
                 ->field('popular_id,a.goods_id,goods_name, goods_logo, price, spec_price')
                 ->order('a.sort asc')
                 ->where($where)
                 ->limit(5)->select();*/

            //全屋套餐
            $package_model = model('package');
            $list_row = request()->post('list_row', 8); //每页数据
            $page = request()->post('page', 1); //当前页
            $where = ['partner_id' => 0, 'is_display' => 1];
            $totalCount = $package_model->where($where)->count();
            $first_row = ($page - 1) * $list_row;
            $field = ['id', 'package_title','package_brief', 'package_logo', 'action_info', 'package_price'];
            $lists = $package_model->where($where)->field($field)->limit($first_row . ',' . $list_row)->order('sort desc')->select();
            foreach ($lists as $list_k => $list_val) {
                $id_arr = model('package_goods')->where(['package_id' => $list_val['id']])->column('goods_id');
                $goods_list = [];
               if ($id_arr) {
                   $goods_list = model('goods')->where(['id' => ['in', $id_arr]])->field('id,goods_logo,goods_name')->select();
                }
               /* $action_info = $this->get_package_info($list_val['id'], 0);
//                $lists[$list_k]['action_info'] = $action_info;
                foreach ($action_info as $k => $val) {
//                    foreach ($val as $good_k => $good_v) {
                        if (isset($val['goods_list'])) {
                            foreach ($val['goods_list'] as $goods_info_k => $goods_info_v) {
                                $goods_info = model('goods')->where(['id' => $goods_info_v['goods_id']])->field('id,goods_logo,goods_name')->find();
                                if ($goods_info) {
                                    $good_detail = $goods_info;
                                    $goods_list[] = $good_detail;
                                }
                            }
//                        }
                    }
                }*/
                $lists[$list_k]['action_info'] = a_array_unique($goods_list);
            }
            $pageCount = ceil($totalCount / $list_row);
            $package = [
                'list' => $lists ? $lists : [],
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
            ];

            //广告图
            /* $background = model('banner')
                 ->field('banner_name,banner_describe,banner_pic,link_type,link_url,goods_id')
                 ->where(['banner_cate_id' => BannerConstant::BANNER_TYPE_ABOUT])
                 ->order('sort desc')
                 ->find();*/


            //分类
            $goods_cate = model('goods_cate')->field('id,classname,logo_pic,pid')->where(['pid' => 0])->order('sort desc')->select();

            foreach ($goods_cate as $k => $v) {
                //分类商品
                $goods_list = model('goods')->where(['cate_id' => $v['id'], 'is_sale' => 1, 'is_audit' => 1])->field('id,goods_name,price,goods_logo')->order('sort desc')->limit(6)->select();
                $goods_cate[$k]['goods_list'] = $goods_list?$goods_list:[];
            }

            $data = [
//                'cate_list' => $cate_list,
                'banner' => $banner,
//                'limit_sales' => $limit_sales,
//                'limit_special' => $limit_special,
//                'new_goods' => $new_goods,
//                'popular' => $popular,
                'package' => $package,
                'goods_cate' => $goods_cate,
//                 'background' => $background,
                'popups_banner' => $popups_banner,
            ];
            $json_arr =  ["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
    }

    public function leftMenu()
    {
        //分类
        $goods_cate = model('goods_cate')->field('id,classname,logo_pic,pid')->where(['pid' => 0])->order('sort desc')->select();

        $page_list = model('package_style')->where(['status' => 1])->field('id, classname')->order('sort desc')->select();
        foreach ($page_list as $k => $v) {
            $page_list[$k]['link'] = '/Goods/package?style_id='.$v['id'];
        }
        $cate_list[] = [
            'name' => '全屋套餐',
            'link' => '/Goods/package',
            'data' => $page_list,
        ];
        foreach ($goods_cate as $k => $v) {
            //分类nav
            $data_cate = model('goods_cate')->where(['pid' => $v['id']])->field('id,classname')->select();
            foreach ($data_cate as $k1 => $v1) {
                $data_cate[$k1]['link'] ='/Goods/goodsList?kindId='.$v['id'].'&kindTwoId='.$v1['id'];
            }
            $cate_list[] = [
                'id' => $v['id'],
                'name' => $v['classname'],
                'link' => '/Goods/goodsList?kindId='.$v['id'],
                'data' => $data_cate,
            ];
        }
        $data = [
            'cate_list' => $cate_list,
        ];
        $json_arr =  ["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    public function uploadImg(){
        $base64_img = request()->post('img_str');
        $path = request()->post('path', 'headimg');
        //$img_path_arr = [];
        //$pic_show = [];
        $up_dir='./uploads/'.$path.'/'.date('Ymd').'/'; //存放在当前目录的upload文件夹下
        $json_arr = $this->uploadInfo($base64_img, $up_dir);
        if ($json_arr['status'] == 0) {
            ajaxReturn($json_arr);
        } else {
            $img_path = $json_arr['data']['pic'];
            if ($img_path && $this->user_id) {
                model('user')->save(['head_img' => $img_path], ['id' => $this->user_id]);
            }
            $json_arr =  ["status"=>1, "msg"=>'图片上传成功', 'data' => ['pic' => $img_path]];
            ajaxReturn($json_arr);
        }

    }

    public function uploadInfo($base64_img, $up_dir)
    {
        if(!file_exists($up_dir)){
            mkdir($up_dir,0777,true);
        }
        if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_img, $result)){
            $type = $result[2];
            if(in_array($type,array('pjpeg','jpeg','jpg','gif','bmp','png'))){
                $new_file = $up_dir.date('YmdHis').'.'.$type;
                if(file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_img)))){
                    $img_path = substr(str_replace('../../..', '', $new_file), 1);
                    if (getSetting('alioss.is_oss') == 1) {
                        $img_path = oss_upload($img_path);
                    }
                    //$img_path_arr[] = $img_path;
                    //$pic_show[] = $this->PictureUrlDispose($img_path);
                    $json_arr =  ["status"=>1, "msg"=>'图片上传成功', 'data' => ['pic' => $img_path]];
                    return $json_arr;
                }else{
                    $json_arr =  ["status"=>0, "msg"=>'图片上传失败', 'data' => []];
                    return $json_arr;
                }
            }else{
                //文件类型错误
                $json_arr =  ["status"=>0, "msg"=>'图片上传类型错误', 'data' => []];
                return $json_arr;
            }
        }else{
            //文件错误
            $json_arr =  ["status"=>0, "msg"=>'文件错误', 'data' => []];
            return $json_arr;
        }
    }


    /*
     * 获取地区
     */
    public function getRegion(){
        $parent_id = request()->post('parent_id');
        if ($parent_id != '') {
            $where = ["parent_id" => $parent_id];
        } else {
            $where = [];
        }
        $data = db('region')->where($where)->select();
        $json_arr =  ["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        echo json_encode($json_arr); die;
//        ajaxReturn($json_arr);
    }

    public function isActivity()
    {
        $list = getSetting('activity');
        $list['logo'] = $list['logo'];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]]);
    }

    public function tradeContract()
    {
        $trade_contract = model('content')->field('title, content')->where(['class_id' => ContentConstant::CONTENT_SYSTEM_TRADE_CONTRACT])->find();
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $trade_contract]]);
    }

    /**
     * 获取配置参数
     */
    public function setting()
    {
        $name = request()->post('name');
        $value = request()->post('value');
        if ($name) {
            if ($value) {
                $list = getSetting($name.'.'.$value);
            } else {
                $list = getSetting($name);
            }
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
    }

    /**
     * 获取小程序二维码
     */
    public function shareQr()
    {
        $path = request()->post('path');
//        $path = '/pages/DIY/DIY?id='.$planid;
        if (!$path) {
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
        }
        $save_prefix = request()->post('save_prefix', 'diy');
        $width = request()->post('width', 430);
        $save_dir = "/uploads/share/".$save_prefix.'/';
        if (!file_exists('.'.$save_dir)) {
            mkdir('.'.$save_dir, 777, true);
        }
        $filename = substr($path,strripos($path,"?")+1)?substr($path,strripos($path,"?")+1):base64_encode($path);
        $savePath = $save_dir.$filename.".jpg";
        $data = ['img' => picture_url_dispose($savePath)];
        if (file_exists('.'.$savePath)) {
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data]);
        }
        $res = $this->qrcode('.'.$savePath, $path, $width);
        if ($res['status'] == 1) {
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data]);
        } else {
            ajaxReturn($res);
        }
    }

    public function gicBanner()
    {
        $banner1 = model('banner')
            ->field('banner_name,banner_describe,banner_pic,phone_pic,show_type,link_type,link_url,goods_id')
            ->where(['banner_cate_id' => 11, 'is_display' => 1])
            ->order('sort desc')
            ->find();
        $data = [
//                'cate_list' => $cate_list,
            'banner1' => $banner1['phone_pic'],
            'banner2' => $banner1['phone_pic'],
            'banner3' => $banner1['phone_pic'],
        ];
        $json_arr =  ["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }


    public function appointmentHall()
    {
        $province_id = request()->post('province_id');
        $city_id = request()->post('city_id');
        if (!$province_id) {
            ajaxReturn(['status' => 0, 'msg' => '请选择省']);
        }
       /* if (!$city_id) {
            ajaxReturn(['status' => 0, 'msg' => '请选择市']);
        }*/
        $estate_name = request()->post('estate_name');
        if (!$estate_name) {
            ajaxReturn(['status' => 0, 'msg' => '请填写小区']);
        }
        $telephone = request()->post('telephone');
        if (!$telephone) {
            ajaxReturn(['status' => 0, 'msg' => '请填写手机号']);
        }
        if (!$this->VerifyTelephone($telephone)) {
            ajaxReturn(['status' => 0, 'msg' => '手机号格式不正确']);
        }
        $username = request()->post('username');
        if (!$username) {
            ajaxReturn(['status' => 0, 'msg' => '请填写姓名']);
        }
        $appointment_time = request()->post('appointment_time');
        if (!$appointment_time) {
            //ajaxReturn(['status' => 0, 'msg' => '请填写预约时间']);
        }
        $data = [
            'estate_name' => $estate_name,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'telephone' => $telephone,
            'username' => $username,
            'appointment_time' => $appointment_time,
        ];
        $res = model('appointment_hall')->save($data);
        if ($res) {
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        }
    }
}