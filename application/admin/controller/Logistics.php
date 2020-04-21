<?php


namespace app\admin\controller;

use app\admin\helper\ManagerHelper;
use app\common\helper\EncryptionHelper;
use app\common\helper\PHPExcelHelper;
use app\common\constant\SystemConstant;
use Think\Db;

class Logistics extends Base
{

    use ManagerHelper;
    use EncryptionHelper;
    use PHPExcelHelper;

    public function __construct()
    {
        parent::__construct();
    }
    //物流公司分类
    public function logisticsCate(){
        $logistics_cate=Db::name('logistics')
            ->field('id,name')
            ->order('id desc')
            ->where('delete_time','null')
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('cate_list',$logistics_cate);
        return $this->fetch();
    }

    // 增加物流公司分类
    public function addCate()
    {
        if (request()->isPost()) {
            $data = input("post.");
            if (!$data['level_name']) {
                ajaxReturn(["status" => 0, "msg" => "请填写分类名称！"]);
            }
            $res=Db::name('logistics')->where('name',$data['level_name'])->where('delete_time','null')->find();
            if ($res) {
                ajaxReturn(["status" => 0, "msg" => "类名已存在！"]);
            }

            if(!$data['id']) {
                $max_id=Db::name('logistics')->max('id');
                $insert_id=$max_id+1;
                $data['id']=$insert_id;
                $save_content = [
                    'id'=>$data['id'],
                    'name' => $data['level_name'],
                    'create_time' => time()
                ];
                $end = Db::name('logistics')->insert($save_content);
                $content="增加分类";
                $before_json=[];
                $after_json=$end;

            }else{
                $rea=Db::name('logistics')->where('id',$data['id'])->find();
                if (!$rea) {
                    ajaxReturn(["status" => 0, "msg" => "此级别不存在！"]);
                }else{
                    $update_content = [
                        'name' => $data['level_name'],
                        'update_time' => time()
                    ];
                    $content="修改分类";
                    $before_json=$rea;
                    $end = Db::name('logistics')->where('id',$data['id'])->update($update_content);
                    $after_json=$end;
                }
            }
            if ($end) {
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
    }

    /**
     * 删除物流公司分类
     */
    public function delCate()
    {
        $id = input("id");
        if (!$id) {
            ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $rea=Db::name('logistics')->where('id',$id)->find();
        if (!$rea) {
            ajaxReturn(["status" => 0, "msg" => "分类不存在！"]);
        }
        $del = model('logistics')->destroy($id);
        if ($del) {
            $content="删除分类";
            $before_json=$rea;
            $after_json=$del;
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        } else {
            ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
        }
    }
}