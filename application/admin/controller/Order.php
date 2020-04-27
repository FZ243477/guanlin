<?php


namespace app\admin\controller;
use app\common\constant\CartConstant;
use app\common\constant\MoneyWaterConstant;
use app\common\helper\CartHelper;
use app\common\helper\CurlHelper;
use app\common\helper\GoodsHelper;
use app\admin\helper\ManagerHelper;
use app\common\helper\MessageHelper;
use app\common\helper\OrderHelper;
use app\common\helper\PayHelper;
use app\common\helper\PHPExcelHelper;
use app\common\constant\SystemConstant;
use app\common\constant\OrderConstant;
use app\common\helper\VerificationHelper;
use think\Db;
use think\Request;
use PHPExcel_IOFactory;

class Order extends Base
{
    use ManagerHelper;
    use PHPExcelHelper;
    use OrderHelper;
    use PayHelper;
    use CartHelper;
    use GoodsHelper;
    use MessageHelper;
    use CurlHelper;
    use VerificationHelper;

    public function __construct()
    {
        parent::__construct();

    }
    /*
    *订单列表
    */
    public function orderList(){
        $map = [];
        $name = request()->param("name");
        $urgent_type = request()->param("urgent_type");
        $order_no = request()->param("order_no");
        $starttime = request()->param("starttime");
        $endtime = request()->param("endtime");
        $status = request()->param("status");
        //dump($status);exit;
        $this->assign("name", $name);
        $this->assign("order_no", $order_no);
        $this->assign("starttime", $starttime);
        $this->assign("endtime", $endtime);
        $this->assign("status", $status);

        $starttime=strtotime($starttime);
        $endtime=strtotime($endtime);
        if ($starttime && $endtime) {
            $map['a.create_time'] = ['between', [$starttime, $endtime]];
        } elseif ($starttime) {
            $map['a.create_time'] = ['egt', $starttime];
        } elseif ($endtime) {
            $map['a.create_time'] = ['elt', $endtime];
        }
        if (isset($urgent_type)) {
            //dump($urgent_type);exit;
            if($urgent_type !=2){
            $map['a.urgent_type'] = $urgent_type;
        }

        }
        $this->assign("urgent_type", $urgent_type);
        if (isset($status)) {
            $map['a.state'] = $status;
        }

        if ($name) {
            $map['b.nickname|a.take_phone|a.fphone|a.take_name'] = ["like", "%$name%"];
            //$map['b.nickname|a.telephone|a.consignee|b.realname|b.telephone'] = ["like", "%$name%"];
            $this->assign("name", $name);
        }
        if ($order_no) {
            $map['a.order_id'] = ["like", "%$order_no%"];
            $this->assign("order_no", $order_no);
        }

        $order_list = model('order')->alias('a')
            ->join('user b', 'a.uid = b.id', 'left')
            ->field('a.*')
            ->order('a.create_time desc')
            ->where($map)
            ->paginate(10,false,['query'=>request()->param()]);
        foreach ($order_list as $k => $v) {
            $user = model('user')->where(['id' => $v['uid']])->field('nickname,telephone')->find();
            $order_list[$k]['name'] = $user['nickname'];
            $order_list[$k]['tel'] = $user['telephone'];
            $order_list[$k]['is_pay'] = 0;
            $order_list[$k]['is_audit'] = 0;
        }
        $this->assign("order_list", $order_list);

        $m = model('order');
        unset($map['a.state']);
        $count = $m->alias('a')
            ->join('user b', 'a.uid = b.id', 'left')->where($map)->count();
        $count1 = $m->alias('a')
            ->join('user b', 'a.uid = b.id', 'left')
            ->where(["a.state" => 0,"a.paid"=>0])->where($map)->count();//待付款
        $count2 = $m->alias('a')
            ->join('user b', 'a.uid = b.id', 'left')
            ->where(["a.state" => 1])->where($map)->count();//待发货
        $count3 = $m->alias('a')
            ->join('user b', 'a.uid = b.id', 'left')
            ->where(["a.state" => 2])->where($map)->count();//已发货
        $count4 = $m->alias('a')
            ->join('user b', 'a.uid = b.id', 'left')
            ->where(["a.state" => 3])->where($map)->count();//已签收
        $count5 = $m->alias('a')
            ->join('user b', 'a.uid = b.id', 'left')
            ->where(["a.state" => 4])->where($map)->count();//已完成

        $this->assign("count", $count);
        $this->assign("count1", $count1);
        $this->assign("count2", $count2);
        $this->assign("count3", $count3);
        $this->assign("count4", $count4);
        $this->assign("count5", $count5);
        return $this->fetch();
    }

    //补充订单价格及订单重量信息
    public function  supply_order(){
        $id = request()->param("order_id");  // 得到order的id
        $weight= request()->param("weight");  // 得到order的重量
        $order=model('order')->where('id',$id)->find();
        if(!$order){
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
        }
        if($order['urgent_type']==0){
            $unit_price=Db::name('unit_price')->where('id',0)->find();
        }else{
            $unit_price=Db::name('unit_price')->where('id',1)->find();
        }
        $weight_price=$weight*$unit_price['price'];
        $save_content=[
            'price'=>$weight_price,
            'weight'=>$weight,
            'has_take'=>1
        ];
        $content = "审核订单";
        $before_json = model('order')->where('id',$id)->find();
        $save=model('order')->where('id',$id)->update($save_content);
        $after_json = $save;
        if ($save) {
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
        }
    }

    //确认签收
    public function  sure_order(){
       $data=input();
       $order=model('order')->where('id',$data['id'])->find();
       if(!$order){
           ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
       }
        $save_content = [
            'state'=>3,
            'update_time'=>time()
        ];
        $before_json = $order;
        $save=model('order')->where('id',$order['id'])->update($save_content);
        $after_json = $save;
        $content = "确认签收";
        if ($save) {
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
        }
    }

    //发货
    public function  delivery_end(){
        $id = request()->post('id');
        //$data['express_name'] = request()->post('express_name');
        $data['delivery_end_id'] = request()->post('express_no');
        $data['delivery_end_time'] = date('Y-m-d H:i:s', time());
        if(!$data['delivery_end_id']){
            ajaxReturn(["status" => 0, "msg" => "订单号不能为空"]);
        }
        $order=model('order')->where('id',$id)->find();
        if(!$order){
            ajaxReturn(["status"=>0,"msg"=>"此订单不存在，请检查."]);
        }
        if($order['state']!=1){
            ajaxReturn(["status"=>0,"msg"=>"订单状态错误."]);
        }
        $data['state']=2;
        $before_json = model('order')->where('id',$id)->find();
        $res = model('order')->where('id',$id)->update($data);
        $after_json = $res;
        $content = "订单发货";
        if ($res) {
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            ajaxReturn(["status" => 1, "msg" => "发货成功！"]);
        } else {
            ajaxReturn(["status" => 0, "msg" => "网络繁忙，请稍后~~"]);
        }
    }
    /*
    *订单详情
    *yb 2017-08-07
    */
    public function detail()
    {
        $id = request()->param("order_id");  // 得到order的id
        $user_id = input("user_id");  // 得到order的user_id
        $store_id = input("store_id");  // 得到order的store_id
        $order = model('order')->where('id=' . $id)->find();
       // dump($user_id);exit;
        if (!$order) {
            $this->error('没有此订单！');
        }
        $map['order_id'] = $order['id'];
        $user = model('user')->where(['id' => $order['uid']])->find();
        $order['realname'] = $user['nickname'];
        $order['fphone'] = $user['telephone'];
        $logistic=model('logistics')->where('id',$order['logi_id'])->find();
        $order['logi_name']=$logistic['name'];
        if($order['paid']==1){$order['paid_state']="已支付";}else{$order['paid_state']="未支付";}
        $order['order_status_name'] = OrderConstant::order_status_array_value($order['state']);
        $order['pay_way_name'] = "微信支付";

        $this->assign('info', $order);
        return $this->fetch();
    }

