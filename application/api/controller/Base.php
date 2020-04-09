<?php
namespace app\api\controller;

use \think\Controller;
use app\common\helper\TokenHelper;

class Base extends Controller{

    use TokenHelper;

    public $user_id;

    public function __construct()
    {
        parent::__construct();

        if (!request()->isPost()) {
            die('非法访问');
        }

        $token = request()->post('token');
        $result = $this->getUserInfoByToken($token);
        if ($result) {
            $this->user_id = $result;

        } else {
            $this->user_id = 0;
            //ajaxReturn(['status' => -1, 'msg' => '无效token']);
        }
    }

    public function get_user_id()
    {

    }



}