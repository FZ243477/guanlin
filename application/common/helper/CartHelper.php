<?php


namespace app\common\helper;


use app\common\constant\CartConstant;
use app\common\constant\SystemConstant;
use app\common\constant\PreferentialConstant;

trait CartHelper
{
    /**
     * 检查商品库存
     * @param $goods_num
     * @param $goods
     * @param $cart_goods
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function check_goods_store($goods_num, $goods, $cart_goods, $type = 0, $cart_type = 0)
    {
        $mg  = model("goods");
        $msl = model("spec_goods_price");
        $goods_num = (int)$goods_num;
        $goods_id = $goods['id'];
        $sku_list_id = $cart_goods['sku_id'];
        $goods_info = $mg ->where(array("id" => $goods_id))->find();
        if (!$goods_info) {
            return array("status" => '0', "msg" => "没有该商品！");
        }
        if (!$goods_info['is_sale'] || !$goods_info['is_audit']) {
            return array("status" => '0', "msg" => "该商品已下架！");
        }
        if ($sku_list_id > 0) {
            if(!$goods_info['is_sku']){
                return array("status"=>'0', "msg"=>"该商品已下架！");
            }
            $sku_list_info = $msl->where(array('goods_id'=>$goods_id,'key'=>$sku_list_id))->find();
            if(!$sku_list_info){
                return array("status" => '0', "msg" => "商品没有该属性！");
            }
            if($sku_list_info['store_count'] < $goods_num){
                return array("status"=>'0' ,"msg" => "该商品库存不足！");
            }

            /*$stores  = $sku_list_info['store_count'];
            $goods_code  = $sku_list_info['bar_code'];
            $price  = $sku_list_info['price'];*/

        } else {
            if ($goods_info['stores'] < $goods_num) {
                return array("status" => '0', "msg" => "该商品库存不足！");
            }
            /* $stores  = $goods_info['stores'];
             $goods_code = $goods_info['goods_code'];
             $price  = $goods_info['price'];*/
        }
        // 商品参与促销

        //限时抢购 不能超过购买数量
        if($goods['prom_type'] == PreferentialConstant::PREFERENTIAL_TYPE_LIMIT_SALES)
        {
            $limit_sales_data = [
                'goods_id' => $goods['id'],
                'sku_id' => $cart_goods['sku_id'],
                'start_time' => ['lt', time()],
                'end_time' => ['gt', time()]
            ];

            $limit_sales = model('limit_sales')->where($limit_sales_data)->find(); // 限时抢购活动
            if($limit_sales){

                $cart_num = $cart_goods['goods_num'];

                if ($type == 0) {
                    $cart_goods_num = $goods_num + $cart_num;
                } else {
                    $cart_goods_num = $cart_num;
                }
                // 如果购买数量 大于每人限购数量
                if( $cart_goods_num > $limit_sales['person_num'])
                {
                    if ($cart_goods_num) {
                        $error_msg = "你当前购物车已有 ".($cart_goods_num-$goods_num)." 件!";
                    } else {
                        $error_msg = '';
                    }
                    $return_arr = ['status' => 0, 'msg' => "每人限购 {$limit_sales['person_num']}件 $error_msg", 'data'=> []]; // 返回结果状态
                    return $return_arr;
                    //exit(json_encode($return_arr));
                }
                // 如果剩余数量 不足 限购数量, 就只能买剩余数量
                if(($limit_sales['max_buy_num'] - $limit_sales['buy_num']) < $limit_sales['person_num']) {
                    $return_arr = ['status' => 0, 'msg' => "库存不够,你只能买".($limit_sales['max_buy_num'] - $limit_sales['buy_num'])."件了.", 'data'=> []]; // 返回结果状态
                    return $return_arr;
                    //exit(json_encode($return_arr));
                }
                $user_goods_num = model('order_goods')
                    ->alias('a')
                    ->join('order b', 'a.order_id = b.id', 'left')
                    ->where([
                        'b.pay_status' => 1,
                        'b.user_id' => $this->user_id,
                        'a.goods_id' => $cart_goods['goods_id'],
                        'a.sku_id' => $cart_goods['sku_id'],
                        'b.order_time' => ['between', [
                            date('Y-m-d H:i:s', $limit_sales['start_time']),
                            date('Y-m-d H:i:s', $limit_sales['end_time'])
                        ]]
                    ])->sum('a.goods_num');
                if ($cart_type == 1) {
                    $user_goods_num = $cart_goods['goods_num'] + $goods_num;
                }
                // 如果剩余数量 不足 限购数量, 就只能买剩余数量
                if($user_goods_num >= $limit_sales['person_num']) {
                    $return_arr = ['status' => 0, 'msg' => "每人限制购买".$limit_sales['person_num']."件", 'data'=> []]; // 返回结果状态
                    return $return_arr;
                    //exit(json_encode($return_arr));
                }
            }

        }
        return ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
    }


    /**
     * 得到sku介绍的字符串
     * $sku_list_id
     */
    private function getSkuDes($skuid, $s=''){
        $res = model('sku_list')->find($skuid);
        $sku_arr = array_filter( explode("|||||", $res["attr_list"]) );
        $m = model("sku_attr");
        $str = "";
        foreach($sku_arr as $v){
            $sku_info = $m->where(array("id"=>$v))->find();
            $sku_pname = $m->where(array("id"=>$sku_info['pid']))->value('classname');
            $str .= $sku_pname.":".$sku_info['classname'].$s;
        }
        if (!$str) {
            $str = '';
        }
        return ['sku_info' => trim($str,$s), 'price' => $res['price'], 'goods_code' => $res['goods_code']];
    }



    /**
     * 检测购物车商品数量
     * $goods_type      商品类型
     * $goods_id        商品id
     * $num             购买数量
     * $sku_list_id     商品sku_list_id
     */
    private function checkCartNum($goods_id, $nums, $sku_list_id)
    {

        $mg  = model("goods");
        $msl = model("spec_goods_price");


        $goods_info = $mg ->where(array("id" => $goods_id))->find();
        if (!$goods_info) {
            return array("status" => '0', "msg" => "没有该商品！");
        }
        if (!$goods_info['is_sale'] || !$goods_info['is_audit']) {
            return array("status" => '0', "msg" => "该商品已下架！");
        }
        if ($sku_list_id > 0) {
            if(!$goods_info['is_sku']){
                return array("status"=>'0', "msg"=>"该商品已下架！");
            }
            $sku_list_info = $msl->where(array('goods_id'=>$goods_id,'key'=>$sku_list_id))->find();
            if(!$sku_list_info){
                return array("status" => '0', "msg" => "商品没有该属性！");
            }
            if($sku_list_info['store_count'] < $nums){
                return array("status"=>'0' ,"msg" => "该商品库存不足！");
            }

            /*$stores  = $sku_list_info['store_count'];
            $goods_code  = $sku_list_info['bar_code'];
            $price  = $sku_list_info['price'];*/

        } else {
            if ($goods_info['stores'] < $nums) {
                return array("status" => '2', "msg" => "该商品库存不足！");
            }
            /* $stores  = $goods_info['stores'];
             $goods_code = $goods_info['goods_code'];
             $price  = $goods_info['price'];*/
        }
//        return array("status" => '1', "nums" => $nums, "stores" => $stores,"price" => $price,  "goods_code" => $goods_code );
        return array("status" => '1');
    }


    /**
     * 加入购物车
     * @param $goods_id
     * @param $goods_num
     * @param $sku_id
     * @param $cart_type
     * @param array $goods_price
     * @param array $partner_id
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function addCartHandle($user_id, $goods_id, $goods_num, $sku_id, $cart_type, $goods_price = [], $partner_id=0) {
        // 判断商品
        $return = $this->checkCartNum($goods_id, $goods_num, $sku_id);
        if ($return['status'] != 1) {
            $return_arr = ['status' => 0, 'msg' => $return['msg'],'data'=> []]; // 返回结果状态
            return $return_arr;
        }

        if (!$goods_id || !$goods_num) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM,'data'=> []]; // 返回结果状态
            return $return_arr;
        }
        if ($goods_num <= 0) {
            $return_arr = ['status' => 0, 'msg' => '商品数量必须大于0','data'=> []]; // 返回结果状态
            return $return_arr;
        }

        $goods = model('Goods')->where(['id' => $goods_id])->find(); // 找出这个商品

        $where = [
            'partner_id' => $partner_id,
            'user_id' => $user_id,
            'cart_type' => $cart_type,
        ];

        if($cart_type == CartConstant::CART_TYPE_CART_ORDER) {
            $where['goods_id'] = $goods['id'];
            if ($sku_id) {
                $where['sku_id'] = $sku_id;
            }
        }

        $cart_goods = model('Cart')->where($where)->find(); // 查找购物车是否已经存在该商品

       /* if (!$goods_price) { //活动商品检测
            if ($cart_type == CartConstant::CART_TYPE_NORMAL_BUY) {
                $cart_goodsinfo['sku_id'] = $sku_id;
                $cart_goodsinfo['goods_id'] = $goods_id;
                $cart_goodsinfo['goods_num'] = 0;
            } else {
                $cart_goodsinfo = $cart_goods;
            }
            $result = $this->check_goods_store($goods_num, $goods, $cart_goodsinfo, 0, $cart_type);

            if ($result['status'] == 0) {
                return $result;
            }
        }*/

        $price = $oprice = $goods['price']; // 如果商品规格没有指定价格则用商品原始价格

        if ($partner_id) {
//            $coefficient = model('partner')->where('id', $partner_id)->value('coefficient');
            $partner_coefficient = model('partner_goods')->where(['goods_id'=>$goods_id,'partner_id'=>$partner_id])->value('coefficient');
            $coefficient_price = model('partner_goods')->where(['goods_id'=>$goods_id,'partner_id'=>$partner_id])->value('price');
            if ($sku_id) {
                $SpecGoodsPrice = model('SpecGoodsPrice')->where(["key" => $sku_id, 'goods_id' => $goods_id])->find();
                $goods_skuinfo = $SpecGoodsPrice['key_name'];
                if ($partner_coefficient) {
                    $SpecGoodsPrice['price']>0?$price = $SpecGoodsPrice['price']*$partner_coefficient:false;
                } /*else if ($coefficient) {
                    $SpecGoodsPrice['price']>0?$price = $SpecGoodsPrice['price']*$coefficient:false;
                }*/
            } else {
                if ($coefficient_price) {
                    $price = $coefficient_price;
                } /*else if ($partner_coefficient) {
                    $goods['price']>0?$price = $goods['price']*$partner_coefficient:false;
                }*//* else if ($coefficient) {
                    $goods['price']>0?$price = $goods['price']*$coefficient:false;
                }*/
                $goods_skuinfo = '';
            }
        } else {
            if ($sku_id) {
                $SpecGoodsPrice = model('SpecGoodsPrice')->where(["key" => $sku_id, 'goods_id' => $goods_id])->find();
                $goods_skuinfo = $SpecGoodsPrice['key_name'];
                $price = $oprice = $SpecGoodsPrice['price'];
            } else {
                $goods_skuinfo = '';
            }


        }

        // 商品参与促销
        if($goods['prom_type'] != PreferentialConstant::PREFERENTIAL_TYPE_NORMAL_GOODS)
        {
            $prom = $this->get_goods_promotion($goods['prom_type'], $goods['prom_id'], $goods_id, $sku_id, $partner_id);
            if ($prom['price']) {
                $price = $prom['price'];
            }
            $goods['prom_type'] = $prom['prom_type'];
            $goods['prom_id']   = $prom['prom_id'];
        }

        if ($goods_price) {
            $price = $goods_price['price'];
            $oprice = $goods_price['oprice'];
        }

        // 如果商品购物车已经存在
        if($cart_goods) {
            // 如果购物车的已有数量加上 这次要购买的数量  大于  库存输  则不再增加数量
            /*if(($cart_goods['goods_num'] + $goods_num) > $goods['goods_store']) {
                $goods_num = 0;
            }*/
            if($cart_type == CartConstant::CART_TYPE_CART_ORDER) {
                $goods_num += $cart_goods['goods_num'];
            }
            $where = [
                'partner_id' => $partner_id,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id,
                'goods_skuinfo' => $goods_skuinfo,
                'goods_num' => $goods_num,
                'goods_price' => $price,
                'goods_oprice' => $oprice,
                'cart_type' => $cart_type,  // 购买价
                'store_id' => $goods['store_id'],
                'prom_type' => $goods['prom_type'],   // 0 普通订单,1 限时抢购
                'prom_id' => $goods['prom_id'],   // 活动id
            ];
            $result = model('Cart')->update($where, ['id' => $cart_goods['id']]); // 数量相加
            //$cart_count = model('Cart')->where(['user_id' => $user_id, 'cart_type' => CartConstant::CART_TYPE_CART_ORDER,])->value('goods_num');
            $return_arr = ['status' => 1, 'msg' => '成功加入购物车', 'data'=> []]; // 返回结果状态
            return $return_arr;
        } else {

            $data = array(
                'partner_id' => $partner_id,
                'user_id' => $user_id,   // 用户id
                'goods_id'  => $goods_id,   // 商品id
                'sku_id' => $sku_id,
                'goods_skuinfo' => $goods_skuinfo,
                'goods_price' => $price,  // 购买价
                'goods_oprice' => $oprice,  // 购买价
                'cart_type' => $cart_type,  // 购买价
                'goods_num' => $goods_num, // 购买数量
                'store_id' => $goods['store_id'],
                'add_time' => date('Y-m-d H:i:s'), // 加入购物车时间
                'prom_type' => $goods['prom_type'],   // 0 普通订单,1 限时抢购
                'prom_id'  => $goods['prom_id'],   // 活动id
            );
            $insert_id = model('Cart')->insert($data);
            //$cart_count = model('Cart')->where(['user_id' => $user_id, 'cart_type' => CartConstant::CART_TYPE_CART_ORDER,])->value('goods_num');
            $return_arr = ['status' => 1, 'msg' => '成功加入购物车', 'data'=> []]; // 返回结果状态
            return $return_arr;
        }
    }

    /**
     * 不在购物车展示的购物车商品添加
     * @param $goods_id
     * @param $goods_num
     * @param $sku_id
     * @param $cart_type
     * @param array $goods_price
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function addCartHandleS($user_id, $goods_id, $goods_num, $sku_id, $cart_type, $goods_price = [], $partner_id=0) {
        // 判断商品
        $return = $this->checkCartNum($goods_id, $goods_num, $sku_id);
        if ($return['status'] != 1) {
            $return_arr = ['status' => 0, 'msg' => $return['msg'],'data'=> []]; // 返回结果状态
            return $return_arr;
        }

        if (!$goods_id || !$goods_num) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM,'data'=> []]; // 返回结果状态
            return $return_arr;
        }
        if ($goods_num <= 0) {
            $return_arr = ['status' => 0, 'msg' => '商品数量必须大于0','data'=> []]; // 返回结果状态
            return $return_arr;
        }

        $goods = model('Goods')->where(['id' => $goods_id])->find(); // 找出这个商品

        $where = [
            'partner_id' => $partner_id,
            'user_id' => $user_id,
            'cart_type' => $cart_type,
        ];

        if($cart_type == CartConstant::CART_TYPE_CART_ORDER) {
            $where['goods_id'] = $goods['id'];
            if ($sku_id) {
                $where['sku_id'] = $sku_id;
            }
        }

        $cart_goods = model('Cart')->where($where)->find(); // 查找购物车是否已经存在该商品

        if (!$goods_price) { //活动商品检测

            $result = $this->check_goods_store($goods_num, $goods, $cart_goods);
            if ($result['status'] == 0) {
                return $result;
            }

        }

        $price = $oprice = $goods['price']; // 如果商品规格没有指定价格则用商品原始价格

        if ($sku_id) {
            $SpecGoodsPrice = model('SpecGoodsPrice')->where(["key" => $sku_id, 'goods_id' => $goods_id])->find();
            $goods_skuinfo = $SpecGoodsPrice['key_name'];
            $price = $oprice = $SpecGoodsPrice['price'];
        } else {
            $goods_skuinfo = '';
        }

        // 商品参与促销
        if($goods['prom_type'] != PreferentialConstant::PREFERENTIAL_TYPE_NORMAL_GOODS)
        {
            $prom = $this->get_goods_promotion($goods['prom_type'], $goods['prom_id'], $goods_id, $sku_id, $partner_id);
            if ($prom['price']) {
                $price = $prom['price'];
            }
            $goods['prom_type'] = $prom['prom_type'];
            $goods['prom_id']   = $prom['prom_id'];
        }

        if ($goods_price) {
            $price = $goods_price['price'];
            $oprice = $goods_price['oprice'];
        }

        $data = array(
            'partner_id' => $partner_id,
            'user_id' => $user_id,   // 用户id
            'goods_id'  => $goods_id,   // 商品id
            'sku_id' => $sku_id,
            'goods_skuinfo' => $goods_skuinfo,
            'goods_price' => $price,  // 购买价
            'goods_oprice' => $oprice,  // 购买价
            'cart_type' => $cart_type,  // 购买价
            'goods_num' => $goods_num, // 购买数量
            'store_id' => $goods['store_id'],
            'add_time' => date('Y-m-d H:i:s'), // 加入购物车时间
            'prom_type' => $goods['prom_type'],   // 0 普通订单,1 限时抢购
            'prom_id'  => $goods['prom_id'],   // 活动id
        );


        $insert_id = model('Cart')->insert($data);
        //$cart_count = model('Cart')->where(['user_id' => $user_id, 'cart_type' => CartConstant::CART_TYPE_CART_ORDER,])->value('goods_num');
        $return_arr = ['status' => 1, 'msg' => '成功加入购物车', 'data'=> []]; // 返回结果状态
        return $return_arr;

    }


    /**
     * 不在购物车展示的购物车商品添加
     * @param $goods_id
     * @param $goods_num
     * @param $sku_id
     * @param $cart_type
     * @param array $goods_price
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function addCartHandlePackage($user_id, $goods_id, $goods_num, $sku_id, $cart_type, $goods_price = [],$package_id, $partner_id=0) {
        // 判断商品
        $return = $this->checkCartNum($goods_id, $goods_num, $sku_id);
        if ($return['status'] != 1) {
            $return_arr = ['status' => 0, 'msg' => $return['msg'],'data'=> []]; // 返回结果状态
            return $return_arr;
        }

        if (!$goods_id || !$goods_num) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM,'data'=> []]; // 返回结果状态
            return $return_arr;
        }
        if ($goods_num <= 0) {
            $return_arr = ['status' => 0, 'msg' => '商品数量必须大于0','data'=> []]; // 返回结果状态
            return $return_arr;
        }

        $goods = model('Goods')->where(['id' => $goods_id])->find(); // 找出这个商品

        $where = [
            'partner_id' => $partner_id,
            'user_id' => $user_id,
            'cart_type' => $cart_type,
        ];

        if($cart_type == CartConstant::CART_TYPE_CART_ORDER) {
            $where['goods_id'] = $goods['id'];
            if ($sku_id) {
                $where['sku_id'] = $sku_id;
            }
        }

        $price = $oprice = $goods['price']; // 如果商品规格没有指定价格则用商品原始价格

        if ($sku_id) {
            $SpecGoodsPrice = model('SpecGoodsPrice')->where(["key" => $sku_id, 'goods_id' => $goods_id])->find();
            $goods_skuinfo = $SpecGoodsPrice['key_name'];
            $price = $oprice = $SpecGoodsPrice['price'];
        } else {
            $goods_skuinfo = '';
        }

        if ($goods_price) {
            $price = $goods_price['price'];
            $oprice = $goods_price['oprice'];
        }

        $data = array(
            'partner_id' => $partner_id,
            'user_id' => $user_id,   // 用户id
            'goods_id'  => $goods_id,   // 商品id
            'sku_id' => $sku_id,
            'package_id' => $package_id,
            'goods_skuinfo' => $goods_skuinfo,
            'goods_price' => $price,  // 购买价
            'goods_oprice' => $oprice,  // 购买价
            'cart_type' => $cart_type,  // 购买价
            'goods_num' => $goods_num, // 购买数量
            'store_id' => $goods['store_id'],
            'add_time' => date('Y-m-d H:i:s'), // 加入购物车时间
            'prom_type' => $goods['prom_type'],   // 0 普通订单,1 限时抢购
            'prom_id'  => $goods['prom_id'],   // 活动id
        );


        $insert_id = model('Cart')->insert($data);
        //$cart_count = model('Cart')->where(['user_id' => $user_id, 'cart_type' => CartConstant::CART_TYPE_CART_ORDER,])->value('goods_num');
        $return_arr = ['status' => 1, 'msg' => '成功加入购物车', 'data'=> []]; // 返回结果状态
        return $return_arr;

    }
    /**
     * 价格刷新
     * @param $cartList
     * @param int $pay_integral
     * @param int $coupon_id
     * @param array $address
     * @param int $user_id
     * @param int $partner_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderPrice($cartList, $pay_integral = 0, $coupon_id = 0, $address = [], $payment_id = 0, $user_id = 0, $partner_id = 0)
    {

        $total_num = $goods_price = 0;
//        $coefficient = model('partner')->where('id', $partner_id)->value('coefficient');
        $brand = [];
        //购物车价格判断
        foreach ($cartList as $k => $val){
            $price = $val['goods_price'];
            if (isset($val['id'])) {
                $cart_type = model('cart')->where('id', $val['id'])->value('cart_type');
            } else {
                $cart_type = CartConstant::CART_TYPE_CART_ORDER;
            }
            $goods = model('goods')->where(['id' => $val['goods_id']])->find();
            $brand[] = model('goods_brand')->where(['id' => $goods['brand_id']])->field('province_id,city_id')->find();
            if ($val['sku_id']) {
                $SpecGoodsPrice = model('SpecGoodsPrice')->where(["key" => $val['sku_id'], 'goods_id' => $val['goods_id']])->find();
                $goods_skuinfo = $SpecGoodsPrice['key_name'];
                $goods['goods_code'] = $SpecGoodsPrice['bar_code'];
                $goods['cost_price'] = $SpecGoodsPrice['cost_price'];
                $goods['oprice'] = $SpecGoodsPrice['oprice'];
                $goods['b_price'] = $SpecGoodsPrice['b_price'];
            } else {
                $SpecGoodsPrice = [];
                $goods_skuinfo = '';
            }

            if ($cart_type != CartConstant::CART_TYPE_PACKAGE_BUY) {
                $price = $goods['price'];
                if ($SpecGoodsPrice) {
                    $price = $SpecGoodsPrice['price'];
                }

                if ($partner_id) {
                    $partner_coefficient = model('partner_goods')->where(['goods_id'=>$val['goods_id'],'partner_id'=>$partner_id])->value('coefficient');
                    $coefficient_price = model('partner_goods')->where(['goods_id'=>$val['goods_id'],'partner_id'=>$partner_id])->value('price');
                    if ($val['sku_id']) {
                        $SpecGoodsPrice = model('SpecGoodsPrice')->where(["key" => $val['sku_id'], 'goods_id' => $val['goods_id']])->find();
                        if ($partner_coefficient) {
                            $SpecGoodsPrice['price']>0?$price = $SpecGoodsPrice['price']*$partner_coefficient:false;
                        } /*else if ($coefficient) {
                            $SpecGoodsPrice['cost_price']>0?$price = $SpecGoodsPrice['cost_price']*$coefficient:false;
                        }*/
                    } else {
                        if ($coefficient_price) {
                            $price = $coefficient_price;
                        }/* else if ($partner_coefficient) {
                            $goods['price']>0?$price = $goods['price']*$partner_coefficient:false;
                        }*//* else if ($coefficient) {
                            $goods['price']>0?$price = $goods['price']*$coefficient:false;
                        }*/
                    }
                }
                if ($goods['prom_type'] != PreferentialConstant::PREFERENTIAL_TYPE_NORMAL_GOODS) {
                    $prom = $this->get_goods_promotion($goods['prom_type'], $goods['prom_id'], $goods['id'], $val['sku_id'], $partner_id);
                    if ($prom['price']) {
                        $price = $prom['price'];
                    }
                    $goods['prom_type'] = $prom['prom_type'];
                    $goods['prom_id'] = $prom['prom_id'];
                }

                if ($user_id && $price != $cartList[$k]['goods_price']) {
                    model('cart')->update(['goods_price' => $price], ['id' => $val['id']]);
                }
            }
            $cartList[$k]['goods_price'] = $price;
