<?php


namespace app\api\controller;
use app\common\constant\BannerConstant;
use app\common\constant\CartConstant;
use app\common\constant\PreferentialConstant;
use app\common\constant\SystemConstant;
use app\common\constant\UserConstant;
use app\common\helper\CartHelper;
use app\common\helper\GoodsHelper;
use app\common\helper\UserHelper;
use app\common\helper\PreferentialHelper;;

class Meal extends Base
{
    use UserHelper;
    use PreferentialHelper;
    use GoodsHelper;
    use CartHelper;

    public function __construct()
    {
        parent::__construct();
    }

    public function packageCate()
    {
        if (request()->isPost()) {

            /* $banner = model('banner')
                 ->field('banner_name,banner_describe,banner_pic,link_type,link_url,goods_id')
                 ->where(['banner_cate_id' => BannerConstant::BANNER_TYPE_PACKAGE])
                 ->order('sort desc')
                 ->find();*/
            $packageStyle = model('package_style')->field('id,classname')->where(['pid' => 0, 'status' => 1])->order('sort desc')->select();



            $estate = model('package_estate')->field('id,classname')->where(['pid' => 0, 'status' => 1])->order('sort desc')->select();
            $data = [
//                'banner' => $banner,
                'packageStyle' => $packageStyle,
                'estate' => $estate,
            ];
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
    }

    public function packageList()
    {
        if (request()->isPost()) {
            $package_model = model('package');
            $list_row = request()->post('list_row', 10); //每页数据
            $page = request()->post('page', 1); //当前页
            $style_id = request()->post('style_id'); //当前页
            $estate_id = request()->post('estate_id'); //当前页

            $where = ['partner_id' => 0, 'is_display' => 1];

            if ($style_id) {
                $where['style_id'] = $style_id;
            }
            if ($estate_id) {
                $where['estate_id'] = $estate_id;
            }

            $totalCount = $package_model->where($where)->count();
            $first_row = ($page-1)*$list_row;

            $field = ['id','package_title','package_brief','package_des','package_logo','package_price', 'vr_link'];
            $lists = $package_model->where($where)->field($field)->limit($first_row, $list_row)->order('sort desc')->select();

            $pageCount = ceil($totalCount/$list_row);

            $data = [
                'list' => $lists ? $lists : [],
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
    }

    public function packageDetail()
    {
        if (request()->isPost()) {

            $package_model = model('package');
            $package_id = request()->post('package_id', 0, 'intval'); //每页数据

            if (!$package_id) {
                $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
                ajaxReturn($json_arr);
            }
            $where = ['id' => $package_id];
            $field = ['id','package_title','package_brief','package_des','package_logo','package_price','min_price', 'vr_link','package_des','package_banner','design_id'];
            $lists = $package_model->where($where)->field($field)->order('sort desc')->find();

            if (!$lists) {
                $json_arr = ['status' => 0, 'msg' => '该套餐不存在', 'data' => []];
                ajaxReturn($json_arr);
            }

            $package_cate = model('package_cate')->field('id,classname,logo_pic,pid')->where(['pid' => 0, 'is_del'=>'0','status'=>'1'])->select();
            $action_info = $this->get_package_info($package_id);
//            $action_one = $lists['action_one'];
//            $action_info = $lists['action_info'];
//            unset($lists['action_info']);
//            unset($lists['action_one']);
            $lists['vr_link'] = str_replace('https://pano.kujiale.com', 'https://pano6.p.kujiale.com', $lists['vr_link']);

            $data = [
                'list' => $lists,
                'action_info' => $action_info,
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
    }


    public function addCart()
    {
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }
        $post_data = request()->post();
        $package_model = model('package');
        $package_id = request()->post('package_id', 0, 'intval'); //每页数据

        if (!$package_id) {
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
            ajaxReturn($json_arr);
        }
        $where = ['id' => $package_id];
        $field = ['id','package_title','package_brief','package_des','package_logo','package_price', 'vr_link','action_info','action_one','package_des','package_banner'];
        $lists = $package_model->where($where)->field($field)->order('sort desc')->find();

        if (!$lists) {
            $json_arr = ['status' => 0, 'msg' => '该套餐不存在', 'data' => []];
            ajaxReturn($json_arr);
        }

        $action_active = $post_data['action_active'];

        $action_info = $this->get_package_info($package_id);

        $package_list = [];
        foreach ($action_info as $k => $v) {
            foreach ($v['goods_list'] as $kk => $vv) {
                if ($action_active[$k] == $kk) {
                    $package_list[] = $vv;
                }
            }
        }

        if ($package_list) {
            $where = [
                'user_id' => $this->user_id,
                'cart_type' => CartConstant::CART_TYPE_PACKAGE_NEW,
            ];
            model('Cart')->where($where)->delete(); // 查找购物车是否已经存在该商品
            foreach ($package_list as $k => $v) {
                $goods_price = ['price' => $v['preferential_price'], 'oprice' => $v['price']];
                $result = $this->addCartHandlePackage(
                    $this->user_id,
                    $v['goods_id'],
                    $v['goods_num'],
                    $v['sku_id']?$v['sku_id']:0,
                    CartConstant::CART_TYPE_PACKAGE_NEW,
                    $goods_price,
                    $package_id,
                    0
                );
                if ($result['status'] == 0) {
                    $return_arr = ['status' => 0, 'msg' => $result['msg'], 'data'=> []]; // 返回结果状态
                    ajaxReturn($return_arr);
                }
            }
            $return_arr = ['status' => 1, 'msg' => '成功加入购物车', 'data'=> []]; // 返回结果状态
            ajaxReturn($return_arr);
        } else {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data'=> []]; // 返回结果状态
            ajaxReturn($return_arr);
        }

    }


    /**
     * 获得全屋套餐的商品信息
     * @param $package_id
     */
    private function get_package_info($package_id)
    {
        $action_info = model('package_scene')->where(['package_id' => $package_id])->field('package_id,scene_pic,scene_name,sort')->order('sort asc')->select();
        foreach ($action_info as $k => $v) {
            $v['scene_pic']?$action_info[$k]['scene_pic'] = picture_url_dispose($v['scene_pic']):false;
            $meal_goods = model('package_goods')->where(['package_id' => $package_id, 'key' => $v['sort']])->select();
            $total_num = 0;
            $num = 0;
            foreach ($meal_goods as $k1 => $v1) {
                $goods = model('goods')->where(['id' => $v1['goods_id']])->find();
                if ($goods) {
                    $str = "";
                    if ($v1['sku_id']) {
                        $result = model('SpecGoodsPrice')->where(["key" => $v1['sku_id'], 'goods_id' => $v1['goods_id']])->find();
                        $str = $result['key_name'] . ' ';
                        $prom = $this->get_goods_promotion($result['prom_type'], $result['prom_id'], $goods['id'], $result['key']);
                        $goods['price'] = $result['price'];
                        if ($prom['price']) {
                            $goods['spec_price'] = $prom['price'];
                        } else {
                            $goods['spec_price'] = $result['price'];
                        }
                    } else {
                        $prom = $this->get_goods_promotion($goods['prom_type'], $goods['prom_id'], $goods['id']);
                        if ($prom['price']) {
                            $goods['spec_price'] = $prom['price'];
                        } else {
                            $goods['spec_price'] = $goods['price'];
                        }
                    }
                    $meal_goods[$k1]['goods_name'] = $goods['goods_name'];
                    $meal_goods[$k1]['goods_logo'] = $goods['goods_logo'];
                    $meal_goods[$k1]['goods_code'] = $goods['goods_code'];
                    $meal_goods[$k1]['price'] = $goods['spec_price'];
                    $meal_goods[$k1]['sku_info'] = $str;
                    $total_num += $v1['goods_num'];
                    $num ++;
                } else {
                    unset($meal_goods[$k1]);
                }
            }
            $meal_goods = a_array_unique($meal_goods);
            $action_info[$k]['goods_list'] = $meal_goods;
            $action_info[$k]['num'] = $num;
            $action_info[$k]['total_num'] = $total_num;
        }
        return $action_info;
    }

}