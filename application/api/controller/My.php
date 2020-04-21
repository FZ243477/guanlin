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



    //寄件首页
    public function index_list(){
        $uid = $this->user_id;
        $user_content=model('user')->where('id',$uid)->find();
        if(!$user_content){
            $return_arr = ['status'=>0, 'msg'=>'操作失败','data'=> []];
            exit(json_encode($return_arr));
        }
        $list=[
            'id'=>$user_content['id'],
            'nickname'=>$user_content['nickname'],
            'headimgurl'=>$user_content['head_img'],
            'telephone'=>$user_content['telephone'],
        ];
        if($list){
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]];
            ajaxReturn($json_arr);
        }else{
            $return_arr = ['status'=>0, 'msg'=>'操作失败','data'=> []];
            exit(json_encode($return_arr));
        }
    }
}