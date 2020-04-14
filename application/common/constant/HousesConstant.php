<?php


namespace app\common\constant;


class HousesConstant
{
    const HOUSES_TYPE_VR = 1; //vr
    const HOUSES_TYPE_CASE = 2; //实景案例
    const HOUSES_TYPE_ARTICLE = 3; //文章

    const HOUSES_TYPE_VR_NAME = 'VR'; //vr
    const HOUSES_TYPE_CASE_NAME = '实景案例'; //实景案例
    const HOUSES_TYPE_ARTICLE_NAME = '文章'; //文章

    static $house_type_array = [
        self::HOUSES_TYPE_VR => self::HOUSES_TYPE_VR_NAME,
        self::HOUSES_TYPE_CASE => self::HOUSES_TYPE_CASE_NAME,
        self::HOUSES_TYPE_ARTICLE => self::HOUSES_TYPE_ARTICLE_NAME,
    ];

    static function house_type_array_value($key){
        return isset(self::$house_type_array[$key])?self::$house_type_array[$key]:'';
    }
}