//            $cartList[$k]['goodsInfo'] = $goods;
            $cartList[$k]['goods_pic'] = $goods['goods_logo'];
            $cartList[$k]['goods_name'] = $goods['goods_name'];
            $cartList[$k]['goods_unit'] = $goods['goods_unit'];
            $cartList[$k]['weight'] = $goods['weight'];
            $cartList[$k]['goods_code'] = $goods['goods_code'];
            $cartList[$k]['cost_price'] = $goods['cost_price'];
            $cartList[$k]['goods_oprice'] = $goods['oprice'];
            $cartList[$k]['b_price'] = $goods['b_price'];
            $cartList[$k]['goods_skuinfo'] = $goods_skuinfo;

            $cartList[$k]['goods_fee'] = $val['goods_num'] * $price;
            $total_num += $val['goods_num'];
            $goods_price += $cartList[$k]['goods_fee'];
        }

        //优惠券金额计算
        $coupon_price = 0;
        if ($coupon_id) {
            $coupon = model('coupon_data')->where(['id' => $coupon_id, 'status' => 0])->field('deduct,coupon_type,limit_money,use_type,goods_info')->find();
            if ($coupon['limit_money'] <= $goods_price) {
                if($coupon['use_type'] == 1){ //部分商品
                    $goods_info = json_decode($coupon['goods_info'], true);
                    $goods_price1 = 0;
                    foreach ($goods_info as $k => $v) {
                        foreach ($cartList as $k1 => $v1) {
                            $v['sku_id'] = $v['sku_id']?$v['sku_id']:0;
                            if ($v1['goods_id'] == $v['goods_id'] && $v1['sku_id'] == $v['sku_id']) {
                                if($coupon['coupon_type'] == 1) {
                                    $coupon_price = $coupon['deduct'];
                                } else {
                                    $goods_price1+=$v1['goods_price']*$v1['goods_num'];
                                }
                            }
                        }
                    }
                    if($coupon['coupon_type'] == 2) {
                        $deduct = $coupon['deduct']/10;
                        $coupon_price = $goods_price1 - ($goods_price1 * $deduct);
                    }

                } else {
                    if($coupon['coupon_type'] == '1') {
                        $coupon_price = $coupon['deduct'];
                    } else if($coupon['coupon_type'] == '2') {
                        $deduct = $coupon['deduct']/10;
                        $coupon_price = $goods_price - ($goods_price * $deduct);
                    }
                }

            }

        }

        $coupon_price = round($coupon_price?$coupon_price:0, 2);

        $integral_exchange_money_one = getSetting('integral.integral_exchange_money_one');
        $integral_exchange_money_all = getSetting('integral.integral_exchange_money_all');

        $integral_money = round($pay_integral * $integral_exchange_money_all / $integral_exchange_money_one, 2);

        $total_price = $goods_price; //订单总额

        $order_amount = $total_price-$coupon_price-$integral_money; //支付金额

        $order_amount < 0 ? $order_amount = 0.01 : false;

        //运费计算
        $express_fee = 0;
        //判断是否达到免运费金额
        /*$order_full_amount = getSetting('order.order_full_amount');
        if ($total_price < $order_full_amount) {
            $order_full_amount_freight = getSetting('order.order_full_amount_freight');
            $express_fee =  round($total_price*$order_full_amount_freight/100, 2);
        } else {*/
        if ($address && $address['province_id']) {
            //没有达到免运费，查询运费规则
            $whereOr = "find_in_set(".$address['province_id'].",province_id) or find_in_set(".$address['city_id'].",city_id)";
            $freight = model('freight')->where($whereOr)->field('freight_fee')->find();
            if ($freight) {
                $express_fee =  round($total_price*$freight['freight_fee']/100, 2);
            }
        }
