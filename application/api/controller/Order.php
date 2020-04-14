<?php

namespace app\api\controller;

use app\common\constant\OrderConstant;
use app\common\constant\SystemConstant;
use app\common\constant\CartConstant;
use app\common\helper\CartHelper;
use app\common\helper\GoodsHelper;
use app\common\helper\OrderHelper;
use app\common\helper\VerificationHelper;
use think\Db;

class Order extends Base
{
    use CartHelper;
    use OrderHelper;
    use GoodsHelper;
    use VerificationHelper;

    public function __construct()
    {
        parent::__construct();
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }
    }


    /**
     * 确认订单页面
     */
    public function cartOrder()
    {
        $order_type = request()->post('order_type', 0);
        $goods_id = request()->post('goods_id');
        $address_id = request()->post('address_id');
        $houses_case_id = request()->post('houses_case_id');
        if (!$goods_id) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $address = model('address')->where(['id' => $address_id])->field('consignee,province,city,district,address,telephone')->find();
        if (!$address) {
            $address = model('address')->where(['user_id' => $this->user_id])->field('consignee,province,city,district,address,telephone')->order('is_default desc, id desc')->find();
        }

        if ($order_type == 1) { //单品
            $goods_num = request()->post('goods_num');
            if (!$goods_num) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
            }
            $goods = model('goods')
                ->where(['id' => $goods_id])
                ->field('goods_name,goods_price,goods_unit,goods_size,goods_oprice,goods_logo,express_fee,install_fee')
                ->find();
            $data['goods_price'] = $goods['goods_price'] * $goods_num;
            $data['express_fee'] = $goods['express_fee'] * $goods_num;
            $data['install_fee'] = $goods['install_fee'] * $goods_num;
            $data['total_fee'] =  $data['goods_price'] + $data['express_fee'] + $data['install_fee'];
            $order_goods = [
                'goods_id' => $goods_id,
                'goods_name' => $goods['goods_name'],
                'goods_price' => $goods['goods_price'],
                'goods_size' => $goods['goods_size'],
                'goods_logo' => $goods['goods_logo'],
            ];
            $data = [
                'address' => $address, // 收货地址
                'detail' => $order_goods, // 收货地址
                'total_fee' => $data['total_fee'], // 收货地址
                'total_num' => $goods_num, // 收货地址
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];

            ajaxReturn($json_arr);
        } else { //整装
            $goods_id = explode(',', $goods_id);

            $list = db('goods_cate')->where(['pid' => 0])->field('id,name')->select();
            $houses_goods = [];
            foreach ($list as $k => $v) {
                $list[$k]['cate'] = db('goods_cate')->where(['pid' => $v['id']])->field('id,name')->select();
                foreach ($list[$k]['cate'] as $k1 => $v1) {
                    $houses_goods[] = model('houses_goods')
                        ->alias('hg')
                        ->join('goods g', 'hg.goods_id = g.id', 'left')
                        ->where(['hg.houses_case_id' => $houses_case_id, 'hg.cate_id' => $v1['id'], 'goods_id' => ['in', $goods_id]])
                        ->field('g.id,goods_name,goods_logo,goods_price,goods_oprice,
                             goods_size,goods_unit,express_fee,install_fee,hg.goods_num')
                        ->find();
                }
            }
            $goods_price = 0;
            $express_fee = 0;
            $install_fee = 0;
            foreach ($houses_goods as $k => $v) {
                $goods_price += $v['goods_num'] * $v['goods_price'];
                $express_fee += $v['goods_num'] * $v['express_fee'];
                $install_fee += $v['goods_num'] * $v['install_fee'];
            }
            $data['express_fee'] = $express_fee;
            $data['install_fee'] = $install_fee;
            $data['goods_price'] = $goods_price;
            $data['total_fee'] = $goods_price + $express_fee + $install_fee;

            $field = 'hc.id,hc.name,hc.logo,deposit,total_price,finish_date,area,space,style';
            $housesCase = model('houses_case')
                ->alias('hc')
                ->join('houses_type ht', 'ht.id = hc.houses_type_id', 'left')
                ->join('houses h', 'h.id = ht.houses_id', 'left')
                ->where(['hc.id' => $houses_case_id])
                ->field($field)
                ->find();

            $data = [
                'address' => $address, // 收货地址
                'detail' => $housesCase, // 收货地址
                'total_fee' => $data['total_fee'], // 收货地址
                'total_num' => 1,
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];

            ajaxReturn($json_arr);
        }


    }

    /**
     * 添加订单
     */
    public function addOrder()
    {
        $order_type = request()->post('order_type', 0);
        $goods_id = request()->post('goods_id');
        $address_id = request()->post('address_id');
        $houses_case_id = request()->post('houses_case_id');
        if (!$address_id || !$goods_id) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $address = model('address')->where(['id' => $address_id])->field('consignee,province,city,district,address,telephone')->find();
        if (!$address) {
            ajaxReturn(['status' => 0, 'msg' => '缺少收货人信息', 'data' => []]); // 返回结果状态
        }
        $remark = request()->post('remark');
        $order_no = $this->get_order_sn();

        $data = [
            'order_no' => $order_no, // 订单编号
            'user_id' => $this->user_id, // 用户id
            'address_id' => $address_id, // 收货地址ID
            'consignee' => $address['consignee'], // 收货人
            'province' => $address['province'],//'省份id',
            'city' => $address['city'],//'城市id',
            'district' => $address['district'],//'县',
            'place' => $address['address'],//'详细地址',
            'telephone' => $address['telephone'],//'手机',
            'remark' => $remark, //'给卖家留言',
            'order_time' => time(), // 下单时间
            'order_type' => $order_type,
            'pay_status' => OrderConstant::PAY_STATUS_NONE,
            'order_status' => OrderConstant::ORDER_STATUS_WAIT_PAY,
        ];
        Db::startTrans();
        if ($order_type == 1) { //单品
            $goods_num = request()->post('goods_num');
            if (!$goods_num) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
            }
            $goods = model('goods')
                ->where(['id' => $goods_id])
                ->field('goods_name,goods_price,goods_unit,goods_size,goods_oprice,goods_logo,express_fee,install_fee')
                ->find();
            $data['goods_price'] = $goods['goods_price'] * $goods_num;
            $data['express_fee'] = $goods['express_fee'] * $goods_num;
            $data['install_fee'] = $goods['install_fee'] * $goods_num;
            $data['total_fee'] =  $data['goods_price'] + $data['express_fee'] + $data['install_fee'];

            $order_id = model('order')->save($data);
            if (!$order_id) {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => '生成订单失败1']);
            }
            $order_goods = [
                'user_id' => $this->user_id,
                'order_id' => $order_id,
                'goods_id' => $goods_id,
                'goods_name' => $goods['goods_name'],
                'goods_price' => $goods['goods_price'],
                'goods_oprice' => $goods['goods_oprice'],
                'goods_unit' => $goods['goods_unit'],
                'goods_size' => $goods['goods_size'],
                'goods_logo' => $goods['goods_logo'],

            ];
            $res = model('order_goods')->save($order_goods);
            if (!$res) {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => '生成订单失败2']);
            }
        } else { //整装
            $goods_id = explode(',', $goods_id);

            $list = db('goods_cate')->where(['pid' => 0])->field('id,name')->select();
            $houses_goods = [];
            foreach ($list as $k => $v) {
                $list[$k]['cate'] = db('goods_cate')->where(['pid' => $v['id']])->field('id,name')->select();
                foreach ($list[$k]['cate'] as $k1 => $v1) {
                      $houses_goods[] = model('houses_goods')
                        ->alias('hg')
                        ->join('goods g', 'hg.goods_id = g.id', 'left')
                        ->where(['hg.houses_case_id' => $houses_case_id, 'hg.cate_id' => $v1['id'], 'goods_id' => ['in', $goods_id]])
                        ->field('g.id,goods_name,goods_logo,goods_price,goods_oprice,
                             goods_size,goods_unit,express_fee,install_fee,hg.goods_num')
                        ->find();
                }
            }
            $goods_price = 0;
            $express_fee = 0;
            $install_fee = 0;
            foreach ($houses_goods as $k => $v) {
                $goods_price += $v['goods_num'] * $v['goods_price'];
                $express_fee += $v['goods_num'] * $v['express_fee'];
                $install_fee += $v['goods_num'] * $v['install_fee'];
            }
            $data['express_fee'] = $express_fee;
            $data['install_fee'] = $install_fee;
            $data['goods_price'] = $goods_price;
            $data['total_fee'] = $goods_price + $express_fee + $install_fee;
            $order_id = model('order')->save($data);
            if (!$order_id) {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => '生成订单失败1']);
            }
            $field = 'hc.id,hc.name,hc.logo,deposit,total_price,finish_date,area,space,style';
            $housesCase = model('houses_case')
                ->alias('hc')
                ->join('houses_type ht', 'ht.id = hc.houses_type_id', 'left')
                ->join('houses h', 'h.id = ht.houses_id', 'left')
                ->where(['hc.id' => $houses_case_id])
                ->field($field)
                ->find();
            $order_list = [
                'user_id' => $this->user_id,
                'order_id' => $order_id,
                'houses_case_id' => $houses_case_id,
                'houses_case_name' => $housesCase['name'],
                'houses_case_logo' => $housesCase['logo'],
                'area' => $housesCase['area'],
                'space' => $housesCase['space'],
                'style' => $housesCase['style'],
                'finish_date' => $housesCase['finish_date'],
                'deposit' => $housesCase['deposit'],
                'total_price' => $housesCase['total_price'],
            ];
            $order_list = model('order_list')->insert($order_list);
            if (!$order_list) {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => '生成订单失败2']);
            }

            foreach ($houses_goods as $k => $v) {
                $order_goods = [
                    'user_id' => $this->user_id,
                    'order_id' => $order_id,
                    'goods_id' => $v['goods_id'],
                    'goods_name' => $v['goods_name'],
                    'goods_price' => $v['goods_price'],
                    'goods_oprice' => $v['goods_oprice'],
                    'goods_unit' => $v['goods_unit'],
                    'goods_size' => $v['goods_size'],
                    'goods_pic' => $v['goods_pic'],
                ];
                $res = model('order_goods')->insert($order_goods);
                if (!$res) {
                    Db::rollback();
                    ajaxReturn(['status' => 0, 'msg' => '生成订单失败3']);
                }
            }
        }

        Db::commit();
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['order_no' => $order_no]]);
    }


    public function certificate()
    {
        $data['order_no'] = request()->post('order_no');
        if (!$data['order_no']) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }

        $order = model('order')
            ->where(['order_no' => $data['order_no']])
            ->field('order_status')
            ->find();

        //model('order')->save(['is_certificate' => OrderConstant::ORDER_CERTIFICATE_NONE], ['order_no' => $order['order_no']]);

        if ($order['order_status'] != OrderConstant::ORDER_STATUS_WAIT_PAY
            && $order['order_status'] != OrderConstant::ORDER_STATUS_FINAL_ORDER) {
            ajaxReturn(['status' => 0, 'msg' => '该订单已经不能上传凭证了']);
        }


        $data['certificate'] = request()->post('certificate');
        if (!$data['certificate']) {
            ajaxReturn(['status' => 0, 'msg' => '请上传凭证']);
        }
        $data['re_username'] = getSetting('system.re_username');
        $data['re_account'] = getSetting('system.re_account');
        $data['re_bank'] = getSetting('system.re_bank');
        $data['user_id'] = $this->user_id;
        $data['create_time'] = time();
        model('order_certificate')->save($data);
        //更改订单状态为2
        model('order')->save(['order_status' => 3, 'pay_status' => 1], ['order_no' => $data['order_no']]);
        $return_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]; //
        ajaxReturn($return_arr);
    }

    public function orderList()
    {
        if (request()->isPost()) {
            $status = request()->post('status', 0);
            $list_row = request()->post('list_row', 10); //每页数据
            $page = request()->post('page', 1); //当前页

            if (!in_array($status, [0, 1, 2, 3, 4, 5, 6, 7])) {
                $return_arr = ['status' => 0, 'msg' => '参数status错误', 'data' => []]; //
                ajaxReturn($return_arr);
            }

            $order_data = ['is_del' => 0];
            $order_data['user_id'] = $this->user_id;
            $order_data['partner_id'] = 0;

            if ($status != 0) {
                $order_data['order_status'] = $status;
                switch ($status) {
                    case 1: //待付款
                        $order_data['order_status'] = OrderConstant::ORDER_STATUS_WAIT_PAY;
                        break;
                    case 2: //待付尾款
                        $order_data['order_status'] = OrderConstant::ORDER_STATUS_FINAL_ORDER;
                        break;
                    case 3: //待审核
                        $order_data['order_status'] = OrderConstant::ORDER_STATUS_WAIT_SEND;
                        $order_data['sure_status'] = 0;
                        break;
                    case 4: //待发货
                        $order_data['order_status'] = OrderConstant::ORDER_STATUS_WAIT_SEND;
                        $order_data['sure_status'] = 1;
                        break;
                    case 5: //待收货
                        $order_data['order_status'] = OrderConstant::ORDER_STATUS_WAIT_RECEIVE;
                        break;
                    case 6: //待评价
                        $order_data['order_status'] = OrderConstant::ORDER_STATUS_WAIT_COMMENT;
                        break;
                    case 7: //退款售后
                        $order_data['order_status'] = ['in', [
                            OrderConstant::ORDER_STATUS_UN_REFUND,
                            OrderConstant::ORDER_STATUS_APPLY_REFUND,
                            OrderConstant::ORDER_STATUS_FINISH_REFUND
                        ]];
                        break;
                }
            }

            $totalCount = model('order')->where($order_data)->count();

            $pageCount = ceil($totalCount / $list_row);
            $field = 'id,order_no,sure_status,pay_status,is_certificate,pay_order_status,is_shipping,order_status,is_refund,order_time,integral_money,total_fee,pay_price,is_confirm_integration,shipping_time';
            $first_row = ($page - 1) * $list_row;
            $order_list = model('order')
                ->where($order_data)
                ->order('order_time desc')
                ->limit($first_row, $list_row)
                ->field($field)
                ->select();

            if ($order_list) {
                $field = 'id order_goods_id, goods_id,sku_id,goods_name,goods_pic,goods_price,goods_num,goods_code,goods_unit,weight';
                foreach ($order_list as $k => $v) {
                    $order_list[$k] = $this->set_btn_order_status($v);
                    $order_goods_list = model('order_goods')->field($field)->where(['order_id' => $v['id']])->select();
                    $goods_nums = 0;
                    foreach ($order_goods_list as $kk => $vv) {
                        $goods_nums += $vv['goods_num'];
                    }
                    $order_list[$k]['goods_num'] = $goods_nums;  //订单商品总数量
                    $order_list[$k]['goods_list'] = $order_goods_list;
                }
            }

            $data = [
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
                'list' => $order_list ? $order_list : [],
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            echo json_encode($json_arr);
            exit;
        }
    }

    /**
     * @version 订单详情
     */
    public function orderDetail()
    {

        $order_no = request()->post('order_no');
        if (empty($order_no)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]; //
            ajaxReturn($return_arr);
        }

        $order_data = ['is_del' => 0];
        $order_data['user_id'] = $this->user_id;
        $order_data['order_no|out_trade_no'] = $order_no;

        $order_info = model('order')->where($order_data)->order('id desc')->find();

        if (!$order_info) {
            $return_arr = ['status' => 0, 'msg' => "订单已删除", 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $order_info = $this->set_btn_order_status($order_info);
        //订单信息
        $order_info_data = [];
        //订单商品列表
        $goods_list = [];
        $order_info_data['order_id'] = $order_info['id'];
        $order_info_data['order_no'] = $order_info['order_no'];
        $order_info_data['install_status'] = $order_info['install_status'];
        $order_info_data['order_status'] = $order_info['order_status'];
        $order_info_data['order_status_code'] = $order_info['order_status_code'];
        $order_info_data['order_status_desc'] = $order_info['order_status_desc'];
        $order_info_data['order_btn'] = $order_info['order_btn'];
        $order_info_data['order_time'] = $order_info['order_time'];
        $order_info_data['integral'] = $order_info['integral'];
        $order_info_data['integral_money'] = $order_info['integral_money'];
        $order_info_data['coupon_money'] = $order_info['coupon_price'];
        $order_info_data['return_integral'] = $order_info['return_integral'];  // 购买商品赠送总积分(已支付才有值)
        $order_info_data['total_price'] = $order_info['total_price']; // 商品总金额
        $order_info_data['total_fee'] = $order_info['total_fee']; // 订单金额（s商品总金额+运费-抵扣）
        $order_info_data['express_fee'] = $order_info['express_fee'];
        $order_info_data['cover_fee'] = $order_info['cover_fee'];
        $order_info_data['paid_money'] = $order_info['paid_money'];
        $order_info_data['pay_wait_price'] = $order_info['total_fee'] - $order_info['paid_money'];
        $order_info_data['pay_status'] = $order_info['pay_status']; // 支付状态(0:未支付，1:已支付)
        $order_info_data['pay_way'] = OrderConstant::order_pay_array_value($order_info['pay_way']); //支付方式：1支付宝 2微信支付 3银联支付 4余额支付 5积分支付
        $order_info_data['pay_price'] = $order_info['pay_price']; // 支付金额
        $order_info_data['trade_no'] = $order_info['trade_no']; // 支付成功返回的支付平台交易单号
        $order_info_data['pay_time'] = $order_info['pay_time'];
        $order_info_data['express_no'] = $order_info['express_no'];
        $order_info_data['is_shipping'] = $order_info['is_shipping'];  // 0 未发货 1 已发货
        $order_info_data['shipping_time'] = $order_info['shipping_time']; // 发货时间
        $order_info_data['is_receive'] = $order_info['is_receive'];  //是否送达
        $order_info_data['receive_time'] = $order_info['receive_time']; //货物送达时间
        $order_info_data['is_confirm'] = $order_info['is_confirm'];  // 确认收货：0-未确认 1-已确认
        $order_info_data['confirm_time'] = $order_info['confirm_time']; //买家确认收货时间
        $order_info_data['is_comment'] = $order_info['is_comment']; // 0-未评价 1-已评价
        $order_info_data['comment_time'] = $order_info['comment_time']; //评论时间
        $order_info_data['remark'] = $order_info['remark']; //用户备注
        $order_info_data['is_refund'] = $order_info['is_refund']; //是否退款完成0-未完成  1-完成  2-拒绝
        $order_info_data['refuse_refund_reason'] = $order_info['refuse_refund_reason']; //拒绝退款原因

        $order_goods_list = model('order_goods')->where(array('order_id' => $order_info['id']))->select();
        if (!$order_goods_list) {
            $return_arr = ['status' => 0, 'msg' => "订单商品已删除", 'data' => []]; //
            ajaxReturn($return_arr);
        }

        $goods = [];
        foreach ($order_goods_list as $kk => $vv) {
            if ($order_info['pay_status'] == 1) {
                $goods[$kk]['is_refund'] = 0;
                if (!$vv['is_refund']) {
                    if (in_array($order_info['order_status'], ['2,3'])) {
                        $goods[$kk]['is_refund'] = 1;
                    }
                    if (in_array($order_info['order_status'], ['4,5'])) {
                        if (($order_info['confirm_time'] && strtotime($order_info['confirm_time']) + 30 * 3600 > time())) {
                            $goods[$kk]['is_refund'] = 1;
                        }
                    }
                }
            } else {
                $goods[$kk]['is_refund'] = 0;
            }
            $goods[$kk]['order_goods_id'] = $vv['id'];
            $goods[$kk]['sku_id'] = $vv['sku_id'];

            $goods[$kk]['goods_id'] = $vv['goods_id'];
            $goods[$kk]['goods_name'] = $vv['goods_name'];
            $goods[$kk]['goods_num'] = $vv['goods_num'];
            $goods[$kk]['goods_unit'] = $vv['goods_unit'];
            $goods[$kk]['weight'] = $vv['weight'];
            $goods[$kk]['goods_price'] = $vv['goods_price'];
            $goods[$kk]['goods_pic'] = $vv['goods_pic'];
            $goods[$kk]['goods_allprice'] = $vv['goods_price'] * $vv['goods_num'];
        }
        $goods_list['list'] = $goods;

        //退款/售后信息
        $refundinfo = [];
        //$refundinfo['is_applyrefund'] = $order_info['is_applyrefund']; //是否申请退款 0 未申请  1申请  2取消申请
        //$refundinfo['applyrefund_reason'] = $order_info['applyrefund_reason']; //申请退款原因
        //$refundinfo['applyrefund_time'] = $order_info['applyrefund_time']; //申请退款时间
        //$refundinfo['applyrefund_money'] = $order_info['applyrefund_money']; //申请退款金额
        //$refundinfo['is_refund'] = $order_info['is_refund']; // 是否退款完成0-未完成  1-完成  2-拒绝
        //$refundinfo['refuse_refund_reason'] = $order_info['refuse_refund_reason']; //拒绝退款原因
        //$refundinfo['refund_time'] = $order_info['refund_time']; //退款时间
        //$refundinfo['refund_fee'] = $order_info['refund_fee']; //退款金额
        //$refundimg = $order_info['applyrefund_reason_pic'];
        //if ($refundimg) {
        //   $refundimg_arr = explode(',', $refundimg);
        //   foreach ($refundimg_arr as $k => $v) {
        //        $refundimg_arr[$k] = getSetting('system.host') . $v;
        //    }
        //   $refundinfo['refund_pic'] = $refundimg_arr;
        //} else {
        //    $refundinfo['refund_pic'] = [];
        // }


        //收货地址信息
        $address_info = [];
        $address_info['consignee'] = $order_info['consignee'];
        $address_info['telephone'] = $order_info['telephone'];
        $address_info['province'] = $order_info['province'];
        $address_info['city'] = $order_info['city'];
        $address_info['district'] = $order_info['district'];
        $address_info['place'] = $order_info['place'];

        /*//发票信息
        $invoice_info = [];
        $invoice_info['is_invoice'] 		= $order_info['is_invoice'];
        $invoice_info['invoice_title'] 		= $order_info['invoice_title'];
        $invoice_info['invoice_taxnum'] 	= $order_info['invoice_taxnum'];
        $invoice_info['invoice_address'] 	= $order_info['invoice_address'];
        $invoice_info['invoice_mobile'] 	= $order_info['invoice_mobile'];
        $invoice_info['invoice_bankname']   = $order_info['invoice_bankname'];
        $invoice_info['invoice_bankcardid'] = $order_info['invoice_bankcardid'];
        */

        //评价信息
        /*$goods_comment = model('goods_comment')->where(array("order_id"=>$order_info['order_id']))->find();
        $info_commnet['add_time'] = $goods_comment['add_time'];//评价时间
        $info_commnet['content'] = $goods_comment['content'];//评价内容
        $slide_img = explode(",", $goods_comment['slide_img']);
        $info_commnet['slide_img'] = $slide_img;//评价图片
        $info_commnet['desc_star'] = $goods_comment['desc_star'];//商品描述星星数量
        $info_commnet['quality_star'] = $goods_comment['quality_star'];//商品质量星星数量*/

        $certificate = model('order_certificate')->where(['order_no' => $order_info_data['order_no']])->order('create_time asc')->select();

        $data = [
            'order' => $order_info_data, //订单信息
            'goods_list' => $goods_list, //订单商品列表
            'certificate' => $certificate, //订单商品列表
            'address_info' => $address_info, //收货地址信息
            'refund_info' => $refundinfo, //退款/售后信息
            //'info_commnet' => $info_commnet, //评价信息
        ];
        $data = removeNull($data);
        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        echo json_encode($json_arr);
        exit;
    }

    public function delOrder()
    {
        $order_no = request()->post('order_no');
        if (empty($order_no)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $res = model('order')->where(['order_no' => $order_no])->setField('is_del', 1);
        if ($res) {
            $return_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]; //
        } else {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]; //
        }
        ajaxReturn($return_arr);
    }

    /**
     * @version 取消订单
     */

    public function cancelOrder()
    {
        $order_no = request()->post('order_no');
        if (empty($order_no)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $return_arr = $this->cancel_order($order_no);
        ajaxReturn($return_arr);
    }

    /**
     * @version 确认收货
     */
    public function confirmOrder()
    {

        $order_no = request()->post('order_no');
        if (empty($order_no)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]; //
            ajaxReturn($return_arr);
        }

        $return_arr = $this->confirm_order($order_no);
        ajaxReturn($return_arr);
    }


    /**
     * @version 查看物流
     */
    public function orderExpress()
    {

        $order_no = request()->post('order_no');
        if (empty($order_no)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]; //
            ajaxReturn($return_arr);
        }

        $order_data = [];
        $order_data['user_id'] = $this->user_id;
        $order_data['order_no'] = $order_no;

        $order_info = model('order')->where($order_data)->order('id desc')->find();
        if (!$order_info) {
            $return_arr = ['status' => 0, 'msg' => '订单已删除', 'data' => []]; //
            ajaxReturn($return_arr);
        }

        if ($order_info['pay_status'] == 0) {
            $return_arr = ['status' => 0, 'msg' => '订单未支付', 'data' => []]; //
            ajaxReturn($return_arr);
        }

        if ($order_info['order_status'] <= 2) {
            $return_arr = ['status' => 0, 'msg' => '订单未发货', 'data' => []]; //
            ajaxReturn($return_arr);
        }


        if ($order_info['shipping_type'] == 1) {

            if (empty($order_info['express_name']) || empty($order_info['express_no'])) {
                $return_arr = ['status' => 0, 'msg' => '物流信息不完整', 'data' => []]; //
                ajaxReturn($return_arr);
            }

            //物流信息
            $express_name = model('express')->where(['express_ma' => $order_info['express_name']])->find();
            $info = [];
            $info['express_name'] = $express_name['express_company'];
            $info['express_no'] = $order_info['express_no'];
            $info['express_tel'] = $express_name['express_tel'];
            $info['express_logo'] = $express_name['express_logo'];
            //第三方物流查询api
            $res = $this->get_express_info($order_info['express_name'], $order_info['express_no']);  //第三方物流查询api
            $express = json_decode($res, true);

            if ($express['Success']) {
                $exp = $express['Traces'];
                $exp = list_sort_by($exp, 'AcceptTime', 'desc');
            } else {
                $exp = [['AcceptStation' => '物流信息查询失败', 'AcceptTime' => date('Y-m-d H:i:s')]];
            }
        } else {
            $accept = '配送员：'.$order_info['shipping_username'].',联系方式：'.$order_info['shipping_telephone'];
            $exp = [[
                'AcceptStation' => $accept,
                'AcceptTime' => $order_info['shipping_time']
            ]];
        }
        $info['express_data'] = $exp;

        $return_arr = ['status' => 1, 'msg' => '获取成功', 'data' => $info]; //
        ajaxReturn($return_arr);
    }
}