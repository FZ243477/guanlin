<?php


namespace app\admin\controller;

use app\admin\helper\ManagerHelper;
use app\common\helper\EncryptionHelper;
use app\common\helper\PHPExcelHelper;
use app\common\helper\GoodsHelper;
use app\common\constant\SystemConstant;
use Think\Db;

class Transfer extends Base
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
    public function transferList(){
        $name = request()->param('keyword');
        $where = [];
        if ($name) {
            $where['name'] = ['like', "%{$name}%"];
        }
        $this->assign('keyword', $name);
        $transfer_list=Db::name('transfer_station')
            ->field('id,name,telephone,province,city,district,address,detailaddress,is_default')
            ->where($where)
            ->where('delete_time','null')
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('transfer_list',$transfer_list);
        return $this->fetch();
    }

    //新增中转站收货人
    public function transfer_add(){
        $data=input();
        if(isset($data['id'])){
            $edit_goods=Db::name('transfer_station')
                ->field('id,name,telephone,address,province,city,district,detailaddress,is_default')
                ->where('delete_time','null')
                ->where('id',$data['id'])
                ->find();
            $edit_goods['type']=1;
            if($edit_goods['is_default'] ==1){
                $edit_goods['hot_name']="是";
            }else{$edit_goods['hot_name']="否";}
            $this->assign("edit_goods", $edit_goods);
        }else{
            $edit_goods=[
                'name'=>'',
                'telephone'=>'',
                'address'=>'',
                'province'=>'',
                'city'=>'',
                'district'=>'',
                'detailaddress'=>'',
                'is_default'=>'0',
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

    //新增中转站收货人信息操作 添加 修改
    public function save_transfer(){
        if (request()->isPost()) {
            $data = request()->post();
            if (!$data['name']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写收货人名称', 'data' => []]);
            }
            if (!$data['telephone']) {
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
            if (!$data['detailaddress']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写详细地址', 'data' => []]);
            }
            if(!$data['editid']){
                $save_content=[
                    'name'=>$data['name'],
                    'telephone'=>$data['telephone'],
                    'province'=>$data['province'],
                    'city'=>$data['city'],
                    'district'=>$data['district'],
                    'address'=>$data['province'].$data['city'].$data['district'],
                    'detailaddress'=>$data['detailaddress'],
                    'is_default'=>$data['brand'],
                    'create_time'=>time()
                ];
                $save=Db::name('transfer_station')->insertGetId($save_content);
                $houses_id = Db::name('transfer_station')->getLastInsID();
                if ($save) {
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
            if(isset($data['editid'])){
                $edit_content=[
                    'name'=>$data['name'],
                    'telephone'=>$data['telephone'],
                    'province'=>$data['province'],
                    'city'=>$data['city'],
                    'district'=>$data['district'],
                    'address'=>$data['province'].$data['city'].$data['district'],
                    'detailaddress'=>$data['detailaddress'],
                    'update_time'=>time()
                ];
                $before_json=Db::name('transfer_station')->where('id',$data['editid'])->find();
                $edit=Db::name('transfer_station')->where('id',$data['editid'])->update($edit_content);
                $content="修改中转站收货人信息";
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

    //中转站收货人列表
    public function customerList(){
        $name = request()->param('keyword');
        $where = [];
        if ($name) {
            $where['title'] = ['like', "%{$name}%"];
        }
        $this->assign('keyword', $name);
        $transfer_list=Db::name('customer')
            ->where($where)
            ->where('delete_time','null')
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('transfer_list',$transfer_list);
        return $this->fetch();
    }

    //新增中转站收货人
    public function customer_add(){
        $data=input();
        if(isset($data['id'])){
            $edit_goods=Db::name('customer')
                ->where('delete_time','null')
                ->where('id',$data['id'])
                ->find();
            $edit_goods['type']=1;
            $this->assign("edit_goods", $edit_goods);
        }else{
            $edit_goods=[
                'title'=>'',
                'back_logo'=>'',
                'qrcode'=>'',
                'qrname'=>'',
                'qrphone'=>'',
                'delivery_days'=>'',
                'enddelivery_days'=>'',
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

    //新增中转站收货人信息操作 添加 修改
    public function save_customer(){
        if (request()->isPost()) {
            $data = request()->post();

            if (!$data['qrname']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写微信号', 'data' => []]);
            }
            if (!$data['qrphone']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写客服电话', 'data' => []]);
            }
            if (!$data['title']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写标题', 'data' => []]);
            }
            if (!$data['express_logo']) {
                ajaxReturn(['status' => 0, 'msg' => '请上传二维码', 'data' => []]);
            }
            if (!$data['delivery_days']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写预计发货天数', 'data' => []]);
            }
            if (!$data['enddelivery_days']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写预计收货天数', 'data' => []]);
            }
            if(!$data['editid']){
                $save_content=[
                    'title'=>$data['title'],
                    'qrcode'=>$data['express_logo'],
                    'qrphone'=>$data['qrphone'],
                    'qrname'=>$data['qrname'],
                    'delivery_days'=>$data['delivery_days'],
                    'enddelivery_days'=>$data['enddelivery_days'],
                    'create_time'=>time()
                ];
                $save=Db::name('customer')->insertGetId($save_content);
                if ($save) {
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
            if(isset($data['editid'])){
                $edit_content=[
                    'title'=>$data['title'],
                    'qrcode'=>$data['express_logo'],
                    'qrphone'=>$data['qrphone'],
                    'qrname'=>$data['qrname'],
                    'delivery_days'=>$data['delivery_days'],
                    'enddelivery_days'=>$data['enddelivery_days'],
                    'update_time'=>time()
                ];
                $before_json=Db::name('customer')->where('id',$data['editid'])->find();
                $edit=Db::name('customer')->where('id',$data['editid'])->update($edit_content);
                $content="修改客服中心信息";
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
}