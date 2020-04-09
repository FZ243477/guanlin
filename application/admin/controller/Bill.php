<?php


namespace app\admin\controller;
use app\common\constant\OrderConstant;
use app\common\helper\DatetimeHelper;
use app\common\helper\FinanceHelper;
use app\common\helper\PHPExcelHelper;
use app\admin\helper\ManagerHelper;

class Bill extends Base
{
    use DatetimeHelper;
    use PHPExcelHelper;
    use ManagerHelper;
    use FinanceHelper;

    public function __construct()
    {
        parent::__construct();
    }
    public function moneyList()
    {
        $where['partner_id'] = 0;
        $where['pay_status'] = OrderConstant::PAY_STATUS_DOING;
        $money = model('order')->where($where)->select();
        $date_time = $this->todayTimestamp();
        list(
            $ali_pay,
            $we_chat,
            $unionpay,
            $certificate,
            $today_ali_pay,
            $today_we_chat,
            $today_unionpay,
            $today_certificate,
            ) = $this->financeInfo($money, $date_time);
        $sum = $ali_pay + $we_chat + $unionpay + $certificate;
       /* foreach($money as $k=>$v){
            $sum += $v['total_fee'];
        }*/
        //本月收入
        $moneyDay = $this->MonthTimetamp();
        //取得当月0点的Unix时间戳
        $day = date('Y-m-d H:i:s', $moneyDay[0]);
        //取得昨天0点的Unix时间戳
        $month = date('Y-m-d H:i:s', $moneyDay[1]);
        $map['pay_status'] = OrderConstant::PAY_STATUS_DOING;
        $map['partner_id'] = 0;
        $map['pay_time'] = ['between', [$day, $month]];
        $money = model('order')->where($map)->select();
        list(
            $ali_pay,
            $we_chat,
            $unionpay,
            $certificate,
            ) = $this->financeInfo($money, $date_time);
        $all_money_month = $ali_pay + $we_chat + $unionpay + $certificate;
        if(!$all_money_month){
            $all_money_month = "0.00";
        }
        $mbmoney= $sum?($all_money_month/$sum)*100:0;


        $today = date('Y-m-d', $moneyDay[1]);
        $oldday = date('Y-m-d', $moneyDay[0]);
        $start_time = request()->param('start_time', $oldday);
        $end_time = request()->param('end_time', $today);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);

        /*if ($start_time && $end_time) {
            $where['add_time'] = ['between', [strtotime($start_time) ,strtotime($end_time) + 86399]];
        } else if ($start_time) {
            $where['add_time'] = ['egt', strtotime($start_time)];
        } else if ($end_time) {
            $where['add_time'] = ['lt', strtotime($end_time) + 86400];
        }*/

        $json_data = [];
        for ($i = strtotime($start_time); $i <= strtotime($end_time); $i+=86400) {
            $between_time = $this->todayTimestamp($i);
            $where = [];
            $where['partner_id'] = 0;
            $where['pay_status'] = OrderConstant::PAY_STATUS_DOING;
            $where['pay_time'] = ['between', [date('Y-m-d H:i:s', $between_time[0]), date('Y-m-d H:i:s', $between_time[1])]];
            $money = model('order')->where($where)->select();
            list(
                $ali_pay,
                $we_chat,
                $unionpay,
                $certificate,
                ) = $this->financeInfo($money, $date_time);
            $json_data['x_data'][] = date('Y-m-d', $i);
            $json_data['y_data'][] = $ali_pay + $we_chat + $unionpay + $certificate;
        }

        $this->assign('json_data',json_encode($json_data));

        $this->assign('all_money',$sum);
        $this->assign('mbmoney',$mbmoney);
        $this->assign('all_money_month',$all_money_month);

        /*$between_time = $this->todayTimestamp();
        $where['partner_id'] = 0;
        $where['pay_time'] = ['between',  [date('Y-m-d H:i:s', $between_time[0]), date('Y-m-d H:i:s', $between_time[1])]];
        $all_money_day = model('order')->where($where)->sum('total_fee');*/
        $all_money_day =  $today_ali_pay + $today_we_chat + $today_unionpay + $today_certificate;
        $this->assign('all_money_day',$all_money_day);
        $rbmoney = $sum?($all_money_day/$sum)*100:0;
        $this->assign('rbmoney',$rbmoney);

