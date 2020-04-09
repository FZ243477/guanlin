<?php


namespace app\common\helper;

use app\common\constant\OrderConstant;

trait FinanceHelper
{
    private function financeInfo($order, $date_time)
    {
        $we_chat = $ali_pay = $unionpay = $certificate = 0;
        $today_we_chat = $today_ali_pay = $today_unionpay = $today_certificate = 0;
        $order_no = [];

        foreach ($order as $k => $v) {
            $order_time = strtotime($v['order_time']);
            if ($v['pay_way'] == OrderConstant::ORDER_PAY_WAY_ALIPAY) { //支付宝
                if ($v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_ALL
                    ||$v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_DOING_END) {
                    if ($order_time > $date_time[0] && $order_time < $date_time[1]) {
                        $today_ali_pay += $v['total_fee'];
                    }
                    $ali_pay += $v['total_fee'];
                } else if ($v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_DOING_DEPOSIT) {
                    if ($order_time > $date_time[0] && $order_time < $date_time[1]) {
                        $today_ali_pay += $v['deposit_money'];
                    }
                    $ali_pay += $v['deposit_money'];
                }
            } else if ($v['pay_way'] == OrderConstant::ORDER_PAY_WAY_WXPAY) { //微信
                if ($v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_ALL
                    ||$v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_DOING_END) {
                    if ($order_time > $date_time[0] && $order_time < $date_time[1]) {
                        $today_we_chat += $v['total_fee'];
                    }
                    $we_chat += $v['total_fee'];
                } else if ($v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_DOING_DEPOSIT) {
                    if ($order_time > $date_time[0] && $order_time < $date_time[1]) {
                        $today_we_chat += $v['deposit_money'];
                    }
                    $we_chat += $v['deposit_money'];
                }
            } else if ($v['pay_way'] == OrderConstant::ORDER_PAY_WAY_UNIONPAY) { //银联
                if ($v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_ALL
                    ||$v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_DOING_END) {
                    if ($order_time > $date_time[0] && $order_time < $date_time[1]) {
                        $today_unionpay += $v['total_fee'];
                    }
                    $unionpay += $v['total_fee'];
                } else if ($v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_DOING_DEPOSIT) {
                    if ($order_time > $date_time[0] && $order_time < $date_time[1]) {
                        $today_unionpay += $v['deposit_money'];
                    }
                    $unionpay += $v['deposit_money'];
                }
            } else if ($v['pay_way'] == OrderConstant::ORDER_PAY_WAY_CERTIFICATE) { //线下支付
                if ($v['is_certificate'] == OrderConstant::ORDER_CERTIFICATE_DONE) {
                    if ($v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_ALL
                        ||$v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_DOING_END) {
                        if ($order_time > $date_time[0] && $order_time < $date_time[1]) {
                            $today_certificate += $v['total_fee'];
                        }
                        $certificate += $v['total_fee'];
                    } else if ($v['pay_order_status'] == OrderConstant::PAY_ORDER_STATUS_DOING_DEPOSIT) {
                        if ($order_time > $date_time[0] && $order_time < $date_time[1]) {
                            $today_certificate += $v['deposit_money'];
                        }
                        $certificate += $v['deposit_money'];
                    }
                } else if ($v['is_certificate'] == OrderConstant::ORDER_CERTIFICATE_DOING) {
                    $certificate += model('order_certificate')->where([
                        'status' => 1,
                        'order_no' => $v['order_no'
                        ]])->sum('sure_money');
                    if ($order_time > $date_time[0] && $order_time < $date_time[1]) {
                        $today_certificate += model('order_certificate')->where([
                            'status' => 1,
                            'update_time' => ['between', $date_time],
                            'order_no' => $v['order_no']])->sum('sure_money');
                    }
                }
            }
            $order_no[] = $v['order_no'];
        }
        return [
            $ali_pay,
            $we_chat,
            $unionpay,
            $certificate,
            $today_ali_pay,
            $today_we_chat,
            $today_unionpay,
            $today_certificate,
            $order_no
        ];
    }
}