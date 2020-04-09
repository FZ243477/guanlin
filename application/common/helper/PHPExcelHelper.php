<?php


namespace app\common\helper;
use PHPExcel;

trait PHPExcelHelper
{
    /**
     * excel表格导出

     * @param string $fileName 文件名称

     * @param array $headArr 表头名称

     * @param array $data 要导出的数据

     * @author static7  */

    private function excelExport($fileName = '', $headArr = [], $data = []) {


        $objPHPExcel = new PHPExcel();

        $objPHPExcel->getProperties();

        $keyA = ord("A"); // 设置表头

        foreach ($headArr as $v) {

            $colum = chr($keyA);

            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);

            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);

            $keyA += 1;

        }

        $column = 2;

        $objActSheet = $objPHPExcel->getActiveSheet();

        foreach ($data as $key => $rows) { // 行写入

            $span = ord("A");

            foreach ($rows as $keyName => $value) { // 列写入

                $objActSheet->setCellValue(chr($span) . $column, $value);

                $span++;

            }

            $column++;

        }

        $fileName .= "_" . date("Y_m_d", Request()->instance()->time()) . ".xls";

        //$fileName = iconv("utf-8", "gb2312", $fileName); // 重命名表

        $objPHPExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表

        header('Content-Type: application/vnd.ms-excel');

        header("Content-Disposition: attachment;filename=".$fileName);

        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        $objWriter->save('php://output'); // 文件通过浏览器下载

        exit();

    }

    private function exportOrder($lists, $file_name = '')
    {
        $objExcel = new \PHPExcel();
        //set document Property
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
        $objActSheet = $objExcel->getActiveSheet();
        $key = ord("A");
        $letter =explode(',',"A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y");
        $arrHeader = ['订单号','下单时间','支付流水号', '下单人姓名', '下单人手机号', '订单状态', '支付类型','商品金额',
            '优惠金额','物流费','上门服务费', '订单金额', '已付款金额', '待付款金额',
            '收货人', '收货人手机号', '收货地址',
            '商品名称', '商品编号', '供应商', '商品单价', '商品数量', '商品价格','商品实付价格', 'SKU'];
        //填充表头信息
        $lenth =  count($arrHeader);
        for($i = 0;$i < $lenth;$i++) {
            $objActSheet->setCellValue("$letter[$i]1","$arrHeader[$i]");
        };
        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objActSheet->getDefaultStyle()->getAlignment()->setWrapText(TRUE);
        //填充表格信息
        $i = 2;
        foreach($lists as $k=>$v){
            $objActSheet->setCellValue('A'.$i,$v['order_no']);
            $objActSheet->setCellValue('B'.$i,$v['order_time']);
            $objActSheet->setCellValue('C'.$i,$v['trade_no']);
            $objActSheet->setCellValue('D'.$i, $v['name']);
            $objActSheet->setCellValue('E'.$i, $v['tel']);
            $objActSheet->setCellValue('F'.$i, $v['order_status']);
            $objActSheet->setCellValue('G'.$i, $v['pay_way']);
            $objActSheet->setCellValue('H'.$i, $v['total_price']);
            $objActSheet->setCellValue('I'.$i, $v['coupon_price']);
            $objActSheet->setCellValue('J'.$i, $v['express_fee']);
            $objActSheet->setCellValue('K'.$i, $v['cover_fee']);
            $objActSheet->setCellValue('L'.$i, $v['total_fee']);
            $objActSheet->setCellValue('M'.$i, $v['pay_price']);
            $objActSheet->setCellValue('N'.$i, $v['pay_wait_price']);
            $objActSheet->setCellValue('O'.$i, $v['consignee']);
            $objActSheet->setCellValue('P'.$i, $v['telephone']);
            $objActSheet->setCellValue('Q'.$i, $v['place']);
            // 表格高度
            $objActSheet->getRowDimension($i)->setRowHeight(15);
            if ($v['order_goods']) {
                $count = count($v['order_goods']);
                $objActSheet->mergeCells('A'.$i.':'.'A'.($i+$count-1));
                $objActSheet->mergeCells('B'.$i.':'.'B'.($i+$count-1));
                $objActSheet->mergeCells('C'.$i.':'.'C'.($i+$count-1));
                $objActSheet->mergeCells('D'.$i.':'.'D'.($i+$count-1));
                $objActSheet->mergeCells('E'.$i.':'.'E'.($i+$count-1));
                $objActSheet->mergeCells('F'.$i.':'.'F'.($i+$count-1));
                $objActSheet->mergeCells('G'.$i.':'.'G'.($i+$count-1));
                $objActSheet->mergeCells('H'.$i.':'.'H'.($i+$count-1));
                $objActSheet->mergeCells('I'.$i.':'.'I'.($i+$count-1));
                $objActSheet->mergeCells('J'.$i.':'.'J'.($i+$count-1));
                $objActSheet->mergeCells('K'.$i.':'.'K'.($i+$count-1));
                $objActSheet->mergeCells('L'.$i.':'.'L'.($i+$count-1));
                $objActSheet->mergeCells('M'.$i.':'.'M'.($i+$count-1));
                $objActSheet->mergeCells('N'.$i.':'.'N'.($i+$count-1));
                $objActSheet->mergeCells('O'.$i.':'.'O'.($i+$count-1));
                $objActSheet->mergeCells('P'.$i.':'.'P'.($i+$count-1));
                $objActSheet->mergeCells('Q'.$i.':'.'Q'.($i+$count-1));
                foreach ($v['order_goods'] as $k1 => $v1) {
                    $objActSheet->setCellValue('R'.$i, $v1['goods_name']);
                    $objActSheet->setCellValue('S'.$i, $v1['goods_code']);
                    $objActSheet->setCellValue('T'.$i, $v1['store_name']);
                    $objActSheet->setCellValue('U'.$i, $v1['goods_price']);
                    $objActSheet->setCellValue('V'.$i, $v1['goods_num']);
                    $objActSheet->setCellValue('W'.$i, $v1['goods_num']*$v1['goods_price']);
                    $objActSheet->setCellValue('X'.$i, $v1['pay_price']);
                    $objActSheet->setCellValue('Y'.$i, $v1['sku_info']);

                    // 表格高度
                    $objActSheet->getRowDimension($i)->setRowHeight(15);
                    $i++;
                }
            } else {
                $i++;
            }
        }

        $width = [25,25,32,15,15,10,10,10,10,10,15,10,15,15,10,25,50,25,25,20,10,10,10,20,25];
        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth($width[0]);
        $objActSheet->getColumnDimension('B')->setWidth($width[1]);
        $objActSheet->getColumnDimension('C')->setWidth($width[2]);
        $objActSheet->getColumnDimension('D')->setWidth($width[3]);
        $objActSheet->getColumnDimension('E')->setWidth($width[4]);
        $objActSheet->getColumnDimension('F')->setWidth($width[5]);
        $objActSheet->getColumnDimension('G')->setWidth($width[6]);
        $objActSheet->getColumnDimension('H')->setWidth($width[7]);
        $objActSheet->getColumnDimension('I')->setWidth($width[8]);
        $objActSheet->getColumnDimension('J')->setWidth($width[9]);
        $objActSheet->getColumnDimension('K')->setWidth($width[10]);
        $objActSheet->getColumnDimension('L')->setWidth($width[11]);
        $objActSheet->getColumnDimension('M')->setWidth($width[12]);
        $objActSheet->getColumnDimension('N')->setWidth($width[13]);
        $objActSheet->getColumnDimension('O')->setWidth($width[14]);
        $objActSheet->getColumnDimension('P')->setWidth($width[15]);
        $objActSheet->getColumnDimension('Q')->setWidth($width[16]);
        $objActSheet->getColumnDimension('R')->setWidth($width[17]);
        $objActSheet->getColumnDimension('S')->setWidth($width[18]);
        $objActSheet->getColumnDimension('T')->setWidth($width[19]);
        $objActSheet->getColumnDimension('U')->setWidth($width[20]);
        $objActSheet->getColumnDimension('V')->setWidth($width[21]);
        $objActSheet->getColumnDimension('W')->setWidth($width[22]);
        $objActSheet->getColumnDimension('X')->setWidth($width[23]);
        $objActSheet->getColumnDimension('Y')->setWidth($width[24]);

        /*      $outfile = "信息列表.xlsx";
              ob_end_clean();
              header("Content-Type: application/force-download");
              header("Content-Type: application/octet-stream");
              header("Content-Type: application/download");
              header('Content-Disposition:inline;filename="'.$outfile.'"');
              header("Content-Transfer-Encoding: binary");
              header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
              header("Pragma: no-cache");
              $objWriter->save('php://output');*/
        $fileName = $file_name."_" . date("Y_m_d", Request()->instance()->time()) . ".xls";
        $objExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表

        header('Content-Type: application/vnd.ms-excel');

        header("Content-Disposition: attachment;filename=".$fileName);

        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');

        $objWriter->save('php://output'); // 文件通过浏览器下载

        exit();//        $this->excelExport('商品信息表', $headArr, $data_info);
    }

    private function exportOrderGoods($lists, $address_info, $file_name = '')
    {
        $objExcel = new \PHPExcel();
        //set document Property
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');

        $objActSheet = $objExcel->getActiveSheet();
        $address = $address_info['province'].$address_info['city'].$address_info['district'].$address_info['place'];
        $contact = $address_info['consignee'] . $address_info['telephone'];
        $objActSheet->mergeCells('A1:H1');
        $objActSheet->mergeCells('A2:F2');
        $objActSheet->mergeCells('G2:H2');
        $objActSheet->mergeCells('A3:H3');
        $objActSheet->setCellValue('A1','送货明细单');
        $objActSheet->setCellValue('A2','客户姓名及联系方式：'.$contact);
        $objActSheet->setCellValue('G2','送货时间：');
        $objActSheet->setCellValue('A3','送货地址：'.$address);

        $letter =explode(',',"A,B,C,D,E,F,G,H");
        $arrHeader = ['序号','商品名称','商品编码', '商品图片', '数量', '单价', '规格','备注'];
        //填充表头信息

        $lenth =  count($arrHeader);
        for($i = 0;$i < $lenth;$i++) {
            $objActSheet->setCellValue("$letter[$i]4","$arrHeader[$i]");
        };

        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objActSheet->getDefaultStyle()->getAlignment()->setWrapText(TRUE);

        $objActSheet->getStyle('A2:H3')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

        //填充表格信息
        $i = 4;
        $key_id = ord("A");
        $objActSheet->getRowDimension(1)->setRowHeight(35);
        $objActSheet->getRowDimension(2)->setRowHeight(35);
        $objActSheet->getRowDimension(3)->setRowHeight(35);
        $objActSheet->getRowDimension(4)->setRowHeight(30);
        foreach($lists as $k=>$v){
            $i++;
            $objActSheet->setCellValue(chr($key_id).$i,$k+1);
            $objActSheet->setCellValue(chr($key_id+1).$i,$v['goods_name']);
            $objActSheet->setCellValue(chr($key_id+2).$i,$v['goods_code']);

            if($i < 20 && !empty($v['goods_pic']) && file_exists('.'.$v['goods_pic'])){
                $image = '.'.$v['goods_pic'];
                $handle = fopen($image , 'r' ) ;
                if($handle) {
                    //这是一个坑,刚开始我把实例化图片类放在了循环外面,但是失败了,也就是每个图片都要实例化一次
                    $objDrawing = new \PHPExcel_Worksheet_Drawing();
                    $objDrawing->setPath($image);
                    // 设置图片的宽度
                    $objDrawing->setHeight(50);
                    $objDrawing->setWidth(50);
                    $objDrawing->setOffsetX(25);
                    $objDrawing->setOffsetY(5);
                    $objDrawing->setCoordinates(chr($key_id+3) . $i);
                    $objDrawing->setWorksheet($objActSheet);
                    fclose($handle);
                }
            } else {
                $objActSheet->setCellValue(chr($key_id+3).$i, $v['goods_pic']?picture_url_dispose($v['goods_pic']):'');
            }

            $objActSheet->setCellValue(chr($key_id+4).$i, $v['goods_num']);
            $objActSheet->setCellValue(chr($key_id+5).$i, $v['goods_price']);
            $objActSheet->setCellValue(chr($key_id+6).$i, $v['sku_info']);
            $objActSheet->setCellValue(chr($key_id+7).$i, $v['refund_reason']);
            // 表格高度
            $objActSheet->getRowDimension($i)->setRowHeight(50);
        }
        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth(15);
        $objActSheet->getColumnDimension('B')->setWidth(15);
        $objActSheet->getColumnDimension('C')->setWidth(15);
        $objActSheet->getColumnDimension('D')->setWidth(15);
        $objActSheet->getColumnDimension('E')->setWidth(10);
        $objActSheet->getColumnDimension('F')->setWidth(10);
        $objActSheet->getColumnDimension('G')->setWidth(15);
        $objActSheet->getColumnDimension('H')->setWidth(10);


        $objActSheet->getDefaultStyle()->getFont()->setName('仿宋');
        $objActSheet->getStyle('A1:H1')->getFont()->setSize(20); //字体大小
        $objActSheet->getStyle('A1:H3')->getFont()->setName('仿宋')->setBold(true);

        //设置单元格边框
        $style_array = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                )
            ) );
        $objActSheet->getStyle('A1:H'.$i)->applyFromArray($style_array);

        $style_array = array(
            'borders' => array(
                'outline' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THICK,
                    'color' =>array ('argb' => \PHPExcel_Style_Color::COLOR_DARKBLUE),
                )
            ) );
        $objActSheet->getStyle('A1:H'.$i)->applyFromArray($style_array);


        $style_array = array(
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'C6C3C6')
            )
        );
        $objActSheet->getStyle('A'.($i+1).':Z'.($i+100))->applyFromArray($style_array);
        $objActSheet->getStyle('I1:Z'.$i)->applyFromArray($style_array);

        $style_array = array(
            'fill' => array(
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'D9E1F2')
            )
        );
        $objActSheet->getStyle('A1:H1')->applyFromArray($style_array);

        $fileName = $file_name."_" . date("Y_m_d", Request()->instance()->time()) . ".xls";
        $objExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表

        header('Content-Type: application/vnd.ms-excel');

        header("Content-Disposition: attachment;filename=".$fileName);

        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');

        $objWriter->save('php://output'); // 文件通过浏览器下载

        exit();
    }

    private function excelPurchaseExport($lists, $file_name = '')
    {
        $objExcel = new \PHPExcel();
        //set document Property
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
        $objActSheet = $objExcel->getActiveSheet();
        $key = ord("A");
        $letter =explode(',',"A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S");
        $arrHeader = ['订单号','下单时间','订单已付款金额', '订单待付款金额','商品编号','商品名称','SKU','备注', '成本价',
            'B端价', '零售价', '成交价','数量','下单人姓名','下单人手机号', '收货人', '收货人手机号', '收货地址',];
        //填充表头信息
        $lenth =  count($arrHeader);
        for($i = 0;$i < $lenth;$i++) {
            $objActSheet->setCellValue("$letter[$i]1","$arrHeader[$i]");
        };
        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objActSheet->getDefaultStyle()->getAlignment()->setWrapText(TRUE);
        //填充表格信息
        $i = 2;
        foreach($lists as $k=>$v){
            $objActSheet->setCellValue('A'.$i,$v['parent_no']);
            $objActSheet->setCellValue('B'.$i,$v['order_time']);
            $objActSheet->setCellValue('C'.$i,$v['pay_price']);
            $objActSheet->setCellValue('D'.$i, $v['pay_wait_price']);
            $objActSheet->setCellValue('E'.$i, $v['goods_code']);
            $objActSheet->setCellValue('F'.$i, $v['goods_name']);
            $objActSheet->setCellValue('G'.$i, $v['sku_info']);
            $objActSheet->setCellValue('H'.$i, $v['goods_remark']);
            $objActSheet->setCellValue('I'.$i, $v['cost_price']);
            $objActSheet->setCellValue('J'.$i, $v['b_price']);
            $objActSheet->setCellValue('K'.$i, $v['goods_price']);
            $objActSheet->setCellValue('L'.$i, $v['goods_pay_price']);
            $objActSheet->setCellValue('M'.$i, $v['goods_num']);
            $objActSheet->setCellValue('N'.$i, $v['name']);
            $objActSheet->setCellValue('O'.$i, $v['tel']);
            $objActSheet->setCellValue('P'.$i, $v['consignee']);
            $objActSheet->setCellValue('Q'.$i, $v['telephone']);
            $objActSheet->setCellValue('R'.$i, $v['place']);
            // 表格高度
            $objActSheet->getRowDimension($i)->setRowHeight(15);
            $i++;
        }

        $width = [25,25,32,15,15,10,10,10,10,10,15,10,15,15,20,10,20,25,25,20,10,10,10,20,25];
        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth($width[0]);
        $objActSheet->getColumnDimension('B')->setWidth($width[1]);
        $objActSheet->getColumnDimension('C')->setWidth($width[2]);
        $objActSheet->getColumnDimension('D')->setWidth($width[3]);
        $objActSheet->getColumnDimension('E')->setWidth($width[4]);
        $objActSheet->getColumnDimension('F')->setWidth($width[5]);
        $objActSheet->getColumnDimension('G')->setWidth($width[6]);
        $objActSheet->getColumnDimension('H')->setWidth($width[7]);
        $objActSheet->getColumnDimension('I')->setWidth($width[8]);
        $objActSheet->getColumnDimension('J')->setWidth($width[9]);
        $objActSheet->getColumnDimension('K')->setWidth($width[10]);
        $objActSheet->getColumnDimension('L')->setWidth($width[11]);
        $objActSheet->getColumnDimension('M')->setWidth($width[12]);
        $objActSheet->getColumnDimension('N')->setWidth($width[13]);
        $objActSheet->getColumnDimension('O')->setWidth($width[14]);
        $objActSheet->getColumnDimension('P')->setWidth($width[15]);
        $objActSheet->getColumnDimension('Q')->setWidth($width[16]);
        $objActSheet->getColumnDimension('R')->setWidth($width[17]);
        $objActSheet->getColumnDimension('S')->setWidth($width[18]);


        /*      $outfile = "信息列表.xlsx";
              ob_end_clean();
              header("Content-Type: application/force-download");
              header("Content-Type: application/octet-stream");
              header("Content-Type: application/download");
              header('Content-Disposition:inline;filename="'.$outfile.'"');
              header("Content-Transfer-Encoding: binary");
              header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
              header("Pragma: no-cache");
              $objWriter->save('php://output');*/
        $fileName = $file_name."_" . date("Y_m_d", Request()->instance()->time()) . ".xls";
        $objExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表

        header('Content-Type: application/vnd.ms-excel');

        header("Content-Disposition: attachment;filename=".$fileName);

        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');

        $objWriter->save('php://output'); // 文件通过浏览器下载

        exit();//        $this->excelExport('商品信息表', $headArr, $data_info);
    }

    private function excelPurchaseAllExport($lists, $file_name = '')
    {
        $objExcel = new \PHPExcel();
        //set document Property
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
        $objActSheet = $objExcel->getActiveSheet();
        $key = ord("A");
        $letter =explode(',',"A,B,C,D,E,F,G,H,I,J");
        $arrHeader = ['采购单号','供应商','采购订单总价', '商品编号', '商品名称', 'SKU', '备注','成本价', '数量','订单号'];
        //填充表头信息
        $lenth =  count($arrHeader);
        for($i = 0;$i < $lenth;$i++) {
            $objActSheet->setCellValue("$letter[$i]1","$arrHeader[$i]");
        };
        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objActSheet->getDefaultStyle()->getAlignment()->setWrapText(TRUE);
        //填充表格信息
        $i = 2;
        foreach($lists as $k=>$v){
            $objActSheet->setCellValue('A'.$i,$v['purchase_no']);
            $objActSheet->setCellValue('B'.$i,$v['store_name']);
            $objActSheet->setCellValue('C'.$i,$v['purchase_price']);
            // 表格高度
            $objActSheet->getRowDimension($i)->setRowHeight(15);
            if ($v['order_goods']) {
                $count = count($v['order_goods']);
                $objActSheet->mergeCells('A'.$i.':'.'A'.($i+$count-1));
                $objActSheet->mergeCells('B'.$i.':'.'B'.($i+$count-1));
                $objActSheet->mergeCells('C'.$i.':'.'C'.($i+$count-1));
                foreach ($v['order_goods'] as $k1 => $v1) {
                    $objActSheet->setCellValue('D'.$i, $v1['goods_code']);
                    $objActSheet->setCellValue('E'.$i, $v1['goods_name']);
                    $objActSheet->setCellValue('F'.$i, $v1['sku_info']);
                    $objActSheet->setCellValue('G'.$i, $v1['goods_remark']);
                    $objActSheet->setCellValue('H'.$i, $v1['cost_price']);
                    $objActSheet->setCellValue('I'.$i, $v1['goods_num']);
                    $objActSheet->setCellValue('J'.$i, $v1['parent_no']);

                    // 表格高度
                    $objActSheet->getRowDimension($i)->setRowHeight(15);
                    $i++;
                }
            } else {
                $i++;
            }
        }

        $width = [25,25,32,25,25,25,25,10,10,25];
        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth($width[0]);
        $objActSheet->getColumnDimension('B')->setWidth($width[1]);
        $objActSheet->getColumnDimension('C')->setWidth($width[2]);
        $objActSheet->getColumnDimension('D')->setWidth($width[3]);
        $objActSheet->getColumnDimension('E')->setWidth($width[4]);
        $objActSheet->getColumnDimension('F')->setWidth($width[5]);
        $objActSheet->getColumnDimension('G')->setWidth($width[6]);
        $objActSheet->getColumnDimension('H')->setWidth($width[7]);
        $objActSheet->getColumnDimension('I')->setWidth($width[8]);
        $objActSheet->getColumnDimension('J')->setWidth($width[9]);

        /*      $outfile = "信息列表.xlsx";
              ob_end_clean();
              header("Content-Type: application/force-download");
              header("Content-Type: application/octet-stream");
              header("Content-Type: application/download");
              header('Content-Disposition:inline;filename="'.$outfile.'"');
              header("Content-Transfer-Encoding: binary");
              header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
              header("Pragma: no-cache");
              $objWriter->save('php://output');*/
        $fileName = $file_name."_" . date("Y_m_d", Request()->instance()->time()) . ".xls";
        $objExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表

        header('Content-Type: application/vnd.ms-excel');

        header("Content-Disposition: attachment;filename=".$fileName);

        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');

        $objWriter->save('php://output'); // 文件通过浏览器下载

        exit();//        $this->excelExport('商品信息表', $headArr, $data_info);
    }

    private function exportGoods($lists, $file_name = '')
    {
        $objExcel = new \PHPExcel();
        //set document Property
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
        $objActSheet = $objExcel->getActiveSheet();
        $key = ord("A");
        $letter =explode(',',"A,B,C,D,E,F,G,H,I,J,K,L,M");
        $arrHeader = ['ID', '商品名','商品编码','商品价格','供货价','库存','上架','上市时间', 'SKU名称', 'SKU价格', 'SKU供货价', 'SKU库存', 'SKU编码'];
        //填充表头信息
        $lenth =  count($arrHeader);
        for($i = 0;$i < $lenth;$i++) {
            $objActSheet->setCellValue("$letter[$i]1","$arrHeader[$i]");
        };

        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objActSheet->getDefaultStyle()->getAlignment()->setWrapText(TRUE);
        //填充表格信息
        $i = 2;
        foreach($lists as $k=>$v){
            $objActSheet->setCellValue('A'.$i,$v['id']);
            $objActSheet->setCellValue('B'.$i, $v['goods_name']);
            $objActSheet->setCellValue('C'.$i, $v['goods_code']);
            $objActSheet->setCellValue('D'.$i, $v['price']);
            $objActSheet->setCellValue('E'.$i, $v['cost_price']);
            $objActSheet->setCellValue('F'.$i, $v['stores']);
            $objActSheet->setCellValue('G'.$i, $v['is_sale']?'是':'否');
            $objActSheet->setCellValue('H'.$i, $v['sale_time']);
            // 表格高度
//            $objActSheet->getRowDimension($i)->setRowHeight(20);
            $spec_goods_price = $v['spec_goods_price'];
            if ($spec_goods_price) {
                $count = count($spec_goods_price);
                $objActSheet->mergeCells('A'.$i.':'.'A'.($i+$count-1));
                $objActSheet->mergeCells('B'.$i.':'.'B'.($i+$count-1));
                $objActSheet->mergeCells('C'.$i.':'.'C'.($i+$count-1));
                $objActSheet->mergeCells('D'.$i.':'.'D'.($i+$count-1));
                $objActSheet->mergeCells('E'.$i.':'.'E'.($i+$count-1));
                $objActSheet->mergeCells('F'.$i.':'.'F'.($i+$count-1));
                $objActSheet->mergeCells('G'.$i.':'.'G'.($i+$count-1));
                $objActSheet->mergeCells('H'.$i.':'.'H'.($i+$count-1));
                foreach ($spec_goods_price as $k1 => $v1) {
                    $objActSheet->setCellValue('I'.$i,$v1['key_name']);
                    $objActSheet->setCellValue('J'.$i, $v1['price']);
                    $objActSheet->setCellValue('K'.$i, $v1['cost_price']);
                    $objActSheet->setCellValue('L'.$i, $v1['store_count']);
                    $objActSheet->setCellValue('M'.$i, $v1['bar_code']);
                    // 表格高度
//                    $objActSheet->getRowDimension($i)->setRowHeight(20);
                    $i++;
                }
            } else {
                $i++;
            }
        }

        $width = [10, 25, 25, 10, 10, 10, 20, 10, 25, 15, 10, 25];
        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth($width[0]);
        $objActSheet->getColumnDimension('B')->setWidth($width[1]);
        $objActSheet->getColumnDimension('C')->setWidth($width[2]);
        $objActSheet->getColumnDimension('D')->setWidth($width[3]);
        $objActSheet->getColumnDimension('E')->setWidth($width[4]);
        $objActSheet->getColumnDimension('F')->setWidth($width[5]);
        $objActSheet->getColumnDimension('G')->setWidth($width[6]);
        $objActSheet->getColumnDimension('H')->setWidth($width[7]);
        $objActSheet->getColumnDimension('I')->setWidth($width[8]);
        $objActSheet->getColumnDimension('J')->setWidth($width[9]);
        $objActSheet->getColumnDimension('K')->setWidth($width[10]);
        $objActSheet->getColumnDimension('L')->setWidth($width[11]);
        $objActSheet->getColumnDimension('J')->setWidth($width[11]);
        $objActSheet->getColumnDimension('K')->setWidth($width[11]);
        $objActSheet->getColumnDimension('L')->setWidth($width[11]);
        $objActSheet->getColumnDimension('M')->setWidth($width[11]);

        /*      $outfile = "信息列表.xlsx";
              ob_end_clean();
              header("Content-Type: application/force-download");
              header("Content-Type: application/octet-stream");
              header("Content-Type: application/download");
              header('Content-Disposition:inline;filename="'.$outfile.'"');
              header("Content-Transfer-Encoding: binary");
              header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
              header("Pragma: no-cache");
              $objWriter->save('php://output');*/
        $fileName = $file_name."_" . date("Y_m_d", Request()->instance()->time()) . ".xls";
        $objExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表

        header('Content-Type: application/vnd.ms-excel');

        header("Content-Disposition: attachment;filename=".$fileName);

        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');

        $objWriter->save('php://output'); // 文件通过浏览器下载

        exit();//        $this->excelExport('商品信息表', $headArr, $data_info);
    }

    private function exportGoodsNew($lists, $file_name='', $type = 0)
    {
        $objExcel = new \PHPExcel();
        //set document Property
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
        $objActSheet = $objExcel->getActiveSheet();


        //填充表头信息
        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objActSheet->getDefaultStyle()->getAlignment()->setWrapText(TRUE);
        if ($type == 1) {
            $obj_color = \PHPExcel_Style_Color::COLOR_RED;
            $objActSheet->getStyle('B')->getFont()->getColor()->setARGB($obj_color);
            $objActSheet->getStyle('H')->getFont()->getColor()->setARGB($obj_color);
            $objActSheet->getStyle('P')->getFont()->getColor()->setARGB($obj_color);
        }
        //$obj_color = \PHPExcel_Style_Color::COLOR_DARKYELLOW;
        //$objActSheet->getStyle('A2:AA5')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($obj_color);

        $objActSheet->setCellValue("A1","产品信息");
        $letter =explode(',',"A,B,C,D,E,F,G");
        $arrHeader = ['ID','品牌', '商品名','商品编码','SKU', 'SKU名称', 'SKU编码'];
        //填充表头信息
        $lenth =  count($arrHeader);
        for($i = 0;$i < $lenth;$i++) {
            $objActSheet->setCellValue("$letter[$i]2","$arrHeader[$i]");
        };
        $key_id = ord("G");
        $objActSheet->setCellValue(chr($key_id+1)."1","厂家经销价");
        $objActSheet->setCellValue(chr($key_id+1)."2","供货价（含税）");
        $objActSheet->setCellValue(chr($key_id+2)."2","供货折扣");
        $objActSheet->setCellValue(chr($key_id+3)."1","B端结算价");
        $objActSheet->setCellValue(chr($key_id+3)."2","B端结算价（含税）");
        $objActSheet->setCellValue(chr($key_id+3)."3","B端结算价（含税）=B端供货价（含税）+税差");
        $objActSheet->setCellValue(chr($key_id+4)."2","B端供货价（含税）");
        $objActSheet->setCellValue(chr($key_id+4)."3","B端供货价（含税）=供货价(含税)/B端供货系数");
        $objActSheet->setCellValue(chr($key_id+5)."2","B端供货价系数");
        $objActSheet->setCellValue(chr($key_id+5)."3","（按品牌自由可调）");
        $objActSheet->setCellValue(chr($key_id+6)."2","税差");
        $objActSheet->setCellValue(chr($key_id+6)."3","（C端活动价含税-B端供货价含税）/1.13*0.13");
        $objActSheet->setCellValue(chr($key_id+7)."1","C端结算价");
        $objActSheet->setCellValue(chr($key_id+7)."2","C端零售价(含税)");
        $objActSheet->setCellValue(chr($key_id+7)."3","C端零售价(含税)=B端供货价（含税）*C端零售价系数");
        $objActSheet->setCellValue(chr($key_id+8)."2","C端零售价系数");
        $objActSheet->setCellValue(chr($key_id+8)."3","（按品牌自由可调）");
        $objActSheet->setCellValue(chr($key_id+9)."2","C端活动价(含税)");
        $objActSheet->setCellValue(chr($key_id+9)."3","默认C端活动价(含税)=C端零售价(含税)，可手动修改");
        $objActSheet->setCellValue(chr($key_id+10)."2","C端供货价物流安装费(8%)");
        $objActSheet->setCellValue(chr($key_id+10)."3","C端供货价物流安装费=C端零售价（含税）*8%");
        $objActSheet->setCellValue(chr($key_id+11)."2","厂家品牌指导价");


        /* $lenth =  count($arrHeader);
         for($i = 0;$i < $lenth;$i++) {
             $objActSheet->setCellValue("$letter[$i]1","$arrHeader[$i]");
         };*/
        $objActSheet->mergeCells('A1:F1');
        for($i = 0;$i < $lenth;$i++) {
            $objActSheet->mergeCells($letter[$i].'2:'.$letter[$i].'3');
        };
        $objActSheet->mergeCells(chr($key_id+1).'1:'.chr($key_id+2).'1');
        $objActSheet->mergeCells(chr($key_id+1).'2:'.chr($key_id+1).'3');
        $objActSheet->mergeCells(chr($key_id+2).'2:'.chr($key_id+2).'3');
        $objActSheet->mergeCells(chr($key_id+3).'1:'.chr($key_id+6).'1');
        $objActSheet->mergeCells(chr($key_id+7).'1:'.chr($key_id+11).'1');
        $objActSheet->mergeCells(chr($key_id+11).'2:'.chr($key_id+11).'3');
        $objActSheet->getRowDimension(5)->setRowHeight(20);

        //填充表格信息
        $i = 4;
        foreach($lists as $k=>$v){
            $b_coefficient = model('goods_coefficient')->where(['brand_id' => $v['brand_id']])->value('b_coefficient');
            $c_coefficient = model('goods_coefficient')->where(['brand_id' => $v['brand_id']])->value('c_coefficient');
            $b_coefficient?false:$b_coefficient=0.85;
            $c_coefficient?false:$c_coefficient=2.5;
            $objActSheet->setCellValue('A'.$i,$v['id']);
            $objActSheet->setCellValue('B'.$i, $v['brand_name']);
            $objActSheet->setCellValue('C'.$i, $v['goods_name']);
            $objActSheet->setCellValue('D'.$i, $v['goods_code']);
            // 表格高度E
            $objActSheet->getRowDimension($i)->setRowHeight(50);
            $spec_goods_price = $v['spec_goods_price'];
            if ($spec_goods_price) {
                $count = count($spec_goods_price);
                $objActSheet->mergeCells('A'.$i.':'.'A'.($i+$count-1));
                $objActSheet->mergeCells('B'.$i.':'.'B'.($i+$count-1));
                $objActSheet->mergeCells('C'.$i.':'.'C'.($i+$count-1));
                $objActSheet->mergeCells('D'.$i.':'.'D'.($i+$count-1));
                foreach ($spec_goods_price as $k1 => $v1) {
                    $objActSheet->setCellValue('E'.$i, $v1['key']);
                    $objActSheet->setCellValue('F'.$i, $v1['key_name']);
                    $objActSheet->setCellValue('G'.$i, $v1['bar_code']);

                    $price1 = $v1['cost_price']; //供货价（含税）
                    $price2 = ''; //供货折扣

                    $price4 = round($v1['cost_price']/$b_coefficient, 3); //B端供货价（含税）
                    $price5 = $b_coefficient; //B端供货价系数

                    $price7 = round($price4 * $c_coefficient, 3); //C端零售价(含税)
                    $price8 = $c_coefficient; //C端零售价系数
                    $price9 = $v1['price']; //C端活动价(含税)
                    $price10 = ''; //C端供货价物流安装费(8%)
                    $price11 = ''; //厂家品牌指导价

                    $price6 = round(($price9-$price4)/1.13*0.13, 3); //税差
                    $price3 = $price4 + $price6; //B端结算价（含税）

                    $objActSheet->setCellValue(chr($key_id+1).$i, $price1);
                    $objActSheet->setCellValue(chr($key_id+2).$i, $price2);
                    $objActSheet->setCellValue(chr($key_id+3).$i, $price3);
                    $objActSheet->setCellValue(chr($key_id+4).$i, $price4);
                    $objActSheet->setCellValue(chr($key_id+5).$i, $price5);
                    $objActSheet->setCellValue(chr($key_id+6).$i, $price6);
                    $objActSheet->setCellValue(chr($key_id+7).$i, $price7);
                    $objActSheet->setCellValue(chr($key_id+8).$i, $price8);
                    $objActSheet->setCellValue(chr($key_id+9).$i, $price9);
                    $objActSheet->setCellValue(chr($key_id+10).$i, $price10);
                    $objActSheet->setCellValue(chr($key_id+11).$i, $price11);
                    // 表格高度
//                    $objActSheet->getRowDimension($i)->setRowHeight(20);
                    $i++;
                }
            } else {
                $objActSheet->setCellValue('E'.$i,'');
                $objActSheet->setCellValue('F'.$i,'无');
                $objActSheet->setCellValue('G'.$i,'无');

                $price1 = $v['cost_price']; //供货价（含税）
                $price2 = ''; //供货折扣

                $price4 = round($v['cost_price']/$b_coefficient, 3); //B端供货价（含税）
                $price5 = $b_coefficient; //B端供货价系数

                $price7 = round($price4 * $c_coefficient, 3); //C端零售价(含税)
                $price8 = $c_coefficient; //C端零售价系数
                $price9 = $v['price']; //C端活动价(含税)
                $price10 = ''; //C端供货价物流安装费(8%)
                $price11 = ''; //厂家品牌指导价

                $price6 = round(($price9-$price4)/1.13*0.13, 3); //税差
                $price3 = $price4 + $price6; //B端结算价（含税）

                $objActSheet->setCellValue(chr($key_id+1).$i, $price1);
                $objActSheet->setCellValue(chr($key_id+2).$i, $price2);
                $objActSheet->setCellValue(chr($key_id+3).$i, $price3);
                $objActSheet->setCellValue(chr($key_id+4).$i, $price4);
                $objActSheet->setCellValue(chr($key_id+5).$i, $price5);
                $objActSheet->setCellValue(chr($key_id+6).$i, $price6);
                $objActSheet->setCellValue(chr($key_id+7).$i, $price7);
                $objActSheet->setCellValue(chr($key_id+8).$i, $price8);
                $objActSheet->setCellValue(chr($key_id+9).$i, $price9);
                $objActSheet->setCellValue(chr($key_id+10).$i, $price10);
                $objActSheet->setCellValue(chr($key_id+11).$i, $price11);
                $i++;
            }
        }
        //设置单元格边框  锚：bbb
        $styleThinBlackBorderOutline = array(
            'borders' => array (
                'allborders' => array (
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,   //设置border样式
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,  另一种样式
                    'color' => array ('argb' => 'FF000000'),          //设置border颜色
                ),
            ),
        );
        $objActSheet->getStyle( 'A1:X'.$i)->applyFromArray($styleThinBlackBorderOutline);
        $width = [10, 25, 25, 25, 25, 25, 25, 25, 25, 25, 25, 25];
        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth($width[0]);
        $objActSheet->getColumnDimension('B')->setWidth($width[1]);
        $objActSheet->getColumnDimension('C')->setWidth($width[2]);
        $objActSheet->getColumnDimension('D')->setWidth($width[3]);
        $objActSheet->getColumnDimension('E')->setWidth($width[4]);
        $objActSheet->getColumnDimension('F')->setWidth($width[5]);
        $objActSheet->getColumnDimension('G')->setWidth($width[6]);
        $objActSheet->getColumnDimension('H')->setWidth($width[7]);
        $objActSheet->getColumnDimension('I')->setWidth($width[8]);
        $objActSheet->getColumnDimension('J')->setWidth($width[9]);
        $objActSheet->getColumnDimension('K')->setWidth($width[10]);
        $objActSheet->getColumnDimension('L')->setWidth($width[11]);
        $objActSheet->getColumnDimension('J')->setWidth($width[11]);
        $objActSheet->getColumnDimension('K')->setWidth($width[11]);
        $objActSheet->getColumnDimension('L')->setWidth($width[11]);
        $objActSheet->getColumnDimension('M')->setWidth($width[11]);
        $objActSheet->getColumnDimension('N')->setWidth($width[11]);
        $objActSheet->getColumnDimension('O')->setWidth($width[11]);
        $objActSheet->getColumnDimension('P')->setWidth($width[11]);
        $objActSheet->getColumnDimension('Q')->setWidth($width[11]);
        $objActSheet->getColumnDimension('R')->setWidth($width[11]);
        $objActSheet->getColumnDimension('S')->setWidth($width[11]);
        $objActSheet->getColumnDimension('T')->setWidth($width[11]);
        $objActSheet->getColumnDimension('U')->setWidth($width[11]);
        $objActSheet->getColumnDimension('V')->setWidth($width[11]);
        $objActSheet->getColumnDimension('W')->setWidth($width[11]);
        $objActSheet->getColumnDimension('X')->setWidth($width[11]);

        /*      $outfile = "信息列表.xlsx";
              ob_end_clean();
              header("Content-Type: application/force-download");
              header("Content-Type: application/octet-stream");
              header("Content-Type: application/download");
              header('Content-Disposition:inline;filename="'.$outfile.'"');
              header("Content-Transfer-Encoding: binary");
              header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
              header("Pragma: no-cache");
              $objWriter->save('php://output');*/

        $fileName = $file_name."_" . date("Y_m_d", Request()->instance()->time()) . ".xls";
        $objExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表

        header('Content-Type: application/vnd.ms-excel');

        header("Content-Disposition: attachment;filename=".$fileName);

        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');

        $objWriter->save('php://output'); // 文件通过浏览器下载

        exit();//        $this->excelExport('商品信息表', $headArr, $data_info);
    }

    private function exportGoodsTamp($lists, $file_name = '')
    {
        $objExcel = new \PHPExcel();
        //set document Property
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
        $objActSheet = $objExcel->getActiveSheet();
        $key = ord("A");
        $letter =explode(',',"A,B,C,D,E,F,G,H,I,J,K,L,M,K");
        $arrHeader = ['ID', '商品名','商品编码','供应商名称','SKU', 'SKU名称', 'SKU编码', '价格', '供货价'];
        $obj_color = \PHPExcel_Style_Color::COLOR_RED;
        $objActSheet->getStyle('D')->getFont()->getColor()->setARGB($obj_color);
        $objActSheet->getStyle('H')->getFont()->getColor()->setARGB($obj_color);
        $objActSheet->getStyle('I')->getFont()->getColor()->setARGB($obj_color);
        //填充表头信息
        $lenth =  count($arrHeader);
        for($i = 0;$i < $lenth;$i++) {
            $objActSheet->setCellValue("$letter[$i]1","$arrHeader[$i]");
        };

        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objActSheet->getDefaultStyle()->getAlignment()->setWrapText(TRUE);
        //填充表格信息
        $i = 2;
        foreach($lists as $k=>$v){
            $objActSheet->setCellValue('A'.$i,$v['id']);
            $objActSheet->setCellValue('B'.$i, $v['goods_name']);
            $objActSheet->setCellValue('C'.$i, $v['goods_code']);
//            $objActSheet->setCellValue('D'.$i, $v['price']);
//            $objActSheet->setCellValue('E'.$i, $v['cost_price']);
            $objActSheet->setCellValue('D'.$i, $v['store_name']);
            // 表格高度
//            $objActSheet->getRowDimension($i)->setRowHeight(20);
            $spec_goods_price = $v['spec_goods_price'];
            if ($spec_goods_price) {
                $count = count($spec_goods_price);
                $objActSheet->mergeCells('A'.$i.':'.'A'.($i+$count-1));
                $objActSheet->mergeCells('B'.$i.':'.'B'.($i+$count-1));
                $objActSheet->mergeCells('C'.$i.':'.'C'.($i+$count-1));
                $objActSheet->mergeCells('D'.$i.':'.'D'.($i+$count-1));
//                $objActSheet->mergeCells('E'.$i.':'.'E'.($i+$count-1));
//                $objActSheet->mergeCells('F'.$i.':'.'F'.($i+$count-1));
                foreach ($spec_goods_price as $k1 => $v1) {
                    $objActSheet->setCellValue('E'.$i,$v1['key']);
                    $objActSheet->setCellValue('F'.$i,$v1['key_name']);
                    $objActSheet->setCellValue('G'.$i, $v1['bar_code']);
                    $objActSheet->setCellValue('H'.$i, $v1['price']);
                    $objActSheet->setCellValue('I'.$i, $v1['cost_price']);
                    // 表格高度
//                    $objActSheet->getRowDimension($i)->setRowHeight(20);
                    $i++;
                }
            } else {
                $i++;
            }
        }

        $width = [10, 25, 25, 10, 10, 25, 20, 10, 10];
        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth($width[0]);
        $objActSheet->getColumnDimension('B')->setWidth($width[1]);
        $objActSheet->getColumnDimension('C')->setWidth($width[2]);
        $objActSheet->getColumnDimension('D')->setWidth($width[3]);
        $objActSheet->getColumnDimension('E')->setWidth($width[4]);
        $objActSheet->getColumnDimension('F')->setWidth($width[5]);
        $objActSheet->getColumnDimension('G')->setWidth($width[6]);
        $objActSheet->getColumnDimension('H')->setWidth($width[7]);
        $objActSheet->getColumnDimension('I')->setWidth($width[8]);
        /*        $objActSheet->getColumnDimension('J')->setWidth($width[9]);
                $objActSheet->getColumnDimension('K')->setWidth($width[10]);
                $objActSheet->getColumnDimension('L')->setWidth($width[11]);
                $objActSheet->getColumnDimension('J')->setWidth($width[11]);
                $objActSheet->getColumnDimension('K')->setWidth($width[11]);
                $objActSheet->getColumnDimension('L')->setWidth($width[11]);
                $objActSheet->getColumnDimension('M')->setWidth($width[11]);
                $objActSheet->getColumnDimension('K')->setWidth($width[12]);*/

        /*      $outfile = "信息列表.xlsx";
              ob_end_clean();
              header("Content-Type: application/force-download");
              header("Content-Type: application/octet-stream");
              header("Content-Type: application/download");
              header('Content-Disposition:inline;filename="'.$outfile.'"');
              header("Content-Transfer-Encoding: binary");
              header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
              header("Pragma: no-cache");
              $objWriter->save('php://output');*/
        $fileName = $file_name."_" . date("Y_m_d", Request()->instance()->time()) . ".xls";
        $objExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表

        header('Content-Type: application/vnd.ms-excel');

        header("Content-Disposition: attachment;filename=".$fileName);

        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');

        $objWriter->save('php://output'); // 文件通过浏览器下载

        exit();//        $this->excelExport('商品信息表', $headArr, $data_in
    }

    private function exportGoodsSale($lists, $file_name='', $remark = '')
    {
        $objExcel = new \PHPExcel();
        //set document Property
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
        $objActSheet = $objExcel->getActiveSheet();
        //填充表头信息
        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objActSheet->getDefaultStyle()->getAlignment()->setWrapText(TRUE);
        $obj_color = \PHPExcel_Style_Color::COLOR_DARKYELLOW;
        $objActSheet->getStyle('A2:AA5')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($obj_color);


        $objActSheet->setCellValue("A1","备注:");
        $objActSheet->setCellValue("b1",$remark);

        $objActSheet->setCellValue("A2","序号");
        $objActSheet->setCellValue("B2","商品名称");
        $objActSheet->setCellValue("C2","商品编码");
        $objActSheet->setCellValue("D2","品牌");
        $objActSheet->setCellValue("E2","图片");
        $objActSheet->setCellValue("F2","SKU");

        $objActSheet->setCellValue("G2","厂家经销价（账面）");
        $objActSheet->setCellValue("G4","厂家经销价（不含税金）（账面）");
        $objActSheet->setCellValue("G5","A=D/1.13");
        $objActSheet->setCellValue("H4","厂家经销价税金（13%）（账面）");
        $objActSheet->setCellValue("H5","B=A*0.13");
        $objActSheet->setCellValue("I4","厂家经销价金额（账面）");
        $objActSheet->setCellValue("I5","C=A+B");

        $objActSheet->setCellValue("J2","厂家经销价");
        $objActSheet->setCellValue("J4","厂家经销价（含税金）");
        $objActSheet->setCellValue("J5","D=E+F");
        $objActSheet->setCellValue("K4","厂家经销价（不含税金）");
        $objActSheet->setCellValue("K5","E");
        $objActSheet->setCellValue("L4","经销价税金（10%）");
        $objActSheet->setCellValue("L5","F=E*0.1");
        $objActSheet->setCellValue("M4","经销结算价小计");
        $objActSheet->setCellValue("M5","G=E+F");

        $objActSheet->setCellValue("N2","B端结算价");
        $objActSheet->setCellValue("N4","B端供货价(不含税价)");
        $objActSheet->setCellValue("N5","H=E/0.8");
        $objActSheet->setCellValue("O4","B端供货价税金（10%）");
        $objActSheet->setCellValue("O5","I=H*0.10");
        $objActSheet->setCellValue("P4","B端结算价小计");
        $objActSheet->setCellValue("P5","J=H+I");
        $objActSheet->setCellValue("Q4","B端结算差额(不含税价结算）");
        $objActSheet->setCellValue("Q5","K=R-H");

        $objActSheet->setCellValue("R2","C端零售价");
        $objActSheet->setCellValue("R3","C端零售系统面价（含税含物流）");
        $objActSheet->setCellValue("R4","C端零售价（系统面价）");
        $objActSheet->setCellValue("R5","L=J*2.5");

        $objActSheet->setCellValue("S3","C端零售最低限价");
        $objActSheet->setCellValue("S4","C端零售价最低成交价(含税、物流安装另计)");
        $objActSheet->setCellValue("S5","M=N+O");
        $objActSheet->setCellValue("T4","C端零售价最低价(不含税、不含物流安装)");
        $objActSheet->setCellValue("T5","N=E*2.3");
        $objActSheet->setCellValue("U4","C端零售价税金（10%）");
        $objActSheet->setCellValue("U5","O=N*0.1");

        $objActSheet->setCellValue("V3","C端零售实际成交价");
        $objActSheet->setCellValue("V4","成交折扣率");
        $objActSheet->setCellValue("V5","P=Q/L");
        $objActSheet->setCellValue("W4","C端零售实际成交价(含税、含物流安装)");
        $objActSheet->setCellValue("W5","Q");
        $objActSheet->setCellValue("X4","C端零售价实际成交价(不含税、含物流安装)");
        $objActSheet->setCellValue("X5","R=Q/1.1");
        $objActSheet->setCellValue("Y4","C端零售成交价税金（10%）");
        $objActSheet->setCellValue("Y5","S=Q/1.1*0.1");

        $objActSheet->setCellValue("Z2","C端厂家提供面价");
        $objActSheet->setCellValue("Z4","品牌指导价");
        $objActSheet->setCellValue("AA2","件数");

        /* $lenth =  count($arrHeader);
         for($i = 0;$i < $lenth;$i++) {
             $objActSheet->setCellValue("$letter[$i]1","$arrHeader[$i]");
         };*/
        $objActSheet->mergeCells('B1:AA1');
        $objActSheet->mergeCells('A2:A5');
        $objActSheet->mergeCells('B2:B5');
        $objActSheet->mergeCells('C2:C5');
        $objActSheet->mergeCells('D2:D5');
        $objActSheet->mergeCells('E2:E5');
        $objActSheet->mergeCells('F2:F5');
        $objActSheet->mergeCells('G2:I3');
        $objActSheet->mergeCells('J2:M3');
        $objActSheet->mergeCells('N2:Q3');
        $objActSheet->mergeCells('R2:Y2');
        $objActSheet->mergeCells('S3:U3');
        $objActSheet->mergeCells('V3:Y3');
        $objActSheet->mergeCells('Z2:Z3');
        $objActSheet->mergeCells('Z4:Z5');
        $objActSheet->mergeCells('AA2:AA5');
        $objActSheet->getRowDimension(5)->setRowHeight(20);

        //填充表格信息
        $i = 6;
        foreach($lists as $k=>$v){

            $objActSheet->setCellValue('A'.$i,$v['id']);
            $objActSheet->setCellValue('B'.$i, $v['goods_name']);
            $objActSheet->setCellValue('C'.$i, $v['goods_code']);
            $objActSheet->setCellValue('D'.$i, $v['goods_brand']);
            if($i < 17 && !empty($v['goods_logo']) && file_exists('.'.$v['goods_logo'])){
                $image = '.'.$v['goods_logo'];
                $handle = fopen($image , 'r' ) ;
                if($handle) {
                    //这是一个坑,刚开始我把实例化图片类放在了循环外面,但是失败了,也就是每个图片都要实例化一次
                    $objDrawing = new \PHPExcel_Worksheet_Drawing();
                    $objDrawing->setPath($image);
                    // 设置图片的宽度
                    $objDrawing->setHeight(50);
                    $objDrawing->setWidth(50);
                    $objDrawing->setOffsetX(10);
                    $objDrawing->setOffsetY(10);
                    $objDrawing->setCoordinates('E' . $i);
                    $objDrawing->setWorksheet($objActSheet);
                    fclose($handle);
                }
            } else {
                $objActSheet->setCellValue('E'.$i, picture_url_dispose($v['goods_logo']));
            }

            // 表格高度
            $objActSheet->getRowDimension($i)->setRowHeight(50);
            $spec_goods_price = $v['spec_goods_price'];
            if ($spec_goods_price) {
                $count = count($spec_goods_price);
                $objActSheet->mergeCells('A'.$i.':'.'A'.($i+$count-1));
                $objActSheet->mergeCells('B'.$i.':'.'B'.($i+$count-1));
                $objActSheet->mergeCells('C'.$i.':'.'C'.($i+$count-1));
                $objActSheet->mergeCells('D'.$i.':'.'D'.($i+$count-1));
                $objActSheet->mergeCells('E'.$i.':'.'E'.($i+$count-1));
                foreach ($spec_goods_price as $k1 => $v1) {
                    $final_price = $v1['final_price'];
                    $goods_num = $v1['goods_num'];
                    $price = $v1['price'];
                    $e_price = $v1['cost_price'];
                    $f_price = $e_price * 0.1;
                    $d_price = $e_price + $f_price;
                    $g_price = $e_price + $f_price;
                    $a_price = $d_price / 1.13;
                    $b_price = $a_price * 0.13;
                    $c_price = $a_price + $b_price;
                    $h_price = $e_price / 0.8;
                    $i_price = $h_price * 0.1;
                    $j_price = $h_price + $i_price;
                    $l_price = $j_price * 2.5;
                    $n_price = $e_price * 2.3;
                    $o_price = $n_price * 0.1;
                    $q_price = $final_price;
                    $p_price = $l_price?$q_price / $l_price:0;
                    $r_price = $q_price / 1.1;
                    $s_price = $q_price / 1.1 * 0.1;
                    $m_price = $n_price + $o_price;
                    $k_price = $r_price + $h_price;
                    $objActSheet->setCellValue('F'.$i, $v1['key_name']);
                    $objActSheet->setCellValue('G'.$i, round($a_price,2));
                    $objActSheet->setCellValue('H'.$i,round($b_price,2));
                    $objActSheet->setCellValue('I'.$i,round($c_price,2));
                    $objActSheet->setCellValue('J'.$i,round($d_price,2));
                    $objActSheet->setCellValue('K'.$i,round($e_price,2));
                    $objActSheet->setCellValue('L'.$i,round($f_price,2));
                    $objActSheet->setCellValue('M'.$i,round($g_price,2));
                    $objActSheet->setCellValue('N'.$i,round($h_price,2));
                    $objActSheet->setCellValue('O'.$i,round($i_price,2));
                    $objActSheet->setCellValue('P'.$i,round($j_price,2));
                    $objActSheet->setCellValue('Q'.$i,round($k_price,2));
                    $objActSheet->setCellValue('R'.$i,round($l_price,2));
                    $objActSheet->setCellValue('S'.$i,round($m_price,2));
                    $objActSheet->setCellValue('T'.$i,round($n_price,2));
                    $objActSheet->setCellValue('U'.$i,round($o_price,2));
                    $objActSheet->setCellValue('V'.$i,round($p_price,2));
                    $objActSheet->setCellValue('W'.$i,round($q_price,2));
                    $objActSheet->setCellValue('X'.$i,round($r_price,2));
                    $objActSheet->setCellValue('Y'.$i,round($s_price,2));
                    $objActSheet->setCellValue('Z'.$i,round($price,2));
                    $objActSheet->setCellValue('AA'.$i,$goods_num);
                    // 表格高度
//                    $objActSheet->getRowDimension($i)->setRowHeight(20);
                    $i++;
                }
            } else {
                $final_price = $v['final_price'];
                $goods_num = $v['goods_num'];
                $price = $v['price'];
                $e_price = $v['cost_price'];
                $f_price = $e_price * 0.1;
                $d_price = $e_price + $f_price;
                $g_price = $e_price + $f_price;
                $a_price = $d_price / 1.13;
                $b_price = $a_price * 0.13;
                $c_price = $a_price + $b_price;
                $h_price = $e_price / 0.8;
                $i_price = $h_price * 0.1;
                $j_price = $h_price + $i_price;
                $l_price = $j_price * 2.5;
                $n_price = $e_price * 2.3;
                $o_price = $n_price * 0.1;
                $q_price = $final_price;
                $p_price = $l_price?$q_price / $l_price:0;
                $r_price = $q_price / 1.1;
                $s_price = $q_price / 1.1 * 0.1;
                $m_price = $n_price + $o_price;
                $k_price = $r_price + $h_price;
                $objActSheet->setCellValue('F'.$i,'无');
                $objActSheet->setCellValue('G'.$i, round($a_price,2));
                $objActSheet->setCellValue('H'.$i,round($b_price,2));
                $objActSheet->setCellValue('I'.$i,round($c_price,2));
                $objActSheet->setCellValue('J'.$i,round($d_price,2));
                $objActSheet->setCellValue('K'.$i,round($e_price,2));
                $objActSheet->setCellValue('L'.$i,round($f_price,2));
                $objActSheet->setCellValue('M'.$i,round($g_price,2));
                $objActSheet->setCellValue('N'.$i,round($h_price,2));
                $objActSheet->setCellValue('O'.$i,round($i_price,2));
                $objActSheet->setCellValue('P'.$i,round($j_price,2));
                $objActSheet->setCellValue('Q'.$i,round($k_price,2));
                $objActSheet->setCellValue('R'.$i,round($l_price,2));
                $objActSheet->setCellValue('S'.$i,round($m_price,2));
                $objActSheet->setCellValue('T'.$i,round($n_price,2));
                $objActSheet->setCellValue('U'.$i,round($o_price,2));
                $objActSheet->setCellValue('V'.$i,round($p_price,2));
                $objActSheet->setCellValue('W'.$i,round($q_price,2));
                $objActSheet->setCellValue('X'.$i,round($r_price,2));
                $objActSheet->setCellValue('Y'.$i,round($s_price,2));
                $objActSheet->setCellValue('Z'.$i,round($price,2));
                $objActSheet->setCellValue('AA'.$i,$goods_num);
                $i++;
            }
        }
        //设置单元格边框  锚：bbb
        $styleThinBlackBorderOutline = array(
            'borders' => array (
                'allborders' => array (
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,   //设置border样式
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,  另一种样式
                    'color' => array ('argb' => 'FF000000'),          //设置border颜色
                ),
            ),
        );
        $objActSheet->getStyle( 'A2:Z'.$i)->applyFromArray($styleThinBlackBorderOutline);
        $width = [10, 25, 25, 25, 25, 25, 25, 25, 25, 25, 25, 25];
        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth($width[0]);
        $objActSheet->getColumnDimension('B')->setWidth($width[1]);
        $objActSheet->getColumnDimension('C')->setWidth($width[2]);
        $objActSheet->getColumnDimension('D')->setWidth($width[3]);
        $objActSheet->getColumnDimension('E')->setWidth($width[4]);
        $objActSheet->getColumnDimension('F')->setWidth($width[5]);
        $objActSheet->getColumnDimension('G')->setWidth($width[6]);
        $objActSheet->getColumnDimension('H')->setWidth($width[7]);
        $objActSheet->getColumnDimension('I')->setWidth($width[8]);
        $objActSheet->getColumnDimension('J')->setWidth($width[9]);
        $objActSheet->getColumnDimension('K')->setWidth($width[10]);
        $objActSheet->getColumnDimension('L')->setWidth($width[11]);
        $objActSheet->getColumnDimension('J')->setWidth($width[11]);
        $objActSheet->getColumnDimension('K')->setWidth($width[11]);
        $objActSheet->getColumnDimension('L')->setWidth($width[11]);
        $objActSheet->getColumnDimension('M')->setWidth($width[11]);
        $objActSheet->getColumnDimension('N')->setWidth($width[11]);
        $objActSheet->getColumnDimension('O')->setWidth($width[11]);
        $objActSheet->getColumnDimension('P')->setWidth($width[11]);
        $objActSheet->getColumnDimension('Q')->setWidth($width[11]);
        $objActSheet->getColumnDimension('R')->setWidth($width[11]);
        $objActSheet->getColumnDimension('S')->setWidth($width[11]);
        $objActSheet->getColumnDimension('T')->setWidth($width[11]);
        $objActSheet->getColumnDimension('U')->setWidth($width[11]);
        $objActSheet->getColumnDimension('V')->setWidth($width[11]);
        $objActSheet->getColumnDimension('W')->setWidth($width[11]);
        $objActSheet->getColumnDimension('X')->setWidth($width[11]);
        $objActSheet->getColumnDimension('Y')->setWidth($width[11]);
        $objActSheet->getColumnDimension('Z')->setWidth($width[11]);

        /*      $outfile = "信息列表.xlsx";
              ob_end_clean();
              header("Content-Type: application/force-download");
              header("Content-Type: application/octet-stream");
              header("Content-Type: application/download");
              header('Content-Disposition:inline;filename="'.$outfile.'"');
              header("Content-Transfer-Encoding: binary");
              header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
              header("Pragma: no-cache");
              $objWriter->save('php://output');*/

        $fileName = $file_name."_" . date("Y_m_d", Request()->instance()->time()) . ".xls";
        $objExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表

        header('Content-Type: application/vnd.ms-excel');

        header("Content-Disposition: attachment;filename=".$fileName);

        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');

        $objWriter->save('php://output'); // 文件通过浏览器下载

        exit();//        $this->excelExport('商品信息表', $headArr, $data_info);
    }

    private function getSelect($objPHPExcel, $goods_cate, $char)
    {
        for ($j = 2; $j <= 2002; $j++) {
            /*设置下拉*/
            $str_list = implode(',', $goods_cate);
//            $objValidation1 = $objPHPExcel->getActiveSheet()->getCell($char . $i)->getDataValidation(); //从第二行开始有下拉样式
            /*$objValidation1->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)
                ->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                ->setAllowBlank(false)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setErrorTitle('输入的值有误')
                ->setError('您输入的值不在下拉框列表内.')
                ->setPromptTitle('')
                ->setPrompt('')
                ->setFormula1('"' . $list . '"');*/

            //解决下拉框数据来源字串长度过大：将每个来源字串分解到一个空闲的单元格中
            $activeSheet = $objPHPExcel->getActiveSheet();
//            $str_list = "item1,item2,item3,......" ;
            $str_len = strlen($str_list);
            $end_cell = 'AZ1';
            if ($str_len >= 255) {
                $str_list_arr = explode(',', $str_list);
                if ($str_list_arr) {
                    foreach ($str_list_arr as $i => $d) {
                        $c = "AZ" . ($i+1);
                        $activeSheet->setCellValue($c,$d);
                        $end_cell = $c;
                    }
                }

                $activeSheet->getColumnDimension('AZ')->setVisible(false);
            }

            $objValidation2 = $activeSheet->getCell($char.$j)->getDataValidation();
            $objValidation2->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)
                ->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                ->setAllowBlank(true)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setErrorTitle('输入的值有误')
                ->setError('您输入的值不在下拉框列表内.')
                ->setPromptTitle('下拉选择框')
                ->setPrompt('请从下拉框中选择您需要的值！');
            if ($str_len < 255) {
                $objValidation2->setFormula1('"' . $str_list . '"');
            } else {
                $objValidation2->setFormula1("sheet1!AZ1:{$end_cell}");
            }
        }
    }

    /**
     * 联动单元格下拉制作
     * @param $objPHPExcel object PHPExcel类
     * @param $data array 需要联动的数据，最少有两层数组
     * @param $char ['B','C','D']
     * @param $col
     * @param $sheet_name
     */
    public function getMySheetInfo($objPHPExcel, $data,$char, $col, $sheet_name)
    {
        if (count($data) < 2) {
            return false;
        }
        $high = 0;
        $name = '';
        foreach ($data as $key => $first) {
            $name .= $first['name'].',';
            $objPHPExcel->getSheetByName($sheet_name)->setCellValue($col[0] . ($key + 1 + $high), $first['name']);
            $max = 0; //重置max
            $secondNum = count($first['children']);
            if ($secondNum > 0) {
                foreach ($first['children'] as $index => $second) {
                    $objPHPExcel->getSheetByName($sheet_name)->setCellValue($col[$index + 1] . ($key + 1 + $high), $second['name']);
                    $thirdNum = count($second['children']);
                    if ($thirdNum > 0) {
                        if ($thirdNum > $max) {
                            $max = $thirdNum;
                        }
                        foreach ($second['children'] as $id => $third) {
                            $objPHPExcel->getSheetByName($sheet_name)->setCellValue($col[$index + 1] . ($key + 1 + $high + $id + 1), $third['name']);
                        }
                    }
                    $thirdNum = $thirdNum==0?$thirdNum=1:$thirdNum;
                    //定义三级名称
                    $objPHPExcel->addNamedRange(
                        new \PHPExcel_NamedRange(
                            $second['name'],
                            $objPHPExcel->getSheetByName($sheet_name),
                            $col[$index + 1] . ($key + 1 + $high + 1) . ':' . $col[$index + 1] . ($key + 1 + $high + 1 + $thirdNum - 1)
                        )
                    );
                }
            }
            $secondNum = $secondNum==0?$secondNum=1:$secondNum;
            //定义二级名称
            $objPHPExcel->addNamedRange(
                new \PHPExcel_NamedRange(
                    $first['name'],
                    $objPHPExcel->getSheetByName($sheet_name),
                    $col[1] . ($key + 1 + $high) . ':' . $col[1 + $secondNum - 1] . ($key + 1 + $high)
                )
            );

            $high += $max;
        }
        //移花接木
        foreach ($data as $var => $content) {
            $objPHPExcel->getSheetByName($sheet_name)->setCellValue('Z' . ($var + 1), $content['name']);
        }

        $total = count($data);
        $objPHPExcel->addNamedRange(
            new \PHPExcel_NamedRange(
                $sheet_name,
                $objPHPExcel->getSheetByName($sheet_name),
                'Z1' . ':' . 'Z' . $total
            )
        );
        $sheet_name = rtrim($name, ',');
        //数据验证
        for ($i = 2; $i <= 2002; $i++) {
            $this->goodsObjActSheets($objPHPExcel, $char, $i, $sheet_name);
        }

    }

    /**
     * 联动单元格下拉赋值
     * @param $objPHPExcel
     * @param $char
     * @param $i
     * @param $sheet_name
     */
    public function goodsObjActSheets($objPHPExcel, $char, $i, $sheet_name)
    {
        $objValidation = $objPHPExcel->getActiveSheet()->getCell($char[0] . $i)->getDataValidation();
        $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
        $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
        $objValidation->setAllowBlank(false);
        $objValidation->setShowInputMessage(true);
        $objValidation->setShowErrorMessage(true);
        $objValidation->setShowDropDown(true);
        $objValidation->setErrorTitle('输入错误');
        $objValidation->setError('不在列表中的值');
        $objValidation->setPromptTitle('请选择');
//            $objValidation->setPrompt('请从列表中选择一个值.');
        $objValidation->setFormula1('"'.$sheet_name.'"');
        /*if (count($char) > 1) {
            foreach($char as $k => $v) {
                if ($k > 0) {
                    $objValidation = $objPHPExcel->getActiveSheet()->getCell($char[$k] . $i)->getDataValidation();
                    $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                    $objValidation->setAllowBlank(false);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    $objValidation->setErrorTitle('输入错误');
                    $objValidation->setError('不在列表中的值');
                    $objValidation->setPromptTitle('请选择');
//            $objValidation->setPrompt('请从列表中选择一个值.');
                    $objValidation->setFormula1('=INDIRECT($' . $char[$k-1] . '$' . $i . ')');
                }
            }
        }*/
    }
}