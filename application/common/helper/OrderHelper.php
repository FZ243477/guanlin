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
        $order['order_status_desc'] = OrderConstant::order_status_array_value($order['order_status']);
        $order['order_btn'] = $orderBtnArr = $this->orderBtn(0, $order);
        return $order; // 订单该显示的按钮
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


    private function get_order_sn()
    {
        $order_no = date('YmdHis', time()) . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 10), 1))), 0, 4); // 获取生成订单号
        return $order_no;
    }

    /**
     * @param $order_no
     * @param string $transaction_id
     * @param int $total_fee
     * @param int $pay_way
     * @return array
     */
    private function successPayOperation($order_no, $transaction_id = '', $total_fee = 0, $pay_way = 0)
    {
        $m = model('order');
        $map = [];
        $map['order_id'] = $order_no;
        $order = $m->where($map)->select();
        if (!$order) {
            $return = ['status' => 0, 'msg' => '订单不存在'];
            return $return;
        }
        Db::startTrans();
        if ($order['paid'] == OrderConstant::PAY_STATUS_DOING) {
            $msg = '订单不能支付2';
            return ['status' => 0, 'msg' => $msg];
        }
        $data = [
            'state' => 1,
            'paid' => OrderConstant::PAY_STATUS_DOING,
            'trade_no' => $transaction_id,
//            'pay_price' => $total_fee,
//            'pay_way' => $pay_way,
            'paid_time' => time()
        ];
        $res = $m->update($data, ['id' => $order['id']]);
        if (!$res) {
            Db::rollback();
            $msg = '支付失败';
            return ['status' => 0, 'msg' => $msg];
        }

        Db::commit();
        return ['status' => 1, 'msg' => '支付成功'];
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