<?php

namespace app\api\controller;

use app\common\constant\ContentConstant;
use app\common\constant\OrderConstant;
use app\common\constant\PreferentialConstant;
use app\common\constant\SystemConstant;
use app\common\constant\CartConstant;
use app\common\helper\CartHelper;
use app\common\helper\GoodsHelper;
use app\common\helper\PreferentialHelper;
use app\common\constant\IntegralConstant;
use app\common\helper\OrderHelper;
use app\common\helper\StoreHelper;
use app\common\helper\IntegralHelper;
use app\common\helper\VerificationHelper;
use think\Db;

class Order extends Base
{
    use CartHelper;
    use PreferentialHelper;
    use OrderHelper;
    use IntegralHelper;
    use GoodsHelper;
    use StoreHelper;
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
        $cart_type = request()->post('cart_type', CartConstant::CART_TYPE_CART_ORDER);

        $where = ['user_id' => $this->user_id, 'cart_type' => $cart_type, 'partner_id' => 0];

        if ($cart_type == CartConstant::CART_TYPE_CART_ORDER) {
            $where['selected'] = 1;
        }

        $cartList = model('cart')->where($where)->field('id,cart_type,package_id,goods_id,goods_num,goods_price,sku_id,prom_type,prom_id')->select();

        if (empty($cartList)) {
            $json_arr = ['status' => 0, 'msg' => '你没有选择商品', 'data' => ['is_none' => 1]];
            exit(json_encode($json_arr));
        }

        $total_num = $total_price = 0;
        $cart_info = [];
        $is_coupon = 1;
        $brand_id = [];
        foreach ($cartList as $k => $val) {
            $cartList[$k]['goods_fee'] = $val['goods_num'] * $val['goods_price'];
            $total_num += $val['goods_num'];
            $total_price += $cartList[$k]['goods_fee'];
            $goods = model('goods')
                ->field('id,goods_name,goods_logo,price,goods_unit,weight,prom_type,prom_id,brand_id')
                ->where(['id' => $val['goods_id']])
                ->find();
//            $cartList[$k]['goodsInfo'] = $goods;
            $goods_skuinfo = $this->get_sku_des($val['goods_id'], $val['sku_id']);
            $cartList[$k]['goods_skuinfo'] = $goods_skuinfo;
            $cartList[$k]['goods_pic'] = $goods['goods_logo'];
            $cartList[$k]['goods_name'] = $goods['goods_name'];
            $cartList[$k]['goods_unit'] = $goods['goods_unit'];
            $cartList[$k]['weight'] = $goods['weight'];
            //$cartList[$k]['goodsInfo'] = model('goods')->field('goods_id,goods_name,goods_pic,goods_price')->where(['goods_id' => $val['goods_id']])->find();
            $cart_info[] = [
                'goods_id' => $val['goods_id'],
                'sku_id' => $val['sku_id'],
            ];
            if ($val['cart_type'] == CartConstant::CART_TYPE_PACKAGE_BUY) {
                $is_coupon = model('package')->where(['id' => $val['package_id']])->value('is_coupon');
            } else if ($val['cart_type'] == CartConstant::CART_TYPE_PACKAGE_NEW) {
                $is_coupon = model('meal')->where(['id' => $val['package_id']])->value('is_coupon');
            } else{
                if ($val['prom_type'] == 1 ) {
                    $is_coupon = 0;
                }
            }
            $brand_id[] = $goods['brand_id'];
        }

        //$totalPrice = ['total_price' =>$total_price , 'total_num'=> $total_num]; // 总计

        // 收货地址
        $field = 'id,user_id,consignee,telephone,province,province_id,city,district,address';
        $address = model('address')->field($field)->where(['user_id' => $this->user_id, 'partner_id' => 0])->order('is_default desc, add_time desc')->find();
        $address_list = model('address')->field($field)->where(['user_id' => $this->user_id, 'id' => ['neq', $address['id']], 'partner_id' => 0])->order('is_default desc, add_time desc')->select();
        $where = [
            'partner_id' => 0,
            'user_id' => $this->user_id,
            'status' => '0',
            'starttime' => ['elt', time()],
            'endtime' => ['egt', time()],
        ];

        $coupon_Arr = model('coupon_data')
            ->where($where)
            ->field('id cou_id,use_type,goods_info, limit_money, deduct, coupon_type, title,canal,starttime,endtime,use_time,status')
            ->order('deduct desc')
            ->select();

        $coupon_list = [];
        $old_coupon = [];
        foreach ($coupon_Arr as $key => $val_cop) {

            if ($is_coupon == 1 && $val_cop['limit_money'] <= $total_price) {
                if ($val_cop['coupon_type'] == 1) {
                    $val_cop['youhui'] = $val_cop['deduct'];
                } else if ($val_cop['coupon_type'] == 2) {
                    $deduct = $val_cop['deduct'] / 10;
                    $val_cop['youhui'] = $total_price - ($total_price * $deduct);
                }
	
                if ($val_cop['use_type'] == 0) { //全部商品
                    $coupon_list[] = [
                        'cou_id' => $val_cop['cou_id'],
                        'limit_money' => (int)$val_cop['limit_money'],
                        'deduct' => (int)$val_cop['deduct'],
                        'coupon_type' => $val_cop['coupon_type'],
                        'title' => $val_cop['title'],
                    ];
                    continue;
                } else if ($val_cop['use_type'] == 1) { //部分商品

                    $goods_info = json_decode($val_cop['goods_info'], true);

                    if ($goods_info) {
                        $is_true = false;
                        foreach ($goods_info as $k => $v) {
                            $v['sku_id'] = $v['sku_id']?$v['sku_id']:0;
                            foreach ($cart_info as $k1 => $v1) {
                                if ($v1['goods_id'] == $v['goods_id'] && $v1['sku_id'] == $v['sku_id']) {
                                    $is_true = true;
                                }
                            }
                        }
                        if ($is_true == true) {
                            $coupon_list[] = [
                                'cou_id' => $val_cop['cou_id'],
                                'limit_money' => (int)$val_cop['limit_money'],
                                'deduct' => (int)$val_cop['deduct'],
                                'coupon_type' => $val_cop['coupon_type'],
                                'title' => $val_cop['title'],
                            ];
                            continue;
                        }
                    }
                } else if ($val_cop['use_type'] == 2) { //部分商品

                    $goods_info = json_decode($val_cop['goods_info'], true);

                    if ($goods_info) {
                        $is_true = false;
                        foreach ($goods_info as $k => $v) {
                            foreach ($brand_id as $k1 => $v1) {
                                if ($v1['brand_id'] == $v) {
                                    $is_true = true;
                                }
                            }
                        }
                        if ($is_true == true) {
                            $coupon_list[] = [
                                'cou_id' => $val_cop['cou_id'],
                                'limit_money' => (int)$val_cop['limit_money'],
                                'deduct' => (int)$val_cop['deduct'],
                                'coupon_type' => $val_cop['coupon_type'],
                                'title' => $val_cop['title'],
                            ];
                            continue;
                        }
                    }
                }

            }
            $old_coupon[] = [
                'cou_id' => $val_cop['cou_id'],
                'limit_money' => (int)$val_cop['limit_money'],
                'deduct' => (int)$val_cop['deduct'],
                'coupon_type' => $val_cop['coupon_type'],
                'title' => $val_cop['title'],
            ];
        }

