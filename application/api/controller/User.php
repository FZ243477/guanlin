<?php
namespace app\api\controller;
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

}