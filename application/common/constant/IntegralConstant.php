<?php


namespace app\common\constant;


class IntegralConstant
{

    const INTEGRAL_CATE_TYPE_SIGN = 1; //签到赠送
    const INTEGRAL_CATE_TYPE_REGISTER = 2; //注册赠送
    const INTEGRAL_CATE_TYPE_ORDER_GIVE = 3; //订单消费
    const INTEGRAL_CATE_TYPE_ORDER_BUY = 4; //订单抵扣
    const INTEGRAL_CATE_TYPE_ORDER_REFUND = 5; //订单退款
    const INTEGRAL_CATE_TYPE_ADMIN = 6; //系统修改
    const INTEGRAL_CATE_TYPE_INVITE = 7; //邀请好友
    const INTEGRAL_CATE_TYPE_ORDER_CANCEL = 8; //取消订单

    const INTEGRAL_CATE_TYPE_SIGN_NAME = '签到赠送';
    const INTEGRAL_CATE_TYPE_REGISTER_NAME = '注册赠送';
    const INTEGRAL_CATE_TYPE_ORDER_GIVE_NAME = '订单消费赠送积分';
    const INTEGRAL_CATE_TYPE_ORDER_BUY_NAME = '订单抵扣';
    const INTEGRAL_CATE_TYPE_ORDER_REFUND_NAME = '订单退款';
    const INTEGRAL_CATE_TYPE_ADMIN_NAME = '系统修改';
    const INTEGRAL_CATE_TYPE_INVITE_NAME = '邀请好友';
    const INTEGRAL_CATE_TYPE_ORDER_CANCEL_NAME = '取消订单';

    static $integral_cate_type_array = [
        self::INTEGRAL_CATE_TYPE_SIGN => self::INTEGRAL_CATE_TYPE_SIGN_NAME,
        self::INTEGRAL_CATE_TYPE_REGISTER => self::INTEGRAL_CATE_TYPE_REGISTER,
        self::INTEGRAL_CATE_TYPE_ORDER_GIVE => self::INTEGRAL_CATE_TYPE_ORDER_GIVE,
        self::INTEGRAL_CATE_TYPE_ORDER_BUY => self::INTEGRAL_CATE_TYPE_ORDER_BUY,
        self::INTEGRAL_CATE_TYPE_ORDER_REFUND => self::INTEGRAL_CATE_TYPE_ORDER_REFUND,
        self::INTEGRAL_CATE_TYPE_ADMIN => self::INTEGRAL_CATE_TYPE_ADMIN,
        self::INTEGRAL_CATE_TYPE_INVITE => self::INTEGRAL_CATE_TYPE_INVITE_NAME,
        self::INTEGRAL_CATE_TYPE_ORDER_CANCEL => self::INTEGRAL_CATE_TYPE_ORDER_CANCEL_NAME,
    ];

    static function integral_cate_type_array_value($key){
        return self::$integral_cate_type_array[$key];
    }

    const INTEGRAL_USE_TYPE_REGISTER = '注册获得积分';
    const INTEGRAL_USE_TYPE_SIGN = '签到获得积分';
    const INTEGRAL_USE_TYPE_ORDER_GIVE = '购买商品获得积分';
    const INTEGRAL_USE_TYPE_INVITE_GIVE = '邀请好友获得积分';
    const INTEGRAL_USE_TYPE_ORDER_BUY = '下单积分抵扣';
    const INTEGRAL_USE_TYPE_REFUND_ORDER = '部分订单商品退款获得积分';
    const INTEGRAL_USE_TYPE_REFUND_GOODS = '部分订单商品退款返还赠送积分';
    const INTEGRAL_USE_TYPE_CANCEL_ORDER = '取消订单返还积分';


}