        unset($coupon_Arr);
        if ($coupon_list) {
            $couponInfo = [];
            //unset($coupon_list[0]);
            $coupon_list = array_values($coupon_list);
        } else {
            $couponInfo = [];
        }

        $payment_model = model('payment');
        $where = [];
        $where['pay_status'] = 1;
        $where['is_use'] = 0;
        $where['user_id'] = $this->user_id;
        $field = ['id', 'money'];
        $payment_list = $payment_model->where($where)->field($field)->order('create_time desc')->select();
        $data = [
            'cartList' => $cartList,
            'address' => $address, // 收货地址
            'address_list' => $address_list, // 收货地址
            'total_num' => $total_num, // 总计
            'coupon_list' => $coupon_list, // 优惠券
            'old_coupon' => $old_coupon, // 优惠券
            'couponInfo' => $couponInfo,
            'payment_list' => $payment_list,
        ];

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];

        exit(json_encode($json_arr));
    }


    /**
     * 确认订单页面 价格刷新
     */
    public function orderBuy()
    {

        $cart_type = request()->post('cart_type', CartConstant::CART_TYPE_CART_ORDER);
        $address_id = request()->post('address_id', 0); //  使用积分
        $pay_integral = request()->post('pay_integral', 0); //  使用积分
        $coupon_id = request()->post('coupon_id', 0); // $coupon_id =  I('coupon_id',0); //  优惠券id  数组形式
        $cartList = $this->getCartList($this->user_id, $cart_type);
        $address = model('address')->where(['id' => $address_id])->find();
        $payment_id = request()->post('payment_id'); //  定金ID
        $car_price = $this->getOrderPrice($cartList, $pay_integral, $coupon_id, $address, $payment_id, $this->user_id);
        unset($car_price['cartList']);
        $return_arr = array('status' => 1, 'msg' => '计算成功', 'data' => $car_price); // 返回结果状态
        ajaxReturn($return_arr);
    }


    /**
     * 提交订单去付款
     */
    public function addOrder()
    {
        $share_id = request()->post('share_id');
        $source = request()->post('source');
        $cart_type = request()->post('cart_type', CartConstant::CART_TYPE_CART_ORDER);
        $pay_integral = request()->post('pay_integral', 0); //  使用积分
        $coupon_id = request()->post('coupon_id', 0); // $coupon_id =  I('coupon_id',0); //  优惠券id
        $address_id = request()->post('address_id'); //  收货地址id
        $payment_id = request()->post('payment_id'); //  定金ID
        $pay_order_status = request()->post('pay_order_status', OrderConstant::PAY_ORDER_STATUS_ALL); //全额付款

        $cartList = $this->getCartList($this->user_id, $cart_type);
        foreach ($cartList as $k => $v) {
            $goods = model('goods')->where(['id' => $v['goods_id']])->find();
            $result = $this->check_goods_store($v['goods_num'], $goods, $v, 1);
            if ($result['status'] == 0) {
                ajaxReturn($result);
            }
        }
        if (!$address_id) {
            exit(json_encode(['status' => 0, 'msg' => '请完善收货人信息', 'data' => []])); // 返回结果状态
        }

        $address = model('address')->where('id', $address_id)->find();

        if (!$address) {
            ajaxReturn(['status' => 0, 'msg' => '缺少收货人信息', 'data' => []]); // 返回结果状态
        }


        $car_price = $this->getOrderPrice($cartList, $pay_integral, $coupon_id, $address, $payment_id, $this->user_id);
        if ($car_price['is_buy'] == 0) {
            $return_arr = ['status' => 0, 'msg' => '本地区暂不售卖', 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $remark = request()->post('remark'); // $remark = I('remark'); // 给卖家留言

        $order_model = model('order');

        // 插入订单 order
        $order_no = $this->get_order_sn(); // 获取生成订单号

        while ($order_model->where(['order_no' => $order_no])->field('order_no')->find()) {
            $order_no = $this->get_order_sn(); // 获取生成订单号
        }

        $data = [
            'order_no' => $order_no, // 订单编号
            'out_trade_no' => $order_no, // 订单编号
            'share_id' => $share_id, // 用户id
            'user_id' => $this->user_id, // 用户id
            'coupon_id' => $coupon_id, // 优惠券ID
            'address_id' => $address['id'], // 收货地址ID
            'consignee' => $address['consignee'], // 收货人
            'province' => $address['province'],//'省份id',
            'province_id' => $address['province_id'],//'省份id',
            'city' => $address['city'],//'城市id',
            'city_id' => $address['city_id'],//'城市id',
            'district' => $address['district'],//'县',
            'district_id' => $address['district_id'],//'县',
            'place' => $address['address'],//'详细地址',
            'telephone' => $address['telephone'],//'手机',
            'remark' => $remark, //'给卖家留言',
            'total_price' => $car_price['goods_price'],//商品价格',
            'coupon_price' => $car_price['coupon_price'],//'使用优惠券',
            'integral' => $pay_integral, // 使用的积分数量
            'integral_money' => $car_price['integral_money'],//'使用积分抵多少钱',
            'total_fee' => $car_price['order_amount'],//'应付款金额',
            'total_amount' => $car_price['order_amount'],//'应付款金额',
            'express_fee' => $car_price['express_fee'],//'应付款金额',
            'cover_fee' => $car_price['cover_fee'],//'应付款金额',
            'order_time' => date("Y-m-d H:i:s"), // 下单时间
            'update_time' => date("Y-m-d H:i:s"), // 下单时间
            'pay_order_status' => $pay_order_status,
            'deposit_money' => $car_price['deposit_money'],
            'payment_id' => $payment_id, //定金ID
            'payment_money' => $car_price['payment_money'], //定金
            'order_type' => $cart_type,
            'source' => $source,
            'pay_status' => OrderConstant::PAY_STATUS_NONE,
            'order_status' => OrderConstant::ORDER_STATUS_WAIT_PAY,
            'partner_id' => 0,
        ];

        Db::startTrans();
        $result = $order_model->save($data);
        if (!$result) {
            Db::rollback();
            ajaxReturn(['status' => 0, 'msg' => '生成订单失败', 'data' => []]); // 返回结果状态
        }

        $order_id = $order_model->getLastInsID();

        if ($payment_id) {
            model('payment')->isUpdate(true)->save([
                'use_order_no' => $order_no,
                'is_use' => 1,
            ], ['id' => $payment_id]);
        }
        $data['id'] = $order_id;

        $money_exchange_integral_one = getSetting('integral.money_exchange_integral_one');
        $money_exchange_integral_all = getSetting('integral.money_exchange_integral_all');
        $money_exchange = $car_price['order_amount'] * $money_exchange_integral_all / $money_exchange_integral_one;

        // 记录订单操作日志
        //logOrder($order_id,'您提交了订单，请等待系统确认','提交订单',$this->user_id,2);
        $cart_model = model('cart');
        // 1插入order_goods 表
        $cartList = $car_price['cartList'];
        $cart_id = [];

        foreach ($cartList as $key => $val) {
            $price = $car_price['goods_price']?$val['goods_price'] / $car_price['goods_price']:0;

            $goods_pay_price = $car_price['order_amount']?$val['goods_price']/$car_price['order_amount']:0;
            $data2['coupon_price'] = $price * $car_price['coupon_price'];
            $data2['integral_count'] = $price * $pay_integral;
            $data2['return_integral'] = $price * $money_exchange;
            $data2['order_id'] = $order_id; // 订单id
            $data2['order_no'] = $order_no; // 订单id
            $data2['user_id'] = $this->user_id; // 订单id
            $data2['goods_id'] = $val['goods_id']; // 商品id
            $data2['sku_id'] = $val['sku_id']; // 商品id
            $data2['goods_name'] = $val['goods_name']; // 商品名称
            $data2['goods_code'] = $val['goods_code']; // 商品货号
            $data2['goods_pic'] = str_replace(getSetting('system.host'), '', $val['goods_pic']); // 商品货号
            $data2['goods_unit'] = $val['goods_unit']; // 商品单位
            $data2['sku_info'] = $val['goods_skuinfo']; // 商品单位
            $data2['weight'] = $val['weight']; // 商品重量
            $data2['goods_num'] = $val['goods_num']; // 购买数量
            $data2['goods_price'] = $val['goods_price']; // 商品价
            $data2['goods_oprice'] = $val['goods_oprice']; // c端零售价
            $data2['cost_price'] = $val['cost_price']; // 成本价
            $data2['b_price'] = $val['b_price']; // b端供货价
            $data2['goods_pay_price'] = $goods_pay_price * $val['goods_price']; // 实际支付价
            $data2['prom_type'] = $val['prom_type']; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
            $data2['prom_id'] = $val['prom_id']; // 活动id
            $data2['store_id'] = $val['store_id'];
            $result = model('OrderGoods')->insert($data2);
            if (!$result) {
                Db::rollback();
                exit(json_encode(['status' => 0, 'msg' => '生成订单商品失败', 'data' => []])); // 返回结果状态
            }
            $cart_id[] = $val['id'];

            //减少库存，增加销量
            $goods_map = [];
            $goods_map['id'] = $val['goods_id'];
            $res = model('goods')->where(['id' => $val['goods_id']])->setDec('stores', $val['goods_num']);
            if ($val['sku_id']) {
                model('spec_goods_price')->where(['key' => $val['sku_id'], 'goods_id' => $val['goods_id']])->setDec('store_count', $val['goods_num']);
            }
            if ($val['prom_id'] != 0) {
                if ($val['prom_type'] == PreferentialConstant::PREFERENTIAL_TYPE_LIMIT_SALES) {
                    model('limit_sales')->where(['limit_sales_id' => $val['prom_id']])->setInc('sales_num', $val['goods_num']);
                }
            }
            // 扣除商品库存  扣除库存移到 付完款后扣除
            //model('Goods')->where('goods_id = '.$val['goods_id'])->setDec('store_count',$val['goods_num']); // 商品减少库存
        }

        if (!empty($coupon_id)) {
            // 2修改优惠券状态
            $data3['user_id'] = $this->user_id;
            $data3['order_id'] = $order_id;
            $data3['status'] = 1;
            $data3['use_time'] = time();
            $result = model('coupon_data')->save($data3, ['id' => $coupon_id]);
            if (!$result) {
                Db::rollback();
                exit(json_encode(['status' => 0, 'msg' => '使用优惠券失败', 'data' => []])); // 返回结果状态
            }
            //$cid = model('CouponList')->where('id = $coupon_id[$k]')->getField('cid');
            //model('Coupon')->where('id = $cid')->setInc('use_num'); // 优惠券的使用数量加一
        }

        // 3 扣除积分 扣除余额
        if ($pay_integral) {
            $res = $this->integral_record(
                IntegralConstant::INTEGRAL_CATE_TYPE_ORDER_BUY,
                $pay_integral,
                IntegralConstant::INTEGRAL_USE_TYPE_ORDER_BUY,
                1,
                $this->user_id,
                1,
                $order_id
            );
            if ($res && $res['status'] == 0) {
                // 这里记入错误日志
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => '积分抵扣失败', 'data' => []]); // 返回结果状态
            }
        }

        // 4 清空购物车
        $result = $cart_model->where(['id' => ['in', $cart_id]])->delete();
        if (!$result) {
            Db::rollback();
            ajaxReturn(['status' => 0, 'msg' => '清空购物车失败', 'data' => []]); // 返回结果状态
        }

        $this->rebate_log($data);

        if ($car_price['order_amount'] == 0) {
            $res = $this->successPayOperation($order_no, '', 0, 5);
            if ($res['status'] == 1) {
                Db::commit();
                $return_arr = ['status' => 200, 'msg' => '订单支付成功', 'data' => ['order_no' => $order_no]]; //
                ajaxReturn($return_arr);
            } else {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => $res['msg'], 'data' => []]); // 返回结果状态
            }
        } else {
            Db::commit();
            $return_arr = ['status' => 1, 'msg' => '提交订单成功', 'data' => ['order_no' => $order_no]]; //
            ajaxReturn($return_arr);
        }
    }

    /**
     * 单个待付款
     */
    public function payWait()
    {
        $order_no = request()->post('order_no');
        if (!$order_no) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $order_model = model('order');
        $deposit_order_money = getSetting('order.deposit_order_money');
        $order = $order_model->where(['order_no' => $order_no])->find();
        $total_fee = $order['total_fee'];
        if ($order['pay_order_status'] != OrderConstant::PAY_ORDER_STATUS_DOING_DEPOSIT) {
            if ($total_fee > $deposit_order_money) {
                $save_data = [
                    'only_order_no' => '',
                    'deposit_money' => $deposit_order_money,
                    'pay_order_status' => OrderConstant::PAY_ORDER_STATUS_NONE_DEPOSIT,
                    'total_amount' => $total_fee,
                    'update_time' => date('Y-m-d H:i:s', time())
                ];
            } else {
                $save_data = [
                    'only_order_no' => '',
                    'deposit_money' => 0,
                    'pay_order_status' => OrderConstant::PAY_ORDER_STATUS_ALL,
                    'total_amount' => $total_fee,
                    'update_time' => date('Y-m-d H:i:s', time())
                ];
            }
            $res = $order_model->save($save_data, ['order_no' => $order_no]);
            if (!$res) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['order_no' => $order_no]]);
    }

    /**
     * 合并订单
     */
    public function mergeOrder()
    {
        $data = request()->post();
        if (!isset($data['order_no'])) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $order_model = model('order');
        $total_fee = $order_model->where(['order_no' => ['in', $data['order_no']]])->sum('total_fee');
        $deposit_order_money = getSetting('order.deposit_order_money');

        $order = $order_model->where(['order_no' => ['in', $data['order_no']]])->select();
        if (count($order) > 1) {
            $only_order_no = $this->get_order_sn();
            while ($order_model->where(['only_order_no' => $only_order_no])->field('only_order_no')->find()) {
                $only_order_no = $this->get_order_sn(); // 获取生成订单号
            }

            Db::startTrans();
            if ($total_fee > $deposit_order_money) {
                $deposit_money = $deposit_order_money / count($order);
                foreach ($order as $k => $v) {
                    $save_data = [
                        'only_order_no' => $only_order_no,
                        'deposit_money' => $deposit_money,
                        'pay_order_status' => OrderConstant::PAY_ORDER_STATUS_NONE_DEPOSIT,
                        'total_amount' => $total_fee,
                        'update_time' => date('Y-m-d H:i:s', time())
                    ];
                    $res = $order_model->update($save_data, ['order_no' => $v['order_no']]);
                    if (!$res) {
                        Db::rollback();
                        ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
                    }
                }
            } else {
                foreach ($order as $k => $v) {
                    $save_data = [
                        'only_order_no' => $only_order_no,
                        //'pay_order_status' => OrderConstant::PAY_ORDER_STATUS_ALL,
                        'total_amount' => $total_fee,
                        'update_time' => date('Y-m-d H:i:s', time())
                    ];
                    $res = $order_model->update($save_data, ['order_no' => $v['order_no']]);
                    if (!$res) {
                        Db::rollback();
                        ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
                    }
                }
            }
            Db::commit();
        } else {
            $only_order_no = $order[0]['order_no'];
        }
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['only_order_no' => $only_order_no]]);
    }


    public function get_up_user()
    {
        $level_id = request()->post('level_id');
        $user_level = model('user')->where('user_id', $this->user_id)->value('user_level');
        if ($user_level >= $level_id || !$level_id) {
            $return_arr = ['status' => 0, 'msg' => '需升级的等级有误', 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $condition = model('user_level')->where('user_level_id', $level_id)->value('condition');
        $level_name = model('user_level')->where('user_level_id', $level_id)->value('level_name');
        $order_no = $this->get_order_sn('VIP'); // 获取生成订单号

        while (model('user_level_order')->where(['order_no' => $order_no])->field('order_no')->find()) {
            $order_no = $this->get_order_sn(); // 获取生成订单号
        }

        $data['order_no'] = $order_no;
        $data['user_id'] = $this->user_id;
        $data['total_fee'] = $condition;
        $data['level_id'] = $level_id;
        $data['pay_status'] = 0;
        $data['order_time'] = time();
        $data['msg'] = '升级' . $level_name;
        model('user_level_order')->save($data);
        $return_arr = ['status' => 1, 'msg' => '提交订单成功', 'data' => ['order_no' => $order_no]]; //
        ajaxReturn($return_arr);
        /*  model('user')->save(['user_money' => $order], ['user_id' => $user_share['user_id']]);
          $data['share_id'] = $user_share['user_id'];
          $data['user_id'] = $mem['user_id'];
          $data['order_id'] = $order['order_id'];
          $data['order_no'] = $order_no;
          $data['user_money'] = $order['goods_price'];
          $data['content'] = '发展下级代理'.$mem['user_name'].',金额:'.$order['goods_price'];
          model('user_share')->save($data);*/
        //model('user')->where(['user_id' => $user_share['user_id']])->setInc('user_store', $mem);

    }

    public function certificate()
    {

        $data['order_no'] = request()->post('order_no');
        if (!$data['order_no']) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }

        $order = model('order')
            ->where(['out_trade_no|order_no' => $data['order_no']])
            ->field('pay_order_status,sure_status,order_status, is_certificate,order_no,deposit_money,total_fee')
            ->find();

        //model('order')->save(['is_certificate' => OrderConstant::ORDER_CERTIFICATE_NONE], ['order_no' => $order['order_no']]);

        if ($order['is_certificate'] == OrderConstant::ORDER_CERTIFICATE_DONE) {
            ajaxReturn(['status' => 0, 'msg' => '该订单已经不能上传凭证了']);
        }

        $data['order_no'] = $order['order_no'];
        $data['money'] = request()->post('money');
        if (!$data['money']) {
            ajaxReturn(['status' => 0, 'msg' => '请填写金额']);
        }
        $map['status'] = ['neq', 2];
        $map['order_no'] = $order['order_no'];
        $money = model('order_certificate')->where($map)->sum('money');
        if ($order['pay_order_status'] == 0) {
            if ($order['total_fee'] < $money + $data['money']) {
                ajaxReturn(['status' => 0, 'msg' => '填写的金额超出订单金额']);
            }
        } else if ($order['pay_order_status'] == 1) {
            if ($order['deposit_money'] < $money + $data['money']) {
                ajaxReturn(['status' => 0, 'msg' => '填写的金额超出定金金额']);
            }
        } else if ($order['pay_order_status'] == 2) {
            if ($order['total_fee'] < $money + $data['money']) {
                ajaxReturn(['status' => 0, 'msg' => '填写的金额超出订单金额']);
            }
        }
        $data['username'] = request()->post('username');
        if (!$data['username']) {
            ajaxReturn(['status' => 0, 'msg' => '请填写户名']);
        }
        $data['account'] = request()->post('account');
        if (!$data['account']) {
            ajaxReturn(['status' => 0, 'msg' => '请填写账号']);
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
        model('order')->save(['order_status' => 2, 'sure_status' => 0, 'pay_status' => 1, 'is_certificate' => 1], ['order_no' => $order['order_no']]);
        $return_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['order_no' => $order['order_no']]]; //
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
     * @param user_id      用户id
     * @param order_no     订单编号
     * @return  status => 0         错误
     * @return  status => 1         成功
     * @return  status => 2         用户账号被冻结
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
     * @param user_id      用户id
     * @param order_no     订单编号
     * @return  status => 0         错误
     * @return  status => 1         成功
     * @return  status => 2         用户账号被冻结
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
     * @param user_id      用户id
     * @param order_no     订单编号
     * @return  status => 0         错误
     * @return  status => 1         成功
     * @return  status => 2         用户账号被冻结
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
     * @param user_id      用户id
     * @param order_no     订单编号
     * @return  status => 0         错误
     * @return  status => 1         成功
     * @return  status => 2         用户账号被冻结
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


    /**
     * @param user_id             用户id
     * @param order_no             订单编号
     * @param store_exp_star       商铺物流
     * @param store_service_star   商铺服务
     * @param store_desc_star      商铺描述
     * @param is_name             是否匿名 1是 0 否
     * @param goods_list :商品评价信息数组
     * {
     *        order_goods_id : 订单商品id
     *      desc_star:描述相符的星星数量
     *      quality_star:商品质量的星星数量
     *      content 评价内容
     *      slide_img ：评价图片（数组、非必传）
     * }
     * @return  status => 0         错误
     * @return  status => 1         成功
     * @return  status => 2         用户账号被冻结
     * @version 评价订单
     */
    public function commentOrder()
    {

        $order_no = request()->post('order_no');
        $order_goods_id = request()->post('order_goods_id');
        if (empty($order_no) || empty($order_goods_id)) {
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

        if ($order_info['is_comment'] == 1) {
            $return_arr = ['status' => 0, 'msg' => '订单已评价', 'data' => []]; //
            ajaxReturn($return_arr);
        }

        if ($order_info['order_status'] != 4) {
            $return_arr = ['status' => 0, 'msg' => '订单不能评价', 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $order_goods_list = model('order_goods')->where(['id' => $order_goods_id])->find();
        if ($order_goods_list['comment_time'] > 0) {
            $return_arr = ['status' => 0, 'msg' => '订单商品已评价', 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $is_name = request()->post('is_name', 0);
        $desc_star = request()->post('desc_star');
        $quality_star = request()->post('quality_star');
        $service_star = request()->post('service_star');
        $content = request()->post('content');
        $data = request()->post();
        if (isset($data['slide_img'])) {
            $slide_img = $data['slide_img'];
        } else {
            $slide_img = '';
        }
        if (empty($desc_star)) {
            $return_arr = ['status' => 0, 'msg' => '请给商品选择描述打分', 'data' => []]; //
            ajaxReturn($return_arr);
        }
        if (empty($desc_star)) {
            $return_arr = ['status' => 0, 'msg' => '请给商品质量打分', 'data' => []]; //
            ajaxReturn($return_arr);
        }
        if (empty($desc_star)) {
            $return_arr = ['status' => 0, 'msg' => '请给卖家服务态度打分', 'data' => []]; //
            ajaxReturn($return_arr);
        }
        if (empty($content)) {
            $return_arr = ['status' => 0, 'msg' => '请填写评价内容', 'data' => []]; //
            ajaxReturn($return_arr);
        }

        /*foreach ($order_goods_list as $k => $v) {
            if($goods_list[$k]['order_goods_id'] != $v['id']){
                $return_arr = ['status' => 0, 'msg'=> '提交商品的评价信息顺序不对', 'data' => []]; //
                ajaxReturn($return_arr);

            }
            if(!in_array($goods_list[$k]['desc_star'],array(1,2,3,4,5))){
                $return_arr = ['status' => 0, 'msg'=> '提交商品的描述评价信息不完整', 'data' => []]; //
                ajaxReturn($return_arr);
            }
            if(!in_array($goods_list[$k]['quality_star'],array(1,2,3,4,5))){
                $return_arr = ['status' => 0, 'msg'=> '提交商品的质量评价信息不完整', 'data' => []]; //
                ajaxReturn($return_arr);
            }
            if(empty($goods_list[$k]['content'])){
                $return_arr = ['status' => 0, 'msg'=> '提交商品的内容评价信息不完整', 'data' => []]; //
                ajaxReturn($return_arr);
            }
        }*/

        //保存评价信息
        //若是直接默认审核通过，则还需要计算商品表的好评率(best_percent)、评论星级数量(star_num),否则后台审核通过时计算

        if ($slide_img) {
            $slide_img_arr = [];
           /* foreach ($slide_img as $key => $val) {
                $slide_img_arr[$key] =  to_be_included($val, getSetting('system.host'));
            }*/
            $slide_img = implode(',', $slide_img_arr);
        } else {
            $slide_img = '';
        }
        $map = [];
        $map['user_id'] = $this->user_id;
        $map['goods_id'] = $order_goods_list['goods_id'];
        $map['content'] = $content;
        $map['slide_img'] = $slide_img;
        $map['order_id'] = $order_info['id'];
        $map['order_goods_id'] = $order_goods_id;
        $map['add_time'] = date('Y-m-d H:i:s', time());
        $map['is_name'] = $is_name;
        $map['desc_star'] = $desc_star;
        $map['quality_star'] = $quality_star;
        $map['service_star'] = $service_star;

        //$map['quality_star']   = $quality_star;
        $map['status'] = 1;  //状态 0 待审核 1审核通过  2审核不通过

        $res = model('goods_comment')->save($map);

        if (!$res) {
            Db::rollback();
            $return_arr = ['status' => 0, 'msg' => '评价失败', 'data' => []]; //
            ajaxReturn($return_arr);
        }

        $res = model('order_goods')->save(['comment_time' => date('Y-m-d h:i:s', time())], ['id' => $order_goods_id]);
        if (!$res) {
            Db::rollback();
            $return_arr = ['status' => 0, 'msg' => '评价失败', 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $auto = 1;
        if ($auto == 1) {
            //若是直接默认审核通过，则还需要计算商品表的好评率(best_percent)、评论星级数量(star_num),否则后台审核通过时计算
            /*  $map = [];
            $map['goods_id'] = $order_goods_list['goods_id'];
            $map['status'] = 1;
            $desc_star = model('goods_comment')->where($map)->sum('desc_star');
            $quality_star = model('goods_comment')->where($map)->sum('quality_star');
            $service_star = model('goods_comment')->where($map)->sum('service_star');
            $counts = model('goods_comment')->where($map)->count();


            $star = $desc_star / ($counts);
            $best_percent = sprintf('%.2f', $star * 20);
            $star = sprintf('%.2f', $star);

            $data = [];
            $data['star_num'] = $star;
            $data['best_percent'] = $best_percent;
            $data['update_time'] = time();
            $res = model('goods')->save($data, ['id' => $map['goods_id']]);
          if (!$res) {
                Db::rollback();
                $return_arr = ['status' => 0, 'msg' => '评价失败', 'data' => []]; //
                ajaxReturn($return_arr);
            }*/
            //$order_info['store_id'] = $order_info['store_id']?$order_info['store_id']:1;
            //更新订单数据
            $order_goods = model('order_goods')->where(['order_id' => $order_info['id']])->select();
            $is_comment = 1;
            foreach ($order_goods as $k => $v) {
                if (!$v['comment_time']) {
                    $is_comment = 0;
                }
            }
            if ($is_comment == 1) {
                $map = [];
                $map['is_comment'] = $is_comment;
                $map['order_status'] = 5;
                $map['comment_time'] = date('Y-m-d H:i:s', time());
                $res = model('order')->save($map, $order_data);

                $storeOrder = model("store_order")->where(['parent_no' => $order_data['order_no']])->select();
                if ($storeOrder) {
                    foreach ($storeOrder as $storeOrder_k => $storeOrder_v) {
                        model('store_order')->where(['id' => $storeOrder_v['id']])->update(['order_status' => 5, 'is_comment' => $is_comment, 'comment_time' => date('Y-m-d H:i:s', time())]);
                    }
                }
            }

           /* if (!$res) {
                Db::rollback();
                $return_arr = ['status' => 0, 'msg' => '评价失败', 'data' => []]; //
                ajaxReturn($return_arr);
            }*/

            //添加供应商评价数据
            /* $map = [];
             $map['user_id'] 	 = $this->user_id;
             $map['store_id']	 = $order_info['store_id'];
             $map['order_id'] 	 = $order_info['order_id'];
             $map['service_star'] = $store_service_star;
             $map['exp_star']     = $store_exp_star;
             $map['desc_star']    = $store_desc_star;
             $map['add_time'] 	 = date('Y-m-d H:i:s',time());
             $map['is_name']      = $is_name;
             $res = model('store_comment')->add($map);
             if(!$res){
                 model()->rollback();
                 return array('status' => 0,'info'=>'评价失败','real'=>'添加商铺评价表失败');
             }

             //更新供应商表的评分和好评率
             $map = [];
             $map['store_id'] = $order_info['store_id'];
             $service_star = model('store_comment')->where($map)->sum('service_star');
             $exp_star     = model('store_comment')->where($map)->sum('exp_star');
             $desc_star    = model('store_comment')->where($map)->sum('desc_star');
             $counts       = model('store_comment')->where($map)->count();

             $star = ($service_star+$exp_star+$desc_star)/($counts*3);
             $best_percent = sprintf('%.2f',$star*20);
             $star = sprintf('%.2f',$star);

             $data = [];
             $data['star'] = $star;
             $data['best_percent'] = $best_percent;
             $data['update_time'] = date('Y-m-d H:i:s');
             $res = model('store')->where(array('id'=>$map['store_id']))->save($data);
             if(!$res){
                 model()->rollback();
                 return array('status' => 0,'info'=>'评价失败','real'=>'更新商铺表失败');
             }*/
        }
        Db::commit();
        $return_arr = ['status' => 1, 'msg' => '评价成功', 'data' => []]; //
        ajaxReturn($return_arr);
    }


    /**
     * 退款详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refundGoods()
    {
        //after_sales_status  1申请退款 2处理退款申请 3退款完毕
        //$order_no = request()->post('order_no');
        $order_goods_id = request()->post('order_goods_id');
        $after_sales_status = request()->post('after_sales_status', 1);

        if (empty($order_goods_id)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $field = 'order_id, goods_id, goods_unit, weight, goods_name,goods_num, goods_pic, goods_price, coupon_price';
        $order_goods = model('order_goods')->field($field)->where(['id' => $order_goods_id])->find();
        $order_no = model('order')->where('id', $order_goods['order_id'])->value('order_no');
        $list['goods_id'] = $order_goods['goods_id'];
        $list['order_no'] = $order_no;
        $list['goods_name'] = $order_goods['goods_name'];
        $list['goods_num'] = $order_goods['goods_num'];
        $list['goods_unit'] = $order_goods['goods_unit'];
        $list['weight'] = $order_goods['weight'];
        $list['goods_pic'] = $order_goods['goods_pic'];
        $list['goods_price'] = $order_goods['goods_price'] - $order_goods['coupon_price'];


        if ($after_sales_status != 1) {//增加售后编号信息
            $field = ['refund_reason', 'refund_type', 'refund_price', 'refund_hw_status', 'refund_instructions', 'refund_add_time'];
            $data['refund'] = model('refund')->where("order_no", $order_no)->field($field)->find();
        }

        if ($after_sales_status == 3) {
            //退款地址信息
            $field = ['consignee', 'telephone', 'province', 'province', 'district', 'place'];
            $data['address'] = model('order')->where('id', $order_goods['order_id'])->field($field)->find();
            $data['express'] = model('express')->field(['id', 'express_company'])->select();
        }
        if ($after_sales_status == 4) {
            //钱款去向
        }

        $result = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $list];
        exit(json_encode($result));
    }


    public function expressAdd()
    {
        $express_id = request()->post('express_id');
        $refund_order_syn = request()->post('refund_order_syn');
        if (empty($express_id)) {
            $result = ['status' => 0, 'msg' => '请选择物流公司'];
        }
        if (empty($refund_order_syn)) {
            $result = ['status' => 0, 'msg' => '请填写物流单号'];
        }
        model('express')->save(['express_id'=>$express_id,'refund_order_syn'=>$refund_order_syn],['']);

    }

    /**
     * @param user_id      用户id
     * @param order_no     订单编号
     * @param reason       退款原因
     * @param desc         退款详情描述
     * @param slide_img    评价图片（数组、非必传）
     * @return  status => 0         错误
     * @return  status => 1         成功
     * @return  status => 2         用户账号被冻结
     * @version 订单退款/退还售后申请
     */
    public function applyAfterSales()
    {

        $order_goods_id = request()->post('order_goods_id');
        if (empty($order_goods_id)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $order_goods = model('order_goods')->field('order_id, goods_id, goods_name, goods_pic, goods_price, coupon_price')->where(['id' => $order_goods_id])->find();

        $order_res = model('order')->where(['id' => $order_goods['order_id']])->find();

        if (empty($order_res)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]; //
            ajaxReturn($return_arr);
        }

        if ($order_res['pay_status'] == 0) {
            $return_arr = ['status' => 0, 'msg' => '订单未支付不能退款', 'data' => []]; //
            ajaxReturn($return_arr);
        }

        $max_price = $order_goods['goods_price'] - $order_goods['coupon_price'];

        $refund_type = request()->post("refund_type");
        if (empty($refund_type)) {
            $return_arr = ['status' => 0, 'msg' => '请选择退款类型', 'data' => []]; //
            ajaxReturn($return_arr);
        }

        $refund_hw_status = request()->post("refund_hw_status");

        if ($refund_hw_status == '') {
            $return_arr = ['status' => 0, 'msg' => '请选择货物状态', 'data' => []]; //
            ajaxReturn($return_arr);
        }

        $refund_reason = request()->post("refund_tkyy", '', 'trim');
        if (empty($refund_reason)) {
            $return_arr = ['status' => 0, 'msg' => '请选择退款原因', 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $refund_price = request()->post("price", '', 'trim');
        if (empty($refund_price)) {
            $return_arr = ['status' => 0, 'msg' => '请填写退款金额', 'data' => []]; //
            ajaxReturn($return_arr);
        }
        if ($refund_price > $max_price) {
            $return_arr = ['status' => 0, 'msg' => '退款金额超出最大限制，请重填', 'data' => []]; //
            ajaxReturn($return_arr);
        }
        $refund_instructions = request()->post("refund_tksm", '', 'trim');
        if (empty($refund_instructions)) {
            $return_arr = ['status' => 0, 'msg' => '请填写退款说明', 'data' => []]; //
            ajaxReturn($return_arr);
        }


        /* $refund_pic1 = request()->post("refund_pic1", '', 'trim');
         $refund_pic2 = request()->post("refund_pic2", '', 'trim');
         $refund_pic3 = request()->post("refund_pic3", '', 'trim');
         $refund_pic4 = request()->post("refund_pic4", '', 'trim');
         $refund_pic5 = request()->post("refund_pic5", '', 'trim');*/


        if ($order_res) {
            $data = [
                "user_id" => $this->user_id,
                "order_no" => $order_res['order_no'],
                "order_id" => $order_goods_id,
                "refund_type" => $refund_type,
                "refund_reason" => $refund_reason,
                "refund_instructions" => $refund_instructions,
                "refund_add_time" => date('Y-m-d H:i:s'),
                "refund_status" => 1,
            ];

            if ($refund_type == 1 || $refund_type == 2) {
                $data["refund_price"] = $refund_price;
            }

            if ($refund_type == 2) {
                if ($refund_hw_status == 1) {
                    $data["refund_hw_status"] = 1;
                } else {
                    $data["refund_hw_status"] = 0;
                }
            }
            /*if($refund_pic1){
                $data["refund_pic1"] = $refund_pic1;
            }
            if($refund_pic2){
                $data["refund_pic2"] = $refund_pic2;
            }
            if($refund_pic3){
                $data["refund_pic3"] = $refund_pic3;
            }
            if($refund_pic4){
                $data["refund_pic4"] = $refund_pic4;
            }
            if($refund_pic5){
                $data["refund_pic5"] = $refund_pic5;
            }*/

            $yz_refund = model("refund_list")->where(["user_id" => $this->user_id, "order_no" => $order_res['order_no'], "order_id" => $order_goods_id])->count();
            if ($yz_refund) {
                $return_arr = ['status' => 0, 'msg' => '请勿重复提交', 'data' => []]; //
                ajaxReturn($return_arr);
            }
            $res = model("refund_list")->save($data);
            if ($res) {
                $new_info_data = [
                    "is_apply_refund" => $refund_type,
                    "is_refund" => 3,
                    "refund_money" => $refund_price,
                ];
                model("order_goods")->save($new_info_data, ["id" => $order_goods_id]);
                $is_apply_refund = model("order_goods")->where(["order_id" => $order_res['id']])->field('is_apply_refund')->select();
                $apply_service = 0;
                foreach ($is_apply_refund as $k => $v) {
                    if ($v['is_apply_refund'] == 1) {
                        $apply_service++;
                    }
                }
                if ($apply_service < count($is_apply_refund)) {
                    $apply_service = 1;
                } else {
                    $apply_service = 2;
                }
                model("order")->save([
                    'apply_service' => $apply_service,
                    'order_status' => OrderConstant::ORDER_STATUS_APPLY_REFUND
                ], ["id" => $order_res['id']]);
                //更改子订单退款状态
                $storeOrder = model("store_order")->where(['parent_no' => $order_res['order_no']])->select();
                if ($storeOrder) {
                    foreach ($storeOrder as $storeOrder_k => $storeOrder_v) {
                        model('store_order')->where(['id' => $storeOrder_v['id']])->update(['order_status' => 11]);
                        $storeOrderGoods = model('store_order_goods')->where(['order_id' => $storeOrder_v['id']])->select();
                        foreach ($storeOrderGoods as $storeOrderGoods_k => $storeOrderGoods_v){
                            model('store_order_goods')->where(['id' => $storeOrderGoods_v['id']])->update(['is_apply_refund'=>1]);
                        }
                    }
                }
                $return_arr = ['status' => 1, 'msg' => '您已成功提交申请', 'data' => []]; //
                ajaxReturn($return_arr);
            } else {
                $return_arr = ['status' => 0, 'msg' => '申请失败', 'data' => []]; //
                ajaxReturn($return_arr);
            }
        }
    }


    /**
     * 预付定金提交 生成支付编号
     */
    public function payment()
    {
        $id = request()->post('id');
        $name = request()->post('name');
        $province_id = request()->post('province_id');
        $city_id = request()->post('city_id');
        $district_id = request()->post('district_id');
        $place = request()->post('place');
        $telephone = request()->post('telephone');
        $money = request()->post('money');
        $pay_way = request()->post('pay_way');
        $source = request()->post('source');
        $order_no = $this->get_order_sn(OrderConstant::ORDER_NO_DJ_PREFIX);
        if (!$name) {
            ajaxReturn(['status' => 0, 'msg' => '请填写姓名']);
        }
        if (!$province_id) {
            ajaxReturn(['status' => 0, 'msg' => '请选择省']);
        }
        if (!$place) {
            ajaxReturn(['status' => 0, 'msg' => '请填写详细地址']);
        }
        if (!$telephone) {
            ajaxReturn(['status' => 0, 'msg' => '请填写手机号码']);
        }
        if (!$this->VerifyTelephone($telephone)) {
            ajaxReturn(['status' => 0, 'msg' => '手机号码格式不正确']);
        }
        if (!$money) {
            ajaxReturn(['status' => 0, 'msg' => '请填写定金金额']);
        }
        $min_money = 5000;
        if ($money < $min_money) {
            ajaxReturn(['status' => 0, 'msg' => '定金金额不能低于'.$min_money]);
        }
        if (!$pay_way) {
            ajaxReturn(['status' => 0, 'msg' => '请选择支付方式']);
        }
        $data = [
            'name' => $name,
            'user_id' => $this->user_id,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'district_id' => $district_id,
            'place' => $place,
            'telephone' => $telephone,
            'money' => $money,
            'total_money' => $money,
            'pay_way' => $pay_way,
            'pay_status' => 0,
            'order_no' => $order_no,
            'source' => $source,
        ];
        if ($id) {
            model('payment')->save($data, ['id' => $id]);
        } else {
            model('payment')->save($data);
        }
        ajaxReturn(['status' => 1,
            'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS,
            'data' => ['order_no' => $order_no]
        ]);
    }

    /**
     * 预付定金列表
     */
    public function paymentList()
    {
        $payment_model = model('payment');
        $list_row = input('post.list_row', 10); //每页数据
        $page = input('post.page', 1); //当前页
        $where = ['user_id' => $this->user_id];
        $totalCount = $payment_model->where($where)->count();
        $first_row = ($page-1)*$list_row;
        $pay_status = request()->param('pay_status');
        if ($pay_status != '') {
            $where['pay_status'] = $pay_status;
        }
        $field = ['id','order_no','name','province_id','city_id', 'district_id', 'place', 'telephone', 'money', 'pay_way', 'pay_status'];
        $lists = $payment_model->where($where)->field($field)->limit($first_row, $list_row)->order('create_time desc')->select();
        foreach ($lists as $k => $v) {
            $lists[$k]['province'] = model('region')->where(['id' => $v['province_id']])->value('name');
            $lists[$k]['city'] = model('region')->where(['id' => $v['city_id']])->value('name');
            $lists[$k]['district'] = model('region')->where(['id' => $v['district_id']])->value('name');
            if ($v['pay_way'] == OrderConstant::ORDER_PAY_WAY_ALIPAY) {
                $lists[$k]['pay_way_name'] = '支付宝';
            } else if ($v['pay_way'] == OrderConstant::ORDER_PAY_WAY_WXPAY) {
                $lists[$k]['pay_way_name'] = '微信';
            } else {
                $lists[$k]['pay_way_name'] = '未知';
            }
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

    public function paymentRequest()
    {
        $order_no = request()->post('order_no');
        $order = model('payment')->where(['order_no' => $order_no])->field('pay_status')->find();
        if ($order['pay_status'] == 1) {
            ajaxReturn(['status' => 1, 'msg' => '支付成功', 'data' => $order]);
        } else if($order['pay_status'] == 0) {
            ajaxReturn(['status' => 0, 'msg' => '未支付']);
        } else {
            ajaxReturn(['status' => 0, 'msg' => '未支付']);
        }
    }
}