        return $this->fetch();
    }

    public function moneyDetail(){
        $map = [];
        $telephone      = trim(input("get.telephone"));
        $realname    = trim(input("get.realname"));
        $status         = input("get.status");
        $cate         = input("get.cate");
        $pay_way          = trim(input("get.pay_way"));
        $starttime = input('get.starttime','','trim');
        $endtime   = input('get.endtime','','trim');

        if ($starttime && $endtime) {
            $map['a.add_time'] = ['between',[strtotime($starttime),strtotime($endtime)]];
        }elseif ($starttime) {
            $map['a.add_time'] = ['egt',strtotime($starttime)];
        }elseif ($endtime) {
            $map['a.add_time'] = ['elt',strtotime($endtime)];
        }

        if($realname){
            $map['b.nickname'] = ['like',"%$realname%"];
        }
        if($telephone){
            $map['b.telephone']   = ['like',"%$telephone%"];
        }
        if($status !== false && $status !=""){
            $map['a.status'] = $status;
        }
        if($cate){
            $map['a.cate'] = $cate;
        }
        if($pay_way){
            $map['a.pay_way'] = $pay_way;
        }
        $map['a.partner_id'] = 0;
        $res = model("money_water")->alias('a')
            ->join('user b', 'a.user_id = b.id', 'left')
            ->field('b.telephone,b.nickname user_name,a.*')
            ->order('a.add_time desc')
            ->where($map)
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('res',$res);
        $this->assign('telephone',$telephone);
        $this->assign('realname',$realname);
        $this->assign('status',$status);
        $this->assign('cate',$cate);
        $this->assign('pay_way',$pay_way);
        $this->assign('starttime',$starttime);
        $this->assign('endtime',$endtime);
        return $this->fetch();
    }


    //积分交易明细
    public function integralDetail()
    {
        $map = [];
        $telephone      = trim(input("get.telephone"));
        $realname    = trim(input("get.realname"));
        $status         = input("get.status");
        $cate         = input("get.cate");
        $pay_way          = trim(input("get.pay_way"));
        $starttime = input('get.starttime','','trim');
        $endtime   = input('get.endtime','','trim');

        if ($starttime && $endtime) {
            $map['a.add_time'] = ['between',[strtotime($starttime),strtotime($endtime)]];
        }elseif ($starttime) {
            $map['a.add_time'] = ['egt',strtotime($starttime)];
        }elseif ($endtime) {
            $map['a.add_time'] = ['elt',strtotime($endtime)];
        }

        if($realname){
            $map['b.nickname user_name'] = ['like',"%$realname%"];
        }
        if($telephone){
            $map['b.telephone']   = ['like',"%$telephone%"];
        }
        if($status !== false && $status !=""){
            $map['a.status'] = $status;
        }
        if($cate){
            $map['a.cate'] = $cate;
        }
        if($pay_way){
            $map['a.pay_way'] = $pay_way;
        }
        $map['a.partner_id'] = 0;
        $res = model("integral_record")->alias('a')
            ->join('user b', 'a.user_id = b.id', 'left')
            ->field('b.telephone,b.nickname user_name,a.*')
            ->order('a.add_time desc')
            ->where($map)
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('res',$res);
        $this->assign('telephone',$telephone);
        $this->assign('realname',$realname);
        $this->assign('status',$status);
        $this->assign('cate',$cate);
        $this->assign('pay_way',$pay_way);
        $this->assign('starttime',$starttime);
        $this->assign('endtime',$endtime);
        return $this->fetch();
    }


    public function scoreoutexcel(){
        $map = [];
        $telephone      = input("get.telephone");
        $realname    = input("get.realname");
        $status         = input("get.status");
        $starttime = input('get.starttime');
        $endtime   = input('get.endtime');
        if ($starttime && $endtime) {
            $map['a.add_time'] = ['between', [$starttime,$endtime]];
        }elseif ($starttime) {
            $map['a.add_time'] = ['egt',$starttime];
        }elseif ($endtime) {
            $map['a.add_time'] = ['elt',$endtime];
        }
        if($realname){
            $map['b.nickname user_name'] = ['like',"%$realname%"];
        }
        if($telephone){
            $map['b.telephone']   = ['like',"%$telephone%"];
        }
        if($status){
            $map['a.status'] = $status;
        }

        $res = model("integral_record")->alias('a')
            ->join('user b', 'a.user_id = b.id', 'left')
            ->field('b.telephone,b.nickname user_name,a.transaction,a.integral_before,a.integral,a.integral_after,a.remark,a.pay_way,a.cate,a.status,a.add_time')
            ->order('a.add_time desc')
            ->where($map)
            ->select();

        $data = [];

        foreach($res as $k => $v) {
            if ($v['cate'] == 1) {
                $v['cate'] = '订单消费';
            } else if ($v['cate'] == 2) {
                $v['cate'] = "充值";
            } else if ($v['cate'] == 3) {
                $v['cate'] = "提现";
            } else if ($v['cate'] == 4) {
                $v['cate'] = "佣金";
            } else if ($v['cate'] == 5) {
                $v['cate'] = "订单退款";
            } else if ($v['cate'] == 6) {
                $v['cate'] = "系统修改";
            } else if ($v['cate'] == 7) {
                $v['cate'] = "注册赠送";
            } else if ($v['cate'] == 8) {
                $v['cate'] = "订单抵扣";
            } else if ($v['cate'] == 9) {
                $v['cate'] = "订单赠送";
            } else {
                $v['cate'] = "未知";
            }

            // 1余额 2微信 3支付宝
            if ($v['pay_way'] == 1) {
                $v['pay_way'] = "支付宝";
            } else if ($v['pay_way'] == 2) {
                $v['pay_way'] = "微信支付";
            } else if ($v['pay_way'] == 3) {
                $v['pay_way'] = "银联支付";
            } else {
                $v['pay_way'] = "未知";
            }

            // 1余额 2微信 3支付宝
            if ($v['status'] == 0) {
                $v['status'] = "交易中";
            } else if ($v['status'] == 1) {
                $v['status'] = "交易成功";
            } else if ($v['status'] == 2) {
                $v['status'] = "交易失败";
            } else {
                $vv['status'] = "未知";
            }
            $data[$k]['user_name'] = $v['user_name'];
            $data[$k]['telephone'] = $v['telephone'];
            $data[$k]['transaction'] = $v['transaction'];
            $data[$k]['integral'] = $v['integral'];
            $data[$k]['remark'] = $v['remark'];
            $data[$k]['pay_way'] = $v['pay_way'];
            $data[$k]['cate'] = $v['cate'];
            $data[$k]['status'] = $v['status'];
            $data[$k]['add_time'] = $v['add_time'];

        }


        $headArr = ['用户姓名','手机号','流水号','积分','描述','支付方式','明细类型','状态','添加时间'];
        $filename = '账户积分明细';

        $before_json = [];
        $after_json = [];

        $content = '导出账户积分明细息';
        $this->managerLog($this->manager_id, $content, $before_json, $after_json);

        $this->excelExport($filename, $headArr, $data);
    }

    public function outexcel(){
        $map = [];
        $telephone      = input("get.telephone");
        $realname    = input("get.realname");
        $status         = input("get.status");
        $starttime = input('get.starttime');
        $endtime   = input('get.endtime');
        if ($starttime && $endtime) {
            $map['a.add_time'] = ['between', [$starttime,$endtime]];
        }elseif ($starttime) {
            $map['a.add_time'] = ['egt',$starttime];
        }elseif ($endtime) {
            $map['a.add_time'] = ['elt',$endtime];
        }
        if($realname){
            $map['b.nickname user_name'] = ['like',"%$realname%"];
        }
        if($telephone){
            $map['b.telephone']   = ['like',"%$telephone%"];
        }
        if($status){
            $map['a.status'] = $status;
        }

        $res = model("money_water")->alias('a')
            ->join('user b', 'a.user_id = b.id', 'left')
            ->join('order c', 'a.order_id = c.id', 'left')
            ->field('c.order_no,b.telephone,b.nickname user_name,a.transaction,a.money_before,a.money,a.money_after,a.remark,a.pay_way,a.cate,a.status,a.add_time')
            ->order('a.add_time desc')
            ->where($map)
            ->select();

        $data = [];

        foreach($res as $k => $v) {
            $prexf = '+';
            if ($v['cate'] == 1) {
                $v['cate'] = '订单消费';
                $prexf = '-';
            } else if ($v['cate'] == 2) {
                $v['cate'] = "充值";
                $prexf = '+';
            } else if ($v['cate'] == 3) {
                $v['cate'] = "提现";
                $prexf = '-';
            } else if ($v['cate'] == 4) {
                $v['cate'] = "佣金";
                $prexf = '+';
            } else if ($v['cate'] == 5) {
                $v['cate'] = "订单退款";
                $prexf = '+';
            } else if ($v['cate'] == 6) {
                $v['cate'] = "系统修改";
            } else if ($v['cate'] == 7) {
                $v['cate'] = "注册赠送";
            } else if ($v['cate'] == 8) {
                $v['cate'] = "订单抵扣";
            } else if ($v['cate'] == 9) {
                $v['cate'] = "订单赠送";
            } else {
                $v['cate'] = "未知";
            }

            // 1余额 2微信 3支付宝
            if ($v['pay_way'] == 1) {
                $v['pay_way'] = "支付宝";
            } else if ($v['pay_way'] == 2) {
                $v['pay_way'] = "微信支付";
            } else if ($v['pay_way'] == 3) {
                $v['pay_way'] = "银联支付";
            } else {
                $v['pay_way'] = "未知";
            }

            // 1余额 2微信 3支付宝
            if ($v['status'] == 0) {
                $v['status'] = "交易中";
            } else if ($v['status'] == 1) {
                $v['status'] = "交易成功";
            } else if ($v['status'] == 2) {
                $v['status'] = "交易失败";
            } else {
                $vv['status'] = "未知";
            }


            $data[$k]['order_no'] = $v['order_no'];
            $data[$k]['user_name'] = $v['user_name'];
            $data[$k]['telephone'] = $v['telephone'];
            $data[$k]['transaction'] = $v['transaction'];
            $data[$k]['money'] = $prexf.$v['money'];
            $data[$k]['remark'] = $v['remark'];
            $data[$k]['pay_way'] = $v['pay_way'];
            $data[$k]['cate'] = $v['cate'];
            $data[$k]['status'] = $v['status'];
            $data[$k]['add_time'] = $v['add_time'];

        }

        $headArr = ['订单号','下单人姓名','下单人手机号','银行流水号','支付金额','描述','支付方式','明细类型','状态','生成时间'];
        $filename = '账户金额明细';

        $before_json = [];
        $after_json = [];

        $content = '导出账户金额明细息';
        $this->managerLog($this->manager_id, $content, $before_json, $after_json);

        $this->excelExport($filename, $headArr, $data);
    }

}