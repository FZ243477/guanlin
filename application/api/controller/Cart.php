<?php
namespace app\api\controller;
use app\common\constant\CartConstant;
use app\common\constant\PreferentialConstant;
use app\common\constant\SystemConstant;
use app\common\helper\PreferentialHelper;
use app\common\helper\CartHelper;
use app\common\helper\GoodsHelper;

class Cart extends Base
{
    use PreferentialHelper;
    use CartHelper;
    use GoodsHelper;

    public function __construct()
    {
        parent::__construct();

        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }
    }

    /**
     * 将商品加入购物车
     */
    function addCart()
    {
        $goods_id = request()->post('goods_id'); // 商品id
        $goods_num = request()->post('goods_num');// 商品数量
        $sku_id = request()->post('sku_id', 0);// 商品数量
        $cart_type = request()->post('cart_type', CartConstant::CART_TYPE_CART_ORDER);
        $result = $this->addCartHandle($this->user_id, $goods_id, $goods_num, $sku_id, $cart_type);
        if ($result['status'] == 1) {
            $return_arr = ['status' => 1, 'msg' => '成功加入购物车', 'data'=> []]; // 返回结果状态
            ajaxReturn($return_arr);
        } else {
            $return_arr = ['status' => 0, 'msg' => $result['msg'], 'data'=> []]; // 返回结果状态
            ajaxReturn($return_arr);
        }
    }


    /*
     * 请求获取购物车列表
     */
    public function cartList()
    {
        $where = ['user_id' => $this->user_id, 'cart_type' => CartConstant::CART_TYPE_CART_ORDER, 'partner_id' => 0];
        $list = model('cart')->where($where)->field('id,goods_id,goods_num,goods_price,selected,sku_id,goods_skuinfo')->select();

        $total_num = $total_price = $goods_price = 0;

        $cartList = [];
        $k = 0;
        foreach ($list as $key => $val){

            $goods = model('goods')->field('id,goods_name,goods_logo,price,goods_unit,weight,prom_type,prom_id')->where(['id' => $val['goods_id']])->find();
            if (!$goods) {
                model('cart')->destroy($val['id']);
                unset($cartList[$key]);
                continue;
            }

            $cartList[$k] = $val;
            $cartList[$k]['goods_fee'] = $val['goods_num'] * $val['goods_price'];
            $total_num += $val['goods_num'];
            if ($val['selected'] == 1) {
                $total_price += $cartList[$k]['goods_fee'];
            }
            $goods_price += $cartList[$k]['goods_fee'];
            //$cartList[$k]['goodsInfo'] = $goods;
            $cartList[$k]['goods_logo'] = $goods['goods_logo'];
            $cartList[$k]['goods_name'] = $goods['goods_name'];
            $cartList[$k]['goods_unit'] = $goods['goods_unit'];
            $cartList[$k]['weight'] = $goods['weight'];
            $price = $val['goods_price'];

            $prom = $this->get_goods_promotion($goods['prom_type'], $goods['prom_id'], $goods['id'], $val['sku_id']);

            if ($prom['price']) {
                $price = $prom['price'];
            }
            $goods['prom_type'] = $prom['prom_type'];
            $goods['prom_id']   = $prom['prom_id'];

            if ($price != $val['goods_price']) {
                $data = [
                    'goods_price' => $price,
                    'prom_type' => $goods['prom_type'],
                    'prom_id' => $goods['prom_id'],
                ];
                model('cart')->save($data, ['id' => $val['id']]);
            }
            $k++;
        }
        $total_price =  sprintf('%.2f', $total_price);
        $total_price = ['total_price' =>$total_price , 'total_num'=> $total_num,]; // 总计

        $data = [
            'cartList' => $cartList,
            'total_price' => $total_price,
        ];

        $result =['status'=>1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS ,'data'=> $data];
        ajaxReturn($result);
    }

    public function editCart()
    {

        $cart_id = request()->post('cart_id');
        $goods_num = request()->post('goods_num');

        if (!$cart_id || !$goods_num) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM,'data'=> []]; // 返回结果状态
            ajaxReturn($return_arr);
        }
        $cart_goods = model('Cart')->where(['id' => $cart_id])->find();
        if (!$cart_goods) {
            $return_arr = ['status' => 0, 'msg' => '购物车商品不存在','data'=> []]; // 返回结果状态
            ajaxReturn($return_arr);
        }
        $goods = model('goods')->where(['id' => $cart_goods['goods_id']])->find();
        if (!$goods) {
            $return_arr = ['status' => 0, 'msg' => '购物车商品不存在','data'=> []]; // 返回结果状态
            ajaxReturn($return_arr);
        }
        $result = $this->check_goods_store($goods_num, $goods, $cart_goods, 0);

        if ($result['status'] == 0) {
            ajaxReturn($result);
        }

        $data['goods_num'] = $goods_num;
        $result = model('Cart')->save($data, ['id' => $cart_id]);
        $return_arr = ['status'=>1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS ,'data'=> []];
        ajaxReturn($return_arr);

    }

    /**
     * 选中购物车
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function selected()
    {
        $data = request()->post();
        $cart_id = $data['cart_id'];
        $selected = $data['selected'];
        $type = $data['type'];
        if (!$cart_id) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM,'data'=> []]; // 返回结果状态
            ajaxReturn($return_arr);
        }
        foreach ($cart_id as $v) {
            $cart = model('cart')->field('selected')->where(['id' => $v])->find();
            if (!$cart) {
                $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM,'data'=> []]; // 返回结果状态
                ajaxReturn($return_arr);
            }
            if ($type == 0) {
                $selected = 1- $cart['selected'];
            }
            model('cart')->update(['selected' => $selected], ['id' => $v]);
        }
        $result =['status'=>1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS ,'data'=> []];
        ajaxReturn($result);
    }

    /**
     * 删除购物车的商品
     */
    public function delCart()
    {
        $data = request()->post();
        $cart_id =$data['cart_id'];
        if (!$cart_id) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM,'data'=> []]; // 返回结果状态
            ajaxReturn($return_arr);
        }
        $cart_id = array_filter($cart_id);
        foreach ($cart_id as $v) {
            $result = model('Cart')->destroy($v); // 删除id为5的用户数据
            if (!$result) {
                $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE,'data'=> []]; // 返回结果状态
                ajaxReturn($return_arr);
            }
        }
        $return_arr = ['status'=>1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS ,'data'=> []];
        ajaxReturn($return_arr);
    }



}