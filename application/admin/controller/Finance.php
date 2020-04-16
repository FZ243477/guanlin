<?php


namespace app\admin\controller;
use app\admin\helper\ManagerHelper;
use app\common\constant\CartConstant;
use app\common\constant\OrderConstant;
use app\common\helper\DatetimeHelper;
use app\common\helper\FinanceHelper;
use app\common\helper\NumberHepler;
use app\common\helper\OriginalSqlHelper;
use app\common\constant\SystemConstant;
use PHPExcel;

class Finance extends Base
{
    use ManagerHelper;
    use OriginalSqlHelper;
    use NumberHepler;
    use DatetimeHelper;
    use FinanceHelper;
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 财务统计
     */
    public function finance()
    {
        $start_time = input('start_time');
        $end_time = input('end_time');
        if (!$start_time && !$end_time) {
            $lastweek = date('Y-m-d', strtotime("-1 month"));//默认显示30天前
            $start_time = input('begin', $lastweek);
            $end_time = input('end', date('Y-m-d'));
        }

        $assign = $this->getList($start_time, $end_time);
        $is_export = input('is_export');
        if ($is_export == 1) {
            $this->export($assign, $start_time, $end_time);
        }
        $json_data = [];
        $date_time = $this->todayTimestamp();
        for ($i = strtotime($start_time); $i <= strtotime($end_time); $i+=86400) {
            $between_time = $this->todayTimestamp($i);
            $where = [];
            $where['order_time'] = ['between', [date('Y-m-d H:i:s', $between_time[0]), date('Y-m-d H:i:s', $between_time[1])]];
            $money = model('order')->where($where)->sum('pay_price');
            $json_data['x_data'][] = date('Y-m-d', $i);
            $json_data['y_data'][] = $money;
        }

        $this->assign('json_data',json_encode($json_data));
        $this->assign($assign);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        return $this->fetch();
    }

    public function export($assign, $start_time, $end_time)
    {

        $objPHPExcel = new PHPExcel();

        $objPHPExcel->getProperties();
        $headArr = ['时间', $start_time.' 至 '.$end_time];
        $this->getHeader($objPHPExcel, $headArr, 1);
        $i = 2;
        $headArr = ['总收入统计'];
        $this->getHeader($objPHPExcel, $headArr, $i);
        $headArr = ['总收入（元）','微信支付订单金额（元）','支付宝支付订单金额（元）','银联支付订单金额（元）', '线下支付订单金额（元）'];
        $this->getHeader($objPHPExcel, $headArr, $i+1);
        $headArr = [$assign['total_income'], $assign['we_chat'], $assign['ali_pay'], $assign['unionpay'], $assign['certificate']];
        $this->getHeader($objPHPExcel, $headArr, $i+2);

        $headArr = ['总支出统计'];
        $this->getHeader($objPHPExcel, $headArr, $i+4);
        $headArr = ['总支出（元）','已退款订单金额（元）','已提现订单金额（元）'];
        $this->getHeader($objPHPExcel, $headArr, $i+5);
        $headArr = [$assign['disburse_money'], $assign['refund_price'], $assign['withdraw']];
        $this->getHeader($objPHPExcel, $headArr, $i+6);

        $headArr = ['今日收入统计'];
        $this->getHeader($objPHPExcel, $headArr, $i+8);
        $headArr = ['总收入（元）','微信支付订单金额（元）','支付宝支付订单金额（元）','银联支付订单金额（元）', '线下支付订单金额（元）'];
        $this->getHeader($objPHPExcel, $headArr, $i+9);
        $headArr = [$assign['today_total_income'], $assign['today_we_chat'], $assign['today_ali_pay'], $assign['today_unionpay'], $assign['today_certificate']];
        $this->getHeader($objPHPExcel, $headArr, $i+10);

        $headArr = ['今日收入统计'];
        $this->getHeader($objPHPExcel, $headArr, $i+12);
        $headArr = ['总支出（元）','已退款订单金额（元）','已提现订单金额（元）'];
        $this->getHeader($objPHPExcel, $headArr, $i+13);
        $headArr = [$assign['today_disburse_money'], $assign['today_refund_price'], $assign['today_withdraw']];
        $this->getHeader($objPHPExcel, $headArr, $i+14);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objActSheet = $objPHPExcel->getActiveSheet();
        $objActSheet->getDefaultStyle()->getAlignment()->setWrapText(TRUE);
        $objActSheet->getColumnDimension('A')->setWidth('50');
        $objActSheet->getColumnDimension('B')->setWidth('50');
        $objActSheet->getColumnDimension('C')->setWidth('50');
        $objActSheet->getColumnDimension('D')->setWidth('50');
        $objActSheet->getColumnDimension('E')->setWidth('50');
        $objActSheet->mergeCells('B1:E1');
        $objActSheet->mergeCells('B'.$i.':E'.$i);
        $objActSheet->mergeCells('B'.($i+4).':E'.($i+4));
        $objActSheet->mergeCells('B'.($i+8).':E'.($i+8));
        $objActSheet->mergeCells('B'.($i+12).':E'.($i+12));
   /*     $objActSheet->mergeCells('A1:E1');
        $objActSheet->mergeCells('A4:C4');
        $objActSheet->mergeCells('A7:E7');*/
        $obj_color = \PHPExcel_Style_Color::COLOR_DARKYELLOW;
        $objActSheet->getStyle('A'.$i.':E'.$i)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($obj_color);
        $objActSheet->getStyle('A'.($i+4).':E'.($i+4))->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($obj_color);
        $objActSheet->getStyle('A'.($i+8).':E'.($i+8))->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($obj_color);
        $objActSheet->getStyle('A'.($i+12).':E'.($i+12))->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($obj_color);
        $fileName = '财务统计';
        $fileName .= "_" . date("Y_m_d", Request()->instance()->time()) . ".xls";

        $objPHPExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表

        header('Content-Type: application/vnd.ms-excel');

        header("Content-Disposition: attachment;filename=".$fileName);

        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        $objWriter->save('php://output'); // 文件通过浏览器下载

        exit();
    }

    public function getHeader($objPHPExcel, $headArr, $num)
    {
        $keyA = ord("A"); // 设置表头

        foreach ($headArr as $v) {

            $colum = chr($keyA);

            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . $num, $v);

            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . $num, $v);

            $keyA += 1;

        }

    }

    public function getList($start_time, $end_time)
    {
        //查询订单 支付的金额
        $order_model = model('order');

        $date_time = $this->todayTimestamp();
        $we_map = [];
        $we_map['order_time'] = ['between', $date_time];
        $total_income = $order_model->where($we_map)->sum('pay_price');
        $we_map['order_time'] = ['between', [$start_time, $end_time]];
        $today_total_income = $order_model->where($we_map)->sum('pay_price');

        $assign = [
            'total_income' => $this->numberToUnit($total_income),
            'today_total_income' => $this->numberToUnit($today_total_income),

        ];
        return $assign;
    }
}