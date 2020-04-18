<?php
namespace app\api\controller;

use app\common\constant\SystemConstant;
use app\common\helper\VerificationHelper;
use think\Db;

class My extends Base
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
        $field = 'id,name,telephone,address,detailaddress';
        $list = model('transfer_station')->field($field)->select();
        if($list){
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]];
            ajaxReturn($json_arr);
        }else{
            $return_arr = ['status'=>0, 'msg'=>'操作失败','data'=> []];
            exit(json_encode($return_arr));
        }
    }

    //寄件首页
    public function index_list(){
        $uid = $this->user_id;
        $type = Db::name('user_message')
                ->where('uid',$uid)
                ->where('state',0)
                ->find();
        $field = 'id, content,type';
        $list = model('message')->field($field)->where('type',$type)->find();
        if(!$list){
            $list['message_content']="";
        }else{
            $list['message_content']=$list['content'];
        }
        $map['uid'] = $this->user_id;
        $map['paid']=0;
        $map['has_take']=1;
        $paid_num = model('order')->where($map)->count();
        $list['paid_num']=$paid_num;
        if($list){
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]];
            ajaxReturn($json_arr);
        }else{
            $return_arr = ['status'=>0, 'msg'=>'操作失败','data'=> []];
            exit(json_encode($return_arr));
        }
    }
}