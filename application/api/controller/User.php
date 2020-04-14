<?php
namespace app\api\controller;
use app\common\constant\OrderConstant;
use app\common\constant\SystemConstant;
use app\common\helper\VerificationHelper;
use app\common\helper\DatetimeHelper;
use Think\Db;

class User extends Base
{
    use VerificationHelper;
    use DatetimeHelper;

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
            $user = model('user')->where('id', $this->user_id)->field('nickname,head_img,telephone')->find();
            $order = model('order')
                ->where(['order_status' => OrderConstant::ORDER_STATUS_REJECTED])
                ->field('order_no')
                ->order('id desc')
                ->find();
            $data = [
                'user' => $user,
                'order_no' => $order['order_no']
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

}