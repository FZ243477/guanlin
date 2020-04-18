<?php
namespace app\api\controller;
use app\common\constant\SystemConstant;
use app\common\helper\VerificationHelper;
use Think\Db;

class User extends Base
{
    use VerificationHelper;

    public function __construct()
    {
        parent::__construct();
    }
    //实名登记
    public function edituid(){
        $map['id'] = 1;
        //$data = input();
        $data['nickname'] = request()->post('nickname', 0);
        $data['id_type'] = request()->post('id_type', 0);
        $data['id_card'] = request()->post('id_card', 0);
        if($data['nickname']==''){
            $return_arr = ['status'=>0, 'msg'=>'请填写姓名','data'=> []];
            exit(json_encode($return_arr));
        }
        if($data['id_card']==''){
            $return_arr = ['status'=>0, 'msg'=>'请填写身份证号码','data'=> []];
            exit(json_encode($return_arr));
        }
        $save_content=[
            'nickname'=>$data['nickname'],
            'id_type'=>$data['id_type'],
            'id_card'=>$data['id_card'],
        ];
        $save = model('user')->where($map)->isUpdate($save_content);
        if($save){
            $return_arr = ['status'=>1, 'msg'=>'修改成功','data'=> []];
            exit(json_encode($return_arr));
        }else{
            $return_arr = ['status'=>0, 'msg'=>'修改失败','data'=> []];
            exit(json_encode($return_arr));
        }
    }
}