//        }

        //服务费计算
        $cover_fee = 0;
        if ($address && $address['province_id']) {
            $whereOr = "find_in_set(".$address['province_id'].",province_id) or find_in_set(".$address['city_id'].",city_id)";
            $cover = model('cover')->where($whereOr)->field('cover_fee')->find();
            if ($cover) {
                $cover_fee = round($total_price * $cover['cover_fee'] / 100, 2);
            }
        }
        //订单规则，不售卖地区判断
        $is_buy = 1;
        if ($address && $address['province_id']) {
            if ($brand) {
                foreach ($brand as $k => $v) {
                    if ($v['province_id']) {
                        $province_id = explode(',', $v['province_id']);
                        if (!in_array($address['province_id'], $province_id)) {
                            $is_buy = 0;
                        }
                    }
                    if ($v['city_id']) {
                        $city_id = explode(',', $v['city_id']);
                        if (!in_array($address['city_id'], $city_id)) {
                            $is_buy = 0;
                        }
                    }
                }
            }

            /* $whereOr = "find_in_set(".$address['province_id'].",province_id) or find_in_set(".$address['city_id'].",city_id)";
             $cover = model('rules')->where($whereOr)->find();
             if ($cover) {
                 $is_buy = 0;
             }*/

        }

        //订单金额合计
        $total_fee = $order_amount + $express_fee + $cover_fee;
        $order_amount = round($total_fee, 0);
        $payment_money = 0;
        if ($payment_id) {
            $payment_money = model('payment')->where(['id' => $payment_id])->value('money');
            $order_amount = $order_amount -  $payment_money;
            if ($order_amount <= $payment_money) {
                $payment_money = $order_amount;
            }
            $order_amount < 0 ? $order_amount = 0 : false;
        }

        $deposit_order_money = getSetting('order.deposit_order_money');
