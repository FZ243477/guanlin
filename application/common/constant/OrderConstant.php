<?php


namespace app\common\constant;


class OrderConstant
{

    const ORDER_NO_STR_PREFIX = 'YPH'; //主平台订单号

    const ORDER_STATUS_WAIT_PAY = 1; //待付款
    const ORDER_STATUS_FINAL_ORDER = 2;//待付尾款
    const ORDER_STATUS_AUDIT_ORDER = 3;//待审核
    const ORDER_STATUS_WAIT_SEND = 4; //待发货
    const ORDER_STATUS_WAIT_RECEIVE = 5; //待安装
    const ORDER_STATUS_FINISH_ORDER = 6;//订单完成
    const ORDER_STATUS_CANCEL = 7; //取消订单
    const ORDER_STATUS_REJECTED = 8; //驳回


    const ORDER_STATUS_WAIT_PAY_NAME = '待付款'; //待付款
    const ORDER_STATUS_FINAL_ORDER_NAME = '待付尾款';//待付尾款
    const ORDER_STATUS_AUDIT_ORDER_NAME = '待审核';//待审核
    const ORDER_STATUS_WAIT_SEND_NAME = '待发货'; //待发货
    const ORDER_STATUS_WAIT_RECEIVE_NAME = '待安装'; //待安装
    const ORDER_STATUS_WAIT_COMMENT_NAME = '待评价'; //待评价
    const ORDER_STATUS_FINISH_ORDER_NAME = '订单完成'; //订单完成
    const ORDER_STATUS_CANCEL_NAME = '已取消'; //取消订单
    const ORDER_STATUS_REJECTED_NAME = '审核未通过'; //取消订单

    static $order_status_array = [
        self::ORDER_STATUS_CANCEL => self::ORDER_STATUS_CANCEL_NAME,
        self::ORDER_STATUS_WAIT_PAY => self::ORDER_STATUS_WAIT_PAY_NAME,
        self::ORDER_STATUS_WAIT_SEND => self::ORDER_STATUS_WAIT_SEND_NAME,
        self::ORDER_STATUS_WAIT_RECEIVE => self::ORDER_STATUS_WAIT_RECEIVE_NAME,
        self::ORDER_STATUS_FINISH_ORDER => self::ORDER_STATUS_FINISH_ORDER_NAME,
        self::ORDER_STATUS_AUDIT_ORDER => self::ORDER_STATUS_AUDIT_ORDER_NAME,
        self::ORDER_STATUS_FINAL_ORDER => self::ORDER_STATUS_FINAL_ORDER_NAME,
        self::ORDER_STATUS_REJECTED => self::ORDER_STATUS_REJECTED_NAME,
    ];

    static function order_status_array_value($key){
        return isset(self::$order_status_array[$key])?self::$order_status_array[$key]:'';
    }


    const ORDER_PAY_WAY_ALIPAY = 1; //支付宝
    const ORDER_PAY_WAY_WXPAY = 2; //微信支付
    const ORDER_PAY_WAY_UNIONPAY = 3; //银联支付
    const ORDER_PAY_WAY_CERTIFICATE = 4; //线下支付，上传凭证



    const ORDER_PAY_WAY_ALIPAY_NAME = '支付宝'; //支付宝
    const ORDER_PAY_WAY_WXPAY_NAME = '微信支付'; //微信支付
    const ORDER_PAY_WAY_UNIONPAY_NAME = '银联支付'; //银联支付
    const ORDER_PAY_WAY_CERTIFICATE_NAME = '线下支付'; //银联支付

    static $order_pay_array = [
        self::ORDER_PAY_WAY_ALIPAY => self::ORDER_PAY_WAY_ALIPAY_NAME,
        self::ORDER_PAY_WAY_WXPAY => self::ORDER_PAY_WAY_WXPAY_NAME,
        self::ORDER_PAY_WAY_UNIONPAY => self::ORDER_PAY_WAY_UNIONPAY_NAME,
        self::ORDER_PAY_WAY_CERTIFICATE => self::ORDER_PAY_WAY_CERTIFICATE_NAME,
    ];

    static function order_pay_array_value($key){
        return isset(self::$order_pay_array[$key])?self::$order_pay_array[$key]:'';
    }


    const ORDER_CERTIFICATE_NONE = 0; //没上传凭证
    const ORDER_CERTIFICATE_DOING = 1; //上传凭证中
    const ORDER_CERTIFICATE_DONE = 2; //上传凭证完成

    const PAY_STATUS_NONE = 0; //未支付
    const PAY_STATUS_DOING = 1; //已支付

    const PAY_ORDER_STATUS_ALL= 0; //全额支付
    const PAY_ORDER_STATUS_NONE_DEPOSIT = 1; //定金支付,未付定金
    const PAY_ORDER_STATUS_DOING_DEPOSIT = 2; //2，定金支付，未付尾款，
    const PAY_ORDER_STATUS_DOING_END = 3; //，定金支付，已付尾

    const PAY_ORDER_STATUS_ALL_NAME = '全额支付'; //全额支付
    const PAY_ORDER_STATUS_NONE_DEPOSIT_NAME = '待付定金'; //定金支付,未付定金
    const PAY_ORDER_STATUS_DOING_DEPOSIT_NAME = '待付尾款'; //2，定金支付，未付尾款，
    const PAY_ORDER_STATUS_DOING_END_NAME = '已付尾款'; //，定金支付，已付尾

    static $pay_order_status_array = [
        self::PAY_ORDER_STATUS_ALL => self::PAY_ORDER_STATUS_ALL_NAME,
        self::PAY_ORDER_STATUS_NONE_DEPOSIT => self::PAY_ORDER_STATUS_NONE_DEPOSIT_NAME,
        self::PAY_ORDER_STATUS_DOING_DEPOSIT => self::PAY_ORDER_STATUS_DOING_DEPOSIT_NAME,
        self::PAY_ORDER_STATUS_DOING_END => self::PAY_ORDER_STATUS_DOING_END_NAME,
    ];

    static function pay_order_status_array_value($key){
        return isset(self::$pay_order_status_array[$key])?self::$pay_order_status_array[$key]:'';
    }

    const ORDER_SOURCE_CGI = 0; //小程序
    const ORDER_SOURCE_APP = 1; //APP端
    const ORDER_SOURCE_PC = 2; //PC端
    const ORDER_SOURCE_PHONE = 3; //手机端
    const ORDER_SOURCE_ADMIN = 4; //后台录入

    const ORDER_SOURCE_CGI_NAME = '小程序'; //APP端
    const ORDER_SOURCE_APP_NAME = 'APP端'; //APP端
    const ORDER_SOURCE_PC_NAME = 'PC端'; //PC端
    const ORDER_SOURCE_PHONE_NAME = '手机端'; //手机端
    const ORDER_SOURCE_ADMIN_NAME = '后台录入'; //后台录入

    static $order_source_array = [
        self::ORDER_SOURCE_CGI => self::ORDER_SOURCE_CGI_NAME,
        self::ORDER_SOURCE_APP => self:: ORDER_SOURCE_APP_NAME,
        self::ORDER_SOURCE_PC => self::ORDER_SOURCE_PC_NAME,
        self::ORDER_SOURCE_PHONE => self::ORDER_SOURCE_PHONE_NAME,
        self::ORDER_SOURCE_ADMIN => self::ORDER_SOURCE_ADMIN_NAME,
    ];

    static function order_source_value($key){
        return isset(self::$order_source_array[$key])?self::$order_source_array[$key]:'';
    }
}