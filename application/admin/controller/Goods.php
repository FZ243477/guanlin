<?php


namespace app\admin\controller;

use app\admin\helper\ManagerHelper;
use app\common\helper\EncryptionHelper;
use app\common\helper\PHPExcelHelper;
use app\common\helper\GoodsHelper;
use app\common\constant\SystemConstant;
use Think\Db;

class Goods extends Base
{

    use ManagerHelper;
    use EncryptionHelper;
    use PHPExcelHelper;
    use GoodsHelper;

    public function __construct()
    {
        parent::__construct();
    }
    //物品分类列表
    public function goodsCate(){
        $logistics_cate=Db::name('goods_cate')
            ->field('id,name')
            ->order('id desc')
            ->where('delete_time','null')
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('cate_list',$logistics_cate);
        return $this->fetch();
    }

    // 增加物品分类
    public function addCate()
    {
        if (request()->isPost()) {
            $data = input("post.");
            if (!$data['level_name']) {
                ajaxReturn(["status" => 0, "msg" => "请填写分类名称！"]);
            }
            $res=Db::name('goods_cate')->where('name',$data['level_name'])->find();
            if ($res) {
                ajaxReturn(["status" => 0, "msg" => "类名已存在！"]);
            }
            if(!$data['id']) {

                $max_id=Db::name('goods_cate')->max('id');
                $insert_id=$max_id+1;
                $data['id']=$insert_id;
                $save_content = [
                    'id'=>$data['id'],
                    'name' => $data['level_name'],
                    'create_time' => time()
                ];
                $end = Db::name('goods_cate')->insert($save_content);
            }else{
                $rea=Db::name('goods_cate')->where('id',$data['id'])->find();
                if (!$rea) {
                    ajaxReturn(["status" => 0, "msg" => "此级别不存在！"]);
                }else{
                    $update_content = [
                        'name' => $data['level_name'],
                        'update_time' => time()
                    ];
                    $end = Db::name('goods_cate')->where('id',$data['id'])->update($update_content);
                }
            }
            if ($end) {
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
    }

    /**
     * 删除物品分类
     */
    public function delCate()
    {
        $id = input("id");
        if (!$id) {
            ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $rea=Db::name('goods_cate')->where('id',$id)->find();
        if (!$rea) {
            ajaxReturn(["status" => 0, "msg" => "分类不存在！"]);
        }
        $del = model('goods_cate')->destroy($id);
        if ($del) {
            ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        } else {
            ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
        }
    }
}