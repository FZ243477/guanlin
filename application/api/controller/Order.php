<?php

namespace app\api\controller;

use app\common\constant\OrderConstant;
use app\common\constant\SystemConstant;
use app\common\constant\CartConstant;
use app\common\helper\CartHelper;
use app\common\helper\GoodsHelper;
use app\common\helper\OrderHelper;
use app\common\helper\VerificationHelper;
use think\Db;

class Order extends Base
{
    use CartHelper;
    use OrderHelper;
    use GoodsHelper;
    use VerificationHelper;

    public function __construct()
    {
        parent::__construct();
//        if (!$this->user_id) {
//            ajaxReturn(['status' => -1, 'msg' => '请登录']);
//        }
    }

    //获得信息 生成订单
    public function create_order(){
        $map['uid'] = $this->user_id;
        $data['logi_id'] = request()->post('logi_id', 0);
        $data['urgent_type'] = request()->post('urgent_type', 0);
        $data['fast_order'] = request()->post('fast_order', 0);
        $data['faddress_id'] = request()->post('faddress_id', 0);
        $data['take_address_id'] = request()->post('take_address_id', 0);
        $data['goods_cate_id'] = request()->post('goods_cate_id', 0);
        $data['remarks'] = request()->post('remarks', 0);
        if($data['logi_id']==''){
            $return_arr = ['status'=>0, 'msg'=>'请选择物流公司','data'=> []];
            exit(json_encode($return_arr));
        }
        if($data['fast_order']==''){
            $return_arr = ['status'=>0, 'msg'=>'请填写快递单号','data'=> []];
            exit(json_encode($return_arr));
        }
        if($data['faddress_id']==''){
            $return_arr = ['status'=>0, 'msg'=>'请填写发货人地址','data'=> []];
            exit(json_encode($return_arr));
        }
        if($data['take_address_id']==''){
            $return_arr = ['status'=>0, 'msg'=>'请填写收货人地址','data'=> []];
            exit(json_encode($return_arr));
        }
         if($data['goods_cate_id']==''){
             $return_arr = ['status'=>0, 'msg'=>'请填选择物品分类','data'=> []];
             exit(json_encode($return_arr));
         }
         $faddress=model('user_address')->where('id',$data['faddress_id'])->find();
         if(!$faddress){
             $return_arr = ['status'=>0, 'msg'=>'发货人地址不存在','data'=> []];
             exit(json_encode($return_arr));
         }
         $takeaddress=model('user_address')->where('id',$data['take_address_id'])->find();
        if(!$takeaddress){
            $return_arr = ['status'=>0, 'msg'=>'收货人地址不存在','data'=> []];
            exit(json_encode($return_arr));
        }
        $order_no = $this->get_order_sn();
        $save_content=[
            'order_id'=>$order_no,
            'uid'=>$map['uid'],
            'logi_id'=>$data['logi_id'],
            'delivery_id'=>$data['fast_order'],
            'fname'=>$faddress['real_name'],
            'fphone'=>$faddress['phone'],
            'fprovince'=>$faddress['province'],
            'fcity'=>$faddress['city'],
            'fdistrict'=>$faddress['district'],
            'faddress'=>$faddress['province'].$faddress['city'].$faddress['district'],
            'fdetailaddress'=>$faddress['detail'],
            'take_name'=>$takeaddress['real_name'],
            'take_phone'=>$takeaddress['phone'],
            'take_province'=>$takeaddress['province'],
            'take_city'=>$takeaddress['city'],
            'take_district'=>$takeaddress['district'],
            'take_address'=>$takeaddress['province'].$takeaddress['city'].$takeaddress['district'],
            'take_detailaddress'=>$takeaddress['detail'],
            'remarks'=>$data['remarks'],
            'urgent_type'=>$data['urgent_type'],
            'goods_cate_id'=>$data['goods_cate_id'],
            'has_take'=>0,
            'paid'=>0,
            'state'=>0,
            'create_time'=>time(),
        ];
        $save = model('order')->insertGetId($save_content);
        if($save){
            $return_arr = ['status'=>1, 'msg'=>'添加成功','data'=> []];
            exit(json_encode($return_arr));
        }else{
            $return_arr = ['status'=>0, 'msg'=>'添加失败','data'=> []];
            exit(json_encode($return_arr));
        }
    }

