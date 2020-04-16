<?php
namespace app\api\controller;
use app\common\constant\OrderConstant;
use app\common\constant\SystemConstant;
use app\common\constant\UserConstant;
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
            $this->add_access($this->user_id, UserConstant::USER_ACCESS_HOME_PAGE);
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
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

    // 添加访问量记录
    private function add_access($user_id, $type, $source = UserConstant::REG_SOURCE_PC, $pid = 0){
        if (!$user_id) {
            return false;
        }
        $where = [
            'user_id' => $user_id,
            'type' => $type,
            'pid' => $pid,
            'creat_at' => ['between', [time()-60*60*2, time()]]
        ];
        $res = model("access")->where($where)->find();
        if ($res) {
            return false;
        }
        //更新访问量
        $access['pid'] = $pid;
        $access['user_id'] = $user_id;
        $access['creat_at'] = time();
        $access['source'] = $source;
        $access['title'] = UserConstant::uer_access_value($type);
        $hour = date('H',time());
        if($hour >=0 && $hour < 2){
            $time_day = "0-2点";
        }elseif($hour >=2 && $hour < 4){
            $time_day = "2-4点";
        }elseif($hour >=4 && $hour < 6){
            $time_day = "4-6点";
        }elseif($hour >=6 && $hour < 8){
            $time_day = "6-8点";
        }elseif($hour >=8 && $hour < 10){
            $time_day = "8-10点";
        }elseif($hour >=10 && $hour < 12){
            $time_day = "10-12点";
        }elseif($hour >=12 && $hour < 14){
            $time_day = "12-14点";
        }elseif($hour >=14 && $hour < 16){
            $time_day = "14-16点";
        }elseif($hour >=16 && $hour < 18){
            $time_day = "16-18点";
        }elseif($hour >=18 && $hour < 20){
            $time_day = "18-20点";
        }elseif($hour >=20 && $hour < 22){
            $time_day = "20-22点";
        }else{
            $time_day = "22-24点";
        }
        $access['time_day'] = $time_day;
        $access['type'] = $type;
        $res = model("access")->save($access);
        if($res){
            return true;
        }else{
            return false;
        }
    }
}