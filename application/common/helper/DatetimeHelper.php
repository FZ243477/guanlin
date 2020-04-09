<?php


namespace app\common\helper;


trait DatetimeHelper
{
    private function todayTimestamp($time = '')
    {
        if (!$time) {
            $time = time();
        }
        $start = mktime(0,0,0,date("m",$time),date("d",$time),date("Y",$time));
        $end = mktime(23,59,59,date("m",$time),date("d",$time),date("Y",$time));
        return [$start, $end];
    }

    private function MonthTimetamp($month = '')
    {
        if (!$month) {
            $month = date('Y', time()) . "-" . date('m', time());
            $ii = date('m', time());//月份
        } else {
            $ii = substr($month, -2);
        }

        $start = strtotime($month . "-01 00:00:00");  //2015-01-01 00:00:00
        $end = strtotime($month . "-30 23:59:59");  //2015-12-31 23:59:59
        if (in_array($ii, array('1', '3', '5', '7', '8', '10', '12'))) {
            $end = strtotime($month . "-31 23:59:59");  //2015-12-31 23:59:59
        } elseif (in_array($ii, array('2'))) {
            $res = $this->runYear(date('Y', time()));
            $end = strtotime($res . " 23:59:59");  //2015-12-31 23:59:59
        } elseif (in_array($ii, array('4', '6', '9', '11'))) {
            $end = strtotime($month . "-30 23:59:59");  //2015-12-31 23:59:59
        }
        return [$start, $end];
    }

    private function runYear($year){
        $time = mktime(20,20,20,2,1,$year);//取得一个日期的 Unix 时间戳;
        if (date("t",$time)==29){ //格式化时间，并且判断2月是否是29天；
            return $year."-02-29";//是29天就输出时闰年；
        }else{
            return $year."-02-28";
        }
    }
}