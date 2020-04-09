<?php


namespace app\common\constant;


class PreferentialConstant
{
    const LIMIT_SALES_STATUS_TODO = 0; //限时活动未开始
    const LIMIT_SALES_STATUS_DOING = 1;//限时活动进行中
    const LIMIT_SALES_STATUS_ENDING = 2;//限时活动结束

    const PREFERENTIAL_TYPE_NORMAL_GOODS = 0;
    const PREFERENTIAL_TYPE_LIMIT_SALES = 1;
    const PREFERENTIAL_TYPE_POPULAR = 2;
    const PREFERENTIAL_TYPE_NEW_GOODS = 3;
}