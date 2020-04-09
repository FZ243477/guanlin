<?php


namespace app\api\controller;
use app\common\helper\TokenHelper;
use app\common\constant\SystemConstant;
use app\common\constant\UserConstant;
use app\common\helper\GoodsHelper;
use app\common\helper\UserHelper;
use app\common\helper\PreferentialHelper;;

class Goods extends Base
{
    use UserHelper;
    use PreferentialHelper;
    use GoodsHelper;
    use TokenHelper;

    public function __construct()
    {
        parent::__construct();
    }

    public function goodsCate()
    {
        if (request()->isPost()) {
            
            $goodsCate = model('goods_cate')->field('id,classname')->where(['pid' => 0, 'status' => 1])->order('sort desc')->select();
            foreach ($goodsCate as $k => $v) {
                $goodsCate[$k]['cate_list'] = model('goods_cate')->field('id,classname')->where(['pid' => $v['id'], 'status' => 1])->order('sort desc')->select();
            }
            $goodsBrand = model('goods_brand')->field('id,classname')->where(['pid' => 0, 'status' => 1])->order('sort desc')->select();
            $goodsPrice = model('goods_price')->field('id,classname')->where(['status' => 1])->order('sort desc')->select();

            //限时特惠
            $where = ['status' => 1,'start_time' => ['lt', time()], 'end_time' => ['gt', time()], 'partner_id' => 0];
            $limit_special = model('limit_special')->where($where)->field('special_id,end_time,start_time')->order('status desc, sort desc')->find();
            if ($limit_special) {

                /*$limit_special['status'] = 0;
                if ($limit_special['start_time'] < time() && $limit_special['end_time'] > time()) {
                    $limit_special['status'] = 1;
                }
                if ($limit_special['start_time'] < time() && $limit_special['end_time'] < time()) {
                    $limit_special['status'] = 0;
                }
                if ($limit_special['start_time'] > time() && $limit_special['end_time'] > time()) {
                    $limit_special['status'] = 2;
                }*/
                $limit_special['start_time'] = date("m月d日", $limit_special['start_time']);
                $limit_special['end_time'] = date("Y-m-d H:i:s", $limit_special['end_time']);
                $where = ['a.status' => 1, 'b.is_sale' => 1, 'b.is_audit' => 1, 'special_id' => $limit_special['special_id']];

                $limit_sales = model('limit_sales')
                    ->alias('a')
                    ->join('tb_goods b','a.goods_id = b.id','left')
                    ->field('limit_sales_id,a.sales_num,a.max_buy_num,a.goods_id,a.sku_id,goods_name, goods_desc, goods_logo, price, spec_price')
                    ->order('a.sort desc')
                    ->group('goods_id')
                    ->where($where)
                    ->limit(5)->select();
                foreach ($limit_sales as $k => $v) {
                    $price = model('SpecGoodsPrice')->where(["key" => $v['sku_id'], 'goods_id' => $v['goods_id']])->value('price');
                    if ($price) {
                        $lists[$k]['price'] = $price;
                    }
                    $limit_sales[$k]['goods_logo'] = picture_url_dispose($v['goods_logo']);
                    $limit_sales[$k]['percent'] = round($v['sales_num']/$v['max_buy_num']*100);
                    unset($limit_sales[$k]['sales_num']);
                    unset($limit_sales[$k]['max_buy_num']);
                }
            } else {
                $limit_special['special_id'] = 0;
                $limit_special['end_time'] = 0;
                $limit_sales = [];
            }

            //热门单品
            $where = ['a.status' => 1, 'b.is_sale' => 1, 'b.is_audit' => 1, 'a.partner_id' => 0];
            $new_goods = model('new_goods')
                ->alias('a')
                ->join('tb_goods b','a.goods_id = b.id','left')
                ->field('new_goods_id,a.goods_id,a.sku_id,goods_name, goods_logo, price, spec_price')
                ->order('a.sort desc')
                ->where($where)
                ->limit(9)->select();
            foreach ($new_goods as $k => $v) {
                $price = model('SpecGoodsPrice')->where(["key" => $v['sku_id'], 'goods_id' => $v['goods_id']])->value('price');
                if ($price) {
                    $new_goods[$k]['price'] = $price;
                }
				$new_goods[$k]['goods_logo'] = $v['goods_logo'].'?x-oss-process=image/resize,m_fill,h_200,w_200';
            }
            //新品推荐
            $where = ['a.status' => 1, 'b.is_sale' => 1, 'b.is_audit' => 1, 'a.partner_id' => 0];
            $popular = model('popular')
                ->alias('a')
                ->join('tb_goods b','a.goods_id = b.id','left')
                ->field('popular_id,a.goods_id,a.sku_id,goods_name, goods_logo, price, spec_price')
                ->order('a.sort desc')
                ->where($where)
                ->limit(5)->select();
            foreach ($popular as $k => $v) {
                $price = model('SpecGoodsPrice')->where(["key" => $v['sku_id'], 'goods_id' => $v['goods_id']])->value('price');
                if ($price) {
                    $popular[$k]['price'] = $price;
                }
				$popular[$k]['goods_logo'] = $v['goods_logo'].'?x-oss-process=image/resize,m_fill,h_200,w_200';
            }
            $data = [
               
                'goodsCate' => $goodsCate,
                'goodsBrand' => $goodsBrand,
                'goodsPrice' => $goodsPrice,
                'limit_sales' => $limit_sales,
                'limit_special' => $limit_special,
                'new_goods' => $new_goods,
                'popular' => $popular,
            ];
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
    }

    public function goodsList()
    {
        if (request()->isPost()) {
            $goods_model = model('goods');
            $list_row = request()->post('list_row', 20); //每页数据
            $page = request()->post('page', 1); //当前页
            $cate_id = request()->post('cate_id'); //分类ID
            $brand_id = request()->post('brand_id'); //品牌ID
            $price_id = request()->post('price_id'); //价格ID
            $keyword = request()->post('keyword'); //搜索条件
            $sorts = request()->post('sorts', 0); //排序

            $where = ['is_sale' => 1,'is_audit' => 1];

            if ($cate_id) {
                $where['cate_id|cate_two_id'] = $cate_id;
            }
            if ($keyword) {
                $where['goods_name|goods_desc|goods_keywords|goods_code'] = ['like', '%'.$keyword.'%'];
            }

            if ($brand_id) {
                $where['brand_id'] = $brand_id;
            }
            /*if ($price_id) {
                $price_arr = model('goods_price')->where(['id' => $price_id])->field('price_left,price_right')->find();
                if ($price_arr) {
                    if ($price_arr['price_right'] == 0) {
                        $where['price'] = ['gt', $price_arr['price_left']];
                    } else if ($price_arr['price_left'] == 0) {
                        $where['price'] = ['lt', $price_arr['price_right']];
                    } else {
                        $where['price'] = ['between', [$price_arr['price_left'], $price_arr['price_right']]];
                    }

                }
            }*/

            if($sorts == 1){
                $order = 'sale_time desc';
            }elseif($sorts == 2){
                $order = 'sale_time asc';
            }elseif($sorts == 3){
                $order = 'price desc';

            }elseif($sorts == 4){
                $order = 'price asc';
            }else if($sorts == 5){
                $order = 'sales desc';
            }else if($sorts == 6){
                $order = 'sales asc';
            } else {
                $order = 'sort desc, add_time desc';
            }


            $first_row = ($page-1)*$list_row;

            $field = ['id','goods_name','goods_logo','price', 'prom_type', 'prom_id', 'is_sku'];
            if ($sorts == 3 || $sorts == 4 || $price_id) {
                $list = $goods_model->where($where)->field($field)->order($order)->select();
            } else {
                $list = $goods_model->where($where)->field($field)->limit($first_row.','.$list_row)->order($order)->select();
            }

            $lists = [];
            foreach ($list as $key => $val) {
                $result = $this->get_goods_list_price($val['is_sku'], $val['id'], $val['prom_type'], $val['prom_id']);
                $list[$key]['spec_price'] = $result['spec_price'];
                if ($result['price']) {
                    $list[$key]['price'] = $result['price'];
                }
                if ($list[$key]['spec_price']) {
                    $price = $list[$key]['spec_price'];
                } else {
                    $price = $list[$key]['price'];
                }
                unset($list[$key]['prom_type']);
                unset($list[$key]['prom_id']);
                unset($list[$key]['is_sku']);
                if ($price_id) {
                    $price_arr = model('goods_price')->where(['id' => $price_id])->field('price_left,price_right')->find();
                    if ($price_arr['price_right'] == 0) {
                        if ($price > $price_arr['price_left']) {
                            $lists[] = $list[$key];
                        }
                    } else {
                        if ($price > $price_arr['price_left']
                            && $price < $price_arr['price_right']
                        ) {
                            $lists[] = $list[$key];
                        }
                    }

                } else {
                    $lists[] = $list[$key];
                }
            }

            if ($price_id) {
                $totalCount = count($lists);
            } else {
                $totalCount = $goods_model->where($where)->count();
            }
            if ($sorts == 3 ) {
                $last_names = array_column($lists,'price');
                array_multisort($last_names,SORT_DESC, $lists);
            }
            if ($sorts == 4 ) {
                $last_names = array_column($lists,'price');
                array_multisort($last_names,SORT_ASC, $lists);
            }
            if ($sorts == 3 || $sorts == 4 || $price_id) {
                $lists = array_slice($lists, $first_row, $list_row);
            }
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

    public function goodsDetail()
    {
        if (request()->isPost()) {

            $goods_model = model('goods');
            $goods_id = request()->post('goods_id', 0, 'intval'); //每页数据

            if (!$goods_id) {
                $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
                ajaxReturn($json_arr);
            }

            $where = [ 'id' => $goods_id];
            $field = ['id','goods_name','cate_id','is_audit','price','is_sale','goods_desc','service','goods_logo','is_sku','stores','goods_big_banner','product','goods_param','goods_detail_pic','prom_type','prom_id'];
            $lists = $goods_model->where($where)->field($field)->order('sort desc')->find();

            if (!$lists) {
                $json_arr = ['status' => 0, 'msg' => '该商品不存在或已下架', 'data' => []];
                ajaxReturn($json_arr);
            }
            $sku_data = [];
            $spec_key = 0;
			$sku_data = $this->get_spec_list($goods_id);
            if ($lists['is_sku'] && $sku_data) {
               
                foreach($sku_data as $k => $v){
                    $prom = $this->get_goods_promotion($v['prom_type'], $v['prom_id'], $lists['id'], $v['spec_key']);
                    if ($v['price']) {
                        $price = $prom['price'];
                    } else {
                        $price = $v['price'];
                    }
                    $sku_data[$k] = $v;
                    $sku_data[$k]['spec_price'] = $price;
                    $sku_data[$k]['prom_id'] = $prom['prom_id'];
                    $sku_data[$k]['prom_type'] = $prom['prom_type'];
                    if ($prom['stores']) {
                        $sku_data[$k]['stores'] = $v['stores'] > $prom['stores']?$prom['stores']:$v['stores'];
                    }
                }
                //$sku_data = deal_sku_data($sku_data);

                /*查出数据*/
                $defalut_goods_info = current($sku_data);
                if($defalut_goods_info) {
                    $lists['spec_price'] = $defalut_goods_info['spec_price'];
                    $lists['stores'] = $defalut_goods_info['stores'];
                    $lists['price'] = $defalut_goods_info['price'];
                    $lists['goods_code'] = $defalut_goods_info['goods_code'];
                    $lists['prom_id'] = $defalut_goods_info['prom_id'];
                    $lists['prom_type'] = $defalut_goods_info['prom_type'];
                    $spec_key = $defalut_goods_info['spec_key'];
                }
            } else {
                $prom = $this->get_goods_promotion($lists['prom_type'], $lists['prom_id'], $lists['id']);
                if ($prom['price']) {
                    $lists['spec_price'] = $prom['price'];
                    $lists['start_time'] = $prom['start_time'];
                    $lists['end_time'] = $prom['end_time'];
                }
                if ($prom['stores']) {
                    $lists['stores'] = $lists['stores'] > $prom['stores']?$prom['stores']:$lists['stores'];
                }
            }
			/*if (isset($lists['spec_price']) && $lists['spec_price'] ) {
				$lists['oprice'] = $lists['price'];
				$lists['price'] = $lists['spec_price'];
			}*/

            if ($this->user_id) {
                $collect = model('collection')->where(['goods_id' => $goods_id, 'user_id' => $this->user_id])->find();
                if ($collect) {
                    $is_collect = $collect['status'];
                } else {
                    $is_collect = 0;
                }
            } else {
                $is_collect = 0;
            }
            $lists['is_collect'] = $is_collect;

            $similar_data = [];
            $similar_data['is_sale'] = 1;
            if (isset($lists['cate_id'])) {
                $similar_data['cate_id'] = $lists['cate_id'];
                unset($lists['cate_id']);
            }
            $similar_data['id'] = array('neq',$goods_id);

           /* $package = model('package')->field('id,action_info')->select();
            $package_id = [];
            foreach ($package as $goodsInfo) {
                $action_info  = json_decode($goodsInfo['action_info'], true);
                foreach ($action_info as $goodsList) {
                    foreach ($goodsList as $goods) {
                        if (isset($goods['goods_list'])) {
                            foreach ($goods['goods_list'] as $k => $v) {
                                if ($v['goods_id'] == $goods_id) {
                                    $package_id[] = $goodsInfo['id'];
                                }
                            }
                        }
                    }
                }
            }*/
            $package_id =  model('package_goods')->where(['goods_id' => $goods_id])->column('package_id');
            if ($package_id) {
                $package_id = array_unique($package_id);
                $goods_like = model('package')->field('id,package_logo,package_title,package_brief,package_price')->where(['id' => ['in', $package_id]])->select();
            } else {
                $goods_like = [];
            }


            /*$goods_like = $goods_model
                ->where($similar_data)
                ->order('id desc')
                ->limit(4)
                ->field('id,goods_name,goods_desc,price ,goods_logo,prom_type,prom_id')
                ->select();
            foreach ($goods_like as $k => $v) {
                $prom = $this->get_goods_promotion($v['prom_type'], $v['prom_id'], $v['id']);
                if ($prom['price']) {
                    $goods_like[$k]['price'] = $prom['price'] ;
                }
            }*/
            $filter_spec = $this->get_spec($goods_id);
            $commentStatistics = $this->commentStatistics($goods_id);
            $data = [
                'list' => $lists,
                'spec_key' => $spec_key,
                'sku_data' => $sku_data,
                'filter_spec' => $filter_spec,
                'goods_like' => $goods_like,
                'commentStatistics' => $commentStatistics
            ];


            if ($lists['is_sale'] != 1 || $lists['is_audit'] != 1) {
                $json_arr = ['status' => 0, 'msg' => '该商品不存在或已下架', 'data' => $data];
                ajaxReturn($json_arr);
            }
            unset($lists['is_sale']);
            unset($lists['is_audit']);
            $share_token = request()->post('share_token');
            $share_id = $this->getUserInfoByToken($share_token);
 
            if ($this->user_id) {
                $this->footprintAdd($this->user_id, $goods_id);
            }
            $this->add_access($this->user_id, UserConstant::USER_ACCESS_GOODS_DETAIL,UserConstant::REG_SOURCE_PC, 0, $goods_id);

            if ($share_id) {
                if (!$this->user_id) {
                    $json_arr = ['status' => -1, 'msg' => '请登录', 'data' => $data];
                    ajaxReturn($json_arr);
                } else {
                    $first_leader = model('user')->where('id', $this->user_id)->value('first_leader');
                    if (!$first_leader) {
                        $data = [];
                        $data['first_leader'] = $share_id;
                        $first_leader = model('user')->where("id", $share_id)->find();
                        $data['second_leader'] = $first_leader['first_leader'];
                        $data['third_leader'] = $first_leader['second_leader'];
                        model('user')->save($data, ['id' => $this->user_id]);
                        model('user')->where(['id' => $data['first_leader']])->setInc('underling_number');
                        model('user')->where(['id' => $data['second_leader']])->setInc('underling_number');
                        model('user')->where(['id' => $data['second_leader']])->setInc('underling_number');
                    }
                }
            }
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
    }

    public function goodsIsSale()
    {
        $province_id = request()->post('province_id');
        $city_id = request()->post('province_id');
        $goods_id = request()->post('goods_id');
        if (!$goods_id) {
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $brand_id = model('goods')->where(['id' => $goods_id])->value('brand_id');
        $brand = model('goods_brand')->where(['id' => $brand_id])->field('province_id,city_id')->find();
        $is_buy = 1;
        if ($province_id && $city_id) {
            if ($brand) {
                if ($brand['province_id']) {
                    $province_id = explode(',', $brand['province_id']);
                    if (!in_array($province_id, $province_id)) {
                        $is_buy = 0;
                    }
                }
                if ($brand['city_id']) {
                    $brand['city_id'] = explode(',', $brand['city_id']);
                    if (!in_array($city_id, $brand['city_id'])) {
                        $is_buy = 0;
                    }
                }
            }
        }
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['is_buy' => $is_buy]]);
    }

    /**
     * 获取某个商品的评论统计
     * c0:全部评论数  c1:好评数 c2:中评数  c3差评数
     * rate1:好评率 rate2:中评率  c3差评率
     * @param $goods_id
     * @return array
     */
    public function commentStatistics($goods_id)
    {
        $commentWhere = ['is_show'=>1,'goods_id'=>$goods_id,'status'=>1];
        $c1 = model('goods_comment')->where($commentWhere)->where('desc_star in (4,5)')->count();
        $c2 = model('goods_comment')->where($commentWhere)->where('desc_star in (3)')->count();
        $c3 = model('goods_comment')->where($commentWhere)->where('desc_star in (1,2)')->count();
        $c4 = model('goods_comment')->where($commentWhere)->where("slide_img !='' and slide_img NOT LIKE 'N;%'")->count(); // 晒图
        $c0 = $c1 + $c2 + $c3; // 所有评论
        /*if($c0 <= 0){
            $rate1 = 100;
            $rate2 = 0;
            $rate3 = 0;
        }else{
            $rate1 = ceil($c1 / $c0 * 100); // 好评率
            $rate2 = ceil($c2 / $c0 * 100); // 中评率
            $rate3 = ceil($c3 / $c0 * 100); // 差评率
        }*/
        return array('rate0' => $c0, 'rate1' => $c1, 'rate2' => $c2, 'rate3' => $c3, 'c4'=>$c4);
    }

    public function goodsComment()
    {
        $is_pic = request()->post('is_pic', 0);
        $rate = request()->post('rate', 0);
        $goods_id = request()->post('goods_id');
        $list_row = request()->post('list_row', 10); //每页数据
        $page = request()->post('page', 1); //当前页

        $comment_data = [];

        $comment_data['status']   = 1;
        $comment_data['is_show']  = 1;

        if ($rate == 1) {
            $comment_data['desc_star']  = ['in', [4,5]];
        } elseif ($rate == 2) {
            $comment_data['desc_star']  = ['in', [3]];
        } elseif ($rate == 3) {
            $comment_data['desc_star']  = ['in', [1,2]];
        }

        if (!$goods_id) {
            $comment_data['user_id'] = $this->user_id;
            //$json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
            // ajaxReturn($json_arr);
        } else {
            $comment_data['goods_id'] = $goods_id;
        }

        if($is_pic == 1){
            $comment_data['slide_img'] = ['neq',''];
        }

        $totalCount = model('goods_comment')->where($comment_data)->count();

        $pageCount = ceil( $totalCount/$list_row);
        $first_row = ($page-1)*$list_row;
        // 评价列表
        $info = [];
        $info['goods_id'] = $goods_id;
        $comment_lists = model('goods_comment')
            ->where($comment_data)
            ->order('id desc')
            ->limit($first_row, $list_row)
            ->select();
        $comment_list = [];
        foreach ($comment_lists as $k => $v) {
            if($v['is_virtual'] == 0){
                $comment_list[$k]['user_id'] = $v['user_id'];
                $user = model('user')->where(['id' =>$v['user_id']])->find();
                if($v['is_name'] == 0){
                    $comment_list[$k]['user_name'] = $user['nickname'];
                }else{
                    $comment_list[$k]['user_name'] = '匿名用户';
                }
                $comment_list[$k]['user_logo'] = $user['head_img'];

                //下面的值取自订单商品表
                $order_goods = model('order_goods')->where(['id' =>$v['order_goods_id']])->find();
                if(!$order_goods){
                    $comment_list[$k]['goods_num'] = 1;
                }else{
                    $comment_list[$k]['goods_num'] = $order_goods['goods_num'];
                }
                $comment_list[$k]['goods_name'] = $order_goods['goods_name'];
                $comment_list[$k]['goods_logo'] = $order_goods['goods_pic'];
                $comment_list[$k]['goods_id'] = $order_goods['goods_id'];
                $comment_list[$k]['spec_key'] = explode(' ', $order_goods['sku_info']) ;
            }else{
                //虚拟评论
                $comment_list[$k]['user_id'] = 0;
                $comment_list[$k]['user_name'] = $v['virtual_name'];
                $comment_list[$k]['user_logo'] = $v['virtual_logo'];
                $comment_list[$k]['goods_num'] = $v['virtual_num'];
                $comment_list[$k]['spec_key'] = $v['spec_key'];
            }

            $comment_list[$k]['content'] = $v['content'];

            $comment_list[$k]['add_time'] = date('Y年m月d日 H:i', strtotime($v['add_time']));
            $comment_list[$k]['desc_star'] = $v['desc_star'];
            $comment_list[$k]['pic_list'] = $v['slide_img'];
        }


        $comment_data['slide_img'] = array('neq','');
        $comment_pic_nums = model('goods_comment')->where($comment_data)->count();

        unset($comment_data['is_pic']);
        // 评论标签列表
        /* $label_data = [];
         $label_data['status'] = 1;
         $label_data['is_del'] = 0;

         $label_list = model('goods_comment_label')->where($label_data)->order('sort desc')->select();

         if($label_list){
             $comment_label_list = [];
             foreach ($label_list as $k => $v) {
                 $comment_datasss = [];
                 $comment_datasss['goods_id'] = $goods_id;
                 $comment_datasss['status']   = 1;
                 $comment_datasss['is_show']  = 1;
                 $comment_datasss['is_del']   = 0;
                 $comment_label_list[$k]['label_id'] = $v['id'];
                 $comment_label_list[$k]['label_name'] = $v['label_name'];
                 $label_str = $v['id'].',';
                 $comment_datasss['label'] = array('like',"%$label_str%");
                 $comment_label_nums = model('goods_comment')->where($comment_datasss)->count();
                 $comment_label_list[$k]['label_nums'] = $comment_label_nums?$comment_label_nums:0;
             }
         }else{
             $comment_label_list = [];
         }*/

        $comment_datas = [];
        $comment_datas['goods_id'] = $goods_id;
        $comment_datas['status']   = 1;
        $comment_datas['is_show']  = 1;
        $star = model('goods_comment')->where($comment_datas)->field('desc_star,quality_star')->select();

        if($star){
            $all_desc_star = 0;
            $all_quality_star = 0;
            foreach ($star as $k => $v) {
                $all_desc_star += $v['desc_star'];
                $all_quality_star += $v['quality_star'];
            }
            $all_amount = count($star);
            $desc_star = $all_amount?sprintf('%.2f',$all_desc_star/$all_amount):'5.00';
//            $quality_star = sprintf('%.2f',$all_quality_star/$all_amount);
        }else{
            $desc_star = '5.00';
//            $quality_star = '5.00';
        }

        /* $info['comment_nums']       = $all_nums?$all_nums:0; // 评论总数量
         $info['comment_pic_nums']   = $comment_pic_nums?$comment_pic_nums:0; // 评论总数量
         $info['comment_star']       = $goods_info['star_num'];  // 综合评论的星星数量
         $info['comment_percent']    = $goods_info['best_percent'];  // 综合评论的百分比
         $info['desc_star']          = $desc_star;   //描述相符的星星数量
         $info['quality_star']       = $quality_star;  //商品质量的星星数量
         $info['all_page']           = $all_pages;
         $info['page']               = $page;
         $info['comment_list']       = $comment_list;
         $info['comment_label_list'] = $comment_label_list;*/

        $data = [
            'list' => $comment_list ? $comment_list : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
            'desc_star' => $desc_star,
            'comment_pic_nums' => $comment_pic_nums ? $comment_pic_nums : 0,// 有图评论总数量
        ];
        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * @version   我的足记列表
     * @param     user_id      		 用户id
     * @param     page  页面（从1开始）
     * @return    status => 0  		 错误
     * @return    status => 1  		 成功
     * @return    status => 2  		 用户账号被冻结
     * @return    all_page 总页数
     * @return    page 当前页码
     * @return    list 我的足记列表数组
     */
    public function footprintList()
    {
        $list_row = request()->post('list_row', 10);
        $page = request()->post('page', 1);

        $map = [];
        $map['user_id'] = $this->user_id;
        $map['is_del'] = 0;

        $totalCount = model('footprint')->where($map)->count();
        $pageCount = ceil( $totalCount/$list_row);
        $first_row = ($page-1)*$list_row;
        $footprint_list = model('footprint')
            ->where($map)
            ->order('update_time desc')
            ->limit($first_row, $list_row)
            ->field('id as footprint_id,goods_id,update_time')
            ->select();
        if($footprint_list){
            foreach ($footprint_list as $k => $v) {
                $goods = model('goods')->where(['is_audit' => 1, 'is_sale' => 1, 'id' => $v['goods_id']])->find();
                if ($goods) {
                    $footprint_list[$k]['goods_name'] = $goods['goods_name'];
                    $footprint_list[$k]['goods_price']= $goods['price'];
                    $footprint_list[$k]['goods_logo'] = $goods['goods_logo'];
                    $result = $this->get_goods_list_price($goods['is_sku'], $goods['id'], $goods['prom_type'], $goods['prom_id']);
                    $footprint_list[$k]['spec_price'] = $result['spec_price'];
                    if ($result['price']) {
                        $footprint_list[$k]['goods_price'] = $result['price'];
                    }
                    unset($goods);
                } else {
                    unset($footprint_list[$k]);
                }
            }
//            $footprint_list = $this->groupVisit($footprint_list);
        } else {
            $field = 'goods_name, price, goods_logo, is_sku, id, prom_type, prom_id';
            $footprint_list =  model('goods')->where(['is_audit' => 1,'is_sale' => 1, 'is_del' => 0])->field($field)->order('sort desc')->limit(4)->select();
            foreach ($footprint_list as $k => $v) {
                $result = $this->get_goods_list_price($v['is_sku'], $v['id'], $v['prom_type'], $v['prom_id']);
                $footprint_list[$k]['spec_price'] = $result['spec_price'];
                if ($result['price']) {
                    $footprint_list[$k]['goods_price'] = $result['price'];
                } else {
                    $footprint_list[$k]['goods_price'] = $v['price'];
                }

            }
        }
        $data = [
            'list' => $footprint_list ? $footprint_list : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];
        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);

    }

    /* 浏览记录按日期分组 */
    private function groupVisit($visit){
        $curyear = date('Y');
        //今天
        $cur = date('Y-m-d');
        //昨天
        $cur_yes = date('Y-m-d', strtotime(date('Y-m-d'))-1);
        $visit_list = [];
        foreach ($visit as $v) {
            if ($cur == date('Y-m-d', strtotime($v['create_time']))) {
                $date = '今天';
            } else if ($cur_yes == date('Y-m-d', strtotime($v['create_time']))){
                $date = '昨天';
            } else if ($curyear == date('Y', strtotime($v['create_time']))) {

                $date = date('m月d日', strtotime($v['create_time']));
            } else {
                $date = date('Y年m月d日', strtotime($v['create_time']));
            }
            $visit_list[$date][] = $v;
        }
        return $visit_list;
    }


}