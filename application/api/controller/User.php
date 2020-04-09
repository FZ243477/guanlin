<?php
namespace app\api\controller;
use app\common\constant\SystemConstant;
use app\common\helper\GoodsHelper;
use app\common\helper\VerificationHelper;
use app\common\helper\DatetimeHelper;
use Think\Db;

class User extends Base
{
    use VerificationHelper;
    use DatetimeHelper;
    use GoodsHelper;

    public function __construct()
    {
        parent::__construct();
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录', 'data' => []]);
        }
    }

    //个人信息展示
    public function userInfo(){
        if (request()->isPost()) {
            $user = model('user')->where('id', $this->user_id)->field('nickname,head_img,sex,telephone,email,realname,distribut_total,distribut_money')->find();
            $user_list['nickname'] = $user['nickname'];
//            $user_list['level_name'] = $user['level_name'];
            $user_list['head_img'] = $user['head_img'];
            $user_list['telephone'] = $user['telephone'];
            $user_list['email'] = $user['email'];
            $user_list['sex'] = $user['sex'];
            $user_list['realname'] = $user['realname'];
            $user_list['distribut_total'] = $user['distribut_total'];
            $user_list['distribut_money'] = $user['distribut_money'];
//            $user_list['is_sign'] = $is_sign;
            $countList['collect_num'] = model('collection')->where(['user_id' => $this->user_id, 'status' => 1])->count();
            $countList['collect_num'] = $countList['collect_num']?$countList['collect_num']:0;
            $countList['coupon_num'] = model('coupon_data')->where(['user_id' => $this->user_id, 'status' => 0, 'starttime' => ['gt', time()], 'endtime' => ['lt', time()]])->count();
            $countList['coupon_num'] = $countList['coupon_num']?$countList['coupon_num']:0;
            $countList['order_num'] = model('order')->where(['user_id' => $this->user_id])->count();
            $countList['order_num'] = $countList['order_num']?$countList['order_num']:0;
            $countList['order_wait_pay_num'] = model('order')->where(['user_id' => $this->user_id, 'order_status' => 1])->count();
            $countList['order_wait_pay_num'] = $countList['order_wait_pay_num']?$countList['order_wait_pay_num']:0;
            $countList['order_wait_send_num'] = model('order')->where(['user_id' => $this->user_id, 'order_status' => 2])->count();
            $countList['order_wait_send_num'] = $countList['order_wait_send_num']?$countList['order_wait_send_num']:0;
            $countList['order_wait_sure_num'] = model('order')->where(['user_id' => $this->user_id, 'order_status' => 3])->count();
            $countList['order_wait_sure_num'] = $countList['order_wait_sure_num']?$countList['order_wait_sure_num']:0;
            $countList['order_wait_comment_num'] = model('order')->where(['user_id' => $this->user_id, 'order_status' => 4])->count();
            $countList['order_wait_comment_num'] = $countList['order_wait_comment_num']?$countList['order_wait_comment_num']:0;
            $countList['order_complete_num'] = model('order')->where(['user_id' => $this->user_id, 'order_status' => 5])->count();
            $countList['order_complete_num'] = $countList['order_complete_num']?$countList['order_complete_num']:0;
            $countList['order_refund_num'] = model('order')->where(['user_id' => $this->user_id, 'order_status' => ['in', [11,12]]])->count();
            $countList['order_refund_num'] = $countList['order_refund_num']?$countList['order_refund_num']:0;
            $data = [
                'user' => $user_list,
                'countList' => $countList,
            ];
            $data = removeNull($data);
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            exit(json_encode($json_arr));
        }
    }

    /*修改个人信息*/
    public function setInfo(){
        if (request()->isPost()) {
            $map = [];
            $user_id = $this->user_id;
            $map['nickname'] = input('post.realname');
            $map['nickname'] = input('post.nickname');
            if(input('post.realname')) {
                $map['nickname'] = input('post.realname');
            }
            if(input('post.nickname')) {
                $map['nickname'] = input('post.nickname');
            }
            if(input('post.telephone')) {
                $map['telephone'] = input('post.telephone');
            }
            if(input('post.sex')) {
                $map['sex'] = input('post.sex');
            }
            if(input('post.email')) {
                $map['email'] = input('post.email');
            }


            /*if($map['realname']==''){
                $return_arr = ['status'=>0, 'msg'=>'姓名不能为空','data'=> []];
                exit(json_encode($return_arr));
            }
            if($map['nickname']==''){
                $return_arr = ['status'=>0, 'msg'=>'昵称不能为空','data'=> []];
                exit(json_encode($return_arr));
            }
            if($map['telephone']==''){
                $return_arr = ['status'=>0, 'msg'=>'手机不能为空','data'=> []];
                exit(json_encode($return_arr));
            }*/

            $map['update_time'] = strtotime(date('Y-m-d H:i:s',time()));
            $user = model('user');

            $addr_id = $user->save($map,['id' => $user_id]);
            if($addr_id){
                $return_arr = ['status'=>1, 'msg'=>'修改成功','data'=> []];
                exit(json_encode($return_arr));
            }else{
                $return_arr = ['status'=>0, 'msg'=>'修改失败','data'=> []];
                exit(json_encode($return_arr));
            }

        }
    }


    /*修改登录密码*/
    public function edit_password(){
        if (request()->isPost()) {

            $map = [];
            $data = [];
            $user_id = $this->user_id;
            $map['password'] = input('post.password');
            $map['cfmpassword'] = input('post.cfmpassword');
            $map['oldpassword'] = input('post.oldpassword');
            $data['password']= md5($map['password']);
            $data['update_time'] = strtotime(date('Y-m-d H:i:s',time()));
            if($data['password']==''){
                $return_arr = ['status'=>0, 'msg'=>'密码不能为空','data'=> []];
                exit(json_encode($return_arr));
            }
            if($map['cfmpassword']==''){
                $return_arr = ['status'=>0, 'msg'=>'请在次输入没密码','data'=> []];
                exit(json_encode($return_arr));
            }
            if($map['cfmpassword']!=$map['password']){
                $return_arr = ['status'=>0, 'msg'=>'两次密码输入不一致，请重新输入密码','data'=> []];
                exit(json_encode($return_arr));
            }
            if($map['oldpassword']==''){
                $return_arr = ['status'=>0, 'msg'=>'请输入当前密码','data'=> []];
                exit(json_encode($return_arr));
            }
            $user = model('user');
            $user_oldpassword = $user->where(['id' => $user_id,'password' => md5($map['oldpassword'])])->count();
            if($user_oldpassword>0){
                $addr_id = $user->save($data,['id' => $user_id]);
                if($addr_id){
                    $return_arr = ['status'=>1, 'msg'=>'修改成功','data'=> []];
                    exit(json_encode($return_arr));
                }else{
                    $return_arr = ['status'=>0, 'msg'=>'修改失败','data'=> []];
                    exit(json_encode($return_arr));
                }
            }else{
                $return_arr = ['status'=>0, 'msg'=>'当前密码不对','data'=> []];
                exit(json_encode($return_arr));
            }

        }

    }

    //我的银行卡
    public function my_bankcard(){
        if (request()->isPost()) {
            $user_id = $this->user_id;
            $bank_list = model('bank_list');
            $banklist = $bank_list->where(['user_id' => $user_id, 'partner_id' => 0])->select();

            if($banklist){
                foreach ($banklist as $k => $v) {
                    $banklist[$k]['bank_cardid_new'] = substr($v['bank_cardid'], -4);
                }
                //$banklist = removeNull($banklist);
                $return_arr = ['status'=>1, 'msg'=>'操作成功','data'=> ['banklist' => $banklist]];
                ajaxReturn($return_arr);
            }else{
                $return_arr = ['status'=>0, 'msg'=>'操作失败','data'=> []];
                exit(json_encode($return_arr));
            }
        }
    }

    // 添加银行卡
    public function add_bankcard(){
        if (request()->isPost()) {
            $user_id = $this->user_id;
            $bank_list = model('bank_list');
            $map['user_id'] = $user_id;
            $map['bank_cardid'] = input('post.bank_cardid');
            $map['bank_name'] = input('post.bank_name');
            $map['bank_branch'] = input('post.bank_branch');
            $map['realname'] = input('post.realname');
            $map['telephone'] = input('post.telephone');
            $map['card_id'] = input('post.card_id');
            $map['add_time'] = date('Y-m-d H:i:s',time());
            if($map['bank_cardid']==''){
                $json_arr = ['status' => 0, 'msg' => '请填写银行卡号', 'data' => []];
                exit(json_encode($json_arr));
            }
            if($map['bank_name']==''){
                $json_arr = ['status' => 0, 'msg' => '请填写银行名称', 'data' => []];
                exit(json_encode($json_arr));
            }
            if($map['bank_branch']==''){
                $json_arr = ['status' => 0, 'msg' => '请填写支行名称', 'data' => []];
                exit(json_encode($json_arr));
            }
            if($map['realname']==''){
                $json_arr = ['status' => 0, 'msg' => '请填写持卡人名称', 'data' => []];
                exit(json_encode($json_arr));
            }
            if($map['telephone']==''){
                $json_arr = ['status' => 0, 'msg' => '请填写预留手机号', 'data' => []];
                exit(json_encode($json_arr));
            }
            if($map['card_id']==''){
                $json_arr = ['status' => 0, 'msg' => '开户人身份证号码', 'data' => []];
                exit(json_encode($json_arr));
            }

            $lists = $bank_list->where(['user_id' => $this->user_id, 'bank_cardid' => $map['bank_cardid']])->count();
            if($lists>0){
                $json_arr = ['status' => 0, 'msg' => '银行卡已存在', 'data' => []];
                exit(json_encode($json_arr));
            }else{
                $row = $bank_list->save($map);
                if($row){
                    $json_arr = ['status' => 1, 'msg' => '添加成功', 'data' => []];
                    exit(json_encode($json_arr));
                }else{
                    $json_arr = ['status' => 0, 'msg' => '添加失败', 'data' => []];
                    exit(json_encode($json_arr));
                }
            }
        }
    }

    //修改银行卡
    public function edit_bankcard(){
        if (request()->isPost()) {
            $user_id = $this->user_id;
            $bank_list = model('bank_list');
            $id = input('post.id');
            $map = [];
            $map['bank_cardid'] = input('post.bank_cardid');
            $map['bank_name'] = input('post.bank_name');
            $map['bank_branch'] = input('post.bank_branch');
            $map['realname'] = input('post.realname');
            $map['telephone'] = input('post.telephone');
            $map['card_id'] = input('post.card_id');
            $map['update_time'] = strtotime(date('Y-m-d H:i:s',time()));
            if($map['bank_cardid']==''){
                $json_arr = ['status' => 0, 'msg' => '请填写银行卡号', 'data' => []];
                exit(json_encode($json_arr));
            }
            if($map['bank_name']==''){
                $json_arr = ['status' => 0, 'msg' => '请填写银行名称', 'data' => []];
                exit(json_encode($json_arr));
            }
            if($map['bank_branch']==''){
                $json_arr = ['status' => 0, 'msg' => '请填写支行名称', 'data' => []];
                exit(json_encode($json_arr));
            }
            if($map['realname']==''){
                $json_arr = ['status' => 0, 'msg' => '请填写持卡人名称', 'data' => []];
                exit(json_encode($json_arr));
            }
            if($map['telephone']==''){
                $json_arr = ['status' => 0, 'msg' => '请填写预留手机号', 'data' => []];
                exit(json_encode($json_arr));
            }
            if($map['card_id']==''){
                $json_arr = ['status' => 0, 'msg' => '开户人身份证号码', 'data' => []];
                exit(json_encode($json_arr));
            }
            $lists = $bank_list->where(['id' =>$id])->count();
            if($lists>0){
                $res = $bank_list->update($map,['id' => $id]);
                $json_arr = ['status' => 1, 'msg' => '修改成功', 'data' => []];
                exit(json_encode($json_arr));
            }else{
                $json_arr = ['status' => 0, 'msg' => '操作失败', 'data' => []];
                exit(json_encode($json_arr));
            }
        }
    }

    public function bankDetail()
    {
        if(request()->isPost()){
            $map['user_id'] = $this->user_id;
            $map['id'] = input('post.id');
            if($map['id']){
                $field = 'id bank_cardid, bank_name, bank_branch,realname, telephone, card_id';
                $list = model('bank_list')->field($field)->where($map)->find();

                if($list){
                    $list['bank_cardid_new'] = substr($list['bank_cardid'], -4);
                    $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]];
                }else{
                    $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => ['list' => $list]];
                }
            }else{
                $json_arr = ['status'=>0, 'msg'=>SystemConstant::SYSTEM_NONE_PARAM,'data'=> []];
            }
            exit(json_encode($json_arr));
        }
    }
    //删除银行卡
    public function bankDel(){
        if(request()->isPost()){
            $user_id = $this->user_id;
            $id = input('post.id');
            if($id){
                $id_arr = explode(',', $id);
                $result = model('bank_list')->destroy($id_arr);
                if ($result) {
                    $return_arr = ['status'=>1, 'msg'=>'删除成功','data'=> []];
                } else {
                    $return_arr = ['status'=>0, 'msg'=>'删除失败','data'=> []];
                }
            }else{
                $return_arr = ['status'=>0, 'msg'=>'操作失败','data'=> []];
            }

            exit(json_encode($return_arr));
        }
    }


    //签到
    public function sign_in()
    {

        $user_sign_model = model('user_sign');
        $todayTimestamp = $this->todayTimestamp();
        $user_sign = $user_sign_model->where(['user_id' => $this->user_id, 'add_time' => ['between', $todayTimestamp]])->find();
        if ($user_sign) {
            $json_arr = ['status' => 0, 'msg' => '今天已签到', 'data' => []];
            exit(json_encode($json_arr));
        }
        $res = $user_sign_model->save(['user_id' => $this->user_id, 'add_time' => time()]);
        if (!$res) {
            $json_arr = ['status' => 0, 'msg' => '签到失败', 'data' => []];
            exit(json_encode($json_arr));
        }
        $sign_give_integral = getSetting('integral.sign_give_integral');
        model('user')->where('user_id', $this->user_id)->setInc('integral', $sign_give_integral);
        if (!$res) {
            $json_arr = ['status' => 0, 'msg' => '签到失败', 'data' => []];
            exit(json_encode($json_arr));
        } else {
            $json_arr = ['status' => 1, 'msg' => '签到成功', 'data' => []];
            exit(json_encode($json_arr));
        }
    }

    //领取优惠劵
    public function couponReceive(){

        if(request()->isPost()){
            $coupon_model = model('coupon_data');
            $user_id = $this->user_id;
            $coupon_id = input('post.id');
            $coupon_info = model('coupon_data')->where(['coupon_id' => $coupon_id, 'user_id' => $user_id])->find();
            if ($coupon_info) {
                $json_arr = ['status' => 0, 'msg' => '您已经领取过了，不可以重复领取', 'data' => []];
                ajaxReturn($json_arr);
            }
            $coupon = model('coupon')->where(['id' => $coupon_id])->find();
            $add_arr = [
                'user_id' => $user_id,
                'coupon_id' => $coupon_id,
                'goods_info' => $coupon['goods_info'],
                'des' => $coupon['des'],
                'use_type' => $coupon['use_type'],
                'action' => 2,
                'add_time' => date("Y-m-d h:i:d"),
                'deduct' => $coupon['deduct'],
                'limit_money' => $coupon['limit_money'],
                'title' => $coupon['title'],
                'canal' => 1,
                'coupon_type' => $coupon['coupon_type'],
                'starttime' => $coupon['starttime'],
                'endtime' => $coupon['endtime'],
                'partner_id' => 0
            ];
            $coupon_no = 'VIP'.strtoupper(uniqid());
            while(model('coupon_data')->where(['coupon_no' => $coupon_no])->find()) {
                $coupon_no = 'VIP'.strtoupper(uniqid());
            }
            $add_arr['coupon_no'] = $coupon_no;
            model('coupon_data')->insert($add_arr);

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => '']];
            ajaxReturn($json_arr);
        }
    }

    //我的优惠劵
    public function couponList(){
        if(request()->isPost()){
            $map = ['partner_id' => 0];
            $map['user_id'] = $this->user_id;
            $map['status'] = 0;
            //$map['starttime'] = ['lt', time()];
            //$map['endtime'] = ['gt', time()];
            $coupon_type = request()->post('coupon_type', 0);

            if ($coupon_type == 0) {
//                $map['starttime'] = ['lt', time()];
                $map['endtime'] = ['gt', time()];
            } else if ($coupon_type == 1) {
                $map['status'] = 1;
            } else if ($coupon_type == 2) {
                $map['endtime'] = ['lt', time()];
            }
            $list = model('coupon_data')->where($map)->order('id desc')->select();
            $coupon = [];
            foreach ($list as $k => $v) {
                $coupon[$k]['title'] = $v['title'];
                $coupon[$k]['des'] = $v['des'];
                $coupon[$k]['starttime'] = date('Y-m-d H:i:s', $v['starttime']);
                $coupon[$k]['endtime'] = date('Y-m-d H:i:s', $v['endtime']);
                $coupon[$k]['coupon_id'] = $v['id'];
                $coupon[$k]['limit_money'] = intval($v['limit_money']);
                if ($v['coupon_type'] == 1) {
                    $coupon[$k]['deduct'] = intval($v['deduct']);
                } else {
                    $coupon[$k]['deduct'] = $v['deduct'];
                }
                $coupon[$k]['coupon_type'] = $v['coupon_type'];
                if ($v['use_type'] == 1) { //部分商品
                    $goods_info = json_decode($v['goods_info'], true);
                    if ($goods_info) {
                        foreach ($goods_info as $key => $val) {
                            //$goods_info[$key]['sku_id'] = $val['sku_id']?$val['sku_id']:0;
                            $name = model('goods')->where(['id' => $val['goods_id']])->value('goods_name');
                            $sku = $this->get_sku_des($val['goods_id'], $val['sku_id']);
                            $goods_info[$key]['name'] = $name.$sku;
                        }
                    }
                } else if ($v['use_type'] == 2){
                    $goods_list = json_decode($v['goods_info'], true);
                    $goods_info = [];
                    if ($goods_list) {
                        foreach ($goods_list as $key => $val) {
                            $goods_info[$key]['name'] =  model('goods_brand')->where(['id' => $v])->value('classname');
                            $goods_info[$key]['brand_id'] = $val;
                        }
                    }
                } else  {
                    $goods_info = [];
                }
                $coupon[$k]['goods_info'] = $goods_info;
            }
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $coupon]];

            ajaxReturn($json_arr);
        }
    }

    //收货地址列表
    public function addressList(){
        if(request()->isPost()){
            $map = ['partner_id' => 0];
            $map['user_id'] = $this->user_id;
            $list = model('address')->where($map)->order('is_default desc,add_time desc')->select();

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]];

            ajaxReturn($json_arr);
        }
    }

    public function addressInfo(){
        if(request()->isPost()){
            $map['user_id'] = $this->user_id;
            $map['id'] = input('post.address_id');
            if($map['id']){
                $field = 'id address_id, telephone, consignee,province, city, district, province_id, city_id, district_id,address, is_default';
                $list = model('address')->field($field)->where($map)->find();
                if($list){

                    $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]];
                }else{
                    $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => ['list' => $list]];
                }
            }else{
                $json_arr = ['status'=>0, 'msg'=>SystemConstant::SYSTEM_NONE_PARAM,'data'=> []];
            }
            exit(json_encode($json_arr));
        }
    }

    //收货地址

    public function addAddress(){
        if(request()->isPost()){
            $user_id = $this->user_id;
            $address_id = input('post.address_id');
            $consignee = input('post.consignee');
            $telephone = input('post.telephone');
            $province = input('post.province');
            $province_id = input('post.province_id');
            $city = input('post.city');
            $city_id = input('post.city_id');
            $district = input('post.district');
            $district_id = input('post.district_id');
            $address = input('post.address');
            $is_default = input('post.is_default');
            if(!$is_default){
                $is_default = 0;
            } else {
                $is_default = 1;
            }
            //$add_time = time();
            //$update_time = time();
            if(empty($consignee) ){
                $result = array("status"=>0,"msg"=>"请填写收货人","data"=>[]);
                exit(json_encode($result));
            }
            if(empty($telephone) ){
                $result = array("status"=>0,"msg"=>"请填写联系手机号","data"=>[]);
                exit(json_encode($result));
            }
            //手机号码验证
            $res = $this->VerifyTelephone($telephone);
            if(!$res){
                $result = array('status'=>0,'msg'=>'联系手机号码格式不正确',"data"=>[]);
                exit(json_encode($result));
            }
            if(empty($province_id) ){
                $result = array("status"=>0,"msg"=>"请填写省","data"=>[]);
                exit(json_encode($result));
            }
            if (empty($city_id) ){
                $result = array("status"=>0,"msg"=>"请填写市","data"=>[]);
                exit(json_encode($result));
            }
            if(empty($district_id) ){
                $result = array("status"=>0,"msg"=>"请填写区","data"=>[]);
                exit(json_encode($result));
            }
            if( empty($address)){
                $result = array("status"=>0,"msg"=>"请填写详细地址","data"=>[]);
                exit(json_encode($result));
            }


            $addr_data = [];
            $addr_data['consignee'] = $consignee;
            $addr_data['telephone'] = $telephone;
            $addr_data['province_id']  = $province_id;
            $addr_data['province']  = $province;
            $addr_data['city_id']      = $city_id;
            $addr_data['city']      = $city;
            $addr_data['district_id']  = $district_id;
            $addr_data['district']  = $district;
            $addr_data['address']   = $address;
            $addr_data['user_id']   = $user_id;


            if($address_id){
                $addr_data['is_default'] = $is_default;
                if ($addr_data['is_default'] == 1) {
                    $row = [];
                    $row['user_id'] = $user_id;
                    $row['is_default'] = 1;
                    $address = model('address')->where($row)->field('id')->select();
                    foreach ($address as $key => $value) {
                        model('address')->update(['is_default' => 0], ['id' => $value['id']]);
                    }
                }
                $map = [];
                $map['id'] = $address_id;
                $addr_data['update_time'] = strtotime(date('Y-m-d H:i:s',time()));
                $res = model('address')->save($addr_data,['id' => $address_id]);
                if(!$res){
                    $result = array('status'=>0,'msg'=>'修改地址失败',"data"=>[]);
                }else{
                    $result = array('status'=>1,'msg'=>'修改地址成功',"data"=>[]);
                }
            }else{
                $more_map = [];
                $more_map['user_id'] = $user_id;
                $counts = model('address')->where($more_map)->count();
                if($counts >= 10){
                    $result = array('status'=>0,'msg'=>'最大可以添加10条收货地址',"data"=>[]);
                    exit(json_encode($result));
                }
                $res = model('address')->where($addr_data)->count();
                if($res>0){
                    $result = array('status'=>0,'msg'=>'收货地址与已有的重复',"data"=>[]);
                    exit(json_encode($result));
                    //exit(json_encode($result));
                }
                $addr_data['add_time'] = date('Y-m-d H:i:s',time());
                $addr_data['is_default'] = $is_default;
                if ($addr_data['is_default'] == 1) {
                    $row = [];
                    $row['user_id'] = $user_id;
                    $row['is_default'] = 1;
                    $address = model('address')->where($row)->field('id')->select();
                    foreach ($address as $key => $value) {
                        model('address')->update(['is_default' => 0], ['id' => $value['id']]);
                    }
                }

                $addr_id = model('address')->save($addr_data);
                if(!$addr_id){
                    $result = array('status'=>0,'msg'=>'新增地址失败',"data"=>[]);
                }else{
                    $result = array('status'=>1,'msg'=>'新增地址成功',"data"=>[]);
                }
            }
            //$result = $this->addA($user_id,$address_id,$consignee,$telephone,$province,$city,$district,$address,$is_default,$add_time,$update_time);
            exit(json_encode($result));
        }

    }

    /*public function addA($user_id,$address_id,$consignee,$telephone,$province,$city,$district,$address,$is_default,$add_time,$update_time){
        if(empty($user_id) || empty($consignee) || empty($telephone) || empty($province) || empty($city) || empty($district) || empty($address)){
            return array("status"=>0,"msg"=>"网络正忙，请稍后再试,参数userid或consignee或telephone或province或city或address缺少","data"=>[]);
        }
        //手机号码验证
        $res = $this->VerifyTelephone($telephone);
        if(!$res){
            return array('status'=>0,'msg'=>'联系手机号码格式不正确',"data"=>[]);
        }

        $addr_data = [];
        $addr_data['consignee'] = $consignee;
        $addr_data['telephone'] = $telephone;
        $addr_data['province']  = $province;
        $addr_data['city']      = $city;
        $addr_data['district']  = $district;
        $addr_data['address']   = $address;
        $addr_data['user_id']   = $user_id;


        if($address_id){
            $addr_data['is_default'] = $is_default;
            if ($addr_data['is_default'] == 1) {
                $row = [];
                $row['user_id'] = $user_id;
                $row['is_default'] = 1;
                $address = model('address')->where($row)->field('address_id')->select();
                foreach ($address as $key => $value) {
                    model('address')->update(['is_default' => 0], ['address_id' => $value['address_id']]);
                }
            }
            $map = [];
            $map['address_id'] = $address_id;
            $addr_data['update_time'] = strtotime(date('Y-m-d H:i:s',time()));
            $res = model('address')->save($addr_data,['address_id' => $address_id]);
            if(!$res){
                return array('status'=>0,'msg'=>'修改地址失败',"data"=>[]);
            }else{
                return array('status'=>1,'msg'=>'修改地址成功',"data"=>[]);
            }
        }else{
            $more_map = [];
            $more_map['user_id'] = $user_id;
            $counts = model('address')->where($more_map)->count();
            if($counts >= 10){
                return array('status'=>0,'msg'=>'最大可以添加10条收货地址',"data"=>[]);
            }
            $res = model('address')->where($addr_data)->select();
            if($res){
                return array('status'=>0,'msg'=>'收货地址与已有的重复',"data"=>[]);
            }
            $addr_data['create_time'] = strtotime(date('Y-m-d H:i:s',time()));
            $addr_data['is_default'] = $is_default;
            if ($addr_data['is_default'] == 1) {
                $row = [];
                $row['user_id'] = $user_id;
                $row['is_default'] = 1;
                $address = model('address')->where($row)->field('address_id')->select();
                foreach ($address as $key => $value) {
                    model('address')->update(['is_default' => 0], ['address_id' => $value['address_id']]);
                }
            }

            $addr_id = model('address')->save($addr_data);
            if(!$addr_id){
                return array('status'=>0,'msg'=>'新增地址失败',"data"=>[]);
            }else{
                return array('status'=>1,'msg'=>'新增地址成功',"data"=>[]);
            }

        }

    } */

    //删除收货地址
    public function addressDel(){
        if(request()->isPost()){
            $user_id = $this->user_id;
            $address_id = input('post.address_id');
            if($address_id){
                $id_arr = explode(',', $address_id);
                $result = model('address')->destroy($id_arr);
                if ($result) {
                    $return_arr = ['status'=>1, 'msg'=>'删除成功','data'=> []];
                } else {
                    $return_arr = ['status'=>0, 'msg'=>'删除失败','data'=> []];
                }
            }else{
                $return_arr = ['status'=>0, 'msg'=>SystemConstant::SYSTEM_NONE_PARAM,'data'=> []];
            }

            exit(json_encode($return_arr));
        }
    }

    //设置默认地址
    public function defaultAddress(){
        if(request()->isPost()){
            $user_id = $this->user_id;
            $address_id = input('post.address_id');
            if($address_id){
                $map = [];
                $map['user_id'] = $user_id;
                $map['id'] = $address_id;
                $info = model('address')->where($map)->find();
                if($info['is_default']==0){
                    $addr_data['is_default'] = 1;
                    model('address')->save(['is_default' => 0], ['id' => ['neq', $address_id]]);
                    $addr_id = model('address')->save($addr_data,['id' => $address_id]);
                    $return_arr = ['status'=>1, 'msg'=>'设置成功','data'=> []];
                }else{
                    $addr_data['is_default'] = 0;
                    $check = model('address')->save(['is_default' => 1, 'id' => ['neq', $address_id]]);
                    if (!$check) {
                        $new = model('address')->where(['id' => ['neq', $address_id]])->order('add_time desc')->field('id')->find();
                        if ($new) {
                            model('address')->save(['is_default' => 1, 'id' => $new['id']]);
                        } else {
                            ajaxReturn(['status'=>0, 'msg'=>'就一条地址不可取消默认地址','data'=> []]);
                        }
                    }
                    $addr_id = model('address')->save($addr_data,['id' => $address_id]);
                    $return_arr = ['status'=>1, 'msg'=>'设置成功','data'=> []];
                }
            }else{
                $return_arr = ['status'=>0, 'msg'=>SystemConstant::SYSTEM_NONE_PARAM,'data'=> []];
            }
            exit(json_encode($return_arr));
        }
    }

    public  function shareList()
    {
        $share_model = model('rebate_log');
        $list_row = request()->post('list_row', 10); //每页数据
        $page = request()->post('page', 1); //当前页

        $where = ['user_id' => $this->user_id, 'status' => 3];
        $totalCount = $share_model->where($where)->count();
        $first_row = ($page-1)*$list_row;
        $field = ['buy_user_id','order_id','user_id', 'money'];
        $lists = $share_model->where($where)->field($field)->limit($first_row, $list_row)->order('create_time desc')->select();
        foreach ($lists as $k => $v) {
            $order = model('order')->where('id', $v['order_id'])->field('order_no')->find();
            $order_goods = model('order_goods')->where('order_id', $v['order_id'])->field('goods_id,goods_name, goods_pic, goods_num')->select();
            //$goods_num = model('order')->where('order_id', $v['order_id'])->sum('goods_num');
            $user = model('user')->where('id', $v['buy_user_id'])->field('head_img, nickname')->find();
            $lists[$k]['username'] = $user['nickname'];
            $lists[$k]['head_img'] = $user['head_img'];
            $lists[$k]['goods_list'] = $order_goods;
            //$lists[$k]['goods_num'] = $goods_num;
            $lists[$k]['order_no'] = $order['order_no'];
        }
        $pageCount = ceil($totalCount/$list_row);
        /*//总分销人数
        $total_share_num = $share_model->where($where)->group('user_id')->count();
        //今天分销人数
        $todayTimestamp = $this->todayTimestamp();
        $where['create_time'] = ['between', $todayTimestamp];
        $today_share_num = $share_model->where($where)->group('user_id')->count();
        //昨天分销人数
        $todayTimestamp = $this->todayTimestamp(strtotime('-1 day'));
        $where['create_time'] = ['between', $todayTimestamp];
        $yesterday_share_num = $share_model->where($where)->group('user_id')->count();*/

        $data = [
            /*        'total_share_num' => $total_share_num ? $total_share_num : 0,
                    'today_share_num' => $today_share_num ? $today_share_num : 0,
                    'yesterday_share_num' => $yesterday_share_num ? $yesterday_share_num : 0,*/
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
            'list' => $lists ? $lists : [],
        ];

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    public function userMoney()
    {
        $share_model = model('user_share');
        $list_row = request()->post('list_row', 10); //每页数据
        $page = request()->post('page', 1); //当前页

        $where = ['type' => 0];
        $totalCount = $share_model->where($where)->count();
        $first_row = ($page-1)*$list_row;
        $field = ['goods_id','goods_num','user_id','user_money'];
        $lists = $share_model->where($where)->field($field)->limit($first_row, $list_row)->order('create_time desc')->select();
        foreach ($lists as $k => $v) {
//            $nickname = model('user')->where('user_id', $v['user_id'])->value('nickname');
            $goods = model('goods')->where('goods_id', $v['goods_id'])->field('goods_name,goods_unit,goods_logo')->find();
//            $lists[$k]['right_describe'] = '用户'.$nickname.'已经购买了您推荐的产品';
            $lists[$k]['goods_logo'] = $goods['goods_logo'];
            $lists[$k]['goods_name'] = $goods['goods_name'];
            $lists[$k]['goods_unit'] = $goods['goods_unit'];
        }
        $pageCount = ceil($totalCount/$list_row);
        //总分销人数
        $total_share_num = $share_model->where($where)->sum('user_money');
        //今天分销人数
        $todayTimestamp = $this->todayTimestamp();
        $where['create_time'] = ['between', $todayTimestamp];
        $today_share_num = $share_model->where($where)->sum('user_money');
        //昨天分销人数
        $todayTimestamp = $this->todayTimestamp(strtotime('-1 day'));
        $where['create_time'] = ['between', $todayTimestamp];
        $yesterday_share_num = $share_model->where($where)->sum('user_money');

        $data = [
            'total_share_num' => $total_share_num ? $total_share_num : 0,
            'today_share_num' => $today_share_num ? $today_share_num : 0,
            'yesterday_share_num' => $yesterday_share_num ? $yesterday_share_num : 0,
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
            'list' => $lists ? $lists : [],
        ];

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }


    public function appointmentBefore()
    {
        $order_no = request()->post('order_no');
        if (!$order_no) {
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
            ajaxReturn($json_arr);
        }
        $order = model('order')->where(['order_no' => $order_no])->field('id, is_del')->find();
        if (!$order || $order['is_del'] == 1) {
            $json_arr = ['status' => 0, 'msg' => '订单不存在或已删除', 'data' => []];
            ajaxReturn($json_arr);
        }
        $appointment_install = model('appointment_install')
            ->field('telephone,address,appointment_time,user_note,install_status')
            ->where(['order_id' => $order['id']])
            ->find();
        if ($appointment_install) {
            $install_status = $appointment_install['install_status'];
        } else {
            $install_status = 0;
        }
        $data = ['install_status' => $install_status, 'appointment_install' => $appointment_install];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data]);
    }

    public function appointment()
    {
        $order_no = request()->post('order_no');
        if (!$order_no) {
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
            ajaxReturn($json_arr);
        }
        $order = model('order')->where(['order_no' => $order_no])->field('id, is_del, install_status')->find();
        if (!$order || $order['is_del'] == 1) {
            $json_arr = ['status' => 0, 'msg' => '订单不存在或已删除', 'data' => []];
            ajaxReturn($json_arr);
        }

        $count = model('appointment_install')->where(['order_id' => $order['id']])->count();

        if ($order['install_status'] != 0 || $count) {
            $json_arr = ['status' => 0, 'msg' => '该订单已经预约过了', 'data' => []];
            ajaxReturn($json_arr);
        }

        $telephone = request()->post('telephone');
        $address = request()->post('address');
        $appointment_time = request()->post('appointment_time');
        $user_note = request()->post('user_note');
        if (!$telephone) {
            $json_arr = ['status' => 0, 'msg' => '请填写手机号', 'data' => []];
            ajaxReturn($json_arr);
        }
        if (!$this->VerifyTelephone($telephone)) {
            $json_arr = ['status' => 0, 'msg' => '手机号格式不正确', 'data' => []];
            ajaxReturn($json_arr);
        }
        if (!$address) {
            $json_arr = ['status' => 0, 'msg' => '请填写安装地址', 'data' => []];
            ajaxReturn($json_arr);
        }
        if (!$appointment_time) {
            $json_arr = ['status' => 0, 'msg' => '请填写上门时间', 'data' => []];
            ajaxReturn($json_arr);
        }
        if (strtotime($appointment_time) < time()+24*60*60) {
            $json_arr = ['status' => 0, 'msg' => '上门时间必须大于当前时间一天', 'data' => []];
            ajaxReturn($json_arr);
        }
        /* if (!$user_note) {
             $json_arr = ['status' => 0, 'msg' => '请填写备注', 'data' => []];
             ajaxReturn($json_arr);
         }*/

        $data = [
            'telephone' => $telephone,
            'address' => $address,
            'appointment_time' => $appointment_time,
            'user_note' => $user_note,
            'order_id' => $order['id'],
            'user_id' => $this->user_id,
        ];
        Db::startTrans();
        $result = model('appointment_install')->isUpdate(false)->save($data);
        if (!$result) {
            Db::rollback();
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            ajaxReturn($json_arr);
        }
        $result = model('order')->isUpdate(true)->save(['install_status' => 1], ['id' => $order['id']]);
        if (!$result) {
            Db::rollback();
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            ajaxReturn($json_arr);
        }
        Db::commit();
        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
        ajaxReturn($json_arr);
    }

    public function appointCommentPrev()
    {
        $order_no = request()->post('order_no');
        if (!$order_no) {
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
            ajaxReturn($json_arr);
        }
        $order = model('order')->where(['order_no' => $order_no])->field('id, is_del, install_status')->find();
        if (!$order || $order['is_del'] == 1) {
            $json_arr = ['status' => 0, 'msg' => '订单不存在或已删除', 'data' => []];
            ajaxReturn($json_arr);
        }
        $appointment_install = model('appointment_install')
            ->field('telephone,address,appointment_time,user_note,install_status,install_user_id')
            ->where(['order_id' => $order['id']])
            ->find();
        if (!$appointment_install) {
            $json_arr = ['status' => 0, 'msg' => '该订单还没有预约安装', 'data' => []];
            ajaxReturn($json_arr);
        }
        /* if ($order['install_status'] == 3) {
             $json_arr = ['status' => 0, 'msg' => '该订单已完成安装评价', 'data' => []];
             ajaxReturn($json_arr);
         }*/
        if ($order['install_status'] < 2) {
            $json_arr = ['status' => 0, 'msg' => '该订单还没有完成安装', 'data' => []];
            ajaxReturn($json_arr);
        }
        $field = 'install_star,content,multiple_pic';
        $list = model('appointment_install_comment')->where(['order_id' => $order['id']])->field($field)->find();
        if ($list) {
            if ($list['multiple_pic']) {
                $multiple_pic = explode(',', $list['multiple_pic']);
                foreach ($multiple_pic as $k => $v) {
                    $multiple_pic[$k] = $v ? picture_url_dispose($v) : '';
                }
                $list['multiple_pic'] = $multiple_pic;
            } else {
                $list['multiple_pic'] = [];
            }
        }
        $install_user_id = $appointment_install['install_user_id'];
        $install_user_name = model('delivery_install_user')->where(['id' => $install_user_id])->value('name');
        $install_user_name?$appointment_install['install_user_name'] = $install_user_name:false;
        unset($appointment_install['install_user_id']);
        $data = [
            'install_status' => $order['install_status'],
            'appointment_install' => $appointment_install,
            'list' => $list,
        ];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data]);

    }

    public function appointmentComment()
    {
        $post = request()->post();

        if (!isset($post['order_no']) || !$post['order_no']) {
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
            ajaxReturn($json_arr);
        }
        $order_no = $post['order_no'];
        $order = model('order')->where(['order_no' => $order_no])->field('id, is_del, install_status')->find();
        if (!$order || $order['is_del'] == 1) {
            $json_arr = ['status' => 0, 'msg' => '订单不存在或已删除', 'data' => []];
            ajaxReturn($json_arr);
        }
        $appointment_install = model('appointment_install')
            ->field('id, install_user_id')
            ->where(['order_id' => $order['id']])
            ->find();
        if (!$appointment_install) {
            $json_arr = ['status' => 0, 'msg' => '该订单还没有预约安装', 'data' => []];
            ajaxReturn($json_arr);
        }
        if ($order['install_status'] == 3) {
            $json_arr = ['status' => 0, 'msg' => '该订单已完成安装评价', 'data' => []];
            ajaxReturn($json_arr);
        }
        if ($order['install_status'] != 2) {
            $json_arr = ['status' => 0, 'msg' => '该订单还没有完成安装', 'data' => []];
            ajaxReturn($json_arr);
        }
        if (!isset($post['content']) || !$post['content']) {
            $json_arr = ['status' => 0, 'msg' => '请填写评价内容', 'data' => []];
            ajaxReturn($json_arr);
        }
        if (!isset($post['install_star']) || $post['install_star'] <= 0) {
            $json_arr = ['status' => 0, 'msg' => '请填评分', 'data' => []];
            ajaxReturn($json_arr);
        }
        $multiple_pic = isset($post['multiple_pic'])?$post['multiple_pic']:'';
        $content = $post['content'];
        $install_star = $post['install_star'];

        $post = [
            'install_star' => $install_star,
            'content' => $content,
            'multiple_pic' => $multiple_pic,
            'user_id' => $this->user_id,
            'order_id' => $order['id'],
            'order_no' => $order_no,
            'install_user_id' => $appointment_install['install_user_id'],
        ];
        Db::startTrans();
        $result = model('appointment_install_comment')->isUpdate(false)->save($post);
        if (!$result) {
            Db::rollback();
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            ajaxReturn($json_arr);
        }
        $result = model('order')->isUpdate(true)->save(['install_status' => 3], ['id' => $order['id']]);
        if (!$result) {
            Db::rollback();
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            ajaxReturn($json_arr);
        }
        $result = model('appointment_install')->isUpdate(true)->save(
            ['install_status' => 3],
            ['id' => $appointment_install['id']]
        );
        if (!$result) {
            Db::rollback();
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            ajaxReturn($json_arr);
        }
        Db::commit();
        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
        ajaxReturn($json_arr);
    }


    public function searchSalesman()
    {
        $work_no = request()->post('work_no');
        $user = model('user')->where(['id' => $this->user_id])->find();
        if (!$user) {
            ajaxReturn(['status' => 0, 'msg' => '用户不存在']);
        }
       $salesman = model('salesman')->field('work_no,name,telephone')->where(['work_no' => $work_no])->find();
        if (!$salesman) {
            ajaxReturn(['status' => 0, 'msg' => '没有该销售员']);
        }

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $salesman];
        ajaxReturn($json_arr);
    }

    public function salesmanList()
    {
        $user = model('user')->where(['id' => $this->user_id])->find();
        if (!$user) {
            ajaxReturn(['status' => 0, 'msg' => '用户不存在']);
        }

        $salesman_id = model('salesman_user')->where(['user_id' => $this->user_id])->value('salesman_id');
        if ($salesman_id) {
            $salesman = model('salesman')->field('work_no,name,telephone')->where(['id' => $salesman_id])->find();
        } else {
            $salesman = [];
        }

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $salesman];
        ajaxReturn($json_arr);
    }


    public function addSalesman()
    {
        $work_no = request()->post('work_no');
        $user = model('user')->where(['id' => $this->user_id])->find();
        if (!$user) {
            ajaxReturn(['status' => 0, 'msg' => '用户不存在']);
        }
        $salesman = model('salesman')->where(['work_no' => $work_no])->find();
        if (!$salesman) {
            ajaxReturn(['status' => 0, 'msg' => '没有该销售员']);
        }
        $salesman_user = model('salesman_user')->where(['user_id' => $this->user_id])->find();
        if (!$salesman_user) {
            model('salesman_user')->save([
                'user_id' => $this->user_id,
                'salesman_id' => $salesman['id'],
            ]);
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS];
        } else {
            $json_arr = ['status' => 0, 'msg' => '您已经绑定过销售员了'];
        }
        ajaxReturn($json_arr);
    }

    public function cancelSalesman()
    {
        $work_no = request()->post('work_no');
        $user = model('user')->where(['id' => $this->user_id])->find();
        if (!$user) {
            ajaxReturn(['status' => 0, 'msg' => '用户不存在']);
        }
        $salesman = model('salesman')->where(['work_no' => $work_no])->find();
        if (!$salesman) {
            ajaxReturn(['status' => 0, 'msg' => '没有该销售员']);
        }
        model('salesman_user')->where(['user_id' => $this->user_id, 'salesman_id' => $salesman['id']])->delete();
        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS];
        ajaxReturn($json_arr);
    }

}