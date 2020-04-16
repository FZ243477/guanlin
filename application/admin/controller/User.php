<?php


namespace app\admin\controller;
use app\admin\helper\ManagerHelper;
use app\common\helper\EncryptionHelper;
use app\common\helper\PHPExcelHelper;
use app\common\constant\SystemConstant;
use app\common\constant\UserConstant;
use Think\Db;

class User extends Base
{
    use ManagerHelper;
    use EncryptionHelper;
    use PHPExcelHelper;

    public function __construct()
    {
        parent::__construct();
    }

    public function userList()
    {
        if (request()->isPost()) {
            $map = [];
            #手机号 昵称
            $telephone = request()->post('telephone', '', 'trim');
            if($telephone){
                $map['telephone'] = ['like', "%".$telephone."%"];
            }
            $nickname = request()->post('nickname', '', 'trim');
            if($nickname){
                $map['nickname']=['like', "%".$nickname."%"];
            }
            $status = request()->post('status');
            if($status != ''){
                $map['status'] = $status;
            }
            $starttime = request()->post('start_time');
            $endtime = request()->post('end_time');

            $start_time = date('Y-m-d 00:00:00',strtotime($starttime));
            $end_time   = date('Y-m-d 23:59:59',strtotime($endtime));
            if($starttime && $endtime){
                $map['reg_time'] = ['between',[$start_time,$end_time]];
            }else{
                if($starttime){
                    $map['reg_time'] = ['gt',$start_time];
                }
                if($endtime){
                    $map['reg_time'] = ['lt',$end_time];
                }
            }
            $list_row = input('post.list_row', 10); //每页数据
            $page = input('post.page', 1); //当前页
            $user_model = model('User');
            $totalCount = $user_model->where($map)->count();
            $first_row = ($page-1)*$list_row;
            $field = [
                'id','telephone','head_img','status', 'nickname','create_time', 'last_login_time',
            ];
            $lists = $user_model->where($map)->field($field)->limit($first_row, $list_row)->order('id desc')->select();
            $pageCount = ceil($totalCount/$list_row);

            $data = [
                'list' => $lists ? $lists : [],
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        } else {
            return $this->fetch();
        }
    }


    public function delUser()
    {
        if (request()->isPOST()) {

            $ids = input('post.id');

            if (!$ids) {
                $json_arr = ["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
                ajaxReturn($json_arr);
            }

            $arr = array_unique(explode('-',($ids)));

            $data = [];
            foreach ($arr as $k => $v) {
                $data[$k] = model('User')->where(['id' => $v])->find();
                $del = model('User')->destroy($v);
                if (!$del) {
                    $json_arr = ["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                    ajaxReturn($json_arr);
                }

            }
            $before_json = $data;
            $after_json = [];
            $content = '删除用户';

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);

            $json_arr = ["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
            ajaxReturn($json_arr);
        }
    }

    /**
     * 导出用户
     */
    public function userExport()
    {
        $map['is_del'] = 0;
        $map['is_platform'] = 1;
        #手机号 昵称
        $telephone = request()->post('telephone', '', 'trim');
        if($telephone){
            $map['telephone'] = ['like', "%".$telephone."%"];
        }
        $nickname = request()->post('nickname', '', 'trim');
        if($nickname){
            $map['nickname|realname']=['like', "%".$nickname."%"];
        }
        $status = request()->post('status', 0, 'intval');
        if($status){
            $map['status'] = $status;
        }
        $starttime = request()->post('start_time');
        $endtime = request()->post('end_time');

        $start_time = date('Y-m-d 00:00:00',strtotime($starttime));
        $end_time   = date('Y-m-d 23:59:59',strtotime($endtime));
        if($starttime && $endtime){
            $map['reg_time'] = ['between',[$start_time,$end_time]];
        }else{
            if($starttime){
                $map['reg_time'] = ['gt',$start_time];
            }
            if($endtime){
                $map['reg_time'] = ['lt',$end_time];
            }
        }
        $user_model = model('User');
        $field = 'nickname,realname,telephone,integral,reg_time,last_login_time,type';
        $lists = $user_model->where($map)->field($field)->order('id desc')->select();

        $data_info = [];
        foreach ($lists as $k => $v) {
            $data_info[$k]['nickname'] = $v['nickname'];
            $data_info[$k]['realname'] = $v['realname'];
            $data_info[$k]['telephone'] = $v['telephone'];
            $data_info[$k]['integral'] = $v['integral'];
            $data_info[$k]['reg_time'] = $v['reg_time'];
            $data_info[$k]['last_login_time'] = $v['last_login_time'];
            $data_info[$k]['importance_degree_name'] = UserConstant::reg_source_value($v['type']);
        }

        $headArr = ['用户昵称','用户姓名','用户手机号','积分数','注册时间','最后登陆时间','用户来源'];

        $content = '导出用户信息';
        $before_json = [];
        $after_json = [];

        $this->managerLog($this->manager_id, $content, $before_json, $after_json);


        $this->excelExport('用户信息表', $headArr, $data_info);
    }


    public function userDetail()
    {
        if (request()->isPost()) {
            $user = model('user');
            $id = request()->post('id');
            if (!$id) {
                $json_arr = ["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
                ajaxReturn($json_arr);
            }
            $field = [
                'id','head_img','telephone',
                'nickname','sex',
                'status','create_time',
                'last_login_time','login_num',
            ];
            $user_list = $user->where(['id' => $id])->field($field)->find();
            if ($user_list) {
                $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['user_list' => $user_list]];
                ajaxReturn($json_arr);
            } else {
                $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => ['user_list' => []]];
                ajaxReturn($json_arr);
            }
        }
        return $this->fetch();
    }

    public function userHandle()
    {
        if (request()->isPost()) {
            $user = model('user');
            $id = request()->post('id');
            if (!$id) {
                $json_arr = ["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
                ajaxReturn($json_arr);
            }
            $map = [];
            $map['id'] = $id;
            $map['is_del'] = 0;
            $info = $user->where($map)->find();
            if(!$info){
                ajaxReturn(['status' => 0, 'msg' => '此用户不存在或已删除', 'data' => []]);
            }
            $data = request()->post();
            $data['update_time'] = time();
            $content = '修改用户信息';
            $field = array_keys($data);
            $field[] = 'id';
            $before_json = $user->field($field)->where(['id' =>  $id])->find();
            $result = $user->save($data, ['id' => $id]);
            $data['id'] = $id;
            $after_json = $data;
            if ($result) {

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
                ajaxReturn($json_arr);
            } else {
                $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                ajaxReturn($json_arr);
            }

        }
        return $this->fetch();
    }


    //优惠券列表
    public function userCoupon(){
        if (request()->isPost()) {
            $list_row = input('post.list_row', 10); //每页数据
            $page = input('post.page', 1); //当前页
            $coupon_model = model('coupon');

            $map = ['isdel' => 0, 'status' => 0, 'coupon_receive' => 2];
            $keyword = request()->post('keyword', '', 'trim');
            if($keyword){
                $map['title'] = ['like', "%".$keyword."%"];
            }

            $starttime = request()->post('start_time');
            $endtime = request()->post('end_time');

            $start_time = strtotime(date('Y-m-d 00:00:00',strtotime($starttime)));
            $end_time   = strtotime(date('Y-m-d 23:59:59',strtotime($endtime)));
            if($starttime && $endtime){
                $map['starttime'] = ['gt',$start_time];
                $map['endtime'] = ['lt',$end_time];
            }else{
                if($starttime){
                    $map['starttime'] = ['gt',$start_time];
                }
                if($endtime){
                    $map['endtime'] = ['lt',$end_time];
                }
            }

            $totalCount = $coupon_model->where($map)->count();
            $first_row = ($page-1)*$list_row;
            $field = [
                'id','coupon_type','deduct','coupon_no','title', 'starttime', 'endtime', 'limit_money',
            ];
            $lists = $coupon_model->where($map)->field($field)->limit($first_row, $list_row)->order('id desc')->select();

            $pageCount = ceil($totalCount/$list_row);

            foreach ($lists as &$v) {
                if($v['coupon_type'] == 1){
                    $v['coupontype_name'] = '现金券';
                    $v['price'] = $v['deduct'].'元';
                }else{
                    $v['coupontype_name'] = '折扣券';
                    $v['price'] = $v['deduct'].'%';
                }
                $v['starttime'] = date('Y-m-d', $v['starttime']);
                $v['endtime'] = date('Y-m-d', $v['endtime']);
            }
            $data = [
                'list' => $lists ? $lists : [],
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
            ];
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        } else {
            return $this->fetch();
        }
    }

    //发放优惠券
    public function userCouponSend(){

        $user_id = request()->post("user_id");

        $ids = request()->post("ids");
        if (!$user_id || !$ids) {
            $json_arr = ["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
            ajaxReturn($json_arr);
        }
        $map = [];
        $map['id'] = $user_id;
        $map['is_del'] = 0;
        $user = model('user');
        $info = $user->where($map)->find();
        if(!$info){
            ajaxReturn(['status' => 0, 'msg' => '此用户不存在或已删除', 'data' => []]);
        }
        $arr = array_unique(explode('-',($ids)));

        // echo "<pre>";
        // print_r($coupon);
        // exit;
        foreach ($arr as $v) {
            $coupon = model('coupon')->where(['id' =>  $v])->find();
            $add_arr = [
                'goods_info' => $coupon['goods_info'],
                'use_type' => $coupon['use_type'],
                'des' => $coupon['des'],
                'user_id' => $user_id,
                'coupon_id' => $coupon['id'],
                'action' => 6,
                'add_time' => date("Y-m-d h:i:d"),
                'deduct' => $coupon['deduct'],
                'limit_money' => $coupon['limit_money'],
                'title' => $coupon['title'],
                'canal' => 1,
                'coupon_type' => $coupon['coupon_type'],
                'starttime' => $coupon['starttime'],
                'endtime' => $coupon['endtime'],
            ];

            $coupon_no = UserConstant::USER_COUPON_HEADER.strtoupper(uniqid());
            while(model('coupon_data')->where(["coupon_no"=>$coupon_no])->find()){
                $coupon_no = UserConstant::USER_COUPON_HEADER.strtoupper(uniqid());
            }

            $add_arr['coupon_no'] = $coupon_no;

            $content = '修改用户信息';
            $field = array_keys($add_arr);
            $field[] = 'id';
            $before_json = model('coupon_data')->field($field)->where(['id' =>  $coupon['id']])->find();

            $res = model('coupon_data')->insert($add_arr);
            if (!$res) {
                $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                ajaxReturn($json_arr);
            } else {
                $data['id'] = $coupon['id'];
                $after_json = $data;

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            }
        }

        $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
        ajaxReturn($json_arr);


    }


    public function userSearch()
    {
        $map['is_del'] = 0;
        $map['is_platform'] = 1;
        #手机号 昵称
        $keyword = request()->param('keyword', '', 'trim');
        if($keyword){
            $map['nickname|realname|telephone'] = ['like', "%".$keyword."%"];
        }
        $this->assign('keyword', $keyword);
        $user_model = model('User');

        $field = [
            'id','telephone','head_img','status','is_child',
            'nickname','reg_time','last_login_time',
        ];
        $list = $user_model->where($map)->field($field)->order('id desc')->paginate(10,false,['query'=>request()->param()]);
        $this->assign('list', $list);
        return $this->fetch();
    }
}