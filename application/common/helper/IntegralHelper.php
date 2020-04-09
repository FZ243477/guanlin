<?php


namespace app\common\helper;

trait IntegralHelper
{
    /**
     * @param $cate 1 签到  2 注册赠送 3 订单消费  4 订单抵扣 5 订单退款 6 系统修改 7邀请好友
     * @param $integral 积分
     * @param $remark 积分使用备注
     * @param $type 0-获得 1-消费
     * @param $user_id 会员id
     * @param int $status 交易状态
     * @param int $order_id 订单id
     * @param string $transaction 流水号
     * @param int $pay_way 支付方式
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function integral_record($cate, $integral, $remark, $type, $user_id, $status = 1, $order_id = 0, $transaction = '', $pay_way = 0 , $admin_id = 0)
    {

        $data['integral'] = $integral;

        if ($data['integral'] <= 0) {
            return false;
        }

        $data['remark'] = $remark;
        $data['cate'] = $cate;
        $data['type'] = $type;
        $data['user_id'] = $user_id;
        $data['order_id'] = $order_id;
        $data['transaction'] = $transaction;
        $data['pay_way'] = $pay_way;
        $data['status'] = $status;
        $data['admin_id'] = $admin_id;
        $data['add_time'] = date('Y-m-d H:i:s', time());

        $user_model = model('user');
        $integral_record_model = model('IntegralRecord');

        $data['integral_before'] = $user_model->where(['id' => $user_id])->value('integral');

        if ($order_id) {
            $find = $integral_record_model->where(['cate' => $cate, 'type' => $type, 'order_id' => $order_id])->find();
        } else {
            $find = [];
        }

        if ($type == 1) {//消费积分
            if (!$find) { //对于下单的积分操作
                $data['integral_after'] = $data['integral_before'] - $data['integral'];

                $res = $user_model->where(['id' => $user_id])->setDec('integral', $data['integral']);
                if (!$res) {
                    return ['status' => 0];
                }
            }
            if ($status == 2) {//对于下单的积分操作,退还
                $res = $user_model->where(['id' => $user_id])->setInc('integral', $data['integral']);
                if (!$res) {
                    return ['status' => 0];
                }
            }
        } else {//获得积分
            if ($status == 1) { //已完成才能获得积分
                $data['integral_after'] = $data['integral_before'] + $data['integral'];

                $res = $user_model->where(['id' => $user_id])->setInc('integral', $data['integral']);
                if (!$res) {
                    return ['status' => 0];
                }
            }

        }

        if ($find) { //已存在记录只改变状态
            $integral_record_model->update(['status' => $data['status']], ['id' => $find['id']]);
        } else {//添加积分记录
            $res =  $integral_record_model->insert($data);
            if (!$res) {
                return ['status' => 0];
            }
        }
        return ['status' => 1];
    }
}