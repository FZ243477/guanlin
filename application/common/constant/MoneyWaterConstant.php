<?php


namespace app\common\constant;


class MoneyWaterConstant
{
    //'消费类型  1  增加    2减少',
    const MONEY_WATER_TYPE_ADD = 1;
    const MONEY_WATER_TYPE_SUB= 2;

    //'状态 0 交易中 1交易成功  2交易失败'
    const MONEY_WATER_STATUS_DOING = 0;
    const MONEY_WATER_STATUS_SUCCESS = 1;
    const MONEY_WATER_STATUS_FAILURE = 2;

    //'类型: 1订单消费 2充值 3提现  4佣金 5订单退款  6系统修改',
    const MONEY_WATER_CATE_ORDER_BUY = 1;
    const MONEY_WATER_CATE_RECHARGE = 2;
    const MONEY_WATER_CATE_WITHDRAW = 3;
    const MONEY_WATER_CATE_COMMISSION = 4;
    const MONEY_WATER_CATE_ORDER_REFUND = 5;
    const MONEY_WATER_CATE_SYSTEM_MODIFY = 6;
}