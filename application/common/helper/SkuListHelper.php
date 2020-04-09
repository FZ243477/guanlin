<?php


namespace app\common\helper;


trait SkuListHelper
{

    /**20180131wzz
     * 得到sku介绍的字符串
     * $sku_list_id
     * $goods_type  1-普通产品 2-活动产品
     */
    private function getSkuDes($sku_list_id)
    {
        if (!$sku_list_id) {
            return "";
        }

        $msl = model("sku_list");


        $msa = model("sku_attr");
        $skuids = $msl->find($sku_list_id);

        if (!$skuids) {
            return "";
        }
        $sku_arr = array_filter(explode("|||||", $skuids['attr_list']));
        $str = "";
        foreach ($sku_arr as $v) {
            $sku_info = $msa->where(array("id" => $v))->find();
            $sku_pname = $msa->where(array("id" => $sku_info['pid']))->getField('classname');
            $str .= $sku_pname . ":" . $sku_info['classname'] . "<br>";
        }
        return trim($str, "<br>");
    }


    /**20180131wzz
     * 检测购物车商品数量
     * $goods_type      商品类型
     * $goods_id        商品id
     * $num             购买数量
     * $sku_list_id     商品sku_list_id
     */
    private function checkCartNum($goods_id, $nums, $sku_list_id)
    {

            $mg = model("goods");
            $msl = model("sku_list");


        $goods_info = $mg->where(array("id" => $goods_id, "is_del" => 0, 'is_sale' => '1'))->find();
        if (!$goods_info) {
            return array("status" => '0', "msg" => "没有该商品！");
        }
        if (!$goods_info['is_sale']) {
            return array("status" => '0', "msg" => "该商品已下架！");
        }
        if ($sku_list_id) {
            if (!$goods_info['is_sku']) {
                return array("status" => '0', "msg" => "该商品已下架！");
            }
            $sku_list_info = $msl->where(array('goods_id' => $goods_id, 'id' => $sku_list_id))->find();
            if (!$sku_list_info) {
                return array("status" => '0', "msg" => "商品没有该属性！");
            }
            if ($sku_list_info['stock'] < $nums) {
                return array("status" => '2', "msg" => "该商品库存不足！", 'nums' => $sku_list_info['stock'], 'stock' => $sku_list_info['stock'], 'price' => $sku_list_info['price'], 'oprice' => $sku_list_info['oprice']);
            }

            $stock = $sku_list_info['stock'];
            $oprice = $sku_list_info['oprice'];
            $price = $sku_list_info['price'];

        } else {
            if ($goods_info['stock'] < $nums) {
                return array("status" => '2', "msg" => "该商品库存不足！", 'nums' => $goods_info['stock'], 'stock' => $goods_info['stock'], 'price' => $goods_info['price'], 'oprice' => $goods_info['oprice']);
            }
            $stock = $goods_info['stock'];
            $oprice = $goods_info['oprice'];
            $price = $goods_info['price'];
        }
        return array("status" => '1', "nums" => $nums, "stock" => $stock, "price" => $price, "oprice" => $oprice);
    }

}