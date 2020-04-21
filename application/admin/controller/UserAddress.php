<?php


namespace app\admin\controller;

use app\admin\helper\ManagerHelper;
use app\common\helper\EncryptionHelper;
use app\common\helper\PHPExcelHelper;
use app\common\helper\GoodsHelper;
use app\common\constant\SystemConstant;
use Think\Db;

class UserAddress extends Base
{

    use ManagerHelper;
    use EncryptionHelper;
    use PHPExcelHelper;
    use GoodsHelper;

    public function __construct()
    {
        parent::__construct();
    }


    //中转站收货人列表
    public function addressList(){
        $name = request()->param('keyword');
        $phone = request()->param('phone');
        $where = [];
        if ($name) {
            $where['real_name'] = ['like', "%{$name}%"];
        }
        if ($phone) {
            $where['phone'] = $phone;
        }
        $this->assign('keyword', $name);
        $this->assign('phone', $phone);
        $user_address_list=model('user_address')
            ->field('id,uid,real_name,phone,country,province,city,district,detail')
            ->where($where)
            ->order('id desc')
            ->where('delete_time','null')
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('transfer_list',$user_address_list);
        return $this->fetch();
    }

    //新增中转站收货人
    public function address_add(){
        $data=input();
        if(isset($data['id'])){
            $edit_goods=model('user_address')
                ->field('id,uid,real_name,phone,country,province,city,district,detail')
                ->where('delete_time','null')
                ->where('id',$data['id'])
                ->find();
            $edit_goods['type']=1;
            $this->assign("edit_goods", $edit_goods);
        }else{
            $edit_goods=[
                'uid'=>'',
                'real_name'=>'',
                'phone'=>'',
                'country'=>'',
                'province'=>'',
                'city'=>'',
                'district'=>'',
                'detail'=>'',
                'type'=>0,
                'id'=>'',
            ];
            $this->assign("edit_goods", $edit_goods);
        }
        if(isset($data['type'])){
            $goods_show=$data['type'];
            $this->assign('goods_show',$goods_show);
        }
        return $this->fetch();
    }

    //用户收货人信息操作 添加 修改
    public function save_address(){
        if (request()->isPost()) {
            $data = request()->post();
            if (!$data['name']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写收货人名称', 'data' => []]);
            }
            if (!$data['phone']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写收货人电话', 'data' => []]);
            }
            if (!$data['province']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写省', 'data' => []]);
            }
            if (!$data['city']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写市', 'data' => []]);
            }
            if (!$data['district']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写区', 'data' => []]);
            }
            if (!$data['detail']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写详细地址', 'data' => []]);
            }
            if(!$data['country']){
                $data['country']="China";
            }
            if(!$data['editid']){
                $save_content=[
                    'real_name'=>$data['name'],
                    'phone'=>$data['phone'],
                    'province'=>$data['province'],
                    'country'=>$data['country'],
                    'city'=>$data['city'],
                    'district'=>$data['district'],
                    'detail'=>$data['detail'],
                    'create_time'=>time()
                ];
                $save=Db::name('user_address')->insertGetId($save_content);
                $content="添加用户收货信息";
                $before_json=[];
                $after_json=$save;
                if ($save) {
                    $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
            if(isset($data['editid'])){
                $edit_content=[
                    'real_name'=>$data['name'],
                    'phone'=>$data['phone'],
                    'province'=>$data['province'],
                    'country'=>$data['country'],
                    'city'=>$data['city'],
                    'district'=>$data['district'],
                    'detail'=>$data['detail'],
                    'update_time'=>time()
                ];
                $before_json=Db::name('user_address')->where('id',$data['editid'])->find();
                $edit=Db::name('user_address')->where('id',$data['editid'])->update($edit_content);
                $content="修改用户收货地址信息";
                $after_json=$edit;
                if ($edit) {
                    $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
        }
    }

    //删除收货地址
    public function del_address(){
        if (request()->isPost()) {
            $data=input();
            $del_id=explode('-',$data['id']);
            $date=[];
            foreach ($del_id as $k=>$v){
                $date['$k'] = model('user_address')->where(['id' => $v])->find();
                $del = model('user_address')->destroy($v);
            }
            $content="删除用户收货地址信息";
            $before_json=$date;
            $after_json=[];
            if ($del) {
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }
}