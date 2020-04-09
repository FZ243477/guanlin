<?php


namespace app\api\controller;


use app\common\constant\OrderConstant;
use app\common\constant\SystemConstant;
use app\common\helper\OrderHelper;

class Withdraw extends Base
{
    use OrderHelper;

    public function __construct()
    {
        parent::__construct();
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录', 'data' => []]);
        }
    }

    public function withdraw()
    {
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }
        $money = request()->post('money');
        $type = request()->post('type', OrderConstant::ORDER_PAY_WAY_ALIPAY);
        if ($type == OrderConstant::ORDER_PAY_WAY_ALIPAY) {
            $account_number = request()->post('account_number');
            $real_name = request()->post('real_name');
            if (!$account_number) {
                ajaxReturn(['status' => 0, 'msg' => '请输入支付宝账号']);
            }
            if (!$real_name) {
                ajaxReturn(['status' => 0, 'msg' => '请输入真实姓名']);
            }
        } else if ($type == OrderConstant::ORDER_PAY_WAY_WXPAY) {
            $real_name = '';
            $account_number = model('user_access_token')->where(['user_id' => $this->user_id])->value('open_id');
            if (!$account_number) {
                ajaxReturn(['status' => 0, 'msg' => '您暂未关注公众号']);
            }
        } else {
            ajaxReturn(['status' => 0, 'msg' => '支付方式错误']);
        }
        if (!$money || $money < 0) {
            ajaxReturn(['status' => 0, 'msg' => '提现金额必须大于0']);
        }

        $user = model('user')->where(['id' => $this->user_id])->find();

        if ($money > $user['distribut_money']) {
            ajaxReturn(['status' => 0, 'msg' => '提现金额不可大于可提现佣金']);
        }
        //$orderName = '悦品荟佣金提现';
        $outTradeNo = $this->get_order_sn(OrderConstant::ORDER_NO_YJ_PREFIX);

        $data = [
            'user_id' => $this->user_id,
            'money' => $money,
            'status' => 0,
            'upidentity' => 1,
            'real_name' => $real_name,
            'account_number' => $account_number,
            'serial_number' => $outTradeNo,
            'type' => $type,
        ];
        $res = model('withdraw_record')->save($data);
        if ($res) {
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
        }
    }

}