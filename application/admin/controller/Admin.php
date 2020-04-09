<?php

namespace app\admin\controller;

use \think\Controller;
use app\common\helper\EncryptionHelper;
use app\admin\helper\ManagerHelper;
use app\common\constant\SystemConstant;

class Admin extends Controller
{
    use EncryptionHelper;
    use ManagerHelper;



    //登录页面
    public function login(){
        if (request()->isPost()) {
            $title = getSetting('system.title');
            $header_logo = getSetting('system.header_logo');
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['title' => $title, 'header_logo' => $header_logo]];
            ajaxReturn($json_arr);
        }
        return $this->fetch('login');
    }

    /**
     * 后台登陆
     */
    public function checkloginajax()
    {
        $data = [];
        $data['telephone'] = trim(request()->post('telephone'));
        if($data['telephone']) {
            $data['password'] = $this->md5_encryption(request()->post('password'));
            $rs = model('manager')->where($data)->find();
            $data = [];
            if($rs){
                if($rs['status']==1){

                    $data_log = [
                        'last_login_time' => date('Y-m-d H:i:s'),
                        'login_num' => $rs['login_num'] + 1,
                    ];
                    session('manager_id', $rs['id']);
                    model('manager')->save($data_log, ['id' => $rs['id']]);

                    $before_json = [];
                    $after_json = [];
                    $content = '登录';

                    $this->managerLog($rs['id'], $content, $before_json, $after_json);
                    session('manager_id', $rs['id']);
                    $data['msg']   =   '登录成功咯~'; // 提示信息内容
                    $data['status'] =   1;  // 状态 如果是success是1 error 是0
                    $data['data']    =   '';
                    ajaxReturn($data); // 登陆成功
                }else{
                    $data['msg']   =   '帐号禁用~'; // 提示信息内容
                    $data['status'] =   0;  // 状态 如果是success是1 error 是0
                    $data['data']    =   '';
                    ajaxReturn($data); // 禁用
                }
            }else{
                $data['msg']   =   '帐号或者密码错误~'; // 提示信息内容
                $data['status'] =   0;  // 状态 如果是success是1 error 是0
                $data['data']    =   '';
                ajaxReturn($data); // 用户名密码错误
            }
        }
    }



    //找回密码操作
   /* public function retrievepwd(){
        if(request()->isPost()){

            $data = request()->post();
            $telephone  = $data['telephone'];
            if(!preg_match("/^1[345789]\d{9}$/", $telephone)){
                echo json_encode(array("status" =>'0', "info" =>'手机号格式错误'));
            }

            if(!preg_match("/^(?![0-9]+$)[0-9A-Za-z]{6,18}$/", $data['password'])){
                $return = array(
                    "status"   => '0',
                    "info"  => "密码格式不正确！"
                );
                echo json_encode($return);
            }

            if($data['password'] != $data['repassword']){
                echo json_encode(array('status'=>0,'info'=>'两次密码必须一致！'));
            }


            $find = model('manager')->where(array('telephone'=>$telephone,'is_del'=>0))->order('id DESC')->find();
            if(!$find){
                echo json_encode(array("status" => 0, "info" => "您还没有开店呢!"));
            }

            if($find['password'] == MD5($data['password'])){
                echo json_encode(array("status" => 0, "info" => "您的密码与原密码重复!"));
            }

            $data = array(
                'password' => MD5($data['password'])
            );
            $find1 = model('manager')->where(array('telephone'=>$telephone,'is_del'=>0))->order('id DESC')->save($data);
            if($find1) {
                echo json_encode(array('status'=>1,'info'=>'您的供应商找回密码成功!'));
            }else{
                echo json_encode(array('status'=>0,'info'=>'您的供应商找回密码失败!'));
            }
        }
        $this->display();
    }*/

    public function logout()
    {
        session('manager_id', null);
        $this->redirect(url('Admin/login'));
    }
}