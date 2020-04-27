<?php
namespace app\api\controller;

use app\common\constant\SystemConstant;
use app\common\helper\VerificationHelper;
use think\Db;

class Mailing extends Base
{
    use VerificationHelper;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 收货地址列表
     */

    //寄件首页
    public function index_list(){
        $uid = $this->user_id;
        $type = Db::name('user_message')
                ->where('uid',$uid)
                ->where('state',0)
                ->select();
        $field = 'id, content,type_id';
        $list=[];
        foreach ($type as $k=>$v){
            $message = model('message')->field($field)->where('type_id',$v['message_type'])->find();
            $list[$k]['message_content'] = $message['content'].$v['order_id'];
        }


        $map['paid']=0;
        $map['state']=0;
        $map['has_take']=1;
        $paid_num = model('order')->where($map)->count();
       // $list['paid_num']=$paid_num;
        $content=[
            'paid_num'=>$paid_num,
            'list'=>$list
        ];
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => [$content]];
            ajaxReturn($json_arr);
    }
}