    //修改收货地址
    public function updateAddress()
    {
        $order_id = request()->post('orderid');
        if (!$order_id) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $order = model('order')->where(['id'=>$order_id])->find();
        if (!$order) {
            ajaxReturn(['status' => 0, 'msg' => '订单不存在']);
        }
        $province = request()->post('province');
        $city = request()->post('city');
        $district = request()->post('district');
        $place = request()->post('address');
        $consignee = request()->post('consignee');
        $telephone = request()->post('telephone');
        if (!$province) {
            ajaxReturn(['status' => 0, 'msg' => '请填写省']);
        }
        if (!$city) {
            ajaxReturn(['status' => 0, 'msg' => '请填写市']);
        }
        if (!$district) {
            ajaxReturn(['status' => 0, 'msg' => '请填写区']);
        }
        if (!$place) {
            ajaxReturn(['status' => 0, 'msg' => '请填写详细地址']);
        }
        if (!$consignee) {
            ajaxReturn(['status' => 0, 'msg' => '请填写收货人']);
        }
        if (!$telephone) {
            ajaxReturn(['status' => 0, 'msg' => '请填写手机号']);
        }
        if (!$this->VerifyTelephone($telephone)) {
            ajaxReturn(['status' => 0, 'msg' => '手机号格式不正确']);
        }
        $before_json = $order;
        $res=model('order')->save([
            'take_province' => $province,
            'take_city' => $city,
            'take_district' => $district,
            'take_detailaddress' => $place,
            'take_name' => $consignee,
            'take_phone' => $telephone,
            'take_address'=>$province.$city.$district.$place
        ],['id'=>$order_id]);
        $after_json = $res;
        $content="修改订单收货人地址";
        if($res){
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        }else{
            ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
        }
    }
    //修改订单金额
    public function editPrice(){
        $id = request()->post('order_id');
        $type = request()->post('type');
        $action_note = request()->post('action_note');
        $pay_price = request()->post('pay_price');
        $order = model('order')->where(['id'=>$id])->find();
        if (!$order) {
            ajaxReturn(['status' => 0, 'msg' => '订单不存在']);
        }
        if($order['paid']==1){
            ajaxReturn(['status' => 0, 'msg' => '此订单已被支付不能修改金额']);
        }
        $save_content=[
            'price'=>$pay_price,
            'note'=>$action_note
        ];
        $before_json = $order;
        $res = model('order')->where('id',$id)->update($save_content);
        $after_json = $res;
        $content="上传订单价格";
        if ($res) {
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            ajaxReturn(["status" => 1, "msg" => "修改成功！"]);
        } else {
            ajaxReturn(["status" => 0, "msg" => "网络繁忙，请稍后~~"]);
        }
    }
    public function orderAction ()
    {
        $type = request()->post('type');
        $action_note = request()->post('action_note');
        if (!$action_note) {
//            ajaxReturn(['status' => 0, 'msg' => '请填写备注']);
        }
        $order_id = request()->post('order_id');
        if (!$order_id) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $order = model('order')->where(['id'=>$order_id])->find();
        if (!$order) {
            ajaxReturn(['status' => 0, 'msg' => '订单不存在']);
        }

//        $id_arr = explode('-', $id);
        $order_certificate = model('order_certificate')->where(['status' => 0, 'order_no' => $order['order_no']])->select();
        if (!$order_certificate) {
            ajaxReturn(['status' => 0, 'msg' => '没有未审核的凭证记录']);
        }
        $pay_price = request()->post('pay_price');
        $data['money'] = $pay_price;
        $data['order_id'] = $order_id;
        $data['action_user'] = model('manager')->where(['id' => $this->manager_id])->value('manager_name');
        $data['action_note'] = $action_note;
        $data['order_status'] = $order['order_status'];
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['is_shipping'];
        $data['log_time'] = time();
//        $data['audit_name'] = $data['action_user'];
//        $data['audit_time'] = $data['log_time'];
        if ($type == 1) {
            $data['total_money'] = $order['pay_price'] + $pay_price;
            $data['pay_type'] = 1;
            if (!$pay_price) {
                ajaxReturn(['status' => 0, 'msg' => '请填写结算金额']);
            }

            if ($pay_price > $order['total_fee'] - $order['pay_price']) {
                ajaxReturn(['status' => 0, 'msg' => '不能大于待结算金额']);
            }
            if ($pay_price == $order['total_fee'] - $order['pay_price']) {
                $data['status_desc'] = '完成全部付款';
                $data_info = [
                    'order_status' => OrderConstant::ORDER_STATUS_WAIT_SEND,
                ];
                model('order')->save($data_info, ['id' => $order_id]);
            } else {
                model('order')->save([
                    'order_status' => OrderConstant::ORDER_STATUS_FINAL_ORDER,
                ], ['id'=>$order_id]);
                $data['status_desc'] = '部分付款';
            }
            model('order')->where('id', $order['id'])->setInc('pay_price', $pay_price);
        } else {
            $data['pay_type'] = 2;
            $data['status_desc'] = '拒绝本次付款';
            model('order')->save([
                'order_status' => OrderConstant::ORDER_STATUS_REJECTED,
            ], ['id'=>$order_id]);
        }

        model('order_certificate')->where([
            'status' => 0,
            'order_no' => $order['order_no']
        ])->setField('status', $type==1?$type:2);
        model('order_action')->save($data);//订单操作记录
        ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
    }

    public function fahuo()
    {
        $id = request()->post('id');
        $data['order_status'] = OrderConstant::ORDER_STATUS_WAIT_RECEIVE;
        $data['is_shipping'] = 1;
        $data['express_name'] = request()->post('express_name');
        $data['express_no'] = request()->post('express_no');
        $data['shipping_time'] = date('Y-m-d H:i:s', time());
        $res = model('order')->save($data, ['id' => $id]);
        $order = model('order')->where(['id' => $id])->find();

        $data_info['status_desc'] = '订单发货';
        $data_info['order_id'] = $id;
        $data_info['action_user'] = model('manager')->where(['id' => $this->manager_id])->value('manager_name');
        $data_info['order_status'] = $order['order_status'];
        $data_info['pay_status'] = $order['pay_status'];
        $data_info['shipping_status'] = $order['is_shipping'];
        $data_info['log_time'] = time();
        model('order_action')->save($data_info);//订单操作记录
        $content = "订单发货";
        if ($res) {
          /*  $msg = getSetting('sms.shipping_template');
            $msg = str_replace('{order_no}', $order['order_no'], $msg);
            $this->smsMessage($order['telephone'], $msg);*/
            ajaxReturn(["status" => 1, "msg" => "发货成功！"]);
        } else {
            ajaxReturn(["status" => 0, "msg" => "网络繁忙，请稍后~~"]);
        }
    }


    /**
     * 导出订单
     *yb 2017-08-07
     */
    public function outexcel()
    {
        $map = [];
//        $map['a.partner_id'] = 0;
        $name = request()->param("name");
        $order_no = request()->param("order_no");
        $starttime = request()->param("starttime");
        $endtime = request()->param("endtime");
        $status = request()->param("status");

        $this->assign("telephone",$name);

        if ($starttime && $endtime) {
            $map['a.create_time'] = ['between', [$starttime, $endtime]];
        } elseif ($starttime) {
            $map['a.create_time'] = ['egt', $starttime];
        } elseif ($endtime) {
            $map['a.create_time'] = ['elt', $endtime];
        }
        $arr_array = [];

        if ($status) {
            $map['a.state'] = $status;
        }
        if ($name) {
            $map['b.nickname|a.take_phone|a.fphone|a.take_name'] = ["like", "%$name%"];
            $this->assign("name", $name);
        }
        if ($order_no) {
            $map['a.order_id'] = ["like", "%$order_no%"];
            $this->assign("order_no", $order_no);
        }

        $refund = model('order')->alias('a')
            ->join('user b', 'a.uid = b.id', 'left')
            ->field('a.*')
            ->order('a.create_time desc')
            ->where($map)
            ->select();

        $data_info = [];
        foreach ($refund as $k => $v) {
            $data_info[$k]['id'] = $v['id'];
            $data_info[$k]['order_id'] = $v['order_id'];
            $data_info[$k]['create_time'] = $v['create_time'];
            $data_info[$k]['name'] = $v['fname'];
            $data_info[$k]['tel'] = $v['fphone'];
            $data_info[$k]['state'] =$v['state'];
            $data_info[$k]['price'] = $v['price'];
            $data_info[$k]['take_name'] = $v['take_name'];
            $data_info[$k]['take_phone'] = $v['take_phone'];
            $data_info[$k]['address'] = $v['take_province'].$v['take_city'].$v['take_district'].$v['take_detailaddress'];
        }
        $headArr = ['id','订单编号','下单时间','下单人姓名','下单人手机号','订单状态','订单金额','收货人姓名','收货人手机号','收货地址'];

        $before_json = [];
        $after_json = [];

        $content = '导出订单信息';
        $this->managerLog($this->manager_id, $content, $before_json, $after_json);

        $this->excelExport('订单信息表', $headArr, $data_info);

    }



