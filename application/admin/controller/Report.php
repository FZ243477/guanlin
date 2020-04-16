<?php

namespace app\admin\controller;


use app\common\constant\UserConstant;
use app\common\helper\OriginalSqlHelper;
use app\admin\helper\ManagerHelper;
use app\common\helper\DatetimeHelper;


class Report extends Base
{
    use OriginalSqlHelper;
    use ManagerHelper;
    use DatetimeHelper;


    public $begin;
    public $end;

    public function _initialize()
    {
        parent::_initialize();
        $start_time = input('start_time');
        $end_time = input('end_time');
        if ($start_time && $end_time) {
            $begin = $start_time;
            $end = $end_time;
        } else {
            $lastweek = date('Y-m-d', strtotime("-1 month"));//默认显示30天前
            $start_time = input('begin', $lastweek);
            $end_time = input('end', date('Y-m-d'));
        }
        $this->begin = strtotime($start_time);
        $this->end = strtotime($end_time) + 86399;
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
    }


    /*
     * 新增用户统计
     */
    public function memReport()
    {
        $today = date('Y-m-d 00:00:00');
        $month = date('Y-m-01 00:00:00');
        $user['today'] = model('user')->where(['create_time' => ['egt', $today]])->count();//今日新增用户
        $user['month'] = model('user')->where(['create_time' => ['egt', $month]])->count();//本月新增用户
        $user['total'] = model('user')->count();//用户总数

        $this->assign('user', $user);

        $brr = [];
        $day = [];
        for ($i = $this->begin; $i <= $this->end; $i = $i + 24 * 3600) {
            $between_time = $this->todayTimestamp($i);
            $where = [];
            $where['create_time'] = ['between', [$between_time[0], $between_time[1]]];
            $brr[] = model('user')->where($where)->count();
            $day[] = date('Y-m-d', $i);
        }

        $result = ['data' => $brr, 'time' => $day];

        $this->assign('result', json_encode($result));
        $map['create_time'] = ['between', [$this->begin, $this->end]];
        $m = model("user");
        $day['Counts'] = $m->where($map)->count();      //总用户数=

        $this->assign('day', $day);
        $mapp['create_time'] = ['between', [$this->begin, $this->end]];
        $member = $m->where($mapp)->order("create_time desc")->order('id desc')->paginate(10, false, ['query' => request()->param()]);
        $this->assign('data', $member);
        return $this->fetch();
    }

    /*
     * 用户活跃统计
     */
    public function memActive()
    {
        $brr = [];
        $day = [];
        $data1 = [];
        for ($i = $this->begin; $i <= $this->end; $i = $i + 24 * 3600) {
            $between_time = $this->todayTimestamp($i);
            $where['creat_at'] = ['between', $between_time];
            $where['partner_id'] = 0;
            $brr[] = model('access')->where($where)->group('user_id')->count();
            $day[] = date('Y-m-d', $i);
        }
        $result = ['data' => $brr, 'time' => $day, 'data1' => $data1];
        $this->assign('result', json_encode($result));

        $dayw[0] = "0-2点";
        $dayw[1] = "2-4点";
        $dayw[2] = "4-6点";
        $dayw[3] = "6-8点";
        $dayw[4] = "8-10点";
        $dayw[5] = "10-12点";
        $dayw[6] = "12-14点";
        $dayw[7] = "14-16点";
        $dayw[8] = "16-18点";
        $dayw[9] = "18-20点";
        $dayw[10] = "20-22点";
        $dayw[11] = "22-24点";
        $brre = [];
        $data1 = [];
        for ($i = 0; $i <= 11; $i++) {
            $where = [];
            $where['time_day'] = $dayw[$i];
            $where['partner_id'] = 0;
            $where['type'] = UserConstant::USER_ACCESS_HOME_PAGE;
            $where['creat_at'] = ['between', [$this->begin, $this->end]];
            $brre[] = model('access')->where($where)->group('user_id')->count();

        }

        $result1 = ['data' => $brre, 'time' => $dayw, 'data1' => $data1];
        $this->assign('result1', json_encode($result1));
        return $this->fetch();
    }


}