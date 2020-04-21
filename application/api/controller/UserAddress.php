<?php
namespace app\api\controller;

use app\common\constant\SystemConstant;
use app\common\helper\VerificationHelper;
use think\Db;

class UserAddress extends Base
{
    use VerificationHelper;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 收货地址列表
     */
    public function index_list(){
        $map['uid'] = $this->user_id;
        $field = 'id, uid, real_name, phone, country, province, city, district,detail';
        $list = model('user_address')->where('delete_time','null')->where($map)->field($field)->order('id desc')->select();
        $list_count = model('user_address')->where('delete_time','null')->where($map)->field($field)->order('id desc')->count();

        if($list || $list_count == 0){
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]];
            ajaxReturn($json_arr);
        }else{
            $return_arr = ['status'=>0, 'msg'=>'操作失败','data'=> []];
            exit(json_encode($return_arr));
        }
    }

    /**
     * 新增收货地址
     */
    public function add(){
        $map['uid'] =$this->user_id;
        $data = input();
        $data['real_name'] = request()->post('real_name', 0);
        $data['phone'] = request()->post('phone', 0);
        $data['country'] = request()->post('country', 0);
        $data['province'] = request()->post('province', 0);
        $data['city'] = request()->post('city', 0);
        $data['district'] = request()->post('district', 0);
        $data['detail'] = request()->post('detail', 0);
        if($data['real_name']==''){
            $return_arr = ['status'=>0, 'msg'=>'姓名不能为空','data'=> []];
            exit(json_encode($return_arr));
        }
        if($data['phone']==''){
            $return_arr = ['status'=>0, 'msg'=>'手机号码不能为空','data'=> []];
            exit(json_encode($return_arr));
        }
        if($data['province']==''){
            $return_arr = ['status'=>0, 'msg'=>'请选择省市区','data'=> []];
            exit(json_encode($return_arr));
        }
        if($data['detail']==''){
            $return_arr = ['status'=>0, 'msg'=>'请填写详细地址','data'=> []];
            exit(json_encode($return_arr));
        }
        if(!isset($data['country']) || $data['country']==0){
            $data['country']='China';
        }
        $save_content=[
            'uid'=>$map['uid'],
            'real_name'=>$data['real_name'],
            'phone'=>$data['phone'],
            'country'=>$data['country'],
            'province'=>$data['province'],
            'city'=>$data['city'],
            'district'=>$data['district'],
            'detail'=>$data['detail'],
            'create_time'=>time()
        ];
        $save = model('user_address')->insertGetId($save_content);
        if($save){
            $return_arr = ['status'=>1, 'msg'=>'添加成功','data'=> []];
            exit(json_encode($return_arr));
        }else{
            $return_arr = ['status'=>0, 'msg'=>'添加失败','data'=> []];
            exit(json_encode($return_arr));
        }
    }


    /**
     * 新增收货地址
     */
    public function edit(){
        $map['uid'] =$this->user_id;
        $data['address_id'] = request()->post('address_id', 0);
        $data['real_name'] = request()->post('real_name', 0);
        $data['phone'] = request()->post('phone', 0);
        $data['country'] = request()->post('country', 0);
        $data['province'] = request()->post('province', 0);
        $data['city'] = request()->post('city', 0);
        $data['district'] = request()->post('district', 0);
        $data['detail'] = request()->post('detail', 0);
        if($data['address_id']==''){
            $return_arr = ['status'=>0, 'msg'=>'操作失败','data'=> []];
            exit(json_encode($return_arr));
        }
        if($data['real_name']==''){
            $return_arr = ['status'=>0, 'msg'=>'姓名不能为空','data'=> []];
            exit(json_encode($return_arr));
        }
        if($data['phone']==''){
            $return_arr = ['status'=>0, 'msg'=>'手机号码不能为空','data'=> []];
            exit(json_encode($return_arr));
        }
        if($data['province']==''){
            $return_arr = ['status'=>0, 'msg'=>'请选择省市区','data'=> []];
            exit(json_encode($return_arr));
        }
        if($data['detail']==''){
            $return_arr = ['status'=>0, 'msg'=>'请填写详细地址','data'=> []];
            exit(json_encode($return_arr));
        }
        if(!isset($data['country']) || $data['country']==0){
            $data['country']='China';
        }
        $save_content=[
            'real_name'=>$data['real_name'],
            'phone'=>$data['phone'],
            'country'=>$data['country'],
            'province'=>$data['province'],
            'city'=>$data['city'],
            'district'=>$data['district'],
            'detail'=>$data['detail'],
            'create_time'=>time()
        ];
        $save = model('user_address')->where('id',$data['address_id'])->update($save_content);
        if($save){
            $return_arr = ['status'=>1, 'msg'=>'修改成功','data'=> []];
            exit(json_encode($return_arr));
        }else{
            $return_arr = ['status'=>0, 'msg'=>'修改失败','data'=> []];
            exit(json_encode($return_arr));
        }
    }

    /**
     * 删除收货地址
     */
    public function del(){
        $data['address_id'] = request()->post('address_id', 0);
        if($data['address_id']==''){
            $return_arr = ['status'=>0, 'msg'=>'操作失败','data'=> []];
            exit(json_encode($return_arr));
        }
        $save = model('user_address')->destroy($data['address_id']);
        if($save){
            $return_arr = ['status'=>1, 'msg'=>'删除成功','data'=> []];
            exit(json_encode($return_arr));
        }else{
            $return_arr = ['status'=>0, 'msg'=>'删除失败','data'=> []];
            exit(json_encode($return_arr));
        }
    }
    /**
     * 收货地址列表
     */
    public function addressList(){
        $map['user_id'] = $this->user_id;
        $field = 'id, telephone, consignee, province, city, district, address, is_default';
        $list = model('address')->where($map)->field($field)->order('is_default desc,id desc')->select();
        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]];
        ajaxReturn($json_arr);

    }


    /**
     * 收货地址详情
     */
    public function addressDetail(){

        $address_id = request()->post('address_id');
        if(!$address_id){
            $json_arr = ['status'=>0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM];
            ajaxReturn($json_arr);
        }

        $field = 'id, telephone, consignee, province, city, district,address, is_default';
        $list = model('address')->field($field)->where(['id' => $address_id])->find();
        if($list){
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]];
        }else{
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => ['list' => $list]];
        }
        ajaxReturn($json_arr);
    }


    /**
     * 添加/修改收货地址
     */
    public function addAddress(){
        $address_id = request()->post('address_id');
        $add_data = [];
        $add_data['consignee']  = request()->post('consignee');
        $add_data['telephone'] = request()->post('telephone');
        $add_data['province']  = request()->post('province');
        $add_data['city']  = request()->post('city');
        $add_data['district']  = request()->post('district');
        $add_data['address']  = request()->post('address');
        $is_default  = request()->post('is_default', 0);

        if(empty($add_data['consignee']) ){
            $result = ['status' => 0,'msg' => '请填写收货人'];
            ajaxReturn($result);
        }
        if(empty($add_data['telephone']) ){
            $result = ['status'=>0,'msg'=>'请填写联系手机号'];
            ajaxReturn($result);
        }
        //手机号码验证
        $res = $this->VerifyTelephone($add_data['telephone']);
        if(!$res){
            $result = ['status'=>0,'msg'=>'联系手机号码格式不正确'];
            ajaxReturn($result);
        }
        if(empty($add_data['province']) ){
            $result = ['status'=>0,'msg'=>'请填写省'];
            ajaxReturn($result);
        }
        if (empty($add_data['city']) ){
            $result = ['status'=>0,'msg'=>'请填写市'];
            ajaxReturn($result);
        }
        if(empty($add_data['district']) ){
            $result = ['status'=>0,'msg'=>'请填写区'];
            ajaxReturn($result);
        }
        if( empty($add_data['address'])){
            $result = ['status'=>0,'msg'=>'请填写详细地址'];
            ajaxReturn($result);
        }
        Db('address')->startTrans();
        if ($is_default == 1) {
            model('address')->update(['is_default' => 0], ['is_default' => 1, 'user_id' => $this->user_id]);
        }
        $add_data['user_id']   = $this->user_id;
        if($address_id){
            $add_data['is_default'] = $is_default;
            $res = model('address')->save($add_data, ['id' => $address_id]);
            if(!$res){
                $result = ['status'=>0,'msg'=>'修改地址失败'];
            }else{
                $result = ['status'=>1,'msg'=>'修改地址成功'];
            }
        }else{
            $counts = model('address')->where(['user_id' => $this->user_id])->count();
            if($counts >= 10){
                $result = ['status'=>0,'msg'=>'最大可以添加10条收货地址'];
                ajaxReturn($result);
            }
            if ($is_default == 0 && $counts == 0) {
                $is_default = 1;
            }
            $res = model('address')->where($add_data)->count();
            if($res > 0){
                $result = ['status'=>0,'msg'=>'收货地址与已有的重复'];
                ajaxReturn($result);
            }
            $add_data['is_default'] = $is_default;
            $res = model('address')->save($add_data);
            if(!$res){
                $result = ['status'=>0,'msg'=>'新增地址失败'];
            }else{
                $result = ['status'=>1,'msg'=>'新增地址成功'];
            }
        }
        if ($res) {
            Db('address')->commit();
        } else {
            Db('address')->rollback();
        }
        ajaxReturn($result);

    }


    //删除收货地址
    public function addressDel(){
        $address_id = request()->post('address_id');
        if(!$address_id){
            $json_arr = ['status'=>0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM];
            ajaxReturn($json_arr);
        }
        $result = model('address')->destroy($address_id);
        if ($result) {
            $json_arr = ['status' => 1, 'msg' => '删除成功'];
        } else {
            $json_arr = ['status' => 0, 'msg' => '删除失败'];
        }
        ajaxReturn($json_arr);
    }

}