<?php


namespace app\common\constant;


class CartConstant
{
    const CART_TYPE_CART_ORDER = 0; //购物车下单
    const CART_TYPE_NORMAL_BUY = 1; //普通购买
    const CART_TYPE_PACKAGE_BUY = 2; //全屋套餐
    const CART_TYPE_SANVJIA_BUY = 3; //三维家购买
    const CART_TYPE_KUJIALE_BUY = 4; //酷家乐购买
    const CART_TYPE_LIGHT_BUY = 5; //轻设计购买
    const CART_TYPE_ADMIN_BUY = 7; //后台录入
    const CART_TYPE_PACKAGE_NEW = 8; //全屋套餐

    const CART_TYPE_CART_ORDER_NAME = '购物车下单'; //购物车下单
    const CART_TYPE_NORMAL_BUY_NAME = '普通购买'; //普通购买
    const CART_TYPE_PACKAGE_BUY_NAME = '全屋套餐'; //全屋套餐
    const CART_TYPE_SANVJIA_BUY_NAME = '三维家购买'; //三维家购买
    const CART_TYPE_KUJIALE_BUY_NAME = '酷家乐购买'; //酷家乐购买
    const CART_TYPE_LIGHT_BUY_NAME = '轻设计购买'; //轻设计购买
    const CART_TYPE_ADMIN_BUY_NAME = '后台录入'; //后台录入
    const CART_TYPE_PACKAGE_NEW_NAME = '全屋套餐'; //后台录入

    static $cart_type_array = [
        self::CART_TYPE_CART_ORDER => self::CART_TYPE_CART_ORDER_NAME,
        self::CART_TYPE_NORMAL_BUY => self::CART_TYPE_NORMAL_BUY_NAME,
        self::CART_TYPE_PACKAGE_BUY => self::CART_TYPE_PACKAGE_BUY_NAME,
        self::CART_TYPE_SANVJIA_BUY => self::CART_TYPE_SANVJIA_BUY_NAME,
        self::CART_TYPE_KUJIALE_BUY => self::CART_TYPE_KUJIALE_BUY_NAME,
        self::CART_TYPE_LIGHT_BUY => self::CART_TYPE_LIGHT_BUY_NAME,
	self::CART_TYPE_ADMIN_BUY => self::CART_TYPE_ADMIN_BUY_NAME,
	self::CART_TYPE_PACKAGE_NEW => self::CART_TYPE_PACKAGE_NEW_NAME,
    ];

    static function cart_type_array_value($key){
        return isset(self::$cart_type_array[$key])?self::$cart_type_array[$key]:'';
    }

}