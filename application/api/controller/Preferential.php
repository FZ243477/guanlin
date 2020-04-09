<?php

namespace app\api\controller;

use app\common\helper\OriginalSqlHelper;
use app\common\helper\ManagerHelper;
use app\common\constant\SystemConstant;

class Preferential extends Base
{
    use OriginalSqlHelper;
    use ManagerHelper;

    public function _initialize()
    {
        parent::_initialize();
    }


    //场次
    public function limitSales()
    {
        $limit_special = model('limit_special');
        $list_row = request()->post('list_row', 10); //每页数据
        $page = request()->post('page', 1); //当前页
        $where = ['status' => 1, 'partner_id' => 0];
        $totalCount = $limit_special
            ->where($where)
            ->count();
        $first_row = ($page - 1) * $list_row;
        $lists = $limit_special
            ->where($where)
            ->field('special_id,special_title,special_describe,special_pic,start_time,end_time')
            ->limit($first_row, $list_row)
            ->order('status desc,start_time asc, sort desc')
            ->select()
        ;
        $pageCount = ceil($totalCount / $list_row);
        foreach ($lists as $k => $v) {
            $lists[$k]['status'] = 0;
            if ($v['start_time'] < time() && $v['end_time'] > time()) {
                $lists[$k]['status'] = 1;
            }
            if ($v['start_time'] < time() && $v['end_time'] < time()) {
                $lists[$k]['status'] = 0;
            }
            if ($v['start_time'] > time() && $v['end_time'] > time()) {
                $lists[$k]['status'] = 2;
            }
            $lists[$k]['start_time'] = date("m月d日", $v['start_time']);
            $lists[$k]['end_time'] = date("Y-m-d H:i:s", $v['end_time']);
        }

        $data = [
            'list' => $lists ? $lists : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];
        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    //场次下面的限时购商品
    public function limitSalesGoods()
    {
        $limit_sales_model = model('limit_sales');
        $list_row = request()->post('list_row', 100); //每页数据
        $page = request()->post('page', 1); //当前页
        $special_id = request()->post('special_id', 1); //场次id
        if (!$special_id) {
            $json_arr = ['status' => 0, 'msg' => "场次special_id必须", 'data' => []];
            ajaxReturn($json_arr);
        }
        $where = ['status' => 1];
        $where['special_id'] = $special_id;
        $totalCount = $limit_sales_model
            ->where($where)
            ->count();
        $first_row = ($page - 1) * $list_row;
        $field = ['limit_sales_id','b.id','a.sku_id', 'b.goods_name', 'b.goods_logo', 'b.price', 'a.spec_price', 'a.max_buy_num', 'a.sales_num', 'b.stores'];
        $lists = $limit_sales_model
            ->alias('a')
            ->join('tb_goods b', 'a.goods_id = b.id', 'left')
            ->where(['a.special_id' => $special_id, 'a.status' => 1, 'b.is_sale' => 1, 'is_audit' => 1])
            ->field($field)
            ->group('goods_id')
            ->limit($first_row, $list_row)
            ->order('a.sort desc')
            ->select()
        ;
        foreach ($lists as $k => $v) {
            $price = model('SpecGoodsPrice')->where(["key" => $v['sku_id'], 'goods_id' => $v['id']])->value('price');
            if ($price) {
                $lists[$k]['price'] = $price;
            }
            $stores = $v['max_buy_num'] - $v['sales_num'];
            $lists[$k]['stores'] = $v['stores']>$stores?$stores:$v['stores'];
			$lists[$k]['goods_logo'] = $v['goods_logo'].'?x-oss-process=image/resize,m_fill,h_200,w_200';
        }
        $pageCount = ceil($totalCount / $list_row);
        $data = [
            'list' => $lists ? $lists : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];
        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }


    //限时购
    public function limitedTimeBuy()
    {
        $limit_sales_model = model('limit_sales');
        $limit_special = model('limit_special');
        $where['status'] = 1;
        $data = $limit_special
            ->where($where)
            ->field('special_id,special_title,special_describe,special_pic,start_time,end_time')
            ->order('end_time desc')
            ->find()
        ;

        $field = ['limit_sales_id', 'b.goods_name', 'b.goods_logo', 'b.price', 'a.spec_price', 'a.max_buy_num', 'a.sales_num'];
        $goods_list = $limit_sales_model
            ->alias('a')
            ->join('tb_goods b', 'a.goods_id = b.id', 'left')
            ->field($field)
            ->order('a.sort desc')
            ->select()
        ;
		foreach ($goods_list as $k => $v) {
			$goods_list[$k]['goods_logo'] = $v['goods_logo'].'?x-oss-process=image/resize,m_fill,h_200,w_200';
		}
        $data['goods_list'] = $goods_list;
        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    public function newGoods()
    {
        $new_goods_model = model('new_goods');

        $list_row = request()->post('list_row', 100); //每页数据
        $page = request()->post('page', 1); //当前页


        $where = ['a.status' => 1, 'a.partner_id' => 0, 'b.is_sale' => 1, 'is_audit' => 1];

        $totalCount = $new_goods_model
            ->alias('a')
            ->join('tb_goods b', 'a.goods_id = b.id', 'left')
            ->where($where)
            ->count();

        $first_row = ($page - 1) * $list_row;
        $field = ['new_goods_id', 'a.goods_id','a.sku_id', 'b.goods_name', 'b.goods_logo', 'b.price', 'b.stores', 'b.sales', 'a.spec_price'];
        $lists = $new_goods_model
            ->where($where)
            ->alias('a')
            ->join('tb_goods b', 'a.goods_id = b.id', 'left')
            ->field($field)
            ->limit($first_row, $list_row)
            ->order('a.sort desc')
            ->select();

        foreach ($lists as $k => $v) {
            $price = model('SpecGoodsPrice')->where(["key" => $v['sku_id'], 'goods_id' => $v['goods_id']])->value('price');
            if ($price) {
                $lists[$k]['price'] = $price;
            }
            $lists[$k]['goods_logo'] = picture_url_dispose($v['goods_logo']);
            $lists[$k]['percent'] = round($v['sales'] / $v['stores'] * 100);
            unset($lists[$k]['sales']);
            unset($lists[$k]['stores']);
			$lists[$k]['goods_logo'] = $v['goods_logo'].'?x-oss-process=image/resize,m_fill,h_200,w_200';
        }

        $pageCount = ceil($totalCount / $list_row);

        $data = [
            'list' => $lists ? $lists : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    public function popular()
    {
        $popular_model = model('popular');

        $list_row = request()->post('list_row', 100); //每页数据
        $page = request()->post('page', 1); //当前页


        $where = ['a.status' => 1, 'a.partner_id' => 0, 'b.is_sale' => 1, 'is_audit' => 1];

        $totalCount = $popular_model
            ->alias('a')
            ->join('tb_goods b', 'a.goods_id = b.id', 'left')
            ->where($where)
            ->count();

        $first_row = ($page - 1) * $list_row;
        $field = ['popular_id', 'a.goods_id','a.sku_id', 'b.goods_name', 'b.goods_logo', 'b.price', 'b.stores', 'b.sales', 'a.spec_price'];
        $lists = $popular_model
            ->where($where)
            ->alias('a')
            ->join('tb_goods b', 'a.goods_id = b.id', 'left')
            ->field($field)
            ->limit($first_row, $list_row)
            ->order('a.sort desc')
            ->select();

        foreach ($lists as $k => $v) {
            $price = model('SpecGoodsPrice')->where(["key" => $v['sku_id'], 'goods_id' => $v['goods_id']])->value('price');
            if ($price) {
                $lists[$k]['price'] = $price;
            }
            $lists[$k]['goods_logo'] = picture_url_dispose($v['goods_logo']);
            $lists[$k]['percent'] = round($v['sales'] / $v['stores'] * 100);
            unset($lists[$k]['sales']);
            unset($lists[$k]['stores']);
			$lists[$k]['goods_logo'] = $v['goods_logo'].'?x-oss-process=image/resize,m_fill,h_200,w_200';
        }

        $pageCount = ceil($totalCount / $list_row);

        $data = [
            'list' => $lists ? $lists : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    //领取优惠劵列表
    public function couponList()
    {

        $coupon_model = model('coupon');
        $list_row = request()->post('list_row', 100); //每页数据
        $page = request()->post('page', 1); //当前页

        $map = ['partner_id' => 0];
        $map['isdel'] = 0;
        $map['status'] = 0;
        $map['coupon_receive'] = ['in', [1,3]];
        $map['star_receive_time'] = ['elt', time()];
        $map['end_receive_time'] = ['egt', time()];
        $map['starttime'] = ['lt', time()];
        $map['endtime'] = ['gt', time()];
        $totalCount = $coupon_model->where($map)->order('id desc')->count();
        $first_row = ($page - 1) * $list_row;

        $list = model('coupon')->where($map)->order('id desc')->limit($first_row, $list_row)->select();
        $user_id = $this->user_id;

        $coupon = [];
        $user = model('user')->where(['id' => $user_id])->find();
        foreach ($list as $k => $v) {
            if ($user_id) {
                if ($v['coupon_receive'] == 3 && strtotime($user['reg_time']) < $v['star_receive_time']) {
                    unset($list[$k]);
                    continue;
                } else {
                    $res = model('coupon_data')->field('coupon_id')->where(['user_id' => $user_id, 'coupon_id' => $v['id']])->find();
                    if ($res) {

                        $coupon[$k]['yh_status'] = 1;
                    } else {
                        $coupon[$k]['yh_status'] = 2;
                    }
                }

            } else {
                $coupon[$k]['yh_status'] = 2;
            }

            $coupon[$k]['id'] = $v['id'];
            $coupon[$k]['coupon_no'] = $v['coupon_no'];
            $coupon[$k]['title'] = $v['title'];
            $coupon[$k]['coupon_type'] = $v['coupon_type'];
            $coupon[$k]['deduct'] = $v['deduct'];
            $coupon[$k]['limit_money'] = $v['limit_money'];
            $coupon[$k]['starttime'] = date('Y-m-d H:i:s', $v['starttime']);
            $coupon[$k]['endtime'] = date('Y-m-d H:i:s', $v['endtime']);
        }

        $pageCount = ceil($totalCount / $list_row);

        $data = [
            'list' => $coupon ? $coupon : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);

    }


    /*public function limitSalesDetail()
    {
        $limit_sales_id = request()->param('limit_sales_id');

        if (!$limit_sales_id) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
        }

        $limit_sales_model = model('limit_sales');

        $where = ['limit_sales_id' => $limit_sales_id];
        $cache = $limit_sales_model
            ->alias('a')
            ->join('tb_goods b','a.goods_id = b.id','left')
            ->order('a.sort desc')
            ->where($where)
            ->find();

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $cache]];
        ajaxReturn($json_arr);
    }*/

}