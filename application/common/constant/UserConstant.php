<?php


namespace app\common\constant;


class UserConstant
{
    const USER_ACCESS_HOME_PAGE = 0; //访问首页
    const USER_ACCESS_GOODS_DETAIL = 1; //访问商品详情

    const USER_ACCESS_HOME_PAGE_NAME = '访问商城首页'; //访问首页
    const USER_ACCESS_GOODS_DETAIL_NAME = '访问商品详情'; //访问商品详情

    static $uer_access_array = [
        self::USER_ACCESS_HOME_PAGE => self::USER_ACCESS_HOME_PAGE_NAME,
        self::USER_ACCESS_GOODS_DETAIL => self::USER_ACCESS_GOODS_DETAIL_NAME,
    ];

    static function uer_access_value($key){
        return self::$uer_access_array[$key];
    }


    const USER_COUPON_HEADER = 'VIP';

    const REG_SOURCE_CGI = 0; //小程序
    const REG_SOURCE_APP = 1; //APP端
    const REG_SOURCE_PC = 2; //PC端
    const REG_SOURCE_PHONE = 3; //手机端
    const REG_SOURCE_EXPORT = 4; //后台导入
    const REG_SOURCE_UNKNOWN = 5; //未知

    const REG_SOURCE_CGI_NAME = '小程序'; //APP端
    const REG_SOURCE_APP_NAME = 'APP端'; //APP端
    const REG_SOURCE_PC_NAME = 'PC端'; //PC端
    const REG_SOURCE_PHONE_NAME = '手机端'; //手机端
    const REG_SOURCE_EXPORT_NAME = '后台导入'; //后台导入
    const REG_SOURCE_UNKNOWN_NAME = '未知'; //未知

    static $reg_source_array = [
        self::REG_SOURCE_CGI => self::REG_SOURCE_CGI_NAME,
        self::REG_SOURCE_APP => self::REG_SOURCE_APP_NAME,
        self::REG_SOURCE_PC => self::REG_SOURCE_PC_NAME,
        self::REG_SOURCE_PHONE => self::REG_SOURCE_PHONE_NAME,
        self::REG_SOURCE_EXPORT => self::REG_SOURCE_EXPORT_NAME,
        self::REG_SOURCE_UNKNOWN => self::REG_SOURCE_UNKNOWN_NAME,
    ];

    static function reg_source_value($key){
        return self::$reg_source_array[$key];
    }

    static function reg_source_array(){
        return self::$reg_source_array;
    }



}