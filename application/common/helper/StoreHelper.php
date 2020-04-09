<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-08-08
 * Time: 16:34
 */


namespace app\common\helper;

use app\common\constant\OrderConstant;


trait StoreHelper
{
    private function storeOrder($data)
    {
        $orderGoods = model('OrderGoods')->where(['order_id' => $data['id']])->field('goods_id')->select();
        $store_goods_id = [];
        foreach ($orderGoods as $k => $v) {
            $store = model('Goods')->where(['id' => $v['goods_id']])->field('id,store_id')->find();
            if ($store['store_id']) {
                $store_goods_id[$store['store_id']][] = $store['id'];
            }
        }
        if ($store_goods_id) {
            foreach ($store_goods_id as $store_id => $value) {
                $store_order_money = 0;
                $store_data = [];
                foreach ($value as $k => $goods_id) {
                    $store_good = model('Goods')->where(['id' => $goods_id])->find();
                    $store_order_money += $store_good['price'];
                }
                $store_data['order_no'] = OrderConstant::ORDER_NO_STR_PREFIX . date('YmdHis', time()) . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                $store_data['parent_no'] = $data['order_no'];
                $store_data['user_id'] = $data['user_id'];
                $store_data['address_id'] = $data['address_id'];
                $store_data['consignee'] = $data['consignee'];
                $store_data['province'] = $data['province'];
                $store_data['province_id'] = $data['province_id'];
                $store_data['city'] = $data['city'];
                $store_data['city_id'] = $data['city_id'];
                $store_data['district'] = $data['district'];
                $store_data['district_id'] = $data['district_id'];
                $store_data['place'] = $data['place'];
                $store_data['telephone'] = $data['telephone'];
                $store_data['total_price'] = $store_order_money;
                $store_data['total_fee'] = $data['total_fee'];
                $store_data['express_fee'] = $data['total_fee'];
                $store_data['order_time'] = $data['order_time'];
                $store_data['source'] = $data['source'];
                $store_data['pay_order_status'] = $data['pay_order_status'];
                model('StoreOrder')->save($store_data);
                $order_store_goods = model("OrderGoods")->where(['order_id' => $data['id'], 'store_id' => $store_id])->select();
                foreach ($order_store_goods as $order_store_goods_k => $order_store_goods_v) {
                    $store_goods['user_id'] = $order_store_goods_v['user_id'];
                    $store_goods['order_id'] = $order_store_goods_v['order_id'];
                    $store_goods['order_no'] = $store_data['order_no'];
                    $store_goods['goods_id'] = $order_store_goods_v['goods_id'];
                    $store_goods['store_id'] = $order_store_goods_v['store_id'];
                    $store_goods['goods_name'] = $order_store_goods_v['goods_name'];
                    $store_goods['goods_nums'] = $order_store_goods_v['goods_nums'];
                    $store_goods['goods_unit'] = $order_store_goods_v['goods_unit'];
                    $store_goods['goods_code'] = $order_store_goods_v['goods_code'];
                    $store_goods['goods_price'] = $order_store_goods_v['goods_price'];
                    $store_goods['goods_oprice'] = $order_store_goods_v['goods_oprice'];
                    $store_goods['goods_pic'] = $order_store_goods_v['goods_pic'];
                    $store_goods['sku_id'] = $order_store_goods_v['sku_id'];
                    $store_goods['sku_info'] = $order_store_goods_v['sku_info'];
                    model('StoreOrderGoods')->insert($store_goods);
                }
            }
        }
    }
}