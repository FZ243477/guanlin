<?php


namespace app\admin\controller;

use app\admin\helper\ManagerHelper;
use app\common\helper\EncryptionHelper;
use app\common\helper\PHPExcelHelper;
use app\common\helper\GoodsHelper;
use app\common\constant\SystemConstant;
use Think\Db;

class Message extends Base
{

    use ManagerHelper;
    use EncryptionHelper;
    use PHPExcelHelper;
    use GoodsHelper;

    public function __construct()
    {
        parent::__construct();
    }


    //提醒信息列表
    public function messageList(){
        $transfer_list=model('message')
            ->field('id,content,type_id')
            ->where('delete_time','null')
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('transfer_list',$transfer_list);
        return $this->fetch();
    }

    //新增中转站收货人
    public function message_add(){
        $data=input();
        if(isset($data['id'])){
            $edit_goods=Db::name('message')
                ->field('id,content,type_id')
                ->where('delete_time','null')
                ->where('id',$data['id'])
                ->find();
            $edit_goods['type']=1;
            $this->assign("edit_goods", $edit_goods);
        }else{
            $edit_goods=[
                'content'=>'',
                'type_id'=>'',
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

    //新增信息操作 添加 修改
    public function save_message(){
        if (request()->isPost()) {
            $data = request()->post();
            if (!$data['content']) {
                ajaxReturn(['status' => 0, 'msg' => '请输入提醒消息内容', 'data' => []]);
            }
            if(!$data['editid']){
                $type_count=model('message')->where('type_id',$data['type_id'])->count();

                if (!$data['type_id']) {
                    ajaxReturn(['status' => 0, 'msg' => '提醒消息内容typeID', 'data' => []]);
                }
                if($type_count !=0){
                    ajaxReturn(['status' => 0, 'msg' => 'typeID不能重复', 'data' => []]);
                }
                $save_content=[
                    'content'=>$data['content'],
                    'type_id'=>$data['type_id'],
                    'create_time'=>time()
                ];
                $save=Db::name('message')->insertGetId($save_content);
                if ($save) {
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
            if(isset($data['editid'])){
                $edit_content=[
                    'content'=>$data['content'],
                    'update_time'=>time()
                ];
                $edit=Db::name('message')->where('id',$data['editid'])->update($edit_content);
                if ($edit) {
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
        }
    }

    //删除信息提示
    public function del_message(){
        if (request()->isPost()) {
            $data=input();
            $del_id=explode('-',$data['id']);
            foreach ($del_id as $k=>$v){
                $del = model('message')->destroy($v);
            }
            if ($del) {
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }
}