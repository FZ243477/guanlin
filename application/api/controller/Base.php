<?php

namespace app\api\controller;

use app\common\helper\TokenHelper;
use \think\Controller;


class Base extends Controller
{

    use TokenHelper;

    public $user_id;

    public function __construct()
    {
        parent::__construct();
        //判断哪些控制器方法不需要post请求
        $this->isMethodPost();

        $token = request()->post('token');

        $result = $this->getUserInfoByToken($token);

        if ($result) {
            $this->user_id = $result;
        } else {
            $this->user_id = 0;
        }

        $this->isUserLogin($this->user_id);
    }

    /**
     * 判断哪些控制器方法不需要post请求
     */
    public function isMethodPost()
    {
        $action_arr = [
            'pay' => ['alipay'],
        ];
        $controller_name = strtolower(request()->controller());
        $action_name = strtolower(request()->action());

        $is_post = true;

        foreach ($action_arr as $k => $v) {
            if ($controller_name == $k) {
                if (empty($v) || in_array($action_name, $v)) {
                    $is_post = false;
                }
            }
        }

        if ($is_post == true && !request()->isPost()) {
            die('非法访问');
        }

    }

    /**
     * 判断哪些控制器方法需要判断登录
     * @param $user_id
     */
    public function isUserLogin($user_id)
    {
        $action_arr = [
            'user' => [],
        ];
        $is_login = false;
        $controller_name = strtolower(request()->controller());
        $action_name = strtolower(request()->action());
        foreach ($action_arr as $k => $v) {
            if ($controller_name == $k) {
                if (empty($v) || in_array($action_name, $v)) {
                    $is_login = true;
                }
            }
        }
        if ($is_login == true && $user_id == 0) {
            ajaxReturn(['status' => -1, 'msg' => '无效token']);
        }

    }
}