//        $deposit_order_percent = getSetting('order.deposit_order_percent');
        //支付定金
//        $deposit_money =  round($total_fee * $deposit_order_percent / 100, 2);
        $deposit_money =  $deposit_order_money;

        $car_price = array(
            'coupon_price' => $coupon_price, // 优惠券
            'integral_money' => $integral_money, // 积分支付
            'order_amount' => $order_amount, // 支付金额
            'goods_price' => round($goods_price, 0),// 商品价格
            'express_fee' => $express_fee,// 运费
            'cover_fee' => $cover_fee,// 服务费
            'is_buy' => $is_buy,// 是否可以购买
            'deposit_money_limit' => $deposit_order_money,
            'deposit_money' => $deposit_money,
            'payment_money' => $payment_money,
            //'total_amount' => $total_price,// 订单总额 不包括优惠券
            'cartList' => $cartList,// 购物车
        );

        return $car_price;
    }

    /**
     * 得到购物车商品
     * @param $cart_type
     * @param $user_id
     * @param $partner_id
     * @return mixed
     */
    public function getCartList($user_id, $cart_type, $partner_id=0)
    {
        $where = ['user_id' => $user_id, 'cart_type' => $cart_type, 'partner_id' => $partner_id];

        if ($cart_type == CartConstant::CART_TYPE_CART_ORDER) {
            $where['selected'] = 1;
        }

        $cartList = model('cart')->where($where)->field('id,goods_id,store_id,sku_id,goods_num,goods_price,goods_oprice,prom_type,prom_id')->select();

        if(empty($cartList)) {
            $json_arr = ['status'=> 0, 'msg' => '你没有选择商品', 'data'=>''];
            exit(json_encode ($json_arr));
        }
        foreach ($cartList as $k => $v) {
            $goods = model('goods')->where(['id' => $v['goods_id']])->find();
            $this->check_goods_store($v['goods_num'], $goods, $v);
        }
        return $cartList;
    }

}