    /**
     * 未支付邮费订单数据
     */
    public function postage_list(){
        $map['uid'] = $this->user_id;
        $list_row = request()->post('list_row', 10); //每页数据
        $page = request()->post('page', 1); //当前页
        $order_data = [];
        $order_data['uid'] = $this->user_id;
            $order_data['state'] = 0;
           $order_data['paid'] = 0;
           $order_data['has_take']=1;

        $totalCount = model('order')->where($order_data)->count();
        $pageCount = ceil($totalCount / $list_row);
        $field = 'id,order_id,state,urgent_type,fname,fphone,fprovince,fcity,fdistrict,faddress,fdetailaddress,weight,take_name,price,take_phone,take_address,take_detailaddress,create_time';
        $first_row = ($page - 1) * $list_row;
        $order_list = model('order')
            ->where($order_data)
            ->order('create_time desc')
            ->limit($first_row, $list_row)
            ->field($field)
            ->select();
        foreach($order_list as $k=>$v){
            $unit_price=Db::name('unit_price')->where('id',$v['urgent_type'])->field('price')->find();
            $order_list[$k]['unit_price']=$unit_price;
            $rest_time=Db::name('customer')->where('id',2)->find();
            $now_day= floor(time()/(3600*24));
            $create_day = intval(strtotime($v['create_time'])/(3600*24));
            $delivery_time=$rest_time['delivery_days']-($now_day-$create_day);
            $enddelivery_time=$rest_time['enddelivery_days']-($now_day-$create_day);
            if($delivery_time<=0){$delivery_time =$rest_time['delivery_days'];}
            if($enddelivery_time<=0){$delivery_time =$rest_time['enddelivery_days'];}
            if($v['state']==1){
                $order_list[$k]['delivery_to_time']=$delivery_time;
            }
            if($v['state']==2){
                $order_list[$k]['end_delivery_time']=$enddelivery_time;
            }
        }
        $data = [
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
            'list' => $order_list ? $order_list : [],
        ];

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 已完成订单数据
     */
    public function history_list(){
        $map['uid'] = $this->user_id;
        $list_row = request()->post('list_row', 10); //每页数据
        $page = request()->post('page', 1); //当前页
        $order_data = [];
        $order_data['uid'] = $this->user_id;

        $order_data['state'] = 4;
        $order_data['paid'] = 1;
        $order_data['has_take']=1;

        $totalCount = model('order')->where($order_data)->count();
        $pageCount = ceil($totalCount / $list_row);
        $field = 'id,order_id,state,urgent_type,fname,fphone,fprovince,fcity,fdistrict,faddress,fdetailaddress,price,create_time,take_name,weight,take_phone,take_address,take_detailaddress';
        $first_row = ($page - 1) * $list_row;
        $order_list = model('order')
            ->where($order_data)
            ->order('create_time desc')
            ->limit($first_row, $list_row)
            ->field($field)
            ->select();
        foreach($order_list as $k=>$v){
            $unit_price=Db::name('unit_price')->where('id',$v['urgent_type'])->field('price')->find();
            $order_list[$k]['unit_price']=$unit_price;
            $rest_time=Db::name('customer')->where('id',2)->find();
            $now_day= floor(time()/(3600*24));
            $create_day = intval(strtotime($v['create_time'])/(3600*24));
            $delivery_time=$rest_time['delivery_days']-($now_day-$create_day);
            $enddelivery_time=$rest_time['enddelivery_days']-($now_day-$create_day);
            if($delivery_time<=0){$delivery_time =$rest_time['delivery_days'];}
            if($enddelivery_time<=0){$delivery_time =$rest_time['enddelivery_days'];}
            if($v['state']==1){
                $order_list[$k]['delivery_to_time']=$delivery_time;
            }
            if($v['state']==2){
                $order_list[$k]['end_delivery_time']=$enddelivery_time;
            }
        }
        $data = [
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
            'list' => $order_list ? $order_list : [],
        ];

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 请求订单列表数据
     */
    public function state_list(){
        $map['uid'] = $this->user_id;
        $state = request()->post('state');
        $list_row = request()->post('list_row', 10); //每页数据
        $page = request()->post('page', 1); //当前页
        if (!in_array($state, [0, 1, 2, 3, 4])) {
            $return_arr = ['status' => 0, 'msg' => '参数status错误']; //
            ajaxReturn($return_arr);
        }
        $order_data = [];
        $order_data['uid'] = $this->user_id;

        if (isset($state)) {
            $order_data['state'] = $state;
        }
        $totalCount = model('order')->where($order_data)->count();
        $pageCount = ceil($totalCount / $list_row);
        $field = 'id,create_time,order_id,state,has_take,urgent_type,fname,fphone,fprovince,fcity,fdistrict,faddress,fdetailaddress,price,take_name,weight,take_phone,take_address,take_detailaddress';
        $first_row = ($page - 1) * $list_row;
        $order_list = model('order')
            ->where($order_data)
            ->order('create_time desc')
            ->limit($first_row, $list_row)
            ->field($field)
            ->select();
            foreach($order_list as $k=>$v){
                $unit_price=Db::name('unit_price')->where('id',$v['urgent_type'])->field('price')->find();
                $order_list[$k]['unit_price']=$unit_price;
                $rest_time=Db::name('customer')->where('id',2)->find();
                $now_day= floor(time()/(3600*24));
                $create_day = intval(strtotime($v['create_time'])/(3600*24));
                $delivery_time=$rest_time['delivery_days']-($now_day-$create_day);
                $enddelivery_time=$rest_time['enddelivery_days']-($now_day-$create_day);
                if($delivery_time<=0){$delivery_time =$rest_time['delivery_days'];}
                if($enddelivery_time<=0){$delivery_time =$rest_time['enddelivery_days'];}
                if($v['state']==1){
                    $order_list[$k]['delivery_to_time']=$delivery_time;
                }
                if($v['state']==2){
                    $order_list[$k]['end_delivery_time']=$enddelivery_time;
                }
            }
        $data = [
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
            'list' => $order_list ? $order_list : [],
        ];

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 确认收货
     */
    public function confirm_order(){
        $map['uid'] = $this->user_id;
        $out_trade_no    = request()->post('order_id');
        if(!$out_trade_no){
            ajaxReturn(['status' => 0, 'msg' => '支付单号为空', 'data' => []]);
        }
        $map = [];
        $map['order_id'] = $out_trade_no;
        $map['uid']      = $this->user_id;
        $res = model('order')->where($map)->find();
        if(!$res){
            ajaxReturn(['status' => 0, 'msg' => '此订单不存在了', 'data' => []]);
        }
        if($res['state']==1){ ajaxReturn(['status' => 0, 'msg' => '此订单未发货', 'data' => []]);}
            $save_content=[
                'state'=>4,
                'end_time'=>time()
            ];
            $before_json = model('order')->where('id',$res['id'])->find();
            $save = model('order')->where('id',$res['id'])->update($save_content);
            $after_json = $save;
            $content = "用户确认收货";
            if($save){
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $return_arr = ['status'=>1, 'msg'=>'操作成功','data'=> []];
                exit(json_encode($return_arr));
            }else{
                $return_arr = ['status'=>0, 'msg'=>'操作失败','data'=> []];
                exit(json_encode($return_arr));
            }
    }

    /**
     * 确认订单页面
     */
    public function cartOrder()
    {
        $order_type = request()->post('order_type', 0);
        $goods_id = request()->post('goods_id');
        $address_id = request()->post('address_id');
        $houses_case_id = request()->post('houses_case_id');
        if (!$goods_id) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $address = model('address')->where(['id' => $address_id])->field('id,consignee,province,city,district,address,telephone')->find();
        if (!$address) {
            $address = model('address')->where(['user_id' => $this->user_id])->field('id,consignee,province,city,district,address,telephone')->order('is_default desc, id desc')->find();
        }

        if ($order_type == 1) { //单品
            $goods_num = request()->post('goods_num');
            if (!$goods_num) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
            }
            $goods = model('goods')
                ->where(['id' => $goods_id])
                ->field('goods_name,goods_price,goods_unit,goods_size,goods_oprice,goods_logo,express_fee,install_fee')
                ->find();
            $data['goods_price'] = $goods['goods_price'] * $goods_num;
            $data['express_fee'] = $goods['express_fee'] * $goods_num;
            $data['install_fee'] = $goods['install_fee'] * $goods_num;
            $data['total_fee'] =  $data['goods_price'] + $data['express_fee'] + $data['install_fee'];
            $order_goods = [
                'goods_id' => $goods_id,
                'goods_name' => $goods['goods_name'],
                'goods_price' => $goods['goods_price'],
                'goods_size' => $goods['goods_size'],
                'goods_logo' => $goods['goods_logo'],
            ];
            $data = [
                'address' => $address, // 收货地址
                'detail' => $order_goods, // 收货地址
                'total_fee' => $data['total_fee'], // 收货地址
                'total_num' => $goods_num, // 收货地址
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];

            ajaxReturn($json_arr);
        } else { //整装
            $goods_arr = explode(',', $goods_id);

            $list = db('goods_cate')->where(['pid' => 0])->field('id,name')->select();

            $goods_price = 0;
            $express_fee = 0;
            $install_fee = 0;
            foreach ($list as $k => $v) {
                $list[$k]['cate'] = db('goods_cate')->where(['pid' => $v['id']])->field('id,name')->select();
                foreach ($list[$k]['cate'] as $k1 => $v1) {
                    $order_goods = model('houses_goods')
                        ->alias('hg')
                        ->join('goods g', 'hg.goods_id = g.id', 'left')
                        ->where(['hg.houses_case_id' => $houses_case_id, 'hg.cate_id' => $v1['id']])
                        ->field('g.id,goods_id,goods_name,goods_logo,goods_price,goods_oprice,
                             goods_size,goods_unit,express_fee,install_fee,hg.goods_num')
                        ->select();
                    foreach ($order_goods as $kk => $vv) {
                        if ($goods_id == 'all' || in_array($vv['goods_id'], $goods_arr)) {
                            $goods_price += $vv['goods_num'] * $vv['goods_price'];
                            $express_fee += $vv['goods_num'] * $vv['express_fee'];
                            $install_fee += $vv['goods_num'] * $vv['install_fee'];
                        }
                    }
                }
            }
            $data['express_fee'] = $express_fee;
            $data['install_fee'] = $install_fee;
            $data['goods_price'] = $goods_price;
            $data['total_fee'] = $goods_price + $express_fee + $install_fee;

            $field = 'hc.id,hc.name,hc.logo,deposit,total_price,finish_date,area,space,style';
            $housesCase = model('houses_case')
                ->alias('hc')
                ->join('houses_type ht', 'ht.id = hc.houses_type_id', 'left')
                ->join('houses h', 'h.id = ht.houses_id', 'left')
                ->where(['hc.id' => $houses_case_id])
                ->field($field)
                ->find();

            $data = [
                'address' => $address, // 收货地址
                'detail' => $housesCase, // 收货地址
                'total_fee' => $data['total_fee'], // 收货地址
                'total_num' => 1,
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];

            ajaxReturn($json_arr);
        }


    }

    /**
     * 添加订单
     */
    public function addOrder()
    {
        $order_type = request()->post('order_type', 0);
        $goods_id = request()->post('goods_id');
        $address_id = request()->post('address_id');
        $houses_case_id = request()->post('houses_case_id');
        if (!$address_id || !$goods_id) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $address = model('address')->where(['id' => $address_id])->field('consignee,province,city,district,address,telephone')->find();
        if (!$address) {
            ajaxReturn(['status' => 0, 'msg' => '缺少收货人信息']); // 返回结果状态
        }
        $remark = request()->post('remark');
        $order_no = $this->get_order_sn();

        $data = [
            'order_no' => $order_no, // 订单编号
            'user_id' => $this->user_id, // 用户id
            'address_id' => $address_id, // 收货地址ID
            'consignee' => $address['consignee'], // 收货人
            'province' => $address['province'],//'省份id',
            'city' => $address['city'],//'城市id',
            'district' => $address['district'],//'县',
            'place' => $address['address'],//'详细地址',
            'telephone' => $address['telephone'],//'手机',
            'remark' => $remark, //'给卖家留言',
            'order_time' => time(), // 下单时间
            'order_type' => $order_type,
            'pay_status' => OrderConstant::PAY_STATUS_NONE,
            'order_status' => OrderConstant::ORDER_STATUS_WAIT_PAY,
        ];
        Db::startTrans();
        if ($order_type == 1) { //单品
            $goods_num = request()->post('goods_num');
            if (!$goods_num) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
            }
            $goods = model('goods')
                ->where(['id' => $goods_id])
                ->field('goods_name,goods_price,goods_unit,goods_size,goods_oprice,goods_logo,express_fee,install_fee')
                ->find();
            $data['goods_price'] = $goods['goods_price'] * $goods_num;
            $data['express_fee'] = $goods['express_fee'] * $goods_num;
            $data['install_fee'] = $goods['install_fee'] * $goods_num;
            $data['total_fee'] =  $data['goods_price'] + $data['express_fee'] + $data['install_fee'];

            $order_id = model('order')->insertGetId($data);
            if (!$order_id) {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => '生成订单失败1']);
            }
            $order_goods = [
                'user_id' => $this->user_id,
                'goods_num' => $goods_num,
                'order_id' => $order_id,
                'goods_id' => $goods_id,
                'goods_name' => $goods['goods_name'],
                'goods_price' => $goods['goods_price'],
                'goods_oprice' => $goods['goods_oprice'],
                'goods_unit' => $goods['goods_unit'],
                'goods_size' => $goods['goods_size'],
                'goods_logo' => $goods['goods_logo'],

            ];
            $res = model('order_goods')->save($order_goods);
            if (!$res) {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => '生成订单失败2']);
            }
        } else { //整装
            $goods_arr = explode(',', $goods_id);

            $list = db('goods_cate')->where(['pid' => 0])->field('id,name')->select();
            $houses_goods = [];
            $goods_price = 0;
            $express_fee = 0;
            $install_fee = 0;
            foreach ($list as $k => $v) {
                $list[$k]['cate'] = db('goods_cate')->where(['pid' => $v['id']])->field('id,name')->select();
                foreach ($list[$k]['cate'] as $k1 => $v1) {
                    $order_goods = model('houses_goods')
                        ->alias('hg')
                        ->join('goods g', 'hg.goods_id = g.id', 'left')
                        ->where(['hg.houses_case_id' => $houses_case_id, 'hg.cate_id' => $v1['id']])
                        ->field('g.id,goods_id,goods_name,goods_logo,goods_price,goods_oprice,
                             goods_size,goods_unit,express_fee,install_fee,hg.goods_num')
                        ->select();
                    foreach ($order_goods as $kk => $vv) {
                        if ($goods_id == 'all' || in_array($vv['goods_id'], $goods_arr)) {
                            $houses_goods[] = $vv->toArray();
                            $goods_price += $vv['goods_num'] * $vv['goods_price'];
                            $express_fee += $vv['goods_num'] * $vv['express_fee'];
                            $install_fee += $vv['goods_num'] * $vv['install_fee'];
                        }
                    }
                }
            }
            $data['express_fee'] = $express_fee;
            $data['install_fee'] = $install_fee;
            $data['goods_price'] = $goods_price;
            $data['total_fee'] = $goods_price + $express_fee + $install_fee;
            $order_id = model('order')->insertGetId($data);
            if (!$order_id) {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => '生成订单失败1']);
            }
            $field = 'hc.id,hc.name,hc.logo,deposit,total_price,finish_date,area,space,style';
            $housesCase = model('houses_case')
                ->alias('hc')
                ->join('houses_type ht', 'ht.id = hc.houses_type_id', 'left')
                ->join('houses h', 'h.id = ht.houses_id', 'left')
                ->where(['hc.id' => $houses_case_id])
                ->field($field)
                ->find();
            $order_list = [
                'user_id' => $this->user_id,
                'order_id' => $order_id,
                'houses_case_id' => $houses_case_id,
                'houses_case_name' => $housesCase['name'],
                'houses_case_logo' => $housesCase['logo'],
                'area' => $housesCase['area'],
                'space' => $housesCase['space'],
                'style' => $housesCase['style'],
                'finish_date' => $housesCase['finish_date'],
                'deposit' => $housesCase['deposit'],
                'total_price' => $housesCase['total_price'],
            ];
            $order_list = model('order_list')->insert($order_list);
            if (!$order_list) {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => '生成订单失败2']);
            }

            foreach ($houses_goods as $k => $v) {
                $order_goods = [
                    'user_id' => $this->user_id,
                    'order_id' => $order_id,
                    'goods_id' => $v['goods_id'],
                    'goods_name' => $v['goods_name'],
                    'goods_price' => $v['goods_price'],
                    'goods_oprice' => $v['goods_oprice'],
                    'goods_unit' => $v['goods_unit'],
                    'goods_size' => $v['goods_size'],
                    'goods_logo' => $v['goods_logo'],
                ];
                $res = model('order_goods')->insert($order_goods);
                if (!$res) {
                    Db::rollback();
                    ajaxReturn(['status' => 0, 'msg' => '生成订单失败3']);
                }
            }
        }

        Db::commit();
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['order_no' => $order_no]]);
    }


    /**
     * 上传凭证
     */
    public function certificate()
    {
        $data['order_no'] = request()->post('order_no');
        if (!$data['order_no']) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }

        $order = model('order')
            ->where(['order_no' => $data['order_no']])
            ->field('order_status')
            ->find();

        //model('order')->save(['is_certificate' => OrderConstant::ORDER_CERTIFICATE_NONE], ['order_no' => $order['order_no']]);

        if ($order['order_status'] != OrderConstant::ORDER_STATUS_WAIT_PAY
            && $order['order_status'] != OrderConstant::ORDER_STATUS_FINAL_ORDER) {
            ajaxReturn(['status' => 0, 'msg' => '该订单已经不能上传凭证了']);
        }


        $data['certificate'] = request()->post('certificate');
        if (!$data['certificate']) {
            ajaxReturn(['status' => 0, 'msg' => '请上传凭证']);
        }
        $data['re_username'] = getSetting('system.re_username');
        $data['re_account'] = getSetting('system.re_account');
        $data['re_bank'] = getSetting('system.re_bank');
        $data['user_id'] = $this->user_id;
        $data['create_time'] = time();
        model('order_certificate')->save($data);
        //更改订单状态为2
        model('order')->save(['order_status' => 3, 'pay_status' => 1], ['order_no' => $data['order_no']]);
        $return_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]; //
        ajaxReturn($return_arr);
    }


    /**
     * 订单列表
     */
    public function orderList()
    {
        $status = request()->post('status', 0);
        $list_row = request()->post('list_row', 10); //每页数据
        $page = request()->post('page', 1); //当前页

        if (!in_array($status, [0, 1, 2, 3, 4, 5, 6, 7, 8])) {
            $return_arr = ['status' => 0, 'msg' => '参数status错误']; //
            ajaxReturn($return_arr);
        }

        $order_data = [];
        $order_data['user_id'] = $this->user_id;

        if ($status) {
            $order_data['order_status'] = $status;
        }

        $totalCount = model('order')->where($order_data)->count();

        $pageCount = ceil($totalCount / $list_row);
        $field = 'id,order_no,order_status,order_type,total_fee';
        $first_row = ($page - 1) * $list_row;
        $order_list = model('order')
            ->where($order_data)
            ->order('order_time desc')
            ->limit($first_row, $list_row)
            ->field($field)
            ->select();
        foreach($order_list as $k=>$v){
            $unit_price=Db::name('unit_price')->where('id',$v['urgent_type'])->field('price')->find();
            $order_list[$k]['unit_price']=$unit_price;
        }
        if ($order_list) {
            foreach ($order_list as $k => $v) {
                $order_list[$k]['order_status_desc'] = OrderConstant::order_status_array_value($v['order_status']);
                if ($v['order_type'] == 1) {
                    $detail = model('order_goods')
                        ->field('goods_id,goods_name,goods_unit,goods_size,goods_logo,goods_num')
                        ->where(['order_id' => $v['id']])
                        ->find();
                } else {
                    $detail = model('order_list')
                        ->field('houses_case_id,houses_case_name,houses_case_logo,area,space,style,finish_date')
                        ->where(['order_id' => $v['id']])
                        ->find();
                }
                $order_list[$k]['detail'] =$detail;
            }
        }

        $data = [
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
            'list' => $order_list ? $order_list : [],
        ];

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 删除订单
     */
    public function delOrder()
    {
        $order_no = request()->post('order_no');
        if (empty($order_no)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]; //
            ajaxReturn($return_arr);
        }
        $res = model('order')->where(['order_no' => $order_no])->setField('is_del', 1);
        if ($res) {
            $return_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]; //
        } else {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]; //
        }
        ajaxReturn($return_arr);
    }

    /**
     * @version 取消订单
     */
    public function cancelOrder()
    {
        $order_no = request()->post('order_no');
        $cancel_cause = request()->post('cancel_cause');
        if (empty($order_no)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]; //
            ajaxReturn($return_arr);
        }
        $order_data = [];
        $order_data['order_no'] = $order_no;
        $order_info = model('order')->where($order_data)->order('id desc')->find();
        if (!$order_info) {
            $return_arr = ['status' => 0, 'msg' => '订单已删除']; //
            ajaxReturn($return_arr);
        }

        if ($order_info['order_status'] == 0) {
            $return_arr = ['status' => 0, 'msg' => '订单已取消']; //
            ajaxReturn($return_arr);
        }
        if ($order_info['order_status'] != 1) {
            $return_arr = ['status' => 0, 'msg' => '已付订单不可以取消']; //
            ajaxReturn($return_arr);
        }
        $res = model('order')->save([
            'order_status' => OrderConstant::ORDER_STATUS_CANCEL,
            'cancel_cause' => $cancel_cause,
            'cancel_time' => time()
        ], $order_data);

        if (!$res) {
            $return_arr = ['status' => 0, 'msg' => '修改订单状态失败'];
            ajaxReturn($return_arr);
        }
        $return_arr = ['status' => 1, 'msg' => '取消成功'];
        ajaxReturn($return_arr);
    }

    /**
     * 修改地址
     */
    public function editOrderAddress()
    {
        $order_no = request()->post('order_no');
        $address_id = request()->post('address_id');
        if (!$order_no || !$address_id) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]; //
            ajaxReturn($return_arr);
        }
        $order_data = [];
        $order_data['order_no'] = $order_no;
        $order_info = model('order')->where($order_data)->find();

        if (!$order_info || $order_info['order_status'] == OrderConstant::ORDER_STATUS_CANCEL) {
            $return_arr = ['status' => 0, 'msg' => '订单已取消或删除']; //
            ajaxReturn($return_arr);
        }

        if ($order_info['order_status'] == OrderConstant::ORDER_STATUS_WAIT_RECEIVE) {
            $return_arr = ['status' => 0, 'msg' => '订单已发货']; //
            ajaxReturn($return_arr);
        }
        $address = model('address')->where(['id' => $address_id])->field('consignee,province,city,district,address,telephone')->find();
        if (!$address) {
            ajaxReturn(['status' => 0, 'msg' => '缺少收货人信息']); // 返回结果状态
        }
        $data = [
            'address_id' => $address_id, // 收货人
            'consignee' => $address['consignee'], // 收货人
            'province' => $address['province'],//'省份id',
            'city' => $address['city'],//'城市id',
            'district' => $address['district'],//'县',
            'place' => $address['address'],//'详细地址',
            'telephone' => $address['telephone'],//'手机',
        ];
        $res = model('order')->save($data, ['order_no' => $order_no]);
        if ($res) {
            ajaxReturn(['status' => 1, 'msg' => '修改地址成功']); // 返回结果状态
        } else {
            ajaxReturn(['status' => 0, 'msg' => '未修改地址']); // 返回结果状态
        }
    }

    /**
     * @version 确认安装
     */
    public function confirmOrder()
    {
        $order_no = request()->post('order_no');

        if (empty($order_no)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]; //
            ajaxReturn($return_arr);
        }

        $order_data = [];
        $order_data['order_no'] = $order_no;
        $order_info = model('order')->where($order_data)->find();

        if (!$order_info) {
            $return_arr = ['status' => 0, 'msg' => '订单已删除']; //
            ajaxReturn($return_arr);
        }

        if ($order_info['order_status'] == OrderConstant::ORDER_STATUS_FINISH_ORDER || $order_info['is_confirm'] == 1) {
            $return_arr = ['status' => 0, 'msg' => '订单已经确认安装']; //
            ajaxReturn($return_arr);
        }

        if ($order_info['order_status'] != OrderConstant::ORDER_STATUS_WAIT_RECEIVE) {
            $return_arr = ['status' => 0, 'msg' => '订单不是待安装状态']; //
            ajaxReturn($return_arr);
        }

        //确认安装
        $map = [];
        $map['order_status'] = OrderConstant::ORDER_STATUS_FINISH_ORDER;
        $map['is_confirm'] = 1;
        $map['confirm_time'] = time();
        $res = model('order')->isUpdate(true)->save($map, $order_data);
        if (!$res) {
            $return_arr = ['status' => 0, 'msg' => '确认安装失败']; //
            ajaxReturn($return_arr);
        } else {
            $return_arr = ['status' => 1, 'msg' => '确认安装成功']; //
            ajaxReturn($return_arr);
        }
    }


    /**
     * @version 查看物流
     */
    public function orderExpress()
    {

        $order_no = request()->post('order_no');
        if (empty($order_no)) {
            $return_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]; //
            ajaxReturn($return_arr);
        }

        $order_data = [];
        $order_data['user_id'] = $this->user_id;
        $order_data['order_no'] = $order_no;

        $order_info = model('order')->where($order_data)->order('id desc')->find();
        if (!$order_info) {
            $return_arr = ['status' => 0, 'msg' => '订单已删除']; //
            ajaxReturn($return_arr);
        }

        if ($order_info['is_shipping'] == 0) {
            $return_arr = ['status' => 0, 'msg' => '订单未发货']; //
            ajaxReturn($return_arr);
        }

        if (empty($order_info['express_name']) || empty($order_info['express_no'])) {
            $return_arr = ['status' => 0, 'msg' => '物流信息不完整']; //
            ajaxReturn($return_arr);
        }

        //物流信息
//        $express_name = model('express')->where(['express_ma' => $order_info['express_name']])->find();
        $info = [];
//        $info['express_name'] = $express_name['express_company'];
        $info['express_no'] = $order_info['express_no'];
//        $info['express_tel'] = $express_name['express_tel'];
//        $info['express_logo'] = $express_name['express_logo'];
        //第三方物流查询api
        $res = $this->get_express_info($order_info['express_name'], $order_info['express_no']);  //第三方物流查询api
        $express = json_decode($res, true);

        if ($express['Success']) {
            $exp = $express['Traces'];
            $exp = list_sort_by($exp, 'AcceptTime', 'desc');
        } else {
            $exp = [['AcceptStation' => '物流信息查询失败', 'AcceptTime' => date('Y-m-d H:i:s')]];
        }

        $info['express_data'] = $exp;

        $return_arr = ['status' => 1, 'msg' => '获取成功', 'data' => $info]; //
        ajaxReturn($return_arr);
    }
}