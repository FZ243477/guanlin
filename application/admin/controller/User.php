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
                'id','telephone','head_img','status','is_child',
                'nickname','reg_time','last_login_time',
            ];
            $lists = $user_model->where($map)->field($field)->limit($first_row, $list_row)->order('id desc')->select();

            foreach ($lists as $k => $v) {
                $first_leader = model('user')->where(['first_leader' => $v['id']])->count();
                $lists[$k]['first_leader_num'] = $first_leader?$first_leader:0;
                $second_leader = model('user')->where(['second_leader' => $v['id']])->count();
                $lists[$k]['second_leader_num'] = $second_leader?$second_leader:0;
                $third_leader = model('user')->where(['third_leader' => $v['id']])->count();
                $lists[$k]['third_leader_num'] = $third_leader?$third_leader:0;
            }

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
     * 导入用户
     */
    public function userImport()
    {
        if (request()->isPost()) {
            $user_import_template = '/static/admin/document/user_import_template.xls';
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['user_import_template' => $user_import_template]];
            ajaxReturn($json_arr);
        }

        return $this->fetch();
    }

    public function userImportHandle()
    {
        if (request()->isPost()) {
            header("content-type:text/html;charset=utf-8");
            //上传excel文件
            $file = request()->file('excel');

            if (!$file) {
                $json_arr = ['status' => 0, 'msg' => '缺少导入文件', 'data' => []];
                ajaxReturn($json_arr);
//                $this->error('缺少导入文件');
//                ajaxReturn(['status' => 0, 'msg' => '缺少导入文件', 'data' => []];
            }

            //将文件保存到public/uploads目录下面
            $info = $file->validate(['size' => 1048576, 'ext' => 'xls,xlsx'])->move('./uploads/excel');
            if ($info) {
                //获取上传到后台的文件名
                $fileName = $info->getSaveName();
                //获取文件路径
                $filePath = ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . 'uploads/excel' . DIRECTORY_SEPARATOR . $fileName;
                //获取文件后缀
                $suffix = $info->getExtension();
                //判断哪种类型
                if ($suffix == "xlsx") {
                    $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                } else {
                    $reader = \PHPExcel_IOFactory::createReader('Excel5');
                }
            } else {
                $json_arr = ['status' => 0, 'msg' => '文件过大或格式不正确导致上传失败', 'data' => []];
                ajaxReturn($json_arr);
//                $this->error('文件过大或格式不正确导致上传失败-_-!');
            }
            //载入excel文件
            $excel = $reader->load("$filePath", $encode = 'utf-8');
            //读取第一张表
            $sheet = $excel->getSheet(0);
            //获取总行数
            $row_num = $sheet->getHighestRow();
            //获取总列数
            $col_num = $sheet->getHighestColumn();
            $data = []; //数组形式获取表格数据
            for ($i = 2; $i <= $row_num; $i++) {
                $data[$i]['reg_time'] = date("Y-m-d H:i:s");
                $data[$i]['telephone'] =  (string)$sheet->getCell("A" . $i)->getValue();
                $data[$i]['realname'] =  (string)$sheet->getCell("B" . $i)->getValue();
                $data[$i]['is_platform'] = 1;
                if (!$data[$i]['telephone']
                    && !$data[$i]['realname']
                ){
                    unset($data[$i]);
                }
                //将数据保存到数据库
            }
            $success = [];
            $failure = [];

            foreach ($data as $k => $v) {
                if (!$v['telephone']) {
                    $msg['msg'] = '第'.$k.'行用户，手机号为空，请重新填写';
                    $failure[] = $msg;
                    unset($data[$k]);
                } else if (!$v['realname']) {
                    $msg['msg'] = '第'.$k.'行用户，姓名不能为空，请重新填写';
                    $failure[] = $msg;
                    unset($data[$k]);
                }
            }

            foreach ($data as $k => $v) {
                $user = model('User')->where(['telephone' => $v['telephone']])->field('id')->find();
                if ($user) {
                    $msg['msg'] = '第'.$k.'行用户，手机号为“'.$v['telephone'].'”您已经录入';
                    $failure[] = $msg;
                    unset($data[$k]);
                } else {
                    $map['password'] = $this->md5_encryption('123456a');
                    $map['type'] = 4;
                    $map['reg_time'] = date('Y-m-d H:i:s');
                    $msg['msg'] = '第'.$k.'行用户，手机号为“'.$v['telephone'].'”录入成功';
                    $success[$k] = $msg;
                }
            }

            $result = model('User')->insertAll($data);

            if ($result) {

                $content = '导入用户信息';
                $before_json = [];
                $after_json = $data;

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);

                $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['success' => $success, 'failure' => $failure]];
                ajaxReturn($json_arr);
            } else {
                $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => ['success' => $success, 'failure' => $failure]];
                ajaxReturn($json_arr);
            }

        }
    }

    public function upUser()
    {
        if (request()->isPost()) {
            $id = request()->post('id',0,'intval');
            $user = model('user');

            $map = [];
            $map['id'] = $id;
            $map['is_del'] = 0;
            $info = $user->where($map)->find();
            if(!$info){
                $json_arr = ['status' => 0, 'msg' => '此用户不存在或已删除', 'data' => []];
                ajaxReturn($json_arr);
            }

            //$appkey = getSetting('kujiale', 'appkey');
            //$appsecret = getSetting('kujiale', 'appsecret');;
            //vendor('kujiale.kujiale');
            //$kujiale_obj = new \kujiale($appkey, $appsecret);

            $url = "https://openapi.kujiale.com/v2/user/upgrade?";
            $vcName = $info['nickname'];
            $post_data = [
                'vcName' => $vcName
            ];
            $get_data = [];
            $get_data["appuid"]  = $id; //第三方用户的ID。
            $json_arr = $this->backDataInfo($url, $post_data, 'post', $get_data);

            if ($json_arr['c'] == 0) {
                $res = $user->where($map)->setField('is_child',1);
                if(!$res){
                    $json_arr = ['status' => 0, 'msg' => '此用户升级失败', 'data' => []];
                    ajaxReturn($json_arr);
                }
            } else if ($json_arr['c'] == 100001) {
                $json_arr = ['status' => 0, 'msg' => '此用户未注册酷家乐', 'data' => []];
                ajaxReturn($json_arr);

            } else {
                if ($json_arr['m'] == 'user has already been B-type user') {
                    $res = $user->where($map)->setField('is_child',1);
                    if(!$res){
                        $json_arr = ['status' => 0, 'msg' => '此用户升级失败', 'data' => []];
                        ajaxReturn($json_arr);
                    }
                } else {
                    $json_arr = ['status' => 0, 'msg' => '此用户升级失败', 'data' => []];
                    ajaxReturn($json_arr);
                }
            }

            $json_arr = ['status' => 1, 'msg' => '此用户成功升级', 'data' => []];

            $before_json = ['id' => $id, 'is_child' => 0];
            $after_json = ['id' => $id, 'is_child' => 1];
            $content = '用户升级';

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
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


    public function userAdd()
    {
        if (request()->isPost()) {
            $user = model('user');

            $telephone = request()->post('telephone','','trim');
            $password = request()->post('password');
            $realname = request()->post('realname');
            $nickname = request()->post('nickname');
            $sex = request()->post('sex');

            if(!$telephone){
                $json_arr = ["status"=>0, "msg"=>" 请填写手机号", 'data' => []];
                ajaxReturn($json_arr);
            }

            if(!preg_match("/^1[345789]\d{9}$/", $telephone)){
                $json_arr = ["status"=>0, "msg"=>" 手机号码格式不正确", 'data' => []];
                ajaxReturn($json_arr);
            }

            if(!$password){
                $json_arr = ["status"=>0, "msg"=>" 请填写密码", 'data' => []];
                ajaxReturn($json_arr);
            }
            if(!$realname){
                $json_arr = ["status"=>0, "msg"=>" 请填写姓名", 'data' => []];
                ajaxReturn($json_arr);
            }
            if(!$nickname){
                $json_arr = ["status"=>0, "msg"=>" 请填写昵称", 'data' => []];
                ajaxReturn($json_arr);
            }
            if(!$sex){
                $json_arr = ["status"=>0, "msg"=>" 请选择性别", 'data' => []];
                ajaxReturn($json_arr);
            }
            //判断手机号是否已经添加了
            $map = [];
            $map['telephone'] = $telephone;
            $map['is_del'] = 0;
            $res = $user->where($map)->select();
            if(count($res)){
                $json_arr = ["status"=>0, "msg"=>" 此手机号已经注册了", 'data' => []];
                ajaxReturn($json_arr);
            }
            $code = randStr(6,true);
            while(model('user')->where(["reg_code"=>$code])->find()){
                $code = randStr(6,true);
            }

            $data = [
                'telephone'  =>  $telephone , //手机号
                'realname'  =>  $realname , //真实姓名
                'nickname'  =>  $nickname , //昵称
                'sex'  =>  $sex , //性别 0未知 1男 2女
                'reg_time'  =>  date("Y-m-d H:i:s") , //注册时间
                "reg_code"  => $code,
                'type'     => 5,
            ];
            $data['is_platform'] = 1;
            $data['password'] = $this->md5_encryption($password);
            $data['create_time'] = time();
            $content = '添加用户信息';
            $before_json = [];
            $result =$user->save($data);
            $data['id'] = $user->getLastInsID();
            $after_json = $data;
            if($result){

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $json_arr = ["status"=>1, "msg"=> SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
                ajaxReturn($json_arr);
            }else{
                $json_arr = ["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                ajaxReturn($json_arr);
            }
        } else {
            return $this->fetch();
        }
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
                'nickname','realname','sex',
                'status','email','reg_time',
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