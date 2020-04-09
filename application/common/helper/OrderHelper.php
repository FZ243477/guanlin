<?php


namespace app\common\helper;

use app\common\constant\MoneyWaterConstant;
use app\common\constant\OrderConstant;
use app\common\constant\IntegralConstant;
use app\common\constant\PreferentialConstant;
use app\common\constant\SystemConstant;
use app\common\constant\UserLevelConstant;
use think\db;

trait OrderHelper
{
    private function set_btn_order_status($order)
    {
        $order['order_status_code'] = $order_status_code = $this->orderStatusDesc(0, $order); // 订单状态显示给用户看的
        $order['order_status_desc'] = OrderConstant::order_status_array_value($order_status_code);
        $order['order_btn'] = $orderBtnArr = $this->orderBtn(0, $order);
        return $order; // 订单该显示的按钮
    }

    /**
     * 获取订单状态的 中文描述名称
     * @param type $order_id 订单id
     * @param type $order 订单数组
     * @return string
     */
    private function orderStatusDesc($order_id = 0, $order = array())
    {
        if (empty($order)) {
            $order = model('Order')->where("id", $order_id)->find();
        }
        /*// 货到付款
        if($order['pay_code'] == 'cod')
        {
            if(in_array($order['order_status'],array(0,1)) && $order['shipping_status'] == 0)
                return OrderConstant::ORDER_STATUS_WAIT_SEND_NAME; //'待发货',
        }
        else // 非货到付款
        {*/
        if ($order['pay_status'] == 0 && $order['order_status'] == 1) {
            return OrderConstant::ORDER_STATUS_WAIT_PAY; //'待支付',
        }


        if ($order['order_status'] == 2) {
            if ($order['sure_status'] == 0) {
                return OrderConstant::ORDER_STATUS_AUDIT_ORDER; //'待审核',
            } else if ($order['sure_status'] == 1) {
                return OrderConstant::ORDER_STATUS_WAIT_SEND; //'待发货',
            }
        }
        if ($order['order_status'] == 7) {
            return OrderConstant::ORDER_STATUS_FINAL_ORDER; //'待付尾款',
        }
        /*if($order['pay_status'] == 1 &&  $order['shipping_status'] == 2 && $order['order_status'] == 1)
            return 'PORTIONSEND'; //'部分发货',
//        }*/
        if ($order['order_status'] == 3)
            return OrderConstant::ORDER_STATUS_WAIT_RECEIVE; //'待收货',
        if ($order['order_status'] == 4)
            return OrderConstant::ORDER_STATUS_WAIT_COMMENT; //'待评价',
        if ($order['order_status'] == 0)
            return OrderConstant::ORDER_STATUS_CANCEL; //'已取消',
        if ($order['order_status'] == 5)
            return OrderConstant::ORDER_STATUS_FINISH_ORDER; //'已完成',
//        if ($order['order_status'] == 6)
//            return OrderConstant::ORDER_STATUS_CERTIFICATE_ORDER; //'上传凭证',
//        if ($order['order_status'] == 7)
//            return OrderConstant::ORDER_STATUS_FINAL_ORDER; //'待付尾款',
        if ($order['order_status'] == 10)
            return OrderConstant::ORDER_STATUS_UN_REFUND; //'已完成',
        if ($order['order_status'] == 11)
            return OrderConstant::ORDER_STATUS_APPLY_REFUND; //'已完成',
        if ($order['order_status'] == 12)
            return OrderConstant::ORDER_STATUS_FINISH_REFUND; //'已完成',

    }

    /**
     * 获取订单状态的 显示按钮
     * @param type $order_id 订单id
     * @param type $order 订单数组
     * @return array()
     */
    private function orderBtn($order_id = 0, $order = array())
    {
        if (empty($order)) {
            $order = model('Order')->where("id", $order_id)->find();
        }
        /**
         *  订单用户端显示按钮
         * 去支付     AND pay_status=0 AND order_status=0 AND pay_code ! ="cod"
         * 取消按钮  AND pay_status=0 AND shipping_status=0 AND order_status=0
         * 确认收货  AND shipping_status=1 AND order_status=0
         * 评价      AND order_status=1
         * 查看物流  if(!empty(物流单号))
         */
        $btn_arr = array(
            'pay_btn' => 0, // 去支付按钮
            'cancel_btn' => 0, // 取消按钮
            'receive_btn' => 0, // 确认收货
            'comment_btn' => 0, // 评价按钮
            'shipping_btn' => 0, // 查看物流
            'certificate_btn' => 0, // 是否要凭证
            'is_confirm_integration' => 0, // 是否到达中转站
            'return_btn' => 0, // 退货按钮 (联系客服)
        );


        /* // 货到付款
         if($order['pay_code'] == 'cod')
         {
             if(($order['order_status']==0 || $order['order_status']==1) && $order['shipping_status'] == 0) // 待发货
             {
                 $btn_arr['cancel_btn'] = 1; // 取消按钮 (联系客服)
             }
             if($order['shipping_status'] == 1 && $order['order_status'] == 1) //待收货
             {
                 $btn_arr['receive_btn'] = 1;  // 确认收货
                 $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
             }
         }*/
        // 非货到付款
        if ($order['pay_status'] == 0 && $order['order_status'] == 1) // 待支付
        {
            $btn_arr['pay_btn'] = 1; // 去支付按钮
            $btn_arr['cancel_btn'] = 1; // 取消按钮
        }
        // 待付尾款
        if ($order['pay_status'] == 1 && $order['pay_order_status'] == 2 && $order['sure_status'] == 1) // 待支付
        {
            $btn_arr['pay_btn'] = 1; // 去支付按钮
        }
        if ($order['pay_status'] == 1 && $order['order_status'] == 2) // 待发货
        {
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }
        if ($order['pay_status'] == 1 && $order['order_status'] == 3 && $order['shipping_time'] > 0) //待收货
        {
            $btn_arr['receive_btn'] = 1;  // 确认收货
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }

        if ($order['is_confirm_integration'] == 1) {
            $btn_arr['is_confirm_integration'] = 1; //是否到达中转站
        }

        if ($order['order_status'] == 4) {
            $btn_arr['comment_btn'] = 1;  // 评价按钮
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }
        if ($order['is_shipping'] == 1) {
            $btn_arr['shipping_btn'] = 1; // 查看物流
        }
        if ($order['is_certificate'] == 1 && ($order['order_status'] == OrderConstant::ORDER_STATUS_WAIT_SEND
            ||$order['order_status'] == OrderConstant::ORDER_STATUS_FINAL_ORDER)) {
            $btn_arr['certificate_btn'] = 1; // 是否要凭证
        }
        /*if($order['shipping_status'] == 2 && $order['order_status'] == 1) // 部分发货
        {
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }*/

        return $btn_arr;
    }