    /**
     * 导入订单
     */
    public function importexcel(){
        set_time_limit(0);
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'task_data');
            if ($info) {
                // 成功上传后 获取上传信息
                // 输出 jpg
                $type=$info->getExtension();
                if($type!="xls"){
                    return $this->error('只能上传xls格式！');
                }
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                $route=$info->getSaveName();
                $data=$this->excelToArray('uploads' . DS . 'task_data' . DS .$route);
                dump($data);exit;
                $err_num=0;
                $succ_num=0;
                $update = [];
                $arr = [];
                foreach ($data as $key =>$v){
                    $info=db('order')->where(['id'=>trim($v['0']),'state'=>2])->find();
                    if(!$info){
                        $err_num=$err_num+1;
                        continue;
                    }
                    if($v['6']=='' ||$v['7']==''){
                        $err_num=$err_num+1;
                        continue;
                    }
                    $update['id']=trim($v['0']); //订单id
                    $update['end_time']=time();//签收时间
                    $arr[] = $update;
                    $succ_num++;
                }
                $i=0;
                $update_list = [];
                $num=0;
                $key=0;
                for($i;$i < count($arr);$i++){
                    $update_list[$key][] = $arr[$i];
                    $num++;
                    if($num == 500){
                        $num = 0;
                        $key++;
                    }
                }
                $userTaskModel = new UserTask();
                foreach ($update_list as $item){
                    try{
                        Db::startTrans();
                        $userTaskModel->saveAll($item);
                        Db::commit();
                    }catch (Exception $e){
                        Db::rollback();
                    }
                }
                $res1=admin_log("单号批量导入", "管理员{$this->admin_info['user_name']}操作:单号批量导入");
                if(!$res1){
                    return $this->error('操作日志写入失败！');
                }
                $this->success("成功{$succ_num},失败{$err_num}");
            } else {
                // 上传失败获取错误信息
                return $this->error($file->getError());
            }
        }
    }

    function excelToArray($filename){
        /** Include PHPExcel_IOFactory */
        vendor("PHPExcel.PHPExcel");
        vendor("PHPExcel.PHPExcel.IOFactory");
        // $obj = new \PHPExcel();

        if (!file_exists($filename)) {
            exit("文件".$filename."不存在");
        }
        $objPHPExcel = PHPExcel_IOFactory::load($filename);
        //开始读取上传到服务器中的Excel文件，返回一个二维数组
        $dataArray = $objPHPExcel->getSheet(0)->toArray();
        $sheet_count = $objPHPExcel->getSheetCount();
        for ($s = 0; $s < $sheet_count; $s++)
        {
            $currentSheet = $objPHPExcel->getSheet($s);// 当前页
            $row_num = $currentSheet->getHighestRow();// 当前页行数
            $col_max = $currentSheet->getHighestColumn(); // 当前页最大列号

            // 循环从第二行开始，第一行往往是表头
            for($i = 3; $i <= $row_num; $i++)
            {
                $cell_values = array();
                for($j = 'A'; $j <= $col_max; $j++)
                {
                    $address = $j . $i; // 单元格坐标
                    $cell_values[] = $currentSheet->getCell($address)->getFormattedValue();
                }
                $import_data[]=$cell_values;

            }
            // 看看数据
            return $import_data;
        }
    }
    /**
     * 送货明细单
     */
    public function outOrderGoods()
    {
        $id = request()->param("order_id");  // 得到order的id
        $data_info  = model('order_goods')
            ->field('goods_name,goods_code,goods_pic,goods_num,goods_price,sku_info,refund_reason')
            ->where(['order_id' => $id])
            ->select();
        $filename = '送货明细单';
        $field = 'consignee,telephone,province_id,city_id,district_id,place';
        $address_info = model('order')->where(['id' => $id])->field($field)->find();
        $address_info['province'] = model('region')->where(['id' => $address_info['province_id']])->value('name');
        $address_info['city'] = model('region')->where(['id' => $address_info['city_id']])->value('name');
        $address_info['district'] = model('region')->where(['id' => $address_info['district_id']])->value('name');
        $this->exportOrderGoods($data_info, $address_info, $filename);
    }

    /**
     * 线上退款
     * */
    public function orderRefund()
    {
        if (request()->isPost()) {
            $order_no = input('post.order_no', '', 'trim');
            $refund_money = input('post.refund_money', '0', 'trim');
            $refund_order_id = input("post.refund_order_id");
            /*$refund_password    = input('post.refund_password','','trim');
            if (GetIp() != '115.192.190.57') {
                if(!$refund_password){
                    ajaxReturn(0,'请填写操作密码');
                }
                $count = model('refund_password')->where(['id'=>'1','password'=>encrypt_pass($refund_password)))->count();
                if($count<1){
                    ajaxReturn(0,'操作密码错误');
                }
            }*/
            //退款前的检验
            if (!$order_no) {
                $return_arr = ['status' => 0, 'msg' => '没有订单编号', 'data' => []]; //
                ajaxReturn($return_arr);
            }
            if (!$refund_money) {
                $return_arr = ['status' => 0, 'msg' => '请填写退款金额', 'data' => []]; //
                ajaxReturn($return_arr);
            }
            $order = model('order')->where(['order_no' => $order_no])->find();
            if (!$order) {
                $return_arr = ['status' => 0, 'msg' => '没有找到订单', 'data' => []]; //
                ajaxReturn($return_arr);
            }

            if ($refund_money > $order['pay_price']) {
                $return_arr = ['status' => 0, 'msg' => '退款金额必须小于支付金额', 'data' => []]; //
                ajaxReturn($return_arr);
            }

            if ($refund_money < 0.01) {
                $return_arr = ['status' => 0, 'msg' => '退款金额不合法', 'data' => []]; //
                ajaxReturn($return_arr);
            }

            $refund_order_goods = model("order_goods")->where(["id" => $refund_order_id])->find();
            if ($refund_money > $refund_order_goods['refund_money']) {
                $return_arr = ['status' => 0, 'msg' => '部分退款金额超出', 'data' => []]; //
                ajaxReturn($return_arr);
            }

            #执行退款
            $order_info = model('order')->where(['order_no' => $order_no])->field('pay_way,order_no,out_trade_no, trade_no,id, user_id')->find();

//            $result = $this->WeixinRefund($order_info['order_no'],$order_info['trade_no'],$refund_money,'微信支付订单'.$order_info['order_no'].'退款',$refund_order_id);

            $way = '微信支付';
            $result['status'] = 1;
            switch ($order_info['pay_way']) {
                /*case 1:#余额支付
                    $result = D('Admin/OrderInfo')->balanceRefund($order_info['user_id'],$refund_money,$order_info['id'],'订单'.$order_info['order_no'].'退款', $refund_order_id);
                    $way = '余额支付';
                    break;*/
                case 1:#支付宝支付
                    $result = $this->aliRefund($order_info['out_trade_no'], $order_info['trade_no'], $refund_money, '支付宝支付订单' . $order_info['order_no'] . '退款', $refund_order_id);
                    $way = '支付宝支付';
                    break;
                case 2:#微信支付
                    $result = $this->WeixinRefund($order_info['out_trade_no'], $order_info['trade_no'], $refund_money, '微信支付订单' . $order_info['order_no'] . '退款', $refund_order_id);
                    $way = '微信支付';
                    break;
                case 4:#银联支付  订单日期  订单时间  原交易编号(支付号)  交易编号  退款金额  附言
                    $result = $this->unionRefund($order_info['orderdate'],$order_info['ordertime'],$order_info['out_trade_no'],$order_info['order_no'].rand(100,999),$refund_money,'银联支付的订单'.$order_info['order_no'].'退款');
                    $way = '银联支付';
                    break;
            }

            $before_json = ['order_no' => $order_no, 'order_goods_id' => $refund_order_id];
            $after_json = ['order_no' => $order_no, 'order_goods_id' => $refund_order_id];
            #退款后的处理
            if ($result['status'] == 0) {
                $msg = $order_no . '订单' . $way . '退款失败';
                $this->addToOrderErrorLog($order_info['user_id'], $order_info['id'], $msg);
                $return_arr = ['status' => 0, 'msg' => $result['msg'], 'data' => []]; //
                ajaxReturn($return_arr);
                $content = '订单商品退款失败';
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            } else {
                /*修改订单状态*/
                $this->refund_after_checkout($refund_money, $refund_order_id, $order_info);
                $content = '订单商品退款完成';
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            }

            $sum_tj = 0;
            $stu_order = model("order_goods")->where(["order_no" => $order_no])->select();
            foreach ($stu_order as $st_key => $st_val) {
                if ($stu_order[$st_key]["is_refund"] == "1" && $stu_order[$st_key]["is_apply_refund"] != "3") {
                    $sum_tj += 1;
                }
            }
            if ($sum_tj == count($stu_order)) {
                $status_data = [
                    "order_status" => OrderConstant::ORDER_STATUS_FINISH_REFUND,
                ];
                model("order")->save($status_data, ['id' => $order_info['id']]);

                $storeOrder = model("store_order")->where(['parent_no' => $order_no])->select();
                if ($storeOrder) {
                    foreach ($storeOrder as $storeOrder_k => $storeOrder_v) {
                        model('store_order')->where(['id' => $storeOrder_v['id']])->update(['order_status' => 12]);
                    }
                }
            }

            $return_arr = ['status' => 1, 'msg' => '退款处理完成', 'data' => []]; //

            ajaxReturn($return_arr);
        }
    }


    /**
     * 拒绝退款
     * */
    public function refuse_refund()
    {
        if (request()->isPost()) {
            $order_no = input('post.order_no', '', 'trim');
            $order_id = input('post.order_id', '', 'trim');
            $refuse_refund_reason = input('post.refuse_refund_reason', '', 'trim');
            $order_res = model('order')->where(['order_no' => $order_no])->find();
            if (!$order_res) {
                $return_arr = ['status' => 0, 'msg' => '无效的订单', 'data' => []]; //
                ajaxReturn($return_arr);
            }
            $refuse_data = [
                'is_refund' => 2,
                'refund_reason' => $refuse_refund_reason,
                'refund_time' => date('Y-m-d H:i:s')
            ];
            $res = model("order_goods")->save($refuse_data, ["id" => $order_id]);
            if ($res) {
                $refund_list_data = [
                    "refund_status" => 3,
                    "refund_count" => $refuse_refund_reason,
                    'refund_edit_time' => date('Y-m-d H:i:s'),
                ];
                model("refund_list")->save($refund_list_data, ["order_no" => $order_no, "order_id" => $order_id]);

                $storeOrder = model("store_order")->where(['parent_no' => $order_no])->select();
                if ($storeOrder) {
                    foreach ($storeOrder as $storeOrder_k => $storeOrder_v) {
                        model('store_order')->where(['id' => $storeOrder_v['id']])->update(['order_status' => 10]);
                        $storeOrderGoods = model('store_order_goods')->where(['order_id' => $storeOrder_v['id']])->select();
                        foreach ($storeOrderGoods as $storeOrderGoods_k => $storeOrderGoods_v) {
                            model('store_order_goods')->where(['id' => $storeOrderGoods_v['id']])->update(['is_refund' => 2, 'refund_time' => date('Y-m-d H:i:s'), 'refund_reason' => $refuse_refund_reason]);
                        }
                    }
                }
                $return_arr = ['status' => 1, 'msg' => '拒绝成功', 'data' => []]; //
                ajaxReturn($return_arr);
            } else {
                $return_arr = ['status' => 0, 'msg' => '拒绝失败', 'data' => []]; //
                ajaxReturn($return_arr);
            }
        }
    }

    /**
     * 同意
     * */
    public function refuse_refund_consent()
    {
        if (request()->isPost()) {
            $order_no = input('post.order_no', '', 'trim');
            $order_id = input('post.order_id', '', 'trim');
            $order_res = model('order')->where(['order_no' => $order_no])->find();
            if (!$order_res) {
                $return_arr = ['status' => 0, 'msg' => '无效的订单', 'data' => []]; //
                ajaxReturn($return_arr);
            }
            $refuse_data = [
                'is_refund' => 4,
                'refund_time' => date('Y-m-d H:i:s'),
            ];
            $res = model("order_goods")->save($refuse_data, ["id" => $order_id]);


            $storeOrder = model("store_order")->where(['parent_no' => $order_no])->select();
            if ($storeOrder) {
                foreach ($storeOrder as $storeOrder_k => $storeOrder_v) {
                    $storeOrderGoods = model('store_order_goods')->where(['order_id' => $storeOrder_v['id']])->select();
                    foreach ($storeOrderGoods as $storeOrderGoods_k => $storeOrderGoods_v) {
                        model('store_order_goods')->where(['id' => $storeOrderGoods_v['id']])->update(['is_refund' => 4, 'refund_time' => date('Y-m-d H:i:s')]);
                    }
                }
            }
            if ($res) {
                $refund_list_data = [
                    "refund_status" => 2,
                    'refund_edit_time' => date('Y-m-d H:i:s'),
                ];
                model("refund_list")->save($refund_list_data, ["order_no" => $order_no, "order_id" => $order_id]);
                $return_arr = ['status' => 1, 'msg' => '申请成功', 'data' => []]; //
                ajaxReturn($return_arr);
            } else {
                $return_arr = ['status' => 0, 'msg' => '申请失败', 'data' => []]; //
                ajaxReturn($return_arr);
            }
        }
    }

    /**
     * 换货完成
     * */
    public function refund_consent_wc()
    {
        if (request()->isPost()) {
            $order_no = input('post.order_no', '', 'trim');
            $order_id = input('post.order_id', '', 'trim');
            $order_res = model('order')->where(['order_no' => $order_no])->find();
            if (!$order_res) {
                $return_arr = ['status' => 0, 'msg' => '无效的订单', 'data' => []]; //
                ajaxReturn($return_arr);
            }
            $refuse_data = [
                'is_refund' => 1,
                'refund_time' => date('Y-m-d H:i:s'),
            ];
            $res = model("order_goods")->save($refuse_data, ["id" => $order_id]);

            $storeOrder = model("store_order")->where(['parent_no' => $order_no])->select();
            if ($storeOrder) {
                foreach ($storeOrder as $storeOrder_k => $storeOrder_v) {
                    $storeOrderGoods = model('store_order_goods')->where(['order_id' => $storeOrder_v['id']])->select();
                    foreach ($storeOrderGoods as $storeOrderGoods_k => $storeOrderGoods_v) {
                        model('store_order_goods')->where(['id' => $storeOrderGoods_v['id']])->update(['is_refund' => 1, 'refund_time' => date('Y-m-d H:i:s')]);
                    }
                }
            }

            if ($res) {
                $refund_list_data = [
                    "refund_status" => 2,
                    'refund_edit_time' => date('Y-m-d H:i:s'),
                ];
                model("refund_list")->save($refund_list_data, ["order_no" => $order_no, "order_id" => $order_id]);
                $return_arr = ['status' => 1, 'msg' => '操作成功', 'data' => []]; //
                ajaxReturn($return_arr);
            } else {
                $return_arr = ['status' => 0, 'msg' => '操作失败', 'data' => []]; //
                ajaxReturn($return_arr);
            }
        }
    }

    /**
     * express列表
     */
    public function expressList()
    {
        $express_model = model('express');
        $keyword = request()->param('keyword');

        $where = [];
        if ($keyword) {
            $where['express_company'] = ['like', "%{$keyword}%"];
        }

        $this->assign('keyword', $keyword);

        $list = $express_model->where($where)->paginate(10,false,['query'=>request()->param()]);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 添加express列表
     */
    public function expressAdd()
    {
        $express_model = model('express');
        $id = request()->param('id');
        $where = ['id' => $id];
        $cache = $express_model->where($where)->find();
//        if ($cache['express_banner_pic']) {
//            $cache['express_banner_pic'] = explode(',', $cache['express_banner_pic']);
//        }
        $this->assign("cache", $cache);

        return $this->fetch();
    }

    /**
     * 操作express
     */
    public function expressHandle()
    {
        if (request()->isPost()) {
            $data = request()->post();
            $id = request()->post('id', 0);
            $express_model = model('express');


            if (!$data['express_company']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写快递公司', 'data' => []]);
            }
            if (!$data['express_ma']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写快递编码', 'data' => []]);
            }
            if (!$data['express_tel']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写快递电话', 'data' => []]);
            }


            if (!isset($data['express_logo'])) {
                ajaxReturn(['status' => 0, 'msg' => '请上传快递logo', 'data' => []]);
            }

            //$data['sort'] = $this->getSort($data['sort'], $express_model, $id, [], 'id');

            if ($id) {
                $data['update_time'] = time();
                $content = '修改快递信息';
                $field = array_keys($data);
                $field[] = 'id';
                $before_json = $express_model->field($field)->where(['id' => $id])->find();
                $result = $express_model->save($data, ['id' => $id]);
                $data['id'] = $id;
                $after_json = $data;

            } else {
                $data['create_time'] = time();
                $content = '添加快递信息';
                $before_json = [];
                $result = $express_model->save($data);
                $data['id'] = $express_model->getLastInsID();
                $after_json = $data;
            }

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($result) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }

    /**
     * 删除express
     */
    public function delExpress()
    {
        if (request()->isPOST()) {

            $ids = request()->post('id');

            $express_model = model('express');
            if (!$ids) {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }

            $arr = array_unique(explode('-', ($ids)));

            $data = $express_model->where(['id' => ['in', $arr]])->find();

            $del = $express_model->destroy($arr);

            if ($del) {
                $before_json = $data;
                $after_json = [];
                $content = '删除快递';

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }

        }
    }
    /**
     * 添加订单
     * @param int $id 订单id
     */
    public function addOrder()
    {
        if (request()->isPost()) {
            $data_info['consignee'] = request()->post('consignee');// 收货人

            $data_info['place'] = request()->post('place'); // 收货地址
            $data_info['telephone'] = request()->post('telephone'); // 手机
//            $data_info['invoice_title'] = $data['invoice_title'];// 发票
            $data_info['remark1'] = request()->post('remark1'); // 管理员备注

            $data_info['pay_way'] = request()->post('pay_way');// 支付方式

            $goods_id_arr = request()->post('goods_id/a');
            if (empty($goods_id_arr)) {
                ajaxReturn(['status' => 0, 'msg' => '请选择商品']);
            }

            $goods_num_arr = request()->post('goods_num/a');
            $goods_remark_arr = request()->post('goods_remark/a');
            $sku_id_arr = request()->post('sku_id/a');
            $new_goods = [];
            //################################订单添加商品
            foreach ($goods_id_arr as $k => $v) {
                $goods = model('goods')->where("id", $v)->find();
                $new_goods[$k]['goods_id'] = $v; // 商品id
                $new_goods[$k]['user_id'] = 0; // 商品id
                $new_goods[$k]['goods_name'] = $goods['goods_name'];
                $new_goods[$k]['goods_unit'] = $goods['goods_unit'];
                $new_goods[$k]['goods_num'] = $goods_num_arr[$k];
                $new_goods[$k]['goods_remark'] = $goods_remark_arr[$k];
                $new_goods[$k]['goods_code'] = $goods['goods_code'];
                $new_goods[$k]['goods_pic'] = $goods['goods_logo'];
                $new_goods[$k]['goods_price'] = $goods['price'];
                $new_goods[$k]['goods_oprice'] = $goods['oprice'];
                $new_goods[$k]['cost_price'] = $goods['cost_price'];
                $new_goods[$k]['b_price'] = $goods['b_price'];
                //$new_goods[$k]['goods_pay_price'] = $goods_pay_price * $goods['goods_price'];

                $new_goods[$k]['store_id'] = $goods['store_id'];
//                    $new_goods[$k]['order_id'] = $order_id;
                $new_goods[$k]['sku_id'] = $sku_id_arr[$k];
                $new_goods[$k]['sku_info'] = '';
                if ($sku_id_arr[$k]) {
                    $result = model('spec_goods_price')->where(['goods_id' => $v, 'key' => $sku_id_arr[$k]])->find();
                    $new_goods[$k]['sku_info'] = $result['key_name'];
                    if ($result['bar_code']) {
                        $new_goods[$k]['goods_code'] = $result['bar_code'];
                    }
                    $new_goods[$k]['goods_price'] = $result['price'];
                    $new_goods[$k]['goods_oprice'] = $result['oprice'];
                    $new_goods[$k]['cost_price'] = $result['cost_price'];
                    $new_goods[$k]['b_price'] = $result['b_price'];
                    //$new_goods[$k]['goods_pay_price'] = $goods_pay_price * $result['goods_price'];
                }

                //model('order_goods')->save($new_goods[$k]);//订单添加商品
            }

//            $goodsArr = array_merge($old_goods_arr, $new_goods);
            $address['province_id'] = request()->post('province_id');
            $address['city_id'] = request()->post('city_id');
            $address['district_id'] = request()->post('district_id');
            $car_price = $this->getOrderPrice($new_goods, 0, 0, $address, 0);
            /*$goodsArr = array_merge($old_goods_arr,$new_goods);
            $result = calculate_price($order['user_id'],$goodsArr,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
            if($result['status'] < 0)
            {
                $this->error($result['msg']);
            }*/
            $order_no = $this->get_order_sn();
            $data_info['user_id'] = request()->post('user_id');
            $data_info['order_no'] = $order_no;
            $data_info['out_trade_no'] = $order_no;
            $data_info['pay_order_status'] = 0;
            $data_info['coupon_id'] = 0;
            $data_info['payment_id'] = 0;
            $data_info['partner_id'] = 0;
            $data_info['source'] = OrderConstant::ORDER_SOURCE_ADMIN;
            $data_info['order_type'] = CartConstant::CART_TYPE_ADMIN_BUY;
            $data_info['pay_way'] = request()->post('payment');
            $data_info['pay_status'] = OrderConstant::PAY_STATUS_NONE;
            $data_info['order_status'] = OrderConstant::ORDER_STATUS_WAIT_PAY;
            $data_info['order_time'] = date("Y-m-d H:i:s");
            $data_info['update_time'] = date("Y-m-d H:i:s");
            $data_info['province_id'] = $address['province_id']; // 省份
            $data_info['city_id'] = $address['city_id']; // 城市
            $data_info['district_id'] = $address['district_id']; // 县
            $data_info['total_price'] = $car_price['goods_price']; // 商品总价
            $data_info['express_fee'] = $car_price['express_fee'];//物流费
            $data_info['total_fee'] = $car_price['order_amount']; // 应付金额
            $data_info['coupon_price'] = $car_price['coupon_price']; // 应付金额
            $data_info['integral_money'] = $car_price['integral_money']; // 应付金额
            $data_info['cover_fee'] = $car_price['cover_fee']; // 应付金额
            $data_info['deposit_money'] = $car_price['deposit_money']; // 应付金额
            $data_info['payment_money'] = $car_price['payment_money']; // 应付金额
            Db::startTrans();
            $o = model('order')->save($data_info);
            if (!$o) {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
            $order_id = model('order')->getLastInsID();

            foreach ($new_goods as $k => $v) {
                $new_goods[$k]['order_id'] = $order_id;
                $new_goods[$k]['order_no'] = $order_no;
                $goods_pay_price = $car_price['order_amount']?$v['goods_price']/$car_price['order_amount']:0;
                $new_goods[$k]['goods_pay_price'] = $goods_pay_price * $v['goods_price'];
            }
            $o = model('order_goods')->insertAll($new_goods);//订单添加商品
            //$l = $orderLogic->orderActionLog($order_id,'edit','修改订单');//操作日志
            if (!$o) {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }

            $data['order_id'] = $order_id;
            $data['action_user'] = model('manager')->where(['id' => $this->manager_id])->value('manager_name');
            $data['action_note'] =  $data_info['remark1'];
            $data['order_status'] = $data_info['order_status'];
            $data['pay_status'] = $data_info['pay_status'];
            $data['log_time'] = time();
            $data['status_desc'] = '后台录单';
            $o = model('order_action')->save($data);//订单操作记录

            if (!$o) {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }

            Db::commit();
            ajaxReturn(['status' => 1, 'msg' => '提交成功']);
        }
        // 获取省份
        $province = model('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $this->assign('order', []);
        $this->assign('province', $province);
        $this->assign('city', '');
        $this->assign('area', '');
        $this->assign('orderGoods', []);
        return $this->fetch();
    }


    /**
     * goods列表
     */
    public function searchGoods()
    {
        $goods_ids = request()->param('goods_ids');
        $sku_ids = request()->param('sku_ids');
        $this->assign('goods_ids', $goods_ids);
        $this->assign('sku_ids', $sku_ids);

        if (request()->isPost()) {

            $goods_model = model('goods');
            $keyword = request()->post('keyword', '', 'trim');
            $goods_cate_id = request()->post('cate');
            $barnd_id = request()->post('brand');

            $goods_id_new = [];
            $spec_key = [];
            if ($goods_ids) {
                $goods_id_arr = explode(',', $goods_ids);
                $sku_ids_arr = explode(',', $sku_ids);
                foreach ($goods_id_arr as $k => $v) {
                    !isset($sku_ids_arr[$k]) ? $sku_ids_arr[$k] = '' : false;
                    if (!$sku_ids_arr[$k]) {
                        $goods_id_new[] = $v;
                    } else {
                        $spec_key[] = $sku_ids_arr[$k];
                    }
                }

            }

            $where = ['a.is_audit' => 1, 'a.delete_time' => null];

            if ($goods_id_new) {
                $where['a.id'] = ['notin', $goods_id_new];
            }

            if ($spec_key) {
                $where['b.key'] = ['notin', $spec_key];
            }

            if ($keyword) {
                $where['a.goods_name|a.goods_code'] = ['like', "%{$keyword}%"];
            }
            if ($goods_cate_id) {
                $where['a.cate_id|a.cate_two_id|a.cate_three_id'] = $goods_cate_id;
            }
            if ($barnd_id) {
                $where['a.brand_id|a.brand_two_id'] = $barnd_id;
            }

            $whereOr = $where;
            $whereOr['b.key'] = null;

            $list_row = input('post.list_row', 10); //每页数据
            $page = input('post.page', 1); //当前页

            $totalCount = model('spec_goods_price')
                ->alias('b')
                ->join('tb_goods a', 'a.id = b.goods_id', 'RIGHT')
                ->where($where)
                ->whereOr(function ($query) use ($whereOr) {
                    $query->where($whereOr);
                })->count();
            $first_row = ($page - 1) * $list_row;
            $list = model('spec_goods_price')
                ->alias('b')
                ->where($where)
                ->whereOr(function ($query) use ($whereOr) {
                    $query->where($whereOr);
                })
                ->field('a.id, a.goods_name, a.goods_logo,a.goods_code, a.stores, a.price, b.price spec_price, b.key, b.key_name')
                ->join('tb_goods a', 'a.id = b.goods_id', 'RIGHT')
                ->order('a.sort desc')
                ->limit($first_row, $list_row)
                ->select();
            foreach ($list as $k => $v) {
                $list[$k]['key'] = $v['key'] ? $v['key'] : 0;
            }

            $pageCount = ceil($totalCount / $list_row);

            //商品分类
            $goods_cate = model('goods_cate')->field('id,classname,pid')->where(['pid' => 0, 'status' => '1'])->order('sort desc')->select();
            $goods_cate_new = [];
            foreach ($goods_cate as $key => $item) {
                $goods_cate_new[] = $item;
                $cate_list = model('goods_cate')->field('id,classname,pid')->where(['pid' => $item['id'], 'status' => '1'])->order('sort desc')->select();
                foreach ($cate_list as $k => $v) {
                    $v['classname'] = '&nbsp;&nbsp;|--' . $v['classname'];
                    $goods_cate_new[] = $v;
                }
            }
            //品牌
            $goods_brand = model('goods_brand')->field('id,classname,pid')->where(['pid' => 0, 'status' => '1'])->order('sort desc')->select();
            $goods_brand_new = [];
            foreach ($goods_brand as $key => $item) {
                $goods_brand_new[] = $item;
                $brand_list = model('goods_brand')->field('id,classname,pid')->where(['pid' => $item['id'], 'status' => '1'])->order('sort desc')->select();
                foreach ($brand_list as $k => $v) {
                    $v['classname'] = '&nbsp;&nbsp;|--' . $v['classname'];
                    $goods_brand_new[] = $v;
                }
            }

            $data = [
                'list' => $list ? $list : [],
                'pageCount' => $pageCount ? $pageCount : 0,
                'totalCount' => $totalCount ? $totalCount : 0,
                'goods_cate_new' => $goods_cate_new ? $goods_cate_new : [],
                'goods_brand_new' => $goods_brand_new ? $goods_brand_new : [],
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        } else {
            return $this->fetch();
        }
    }
    /**
     * 订单编辑
     * @param int $id 订单id
     */
    public function orderEdit()
    {
        $order_id = input('order_id');
        //$orderLogic = new OrderLogic();
        //$order = $orderLogic->getOrderInfo($order_id);
        $order = model('order')->where(['id' => $order_id])->find();
        if ($order['is_shipping'] == 1) {
            $this->error('已发货订单不允许编辑');
            exit;
        }

        $orderGoods = model('order_goods')->where('order_id', $order_id)->select();

        if (request()->isPost()) {
            $data = request()->post();
            $data_info['consignee'] = $data['consignee'];// 收货人

            $data_info['place'] = $data['place']; // 收货地址
            $data_info['telephone'] = $data['telephone']; // 手机
//            $data_info['invoice_title'] = $data['invoice_title'];// 发票
            $data_info['remark1'] = $data['remark1']; // 管理员备注

            $data_info['pay_way'] = $data['payment'];// 支付方式

            $goods_id_arr = $data["goods_id"];
            $goods_num_arr = $data["goods_num"];
            $sku_id_arr = $data["sku_id"];
            $new_goods = $old_goods_arr = $old_goods = [];
            //################################订单添加商品

            if ($goods_id_arr) {
                foreach ($goods_id_arr as $k => $v) {
                    $data = ['order_id' => $order_id, 'goods_id' => $v, 'sku_id' => $sku_id_arr[$k]];
                    $order_goods = model('order_goods')->where($data)->find();
                    if ($order_goods) {
                        $old_goods[$order_goods['id']] = $goods_num_arr[$k];
                    } else {
                        $goods = model('goods')->where("id", $v)->find();
                        $new_goods[$k]['goods_id'] = $v; // 商品id
                        $new_goods[$k]['user_id'] = $order['user_id']; // 商品id
                        $new_goods[$k]['goods_name'] = $goods['goods_name'];
                        $new_goods[$k]['goods_unit'] = $goods['goods_unit'];
                        $new_goods[$k]['goods_num'] = $goods_num_arr[$k];
                        $new_goods[$k]['goods_code'] = $goods['goods_code'];
                        $new_goods[$k]['goods_pic'] = $goods['goods_logo'];
                        $new_goods[$k]['goods_price'] = $goods['price'];
                        $new_goods[$k]['order_id'] = $order_id;
                        $new_goods[$k]['sku_id'] = $sku_id_arr[$k];
                        $new_goods[$k]['sku_info'] = '';
                        if ($sku_id_arr[$k]) {
                            $result = model('spec_goods_price')->where(['goods_id' => $v, 'key' => $sku_id_arr[$k]])->find();
                            $new_goods[$k]['sku_info'] = $result['key_name'];
                            $new_goods[$k]['goods_code'] = $result['bar_code'];
                            $new_goods[$k]['goods_price'] = $result['price'];
                        }
                        model('order_goods')->save($new_goods[$k]);//订单添加商品
                    }
                }

                //################################订单修改删除商品
                foreach ($orderGoods as $val) {
                    if (!isset($old_goods[$val['id']])) {
                        model('order_goods')->destroy($val['id']);//删除商品
                    } else {
                        //修改商品数量
                        if ($old_goods[$val['id']] != $val['goods_num']) {
                            $val['goods_num'] = $old_goods[$val['id']];
                            model('order_goods')->update(['goods_num' => $val['goods_num']], ['id' => $val['id']]);
                        }
                        $old_goods_arr[] = $val;
                    }
                }

            }
            $goodsArr = array_merge($old_goods_arr, $new_goods);

            $address['province_id'] = input('province_id');
            $address['city_id'] = input('city_id');
            $address['district_id'] = input('district_id');
            $car_price = $this->getOrderPrice($goodsArr, $order['integral_money'], $order['coupon_id'], $address, 0);
            /*$goodsArr = array_merge($old_goods_arr,$new_goods);
            $result = calculate_price($order['user_id'],$goodsArr,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
            if($result['status'] < 0)
            {
                $this->error($result['msg']);
            }*/

            $data_info['province_id'] = $address['province_id']; // 省份
            $data_info['city_id'] = $address['city_id']; // 城市
            $data_info['district_id'] = $address['district_id']; // 县

            //################################修改订单费用
            $data_info['total_price'] = $car_price['goods_price']; // 商品总价
            $data_info['express_fee'] = $car_price['express_fee'];//物流费
            $data_info['total_fee'] = $car_price['order_amount']; // 应付金额
            $o = model('order')->save($data_info, ['id' => $order_id]);

            //$l = $orderLogic->orderActionLog($order_id,'edit','修改订单');//操作日志
            if ($o) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
        // 获取省份
        $province = model('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        //获取订单城市
        $city = model('region')->where(array('parent_id' => $order['province_id'], 'level' => 2))->select();
        //获取订单地区
        $area = model('region')->where(array('parent_id' => $order['city_id'], 'level' => 3))->select();


        $this->assign('order', $order);
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('area', $area);
        $this->assign('orderGoods', $orderGoods);
        return $this->fetch();
    }
    /**
     * 订单编辑
     * @param int $id 订单id
     */
    public function orderEditPrice()
    {
        $order_id = input('order_id');
        $order = model('order')->where(['id' => $order_id])->find();
        if ($order['pay_status'] == 1) {
            $this->error('已支付订单不允许修改价格');
            exit;
        }
        if (request()->isPost()) {
            $total_fee = request()->post('total_fee');
            $action_note = request()->post('action_note');
            $order_id = request()->post('order_id');

            if (!$order_id) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
            }
            $order = model('order')->where(['id'=>$order_id])->find();
            if (!$order) {
                ajaxReturn(['status' => 0, 'msg' => '订单不存在']);
            }

            $data['order_id'] = $order_id;
            $data['action_user'] = model('manager')->where(['id' => $this->manager_id])->value('manager_name');
            $data['action_note'] = $action_note;
            $data['order_status'] = $order['order_status'];
            $data['pay_status'] = $order['pay_status'];
            $data['shipping_status'] = $order['is_shipping'];
            $data['log_time'] = time();
            $data['status_desc'] = '修改订单价格';
            $o = model('order_action')->save($data);//订单操作记录
            model('order')->save(['total_fee' => $total_fee, 'total_amount' => $total_fee], ['id' => $order_id]);
            $order_goods = model('order_goods')->field('id, goods_price')->where(['order_id' => $order_id])->select();
            foreach ($order_goods as $k => $v) {
                $goods_pay_price = $order['total_fee']?$v['goods_price']/$order['total_fee']:0;
                model('order_goods')->update(
                    ['goods_pay_price' => $goods_pay_price * $total_fee],
                    ['id' => $v['id']]
                );
            }

            //$l = $orderLogic->orderActionLog($order_id,'edit','修改订单');//操作日志
            if ($o) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
        $this->assign('order', $order);
        return $this->fetch();
    }

    /*
     * 拆分订单
     */
    public function orderSplits()
    {
        $order_id = input('order_id');
        //$orderLogic = new OrderLogic();
        //$order = $orderLogic->getOrderInfo($order_id);
        $order = Db('order')->where(['id' => $order_id])->find();

        if ($order['is_shipping'] == 1) {
            $this->error('已发货订单不允许编辑');
            exit;
        }

        $orderGoods = model('order_goods')->where('order_id', $order_id)->select();

        if (request()->isPost()) {
            $data = input('post.');
            //################################先处理原单剩余商品和原订单信息
            $old_goods = input('old_goods/a');

            $oldArr = [];
            $all_goods = [];
            foreach ($orderGoods as $val) {
                if (empty($old_goods[$val['id']])) {
                    model('order_goods')->where("id=" . $val['id'])->delete();//删除商品
                } else {
                    //修改商品数量
                    if ($old_goods[$val['id']] != $val['goods_num']) {
                        $val['goods_num'] = $old_goods[$val['id']];
                        model('order_goods')->save(['goods_num' => $val['goods_num']], ['id' => $val['id']]);
                    }
                    $oldArr[] = $val;//剩余商品
                }
                $all_goods[$val['id']] = $val;//所有商品信息
            }
            $address['province_id'] = $order['province_id'];
            $address['city_id'] = $order['city_id'];
            $address['district_id'] = $order['district_id'];
            $car_price = $this->getOrderPrice($oldArr, $order['integral_money'], $order['coupon_id'], 0, 0);
            /*$result = calculate_price($order['user_id'],$oldArr,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
            if($result['status'] < 0)
            {
                $this->error($result['msg']);
            }*/
            //修改订单费用
            $res['total_price'] = $car_price['goods_price']; // 商品总价
//            $res['express_fee'] = $car_price['express_fee'];//物流费
            $res['total_fee'] = $car_price['order_amount'] + $order['express_fee']; // 应付金额
            model('order')->save($res, ['id' => $order_id]);
            //################################原单处理结束

            //################################新单处理
            $split_goods = [];
            for ($i = 1; $i < 20; $i++) {
                $temp = $this->request->param($i . '_old_goods/a');
                if (!empty($temp)) {
                    $split_goods[] = $temp;
                }
            }
            $brr = [];
            foreach ($split_goods as $key => $vrr) {
                foreach ($vrr as $k => $v) {
                    $all_goods[$k]['goods_num'] = $v;
                    $brr[$key][] = $all_goods[$k];
                }
            }

            foreach ($brr as $goods) {
                /*$result = calculate_price($order['user_id'],$goods,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
                if($result['status'] < 0)
                {
                    $this->error($result['msg']);
                }*/
                $car_price = $this->getOrderPrice($goods, 0, 0, 0, 0);
                $new_order = $order;
                $new_order['order_no'] = $this->get_order_sn(OrderConstant::ORDER_NO_SON_PREFIX);
                $new_order['parent_no'] = $order['order_no'];
                //修改订单费用
                $new_order['total_price'] = $car_price['goods_price']; // 商品总价
                $new_order['express_fee'] = 0;//物流费
                $new_order['total_fee'] = $car_price['order_amount']; // 应付金额
                /*  $new_order['goods_price']    = $result['result']['goods_price']; // 商品总价
                  $new_order['order_amount']   = $result['result']['order_amount']; // 应付金额
                  $new_order['total_amount']   = $result['result']['total_amount']; // 订单总价*/
                $new_order['order_time'] = date('Y-m-d H:i:s');
                unset($new_order['id']);
                $new_order_id = model('order')->insert($new_order);//插入订单表
                $new_order_id = model('order')->getLastInsID();
                foreach ($goods as $vv) {
                    $vv['order_id'] = $new_order_id;
                    $vv['order_no'] = $new_order['order_no'];
                    unset($vv['id']);
                    $nid = model('order_goods')->save($vv);//插入订单商品表
                }
            }
            //################################新单处理结束
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        }

        foreach ($orderGoods as $val) {
            $brr[$val['id']] = array('goods_num' => $val['goods_num'], 'goods_name' => $val['goods_name'] . $val['sku_info']);
        }
        $this->assign('order', $order);
        $this->assign('goods_num_arr', json_encode($brr));
        $this->assign('orderGoods', $orderGoods);
        return $this->fetch();
    }


    /**
     * 订单采购
     */
    public function purchase()
    {
        $store = model('store')->field('id,store_name')->select();

        $map = [];
        $name = request()->param("name");
        $order_no = request()->param("order_no");
        $starttime = request()->param("starttime");
        $endtime = request()->param("endtime");
        $this->assign("name", $name);
        $this->assign("order_no", $order_no);
        $this->assign("starttime", $starttime);
        $this->assign("endtime", $endtime);
        $this->assign("telephone",$name);
        $store_id = request()->param('store_id', 0);
        /*  if (!$store_id && $store) {
              $store_id = $store[0]['id'];
          }*/
        if ($store_id) {
            $map['a.store_id'] = $store_id;
        }
        $this->assign("store_id", $store_id);

        $partner_id = request()->param("partner_id");
        $this->assign("partner_id",$partner_id);

        if ($partner_id != '') {
            $map['a.partner_id'] = $partner_id;
            $where['partner_id'] = $partner_id;
        }

        $partner = model('partner')->where([])->select();
        $this->assign('partner', $partner);

        //采购状态
        $is_purchase = request()->param('is_purchase', 0);
        $map['s.is_purchase'] = $is_purchase;
        $this->assign("is_purchase",$is_purchase);

        if ($starttime && $endtime) {
            $map['a.order_time'] = ['between', [$starttime, $endtime]];
        } elseif ($starttime) {
            $map['a.order_time'] = ['egt', $starttime];
        } elseif ($endtime) {
            $map['a.order_time'] = ['elt', $endtime];
        }
        if ($is_purchase == 0 ) {
            //$map['a.order_status'] = OrderConstant::ORDER_STATUS_WAIT_SEND;
            $map['a.sure_status'] = 1;
        }
        if ($name) {
            $map['b.nickname|a.telephone|a.consignee|b.realname|b.telephone'] = ["like", "%$name%"];
            $this->assign("name", $name);
        }
        if ($order_no) {
            $map['a.parent_no|a.order_no|a.only_order_no'] = ["like", "%$order_no%"];
            $this->assign("order_no", $order_no);
        }
        $list_row = request()->param('list_row/d', 10);
        $list_row <= 0 ? $list_row = 10 : false;
        $this->assign("list_row", $list_row);

        $is_export = request()->param('is_export');

        if ($is_export == 1) {
            $order_list = model('store_order_goods')->alias('s')
                ->join('store_order a', 's.order_id = a.id', 'left')
                ->join('user b', 'a.user_id = b.id', 'left')
                ->join('store d', 'a.store_id = d.id', 'left')
                ->field('a.*,s.id order_goods_id, d.store_name,s.goods_code,s.goods_name,s.goods_code,s.sku_info,s.goods_remark,s.goods_num,
             s.b_price,s.cost_price,s.goods_price,s.goods_pay_price,b.nickname name,b.telephone tel')
                ->order('a.order_time desc')
                ->where($map)
                ->select();
            foreach ($order_list as $k => $v) {
                $province = model('region')->where(['id' => $v['province_id']])->value('name');
                $city = model('region')->where(['id' => $v['province_id']])->value('name');
                $district = model('region')->where(['id' => $v['district_id']])->value('name');
                $address = $province.$city.$district.$v['place'];
                $order_list[$k]['place'] = $address;
                $order_list[$k]['pay_wait_price'] = $v['total_fee'] - $v['pay_price'];
            }

            $this->purchaseExport($order_list);
        } else {
            $order_list = model('store_order_goods')->alias('s')
                ->join('store_order a', 's.order_id = a.id', 'left')
                ->join('user b', 'a.user_id = b.id', 'left')
                ->join('store d', 'a.store_id = d.id', 'left')
                ->field('a.*,s.id order_goods_id, d.store_name,s.goods_code,s.goods_name,s.goods_code,s.sku_info,s.goods_remark,s.goods_num,
             s.b_price,s.cost_price,s.goods_price,s.goods_pay_price,b.nickname name,b.telephone tel')
                ->order('a.order_time desc')
                ->where($map)
                ->paginate($list_row,false,['query'=>request()->param()]);
            foreach ($order_list as $k => $v) {
                $province = model('region')->where(['id' => $v['province_id']])->value('name');
                $city = model('region')->where(['id' => $v['province_id']])->value('name');
                $district = model('region')->where(['id' => $v['district_id']])->value('name');
                $address = $province.$city.$district.$v['place'];
                $order_list[$k]['place'] = $address;
                $order_list[$k]['pay_wait_price'] = $v['total_fee'] - $v['pay_price'];
            }
        }



        $this->assign("order_list", $order_list);
        $map['s.is_purchase'] = 0;
        $map['a.sure_status'] = 1;
        $count = model('store_order_goods')
            ->alias('s')
            ->join('store_order a', 's.order_id = a.id', 'left')
            ->join('user b', 'a.user_id = b.id', 'left')
            ->join('store d', 'a.store_id = d.id', 'left')
            ->where($map)->count();
        unset($map['a.order_status']);
        unset($map['a.sure_status']);
        $map['s.is_purchase'] = 1;
        $count1 = model('store_order_goods')
            ->alias('s')
            ->join('store_order a', 's.order_id = a.id', 'left')
            ->join('user b', 'a.user_id = b.id', 'left')
            ->join('store d', 'a.store_id = d.id', 'left')
            ->where($map)->count();
        $this->assign("count", $count);
        $this->assign("count1", $count1);
        $this->assign("store", $store);
        return $this->fetch();
    }

    /**
     * 订单采购
     */
    public function purchaseAll()
    {
        $store = model('store')->field('id,store_name')->select();

        $map = [];
        $where = [];
        $name = request()->param("name");
        $order_no = request()->param("order_no");
        $starttime = request()->param("starttime");
        $endtime = request()->param("endtime");
        $this->assign("name", $name);
        $this->assign("order_no", $order_no);
        $this->assign("starttime", $starttime);
        $this->assign("endtime", $endtime);
        $this->assign("telephone",$name);
        $store_id = request()->param('store_id', 0);
        /*  if (!$store_id && $store) {
              $store_id = $store[0]['id'];
          }*/
        if ($store_id) {
            $map['a.store_id'] = $store_id;
            $where['store_id'] = $store_id;
        }
        $this->assign("store_id", $store_id);

        $partner_id = request()->param("partner_id");
        $this->assign("partner_id",$partner_id);

        if ($partner_id != '') {
            $map['a.partner_id'] = $partner_id;
            $where['partner_id'] = $partner_id;
        }

        $partner = model('partner')->where([])->select();
        $this->assign('partner', $partner);
        //采购状态
        $is_purchase = request()->param('is_purchase', 0);
       // $map['s.is_purchase'] = $is_purchase;
        $this->assign("is_purchase",$is_purchase);

        if ($starttime && $endtime) {
           // $map['a.order_time'] = ['between', [$starttime, $endtime]];
        } elseif ($starttime) {
            //$map['a.order_time'] = ['egt', $starttime];
        } elseif ($endtime) {
           // $map['a.order_time'] = ['elt', $endtime];
        }
        if ($is_purchase == 0 ) {
            //$map['a.order_status'] = OrderConstant::ORDER_STATUS_WAIT_SEND;
            //$map['a.sure_status'] = 1;
        }
        if ($name) {
            //$map['b.nickname|a.telephone|a.consignee|b.realname|b.telephone'] = ["like", "%$name%"];
            $this->assign("name", $name);
        }
        if ($order_no) {
            $where['purchase_no'] = ["like", "%$order_no%"];
            $this->assign("order_no", $order_no);
        }



        $is_export = request()->param('is_export');

        if ($is_export == 1) {
            $order_list = model('order_purchase')->where($where)->order('id desc')->select();

            foreach ($order_list as $k => $v) {
                $order_list[$k]['store_name'] = model('store')->where(['id' => $v['store_id']])->value('store_name');
                $order_list[$k]['order_goods'] = model('store_order_goods')
                    ->where(['id' => ['in', $v['order_goods_id']]])
                    ->select();
            }
            $this->purchaseAllExport($order_list);
        } else {
            $order_list = model('order_purchase')->where($where)->order('id desc')->paginate(5,false,['query'=>request()->param()]);

            foreach ($order_list as $k => $v) {
                $order_list[$k]['store_name'] = model('store')->where(['id' => $v['store_id']])->value('store_name');
                $order_list[$k]['order_goods'] = model('store_order_goods')
                    ->where(['id' => ['in', $v['order_goods_id']]])
                    ->select();
            }
        }

        $this->assign("order_list", $order_list);
        $map['s.is_purchase'] = 0;
        $map['a.sure_status'] = 1;
        $count = model('store_order_goods')
            ->alias('s')
            ->join('store_order a', 's.order_id = a.id', 'left')
            ->join('user b', 'a.user_id = b.id', 'left')
            ->join('store d', 'a.store_id = d.id', 'left')
            ->where($map)->count();
        unset($map['a.order_status']);
        unset($map['a.sure_status']);
        $map['s.is_purchase'] = 1;
        $count1 = model('store_order_goods')
            ->alias('s')
            ->join('store_order a', 's.order_id = a.id', 'left')
            ->join('user b', 'a.user_id = b.id', 'left')
            ->join('store d', 'a.store_id = d.id', 'left')
            ->where($map)->count();
        $this->assign("count", $count);
        $this->assign("count1", $count1);
        $this->assign("store", $store);
        return $this->fetch();
    }
    /**
     * 采购导出
     * @param $order_list
     */
    public function purchaseExport($order_list)
    {
        $filename = '待采购订单';

        $this->excelPurchaseExport($order_list, $filename);
    }
    /**
     * 采购导出
     * @param $order_list
     */
    public function purchaseAllExport($order_list)
    {
        $filename = '已采购订单';

        $this->excelPurchaseAllExport($order_list, $filename);
    }

    /**
     * 采购操作
     */
    public function setPurchase()
    {
        if (request()->isPost()) {
            $order_goods_id = request()->param('order_goods_id');
            if (!$order_goods_id) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
            }
            $order_id_arr = explode('-', $order_goods_id);
            $info = model('store_order_goods')->where(['id' => ['in', $order_id_arr]])->field('store_id, id')->select();
            $order_goods_info = [];
            foreach ($info as $k => $v) {
                $order_goods_info[$v['store_id']][] = $v['id'];
            }
            Db::startTrans();
            foreach ($order_goods_info as $k => $v) {
                $res = model('store_order_goods')->update(['is_purchase' => 1], ['id' => ['in', $v]]);
                if (!$res) {
                    Db::rollback();
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
                }
                $store_order_goods = model('store_order_goods')->where(['id' => ['in', $v]])->column('order_id');
                $store_order_goods = array_unique($store_order_goods);
                foreach ($store_order_goods as $kk => $vv) {
                    $count_all = model('store_order_goods')->where(['order_id' => $vv])->count();
                    $is_purchase = model('store_order_goods')->where(['order_id' => $vv, 'is_purchase' => 1])->count();
                    if ($count_all == $is_purchase) {
                        $res = model('store_order')->update(['is_purchase' => 1], ['id' => $vv]);
                        if (!$res) {
                            Db::rollback();
                            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
                        }
                    }
                }
                $goods_order = model('store_order_goods')->where(['id' => ['in', $v]])->field('goods_pay_price,goods_num')->select();
                $purchase_price = 0;
                foreach ($goods_order as $kk => $vv) {
                    $purchase_price = bcadd($purchase_price, bcmul($vv['goods_pay_price'], $vv['goods_num']));
                }
                $purchase_no = $this->get_order_sn(OrderConstant::ORDER_NO_PR_PREFIX);
                $data_info = [
                    'store_id' => $k,
                    'purchase_no' => $purchase_no,
                    'create_time' => time(),
                    'purchase_price' => $purchase_price,
                    'order_goods_id' => implode(',', $v),
                ];
                $res = model('order_purchase')->insert($data_info);
                if (!$res) {
                    Db::rollback();
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
                }
            }
            /* if ($count > 1) {
                 ajaxReturn(['status' => 0, 'msg' => '请选择同一个供应商的商品采购']);
             }
             model('store_order_goods')->save(['is_purchase' => 1], ['id' => ['in', $order_id_arr]]);
             $store_order_goods = model('store_order_goods')->where(['id' => ['in', $order_id_arr]])->column('order_id');
             $store_order_goods = array_unique($store_order_goods);
             foreach ($store_order_goods as $k => $v) {
                 $count_all = model('store_order_goods')->where(['order_id' => $v])->count();
                 $is_purchase = model('store_order_goods')->where(['order_id' => $v, 'is_purchase' => 1])->count();
                 if ($count_all == $is_purchase) {
                     model('store_order_goods')->update(['is_purchase' => 1], ['id' => $v]);
                 }
             }*/

            Db::commit();
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        }
    }
}