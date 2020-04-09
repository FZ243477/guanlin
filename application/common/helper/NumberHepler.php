<?php


namespace app\common\helper;


trait NumberHepler
{
    /**
     * @author cx
     * 数字转换成万亿等单位
     * @param $original_num @原数字或整数数据
     * @param string $num_callback @余数部分
     * @param int $i  递归次数
     * @return string
     */
    private function numberToUnit($original_num, $num_callback = '', $i=0)
    {
        $original_num = number_format($original_num, 2, '.' , ',');
        return $original_num;
        /*$arr = ['','万', '亿', '兆'];
        if ($original_num < 10000) { //小于10000直接返回
            if ($num_callback) {
                $num_callback = '.'.$num_callback;
            }
            return $original_num.$num_callback.$arr[$i];
        }

        $integer = floor($original_num/10000); //整数部分
        $remainder = $original_num%10000; //余数部分
        $point = explode('.', $original_num);
        if (isset($point[1])) {
            $remainder .= $point[1]; //小数点
        }
        if ($i > 0) {
            $remainder .= $arr[$i].$num_callback; //递归拼接前面的单位
        }
	    $i++;
        return $this->numberToUnit($integer, $remainder, $i);*/
    }
}