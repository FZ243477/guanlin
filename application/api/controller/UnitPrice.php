<?php
namespace app\api\controller;

use app\common\constant\SystemConstant;
use app\common\helper\VerificationHelper;
use think\Db;

class UnitPrice extends Base
{
    use VerificationHelper;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 收货地址列表
     */
    public function index_list(){
        $field = 'id,type,price';
        $list = model('unit_price')->field($field)->select();
        if($list){
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $list]];
            ajaxReturn($json_arr);
        }else{
            $return_arr = ['status'=>0, 'msg'=>'操作失败','data'=> []];
            exit(json_encode($return_arr));
        }
    }
}