    private function get_order_sn($pre = OrderConstant::ORDER_NO_STR_PREFIX)
    {

        /*
	        date('Ymd')：这个很容易理解，是在最前方拼接一个当前年月日组成的数字。
	        uniqid()：此函数获取一个带前缀、基于当前时间微秒数的唯一ID。
	        substr(uniqid(), 7, 13)：由于uniqid()函数生成的结果前面7位很久才会发生变化，所以有或者没有对于我们没有多少影响，所以我们截取后面经常发生变化的几位。
	        str_split(substr(uniqid(), 7, 13),1)：我们将刚刚生成的字符串进行分割放到数组里面，str_split()第二个参数是每个数组元素的长度。
	        array_map('ord', str_split(substr(uniqid(), 7, 13),1)))：其中array_map()函数作用为：函数返回用户自定义函数作用后的数组，意思就是ord是函数ord(),而后面第二个参数是ord()函数的参数。可以这么理解ord(str_split(substr(uniqid(),7, 13), 1)))。然后ord()是干啥的，ord()函数php内置函数：返回字符串的首个字符的 ASCII值，意思就是把第二个参数生成的数组每个元素全部转换为数字，因为刚刚我们截取的字符串中含有字母，不适合订单号。
	        implode()：很简单了，把刚刚那个转换成数字的数字在拼接成为一个数字。
	        由于刚刚生成的随机数可能会长短不一（原因就是，每个字符转换为ASCII值可能不一样，有些是2位，有些可能是一位），所以我们同意截取0-8
	        然后加上刚刚的日期数字，现在就凑成了一个等长的高大上的订单号了~
	     */
        $order_no = $pre . date('YmdHis', time()) . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 10), 1))), 0, 4); // 获取生成订单号
        return $order_no;
    }

    /**
     * 根据订单支付成功的操作功能(金额支付不包含积分支付)
     * 1.更新订单状态
     * 2.商品减少库存、增加销量
     * 3.记录流水账
     * 4.用户积分改变
     * 5.抵扣积分（积分流水记录、用户积分扣除）
     * 6.销售提成
     * @param $order_no 支付号 多次提交时避免重复(最后3位随机数，支付成功回调只匹配前几位，不包含后3位)
     * @param string $transaction_id
     * @param int $total_fee
     * @param int $pay_way 支付方式：1支付宝 2微信支付 3银联支付 4余额支付 5积分支付
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    private function successPayOperation($order_no, $transaction_id = '', $total_fee = 0, $pay_way = 0)
    {

        $m = model('order');
        $mg = model('order_goods');

        $map = [];
        $map['out_trade_no'] = $order_no;
        $order_list = $m->where($map)->select();
        if (!$order_list) {
            $return = ['status' => 0, 'msg' => '订单不存在'];
            $msg = $order_no.'订单不存在';
            $this->addToOrderErrorLog(0, 0, $msg);
            return $return;
        }

        foreach ($order_list as $order) {

            Db::startTrans();

            $user_model = model('user');

            $mem = $user_model->find($order['user_id']);

            if ($order['payment_id']) {
                $payment = model('payment')->where(['id' => $order['payment_id']])->find();
                $use_money = $payment['use_money'] + $order['payment_money'];
                $money = $payment['total_money'] - $use_money;
                if ($money > 0) {
                    $is_use = 0;
                } else {
                    $is_use = 1;
                }
                model('payment')->save([
                    'money' => $money,
                    'use_money' => $use_money,
                    'is_use' => $is_use,
                ], ['id' => $order['payment_id']]);
            }
            //判断抵扣的积分是否足够
            $integral = $order['integral'];

            if ($integral > 0) {

                if ($integral > $mem['integral']) {
                    // 这里记入错误日志
                    Db::rollback();
                    $msg = '用户积分不足以抵扣(支付),支付失败！';
                    $this->addToOrderErrorLog($order['user_id'], $order['id'], $msg);
                    return ['status' => 0, 'msg' => $msg];
                }

                //用户账户积分减少
                //积分流水记录
                $res = $this->integral_record(
                    IntegralConstant::INTEGRAL_CATE_TYPE_ORDER_BUY,
                    $integral,
                    IntegralConstant::INTEGRAL_USE_TYPE_ORDER_BUY,
                    1,
                    $order['user_id'],
                    1,
                    $order['id'],
                    $transaction_id,
                    $pay_way
                );

                if (!$res || $res['status'] == 0) {
                    // 这里记入错误日志
                    Db::rollback();
                    $msg = '用户积分扣除失败！';
                    $this->addToOrderErrorLog($order['user_id'], $order['id'], $msg);
                    return ['status' => 0, 'msg' => $msg];
                }
            }
            $res = $this->moneyWater(
                $order['id'],
                $pay_way,
                $order['user_id'],
                $total_fee,
                0,
                0,
                MoneyWaterConstant::MONEY_WATER_TYPE_SUB,
                MoneyWaterConstant::MONEY_WATER_STATUS_SUCCESS,
                MoneyWaterConstant::MONEY_WATER_CATE_ORDER_BUY,
                '订单消费',
                $transaction_id,
                $order['partner_id']
            );
            if (!$res) {
                Db::rollback();
                $this->addToOrderErrorLog($order['user_id'], $order['id'], "记入流水账失败，订单号：" . $order_no . "，支付交易号：{$transaction_id}，信息：记入流水账失败");
                return ['status' => 0, 'msg' => "记入流水账失败"];
            }

            $money_exchange_integral_one = getSetting('integral.money_exchange_integral_one');
            $money_exchange_integral_all = getSetting('integral.money_exchange_integral_all');
            $money_exchange = intval($total_fee * $money_exchange_integral_all / $money_exchange_integral_one);

            if ($money_exchange > 0) {
                //积分流水记录
                $res = $this->integral_record(
                    IntegralConstant::INTEGRAL_CATE_TYPE_ORDER_GIVE,
                    $money_exchange,
                    IntegralConstant::INTEGRAL_USE_TYPE_ORDER_GIVE,
                    0,
                    $order['user_id'],
                    1,
                    $order['id'],
                    $transaction_id,
                    $pay_way
                );

                if (!$res || $res['status'] == 0) {
                    // 这里记入错误日志
                    //Db::rollback();
                    $msg = '用户获得积分失败！';
                    $this->addToOrderErrorLog($order['user_id'], $order['id'], $msg);
                    //return ['status'=>0,'msg'=>$msg];
                }
            }

            //成本价
            $goods_num = 0;
            $goods_cost = 0;

            if ($order['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_DOING_END) {
                $msg = '尾款订单不能支付1';
                $this->addToOrderErrorLog($order['user_id'], $order['id'], $msg);
                return ['status' => 0, 'msg' => $msg];
            } else if ($order['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_ALL){
                if ($order['pay_status'] == OrderConstant::PAY_STATUS_DOING) {
                    $msg = '订单不能支付2';
                    $this->addToOrderErrorLog($order['user_id'], $order['id'], $msg);
                    return ['status' => 0, 'msg' => $msg];
                }
            }

            $data = [
                'pay_status' => OrderConstant::PAY_STATUS_DOING,
                'trade_no' => $transaction_id,
                'sure_status' => 0,
                'is_certificate' => 0,
                'order_no' => $order_no,
                'only_order_no' => $order_no,
                'order_status' => OrderConstant::ORDER_STATUS_WAIT_SEND,
                'pay_price' => $total_fee,
                'paid_money' => $total_fee+$order['paid_money'],
                'pay_way' => $pay_way,
                'pay_time' => date('Y-m-d H:i:s', time()),
            ];


            if ($order['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_NONE_DEPOSIT) {
                //定金支付，未付定金 改为 定金支付已付定金
                $data['pay_order_status'] = OrderConstant::PAY_ORDER_STATUS_DOING_DEPOSIT;
            } else if ($order['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_DOING_DEPOSIT) {
                //定金支付，已付定金 改为 定金支付已付尾款
                $data = [];
                $data['pay_order_status'] = OrderConstant::PAY_ORDER_STATUS_DOING_END;
                $data['order_status'] = OrderConstant::ORDER_STATUS_WAIT_SEND;
                $data['sure_status'] = 0;
                $data['is_certificate'] = 0;
                $data['paid_money'] = $total_fee+$order['paid_money'];
                $data['trade_final_no'] = $transaction_id;
                $data['pay_final_price'] = $order['total_fee'] - $order['deposit_money'];
                $data['pay_final_way'] = $pay_way;
                $data['pay_final_time'] = date('Y-m-d H:i:s', time());

                $datas = [];
                $datas['order_status'] = OrderConstant::ORDER_STATUS_WAIT_SEND;
                $datas['pay_order_status'] = OrderConstant::PAY_ORDER_STATUS_DOING_END;
                model('StoreOrder')->update($datas, ['parent_no' => $order['order_no']]);
                $this->accountLogs($order['id'], $pay_way);
            } else {
                $this->accountLogs($order['id'], $pay_way);
            }

            $res = $m->update($data, ['id' => $order['id']]);
            if (!$res) {
                Db::rollback();
                // 这里记入错误日志
                $msg = '用户支付成功，订单改变状态失败！';
                $this->addToOrderErrorLog($order['user_id'], $order['id'], $msg);
                return ['status' => 0, 'msg' => $msg];
            }


            //减少库存和增加销量
            $order_goods = $mg->where('order_id', $order['id'])->select();
            $mg->save(['order_no' => $order_no], ['order_id' => $order['id']]);
            //$mg->update(['order_no' => $order['out_trade_no']], ['order_id' => $order['id']]);
            foreach ($order_goods as $kk => $vv) {
                //减少库存，增加销量
                $goods_map = [];
                $goods_map['id'] = $vv['goods_id'];
                $goods = model('goods')->where($goods_map)->find();
                //$goods_cost += $goods['goods_cost'] * $vv['goods_num'];
                $goods_num += $vv['goods_num'];
                $goods_data = [];
//                $goods_data['stores'] = $goods['stores'] - $vv['goods_num'];
                $goods_data['sales'] = $goods['sales'] + $vv['goods_num'];
                $res = model('goods')->update($goods_data, $goods_map);
                if ($vv['sku_id']) {
//                    model('spec_goods_price')->where(['key' => $vv['sku_id'], 'goods_id' => $vv['goods_id']])->setInc('store_count', $vv['goods_num']);
                }
                if ($vv['prom_id'] != 0) {
                    if ($vv['prom_type'] == PreferentialConstant::PREFERENTIAL_TYPE_LIMIT_SALES) {
                        model('limit_sales')->where(['limit_sales_id' => $vv['prom_id']])->setInc('sales_num', $vv['goods_num']);
                    }
                    /*if ($vv['prom_type'] == PreferentialConstant::PREFERENTIAL_TYPE_POPULAR) {
                        model('popular')->where(['popular_id' => $vv['prom_id']])->setInc('sales_num', $vv['goods_num']);
                    }
                    if ($vv['prom_type'] == PreferentialConstant::PREFERENTIAL_TYPE_NEW_GOODS) {
                        model('new_goods')->where(['new_goods_id' => $vv['prom_id']])->setInc('sales_num', $vv['goods_num']);
                    }*/
                }
                if (!$res) {
                    Db::rollback();
                    $msg = '减少商品库存,增加销量失败！';
                    $this->addToOrderErrorLog($order['user_id'], $order['id'], $msg);
                    return ['status' => 0, 'msg' => $msg];
                }
            }
            //$this->orderSplit($order['id']);
            $user_model->update(['order_money' => $total_fee], ['id' => $order['user_id']]);
            //model('rebate_log')->update(['status' => 1], ["order_id" => $order['id']]);
        }

        Db::commit();
        // 给用户发送系统消息
        //A('Common/Base')->add_message_log($order['user_id'],'订单支付成功','订单(订单号：'.$order_no.')支付成功！',1);
        return ['status' => 1, 'msg' => '支付成功'];
    }


    /**
     * 预付定金
     * @param $order_no
     * @param string $transaction_id
     * @param int $total_fee
     * @param int $pay_way
     */
    private function successPaymentOperation($order_no, $transaction_id = '', $total_fee = 0, $pay_way = 0)
    {
        $m = model('payment');
        $map = [];
        $map['order_no'] = $order_no;
        $payment = $m->where($map)->find();
        if (!$payment) {
            $msg = $order_no.'订单不存在';
            $return = ['status' => 0, 'msg' => $msg];
            $this->log_result("./wxpayment_log.txt", $msg);
            return $return;
        }
        $data = ['trade_no' => $transaction_id, 'pay_way' => $pay_way, 'pay_status' => OrderConstant::PAY_STATUS_DOING];
        $res = $m->save($data, ['id' => $payment['id']]);
        if (!$res) {
            $msg = $order_no.'用户支付成功，状态修改失败！';
            $this->log_result("./payment_log.txt", $msg);
            return ['status' => 0, 'msg' => $msg];
        }
    }


    //拆分订单
    public function orderSplit($oder_id)
    {

        $data = model('order')->where(['id' => $oder_id])->find();
        $store_order = model('store_order')->where(['parent_no' => $data['order_no']])->select();
        if ($store_order) {
            $store_data['pay_order_status'] = $data['pay_order_status'];
            $store_data['order_status'] = $data['order_status'];
            $store_data['pay_status'] = $data['pay_status'];
            $store_data['sure_status'] = $data['sure_status'];
            $store_data['pay_price'] = $data['pay_price'];
            model('store_order')->save($store_data, ['parent_no' => $data['order_no']]);
        } else {
            $orderGoods = model('OrderGoods')->where(['order_id' => $oder_id])->field('goods_id')->select();
            $store_goods_id = [];

            foreach ($orderGoods as $k => $v) {
                $store_id = model('Goods')->where(['id' => $v['goods_id']])->value('store_id');
                if ($store_id) {
                    $store_goods_id[$store_id][] = $v['goods_id'];
                }
            }

            if ($store_goods_id) {
                Db::startTrans();
                foreach ($store_goods_id as $store_id => $value) {
                    $order_store_goods = model("OrderGoods")->where(['order_id' => $data['id'], 'store_id' => $store_id])->select();
                    $coupon_price = 0;
                    foreach ($order_store_goods as $k => $v) {
                        $coupon_price += $v['coupon_price']*$v['goods_num'];
                    }
                    $store_order_money = 0;
                    $store_data = [];
                    foreach ($value as $k => $goods_id) {
                        $store_good = model('Goods')->where(['id' => $goods_id])->find();
                        $store_order_money += $store_good['price'];
                    }
                    // 插入订单 order
                    $order_no = $this->get_order_sn(OrderConstant::ORDER_NO_STORE_PREFIX); // 获取生成订单号

                    while (model('StoreOrder')->where(['order_no' => $order_no])->field('order_no')->find()) {
                        $order_no = $this->get_order_sn(OrderConstant::ORDER_NO_STORE_PREFIX); // 获取生成订单号
                    }
                    if ($data['order_status'] == OrderConstant::ORDER_STATUS_WAIT_RECEIVE)  {
                        $data['order_status'] = OrderConstant::ORDER_STATUS_WAIT_SEND;
                    }
                    $store_data['store_id'] = $store_id;
                    $store_data['order_no'] = $order_no;
                    $store_data['coupon_price'] = $coupon_price;
                    $store_data['ship_status'] = $data['ship_status'];
                    $store_data['parent_no'] = $data['order_no'];
                    $store_data['out_trade_no'] = $data['out_trade_no'];
                    $store_data['partner_id'] = $data['partner_id'];
                    $store_data['pay_time'] = $data['pay_time'];
                    $store_data['order_status'] = $data['order_status'];
                    $store_data['pay_status'] = $data['pay_status'];
                    $store_data['pay_way'] = $data['pay_way'];
                    $store_data['pay_price'] = $data['pay_price'];
                    $store_data['sure_status'] = $data['sure_status'];
                    $store_data['only_order_no'] = $data['only_order_no'];
                    $store_data['user_id'] = $data['user_id'];
                    $store_data['address_id'] = $data['address_id'];
                    $store_data['consignee'] = $data['consignee'];
                    $store_data['province'] = $data['province'];
                    $store_data['province_id'] = $data['province_id'];
                    $store_data['city'] = $data['city'];
                    $store_data['city_id'] = $data['city_id'];
                    $store_data['district'] = $data['district'];
                    $store_data['district_id'] = $data['district_id'];
                    $store_data['sure_status'] = $data['sure_status'];
                    $store_data['place'] = $data['place'];
                    $store_data['telephone'] = $data['telephone'];
                    $store_data['total_price'] = $store_order_money;
                    $store_data['total_fee'] = $data['total_fee'];
                    $store_data['order_time'] = $data['order_time'];
                    $store_data['source'] = $data['source'];
                    $store_data['type'] = $data['type'];
                    $store_data['pay_order_status'] = $data['pay_order_status'];
                    $res = model('StoreOrder')->insert($store_data);
                    if (!$res) {
                        Db::rollback();
                        return ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE];
                    }
                    $order_id = model('StoreOrder')->getLastInsID();

                    foreach ($order_store_goods as $order_store_goods_k => $order_store_goods_v) {
                        $store_goods['user_id'] = $order_store_goods_v['user_id'];
                        $store_goods['order_id'] = $order_id;
                        $store_goods['is_purchase'] = 0;
                        $store_goods['order_no'] = $store_data['order_no'];
                        $store_goods['parent_no'] = $store_data['parent_no'];
                        $store_goods['partner_id'] = $store_data['partner_id'];
                        $store_goods['order_goods_id'] = $order_store_goods_v['id'];
                        $store_goods['goods_id'] = $order_store_goods_v['goods_id'];
                        $store_goods['store_id'] = $order_store_goods_v['store_id'];
                        $store_goods['goods_name'] = $order_store_goods_v['goods_name'];
                        $store_goods['goods_num'] = $order_store_goods_v['goods_num'];
                        $store_goods['goods_unit'] = $order_store_goods_v['goods_unit'];
                        $store_goods['goods_code'] = $order_store_goods_v['goods_code'];
                        $store_goods['goods_remark'] = $order_store_goods_v['goods_remark'];
                        $store_goods['goods_price'] = $order_store_goods_v['goods_price'];
                        $store_goods['goods_oprice'] = $order_store_goods_v['goods_oprice'];
                        $store_goods['b_price'] = $order_store_goods_v['b_price'];
                        $store_goods['cost_price'] = $order_store_goods_v['cost_price'];
                        $store_goods['goods_pay_price'] = $order_store_goods_v['goods_pay_price'];
                        $store_goods['goods_pic'] = $order_store_goods_v['goods_pic'];
                        $store_goods['goods_pic'] = $order_store_goods_v['goods_pic'];
                        $store_goods['sku_id'] = $order_store_goods_v['sku_id'];
                        $store_goods['sku_info'] = $order_store_goods_v['sku_info'];
                        $res =  model('StoreOrderGoods')->insert($store_goods);
                        if (!$res) {
                            Db::rollback();
                            return ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE];
                        }
                    }

                }
            }
        }
        Db::commit();
        return ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS];
    }

    //整合订单
    public function orderCombine($order_id)
    {
        if (!$order_id) {
            $return_arr = ['status' => 0, 'msg' => '没有订单编号', 'data' => []]; //
            return $return_arr;
        }
        $store_order = model('store_order')->where(['id' => $order_id])->select();
        if (!$store_order) {
            $return_arr = ['status' => 0, 'msg' => '没有订单', 'data' => []]; //
            return $return_arr;
        }
        $store_order_goods = model('store_order_goods')->where(['order_id' => $order_id])->select();
        if (!$store_order_goods) {
            $return_arr = ['status' => 0, 'msg' => '没有订单商品', 'data' => []]; //
            return $return_arr;
        }
        //开启事务
        Db::startTrans();
        //修改供应商订单状态
        $res = model('store_order')->isUpdate(true)->save([
            'is_confirm_integration' => 1,
            'confirm_integration_time' => time()
        ], [
            'id' => $order_id
        ]);
        if (!$res) {
            Db::rollback();
            $return_arr = ['status' => 0, 'msg' => '修改供应商订单状态失败', 'data' => []]; //
            return $return_arr;
        }
        foreach ($store_order_goods as $k => $v) {
            $all_count = model('order_goods')->where([
                'order_no' => $v['parent_no'],
                'store_id' => ['neq', 0]
            ])->count();
            $confirm_count = model('order_goods')->where([
                'order_no' => $v['parent_no'],
                'is_confirm_integration' => 1,
                'store_id' => ['neq', 0]
            ])->count();
            //到达中转站修改订单商品状态
            $res = model('order_goods')->update([
                'is_confirm_integration' => 1,
                'confirm_integration_time' => time()
            ], [
                'id' => $v['order_goods_id']
            ]);
            if (!$res) {
                Db::rollback();
                $return_arr = ['status' => 0, 'msg' => '修改供应商订单商品状态失败', 'data' => []]; //
                return $return_arr;
            }
            //判断订单的商品是否全部到达中转站
            if ($all_count == $confirm_count + 1) {
                $res = model('order')->update([
                    'is_confirm_integration' => 1,
                    'confirm_integration_time' => time()
                ], [
                    'order_no' => $v['parent_no']
                ]);
                if (!$res) {
                    Db::rollback();
                    $return_arr = ['status' => 0, 'msg' => '修改订单状态失败', 'data' => []]; //
                    return $return_arr;
                }
            }
        }
        Db::commit();
        $return_arr = ['status' => 1, 'msg' => '', 'data' => []]; //
        return $return_arr;
    }

    public function tail($order_no)
    {
        $data = [];
        $data['order_status'] = OrderConstant::ORDER_STATUS_WAIT_RECEIVE;
        $data['pay_order_status'] = OrderConstant::PAY_ORDER_STATUS_DOING_END;
        model('StoreOrder')->update($data, ['parent_no' => $order_no]);
//        $order = model('order')->where(['id' => $order_id])->find();
        /* $store_order = model('StoreOrder')->where(['parent_no' => $order['order_no']])->select();
         if ($store_order) {
                 foreach ($store_order as $k => $v){
                     $data = [];
                     $data['order_status'] = OrderConstant::ORDER_STATUS_WAIT_RECEIVE;
                     $data['pay_order_status'] = OrderConstant::PAY_ORDER_STATUS_DOING_END;
                     model('StoreOrder')->update($data, ['id' => $v['id']]);
                 }
         }*/
    }

    public function orderInstallConfirm($order_id)
    {

        if (!$order_id) {
            return ['status' => 0, 'msg' => '缺少参数', 'data' => []];

        }

        $order = model('order')->where(['id' => $order_id])->field('id, is_del, install_status')->find();
        if (!$order || $order['is_del'] == 1) {
            return ['status' => 0, 'msg' => '订单不存在或已删除', 'data' => []];
        }

        $appointment_install = model('appointment_install')
            ->field('id, install_user_id')
            ->where(['order_id' => $order['id']])
            ->find();
        if (!$appointment_install) {
            return ['status' => 0, 'msg' => '该订单还没有预约安装', 'data' => []];
        }
        if (!$appointment_install['install_user_id']) {
            return ['status' => 0, 'msg' => '该订单还没有分配安装', 'data' => []];
        }
        if ($order['install_status'] == 2) {
            return ['status' => 0, 'msg' => '该订单已完成安装', 'data' => []];

        }
        if ($order['install_status'] != 1) {
            return ['status' => 0, 'msg' => '该订单还没有预约', 'data' => []];

        }

        $result = model('order')->isUpdate(true)->save(['install_status' => 2], ['id' => $order['id']]);
        if (!$result) {
            Db::rollback();
            return ['status' => 0, 'msg' => '操作失败', 'data' => []];

        }
        $result = model('appointment_install')->isUpdate(true)->save(
            ['install_status' => 2],
            ['id' => $appointment_install['id']]
        );
        if (!$result) {
            Db::rollback();
            return ['status' => 0, 'msg' => '操作失败', 'data' => []];

        }
        Db::commit();
        return ['status' => 1, 'msg' => '', 'data' => []];
    }
    /**
     * 生成分销记录
     */
    private function rebate_log($order)
    {
        $user = model('user')->where("id", $order['user_id'])->find();

        //$pattern = tpCache('distribut.pattern'); // 分销模式
        $first_rate = getSetting('order.order_distribution_first'); // 一级比例
        $second_rate = getSetting('order.order_distribution_second'); // 二级比例
        $third_rate = getSetting('order.order_distribution_third'); // 三级比例

        //按照商品分成 每件商品的佣金拿出来
        $order_rate = getSetting('order.order_distribution_percentage'); // 订单分成比例
        $commission = $order['total_fee'] * ($order_rate / 100); // 订单的商品总额 乘以 订单分成比例

        // 如果这笔订单没有分销金额
        if ($commission == 0)
            return false;

        $first_money = $commission * ($first_rate / 100); // 一级赚到的钱
        $second_money = $commission * ($second_rate / 100); // 二级赚到的钱
        $thirdmoney = $commission * ($third_rate / 100); // 三级赚到的钱

        //  微信消息推送
        //$wx_user = M('wx_user')->find();
        //$jssdk = new \app\mobile\logic\Jssdk($wx_user['appid'],$wx_user['appsecret']);

        // 一级 分销商赚 的钱. 小于一分钱的 不存储
        if ($user['first_leader'] > 0 && $first_money > 0.01) {
            $data = array(
                'user_id' => $user['first_leader'],
                'buy_user_id' => $user['id'],
                'nickname' => $user['nickname'],
                'order_no' => $order['order_no'],
                'order_id' => $order['id'],
                'goods_price' => $order['total_price'],
                'money' => $first_money,
                'level' => 1,
                'create_time' => time(),
                'partner_id' => $order['partner_id']
            );
            model('rebate_log')->insert($data);
            // 微信推送消息
            $tmp_user = model('user')->where("id", $user['first_leader'])->find();
//            if($tmp_user['oauth']== 'weixin')
//            {
//                //$wx_content = "你的一级下线,刚刚下了一笔订单:{$order['order_no']} 如果交易成功你将获得 ￥".$first_money."奖励 !";
//                //$jssdk->push_msg($tmp_user['openid'],$wx_content);
//            }
        }
        // 二级 分销商赚 的钱.
        if ($user['second_leader'] > 0 && $second_money > 0.01) {
            $data = array(
                'user_id' => $user['second_leader'],
                'buy_user_id' => $user['id'],
                'nickname' => $user['nickname'],
                'order_no' => $order['order_no'],
                'order_id' => $order['id'],
                'goods_price' => $order['total_price'],
                'money' => $second_money,
                'level' => 2,
                'create_time' => time(),
                'partner_id' => $order['partner_id']
            );
            model('rebate_log')->insert($data);
            // 微信推送消息
            $tmp_user = model('user')->where("id", $user['second_leader'])->find();
//            if($tmp_user['oauth']== 'weixin')
//            {
//                //$wx_content = "你的二级下线,刚刚下了一笔订单:{$order['order_no']} 如果交易成功你将获得 ￥".$second_money."奖励 !";
//                //$jssdk->push_msg($tmp_user['openid'],$wx_content);
//            }
        }
        // 三级 分销商赚 的钱.
        if ($user['third_leader'] > 0 && $thirdmoney > 0.01) {
            $data = array(
                'user_id' => $user['third_leader'],
                'buy_user_id' => $user['id'],
                'nickname' => $user['nickname'],
                'order_no' => $order['order_no'],
                'order_id' => $order['id'],
                'goods_price' => $order['total_price'],
                'money' => $thirdmoney,
                'level' => 3,
                'create_time' => time(),
                'partner_id' => $order['partner_id']
            );
            model('rebate_log')->insert($data);
            // 微信推送消息
            $tmp_user = model('user')->where("id", $user['third_leader'])->find();
//            if($tmp_user['oauth']== 'weixin')
//            {
//                //$wx_content = "你的三级下线,刚刚下了一笔订单:{$order['order_no']} 如果交易成功你将获得 ￥".$thirdmoney."奖励 !";
//                //$jssdk->push_msg($tmp_user['openid'],$wx_content);
//            }

        }
        model('order')->save(["is_distribut" => 1], ["id", $order['id']]);  //修改订单为已经分成
    }


    /**
     * @param $order_id
     * @param $pay_way
     * @param $user_id
     * @param $money
     * @param $money_before
     * @param $money_after
     * @param $type
     * @param $status
     * @param $cate
     * @param $des
     * @param $transaction
     */
    private function moneyWater(
        $order_id,
        $pay_way,
        $user_id,
        $money = 0,
        $money_before = 0,
        $money_after = 0,
        $type = MoneyWaterConstant::MONEY_WATER_TYPE_ADD,
        $status = MoneyWaterConstant::MONEY_WATER_STATUS_DOING,
        $cate = MoneyWaterConstant::MONEY_WATER_CATE_ORDER_BUY,
        $des = '',
        $transaction = '',
        $partner_id = 0
    ){
        $log_data = array(
            "user_id" => $user_id,
            "type" => $type,
            "money" => $money,
            "money_before" => $money_before,
            "money_after" => $money_after,
            "pay_way" => $pay_way,
            "add_time" => date('Y-m-d H:i:s', time()),
            "order_id" => $order_id,
            "cate" => $cate,
            "status" => $status,
            "transaction" => $transaction,
            'remark' => $des,
            'partner_id' => $partner_id,
        );
        $res = model("money_water")->insert($log_data);
        return $res;
    }


    private function accountLogs($order_id, $pay_way)
    {
        $res = model('rebate_log')->where('order_id', $order_id)->select();
        foreach ($res as $k => $v) {
            $user = model('user')->where('id', $v['user_id'])->find();
            model('rebate_log')->where('id', $v['id'])->setField('status', 1);
            model('user')->where('id', $v['user_id'])->setInc('distribut_total', $v['money']);
            model('user')->where('id', $v['user_id'])->setInc('frozen_money', $v['money']);
            //$pay_way = $v['pay_way'];
            if ($v['money'] < 0) {
                return false;
            }
            $distribut_total = $user['distribut_total'] + $v['money'];
            $this->moneyWater(
                $order_id,
                $pay_way,
                $v['user_id'],
                $v['money'],
                $user['distribut_total']
                ,$distribut_total,
                MoneyWaterConstant::MONEY_WATER_TYPE_ADD,
                MoneyWaterConstant::MONEY_WATER_STATUS_DOING,
                MoneyWaterConstant::MONEY_WATER_CATE_COMMISSION,
                '总佣金增加',
                '',
                $v['partner_id']
            );
        }


    }

    /**
     * 记录帐户变动
     * @param   int $user_id 用户id
     * @param   float $user_money 可用余额变动
     * @param   int $pay_points 消费积分变动
     * @param   string $desc 变动说明
     * @param   float   distribut_money 分佣金额
     * @param int $order_id 订单id
     * @param string $order_sn 订单sn
     * @return  bool
     */
    private function accountLog($rebate_log, $desc)
    {
        $pay_way = model('order')->where('id', $rebate_log['order_id'])->value('pay_way');
        if ($rebate_log['money'] < 0) {
            return false;
        }
        $user = model('user')->where('id', $rebate_log['user_id'])->find();
        $distribut_money = $user['distribut_money'] + $rebate_log['money'];

        /* 更新用户信息 */
        $update_data = ['distribut_money' => $distribut_money,];
        $update = model('user')->update($update_data, ['id' => $rebate_log['user_id']]);
        if ($update) {
            $this->moneyWater(
                $rebate_log['order_id'],
                $pay_way,
                $rebate_log['user_id'],
                $rebate_log['money'],
                $user['distribut_money'],
                $distribut_money,
                MoneyWaterConstant::MONEY_WATER_TYPE_ADD,
                MoneyWaterConstant::MONEY_WATER_STATUS_SUCCESS,
                MoneyWaterConstant::MONEY_WATER_CATE_COMMISSION,
                '可提现佣金增加',
                '',
                $rebate_log['partner_id']
            );
            return true;
        } else {
            return false;
        }
    }

    private function up_vip_user($mem, $user_share, $goods_cost, $user_level, $order)
    {
        $data = [
            'user_level' => $user_level['user_level_id'],
            'user_store' => $mem['user_store'] + $user_level['package']
        ];
        model('user')->save($data, ['id' => $mem['id']]);

        $data['user_id'] = $mem['id'];
        $data['order_id'] = $order['id'];
        $data['level_id'] = $user_level['user_level_id'];
        $data['order_no'] = $order['order_no'];
        $data['package'] = $user_level['package'];
        $data['use_package'] = $order['use_package'];
        $data['user_money'] = $goods_cost;
        $data['create_time'] = time();
        $data['msg'] = '成为' . $user_level['level_name'];
        model('user_level_up')->save($data);


        if ($mem['user_level'] < UserLevelConstant::USER_LEVEL_SILVER_USER) {
            model('user')->where(['id' => $user_share['id']])->setInc('user_money', $goods_cost);
            model('user')->where(['id' => $user_share['id']])->setInc('total_money', $goods_cost);
            $data['share_id'] = $user_share['id'];
            $data['user_id'] = $mem['id'];
            $data['order_id'] = $order['id'];
            $data['order_no'] = $order['order_no'];
            $data['user_money'] = $goods_cost;
            $data['create_time'] = time();
            $data['content'] = '发展下级代理' . $mem['user_name'] . ',金额:' . $goods_cost;
            model('user_share')->save($data);
        }
    }

    /**
     * 成为代理商的支付回调
     * @param $order_no
     * @param string $transaction_id
     * @param int $total_fee
     * @param int $pay_way
     * @throws \think\exception\DbException
     * @throws db\exception\DataNotFoundException
     * @throws db\exception\ModelNotFoundException
     */
    private function successPayOperationVip($order_no, $transaction_id = '', $total_fee = 0, $pay_way = 0)
    {
        $m = model('user_level_order');
        $map = [];
        $map['order_no'] = $order_no;
        $order = $m->where($map)->find();
        $data = ['trade_no' => $transaction_id, 'pay_price' => $total_fee, 'pay_way' => $pay_way];
        $m->save($data, ['order_id' => $order['id']]);
        $user = model("user")->where('user_id', $order['user_id'])->find();
        $package = model('user_level')->where('user_level_id', $order['level_id'])->value('package');

        //增加包数
        model("user")->where(['user_id' => $user['user_id']])->setInc('user_store', $package);

        //上级代理商拿取提成
        if ($user['user_pid']) {
            $user_up = model('user')->where('user_id', $user['user_pid'])->find();
            if ($user_up['user_level'] > $user['user_level']) {
                $data['share_id'] = $user_up['user_id'];
                $data['user_id'] = $user['user_id'];
                $data['order_id'] = $order['id'];
                $data['order_no'] = $order_no;
                $data['user_money'] = $total_fee;
                $data['content'] = '发展下级代理' . $user['user_name'] . ',金额:' . $order['total_price'];
                model('user_share')->save($data);
                model("user")->where(['user_id' => $user_up['user_id']])->setDec('user_store', $package);
            }
        }
        /*model('user')->save(['user_money' => $order], ['user_id' => $user_share['user_id']]);
        $data['share_id'] = $user_share['user_id'];
        $data['user_id'] = $mem['user_id'];
        $data['order_id'] = $order['id'];
        $data['order_no'] = $order_no;
        $data['user_money'] = $order['total_price'];
        $data['content'] = '发展下级代理'.$mem['user_name'].',金额:'.$order['total_price'];
        model('user_share')->save($data);*/
        //model('user')->where(['user_id' => $user_share['user_id']])->setInc('user_store', $mem);
    }

    private function check_user($share_id)
    {
        if ($share_id) {
            $user = model('user')->where('user_id', $share_id)->field('user_id,user_level,user_store,user_pid')->find();
            if ($user['user_level'] > 2) {
                return $user;
            } else {
                $this->check_user($user['user_pid']);
            }
        }
        return false;
    }

    private function user_share_log($share, $user, $order_id, $order_no, $user_money, $order_goods, $goods_o_price)
    {
        if ($user['user_pid'] == $share['user_id']) {
            $user_money += $goods_o_price; //如果是代理商，他直接发展的下级应加上成本
        }
        $data['share_id'] = $share['user_id'];
        $data['user_id'] = $user['user_id'];
        $data['order_id'] = $order_id;
        $data['order_no'] = $order_no;
        $data['user_money'] = $user_money;
        if ($order_goods) {
            $data['order_goods_id'] = $order_goods['order_goods_id'];
            $data['goods_id'] = $order_goods['goods_id'];
            $data['goods_num'] = $order_goods['goods_num'];
        }
        $data['create_time'] = time();
        $data['content'] = '分销提成' . $user['user_name'] . ',金额:' . $user_money;
        model('user_share')->save($data);

    }

    /**
     * 取消订单
     * @param $order_no @订单编号
     */
    private function cancel_order($order_no)
    {
        $order_data = [];
        $order_data['order_no'] = $order_no;
        $order_data['is_del'] = 0;
        $order_info = model('order')->where($order_data)->order('id desc')->find();
        if (!$order_info) {
            $return_arr = ['status' => 0, 'msg' => '订单已删除', 'data' => []]; //
            return $return_arr;
        }

        if ($order_info['order_status'] == 0) {
            $return_arr = ['status' => 0, 'msg' => '订单已取消', 'data' => []]; //
            return $return_arr;
        }

       /* if ($order_info['pay_status'] == 1) {
            $return_arr = ['status' => 0, 'msg' => '已支付的订单不能取消', 'data' => []]; //
            return $return_arr;
        }*/

        //取消订单
        $res = model('order')->where($order_data)->setField('order_status', 0);
        if (!$res) {
            $return_arr = ['status' => 0, 'msg' => '修改订单状态失败', 'data' => []]; //
            return $return_arr;
        }

        if($res['payment_id']) {
            model('coupon_data')->isUpdate(true)->save(['is_use' => 0], ['id' => $res['payment_id']]);
        }

        model('rebate_log')->update(['status' => 4], ["order_id" => $order_info['id']]);

        $coupon_id = $order_info['coupon_id'];
        if ($coupon_id) {
            model('coupon_data')->isUpdate(true)->save(['status' => 0], ['id' => $coupon_id]);
        }
        $integral = $order_info['integral'];

        if ($integral) {
            $this->integral_record(
                IntegralConstant::INTEGRAL_CATE_TYPE_ORDER_BUY,
                $integral,
                IntegralConstant::INTEGRAL_USE_TYPE_CANCEL_ORDER,
                0,
                $this->user_id,
                1,
                $order_info['id']
            );
        }

        $order_goods = model('order_goods')->where('order_no', $order_no)->select();

        foreach ($order_goods as $kk => $vv) {
            //减少库存，增加销量
            $goods_map = [];
            $goods_map['id'] = $vv['goods_id'];
            $goods =  model('goods')->where($goods_map)->find();
            //$goods_cost += $goods['goods_cost'] * $vv['goods_num'];
            $goods_data = [];
            $goods_data['stores'] = $goods['stores'] + $vv['goods_num'];
            $goods_data['sales']  = $goods['sales'] - $vv['goods_num'];
            $res = model('goods')->isUpdate(true)->save($goods_data, $goods_map);
            if ($vv['sku_id']) {
                model('spec_goods_price')->where(['key' => $vv['sku_id'], 'goods_id' => $vv['goods_id']])->setInc('store_count', $vv['goods_num']);
            }
            if ($vv['prom_id'] != 0) {
                if ($vv['prom_type'] == PreferentialConstant::PREFERENTIAL_TYPE_LIMIT_SALES) {
                    model('limit_sales')->where(['limit_sales_id' => $vv['prom_id']])->setDec('sales_num', $vv['goods_num']);
                }
                /*if ($vv['prom_type'] == PreferentialConstant::PREFERENTIAL_TYPE_POPULAR) {
                    model('popular')->where(['popular_id' => $vv['prom_id']])->setInc('sales_num', $vv['goods_num']);
                }
                if ($vv['prom_type'] == PreferentialConstant::PREFERENTIAL_TYPE_NEW_GOODS) {
                    model('new_goods')->where(['new_goods_id' => $vv['prom_id']])->setInc('sales_num', $vv['goods_num']);
                }*/
            }
        }
        $return_arr = ['status' => 1, 'msg' => '取消成功', 'data' => []]; //
        return $return_arr;
    }

    /**
     * 确认收货
     * @param $order_no
     */
    private function confirm_order($order_no)
    {
        $order_data = [];
//        $order_data['user_id'] = $this->user_id;
        $order_data['order_no'] = $order_no;
        $order_data['is_del'] = 0;
        $order_info = model('order')->where($order_data)->order('id desc')->find();

        if (!$order_info) {
            $return_arr = ['status' => 0, 'msg' => '订单已删除', 'data' => []]; //
            return $return_arr;
        }

        if ($order_info['pay_status'] == 0) {
            $return_arr = ['status' => 0, 'msg' => '订单未支付', 'data' => []]; //
            return $return_arr;
        }

        if ($order_info['order_status'] == 4 || $order_info['is_confirm'] == 1) {
            $return_arr = ['status' => 0, 'msg' => '订单已经收货', 'data' => []]; //
            return $return_arr;
        }

        if ($order_info['order_status'] != 3) {
            $return_arr = ['status' => 0, 'msg' => '订单不是待收货状态', 'data' => []]; //
            return $return_arr;
        }

        //确认收货
        $map = [];
        $map['order_status'] = 4;
        $map['is_confirm'] = 1;
        $map['confirm_time'] = date('Y-m-d H:i:s', time());
        $res = model('order')->isUpdate(true)->save($map, $order_data);
        if (!$res) {
            $return_arr = ['status' => 0, 'msg' => '收货失败', 'data' => []]; //
            return $return_arr;
        } else {
            model('rebate_log')->isUpdate(true)->save(['status' => 2], ["order_id" => $order_info['id']]);
            //收货成功，添加积分
            //A('Common/user')->integral_record(9, $order_info['return_score'], 'order_remark', 0, $order_info['user_id'], 0, $res['id'], '', '', 1);
            $return_arr = ['status' => 1, 'msg' => '确认收货成功', 'data' => []]; //
            return $return_arr;
        }
    }
    /**
     * 保存到错误日志表的操作
     */
    private function addToOrderErrorLog($userid = null, $orderid = null, $msg = null)
    {
        $data = [
            'user_id' => $userid,
            'order_id' => $orderid,
            'msg' => $msg,
            'create_at' => date('Y-m-d H:i:s', time()),
            'status' => 0,
        ];
        model('order_error_log')->insert($data);
    }

    /**
     * 获取物流信息
     */
    private function get_express_info($express, $LogisticCode)
    {
        //   $name = array(
        //       '顺丰快递'=>'SF',
        //       '申通快递'=>'STO',
        //       '韵达快递'=>'YD',
        //       '圆通速递'=>'YTO',
        //       '天天快递'=>'HHTT',
        //       '德邦'=>'DBL',
        //       'EMS'=>'EMS',
        // '中通快递'=>'ZTO'
        //   );
        $ShipperCode = $express;
        $LogisticCode = $LogisticCode;

        $EBusinessID = '1429815';
        $AppKey = 'bde0d028-9954-4e33-9205-23630f9777c9';
        $ReqURL = 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';

        $requestData = "{'OrderCode':'','ShipperCode':'" . $ShipperCode . "','LogisticCode':'" . $LogisticCode . "'}";
        $datas = array(
            'EBusinessID' => $EBusinessID,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->express_encrypt($requestData, $AppKey);
        $result = $this->express_send_post($ReqURL, $datas);
        //根据公司业务处理返回的信息......
        return $result;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    private function express_encrypt($data, $appkey)
    {
        return urlencode(base64_encode(md5($data . $appkey)));
    }

    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    private function express_send_post($url, $datas)
    {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if (empty($url_info['port'])) {
            $url_info['port'] = 80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader .= "Host:" . $url_info['host'] . "\r\n";
        $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader .= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader .= "Connection:close\r\n\r\n";
        $httpheader .= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets .= fread($fd, 128);
        }
        fclose($fd);

        return $gets;

    }

    /**
     * 退款后的数据处理
     */
    private function refund_after_checkout($refund_money, $refund_order_id, $order_info)
    {

        $order_goods = model('order_goods')->where(["id" => $refund_order_id])->find();

        # 2. 更新会员的消费金额/更新会员的订单总数/更新会员购买商品获得的积分
        $user_info = model('user')->where(['id' => $order_goods['user_id']])->find();
        if (!$user_info) {
            $this->addToOrderErrorLog($order_goods['user_id'], $order_goods['order_id'], '订单商品' . $refund_order_id . '退款成功,' . $order_goods['user_id'] . '用户不存在');
        }

        //积分流水记录
        $res = $this->integral_record(
            IntegralConstant::INTEGRAL_CATE_TYPE_ORDER_REFUND,
            $order_goods['return_integral'],
            IntegralConstant::INTEGRAL_USE_TYPE_REFUND_GOODS,
            0,
            $order_info['user_id'],
            1,
            $order_goods['order_id']
        );

        if (!$res || $res['status'] == 0) {
            $this->addToOrderErrorLog($order_goods['user_id'], $order_goods['order_id'], '订单商品' . $refund_order_id . '退款成功,' . $order_goods['user_id'] . '记录退单积分流水失败');
        }

        //3.记录退单积分流水
        $integral = $order_goods['integral_count'] * $order_goods['goods_num'];
        if ($integral > 0) {
            $res = $this->integral_record(
                IntegralConstant::INTEGRAL_CATE_TYPE_ORDER_REFUND,
                $integral,
                IntegralConstant::INTEGRAL_USE_TYPE_REFUND_ORDER,
                0,
                $order_goods['user_id'],
                1,
                $order_goods['order_id']
            );
            if ($res && $res['status'] == 0) {
                // 这里记入错误日志
                //Db::rollback();
                $this->addToOrderErrorLog($order_goods['user_id'], $order_goods['order_id'], '订单商品' . $refund_order_id . '退款成功,' . $order_goods['user_id'] . '记录退单积分流水失败');
                //exit(json_encode(['status' => 0, 'msg' => '积分抵扣失败', 'data' => []])); // 返回结果状态
            }
        }

        $refund_order_goods_data = array(
            "is_refund" => 1,
            "refund_money" => $refund_money,
            "refund_tkcg_time" => date("Y-m-d H:i:s"),
        );
        $refund_order_goods = model("order_goods")->save($refund_order_goods_data, ["id" => $refund_order_id]);
        if (!$refund_order_goods) {
            $this->addToOrderErrorLog($order_goods['user_id'], $order_goods['order_id'], '订单商品' . $refund_order_id . '退款成功,' . $order_goods['user_id'] . '订单商品状态更新失败');
        }


        $mg = model('goods');


        # 5.增加相应商品的库存
        $res5 = $mg->where(array('id' => $order_goods['goods_id']))->setInc('stores', $order_goods['goods_num']);
        if (!$res5) {
            $this->addToOrderErrorLog($order_goods['user_id'], $order_goods['order_id'], '订单商品' . $refund_order_id . '退款成功,' . $order_goods['user_id'] . '增加相应商品的库存失败');
        }

        # 6.减少相应销量
        $res7 = $mg->where(array('id' => $order_goods['goods_id']))->setDec('sales', $order_goods['goods_num']);

        if (!$res7) {
            $this->addToOrderErrorLog($order_goods['user_id'], $order_goods['order_id'], '订单商品' . $refund_order_id . '退款成功,' . $order_goods['user_id'] . '减少销量失败');
        }


        $log_data = array(
            "user_id" => $order_goods['user_id'],
            "type" => 1,
            "money" => $refund_money,
            "money_before" => 0,
            "money_after" => 0,
            "pay_way" => $order_info['pay_way'],
            "add_time" => date('Y-m-d H:i:s', time()),
            "order_id" => $order_goods['order_id'],
            "cate" => 5,
            "status" => 1,
            'remark' => "部分订单商品退款" . $refund_order_id
        );
        $res = model("money_water")->save($log_data);


        if (!$res) {
            $this->addToOrderErrorLog($order_goods['user_id'], $order_goods['order_id'], '订单商品' . $refund_order_id . '退款成功,' . $order_goods['user_id'] . '订单退款记录金额流水失败');
        }
    }


}