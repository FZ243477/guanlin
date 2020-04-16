<?php


namespace app\admin\controller;

use app\admin\helper\ManagerHelper;
use app\common\helper\EncryptionHelper;
use app\common\helper\PHPExcelHelper;
use app\common\constant\SystemConstant;
use Think\Db;

class Designer extends Base
{

    use ManagerHelper;
    use EncryptionHelper;
    use PHPExcelHelper;

    public function __construct()
    {
        parent::__construct();
    }

    public function designerList(){
        $designer_cate=Db::name('houses_designer_level')->select();
        $this->assign('designer_cate',$designer_cate);
        $name = request()->param('keyword');
        $cate_id = request()->param('cate_id');
        $where = [];
        if ($name) {
            $where['a.designer_name'] = ['like', "%{$name}%"];
        }
        if ($cate_id) {
            $where['b.id'] = $cate_id;
        }
        $this->assign('keyword', $name);
        $this->assign('cate_id', $cate_id);
        $designer_list=Db::name('houses_designer')->alias('a')
            ->field('a.id,a.designer_name,a.designer_logo,a.background_logo,a.designer_describe,a.telephone,
            a.collection_num,a.city,a.exp,a.level_id,b.level_name ')
            ->join('houses_designer_level b', 'b.id=a.level_id')
            ->where('a.delete_time','null')
            ->order('a.id desc')
            ->where($where)
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('designer_list',$designer_list);
        return $this->fetch();
    }
    //新增户型页面
    public function designer_add(){
        $data=input();
        if(isset($data['id'])){
            $edit_goods=Db::name('houses_designer')->alias('a')
                ->field('a.id,a.designer_name,a.designer_logo,a.background_logo,a.designer_describe,a.telephone,
                a.city,a.exp,a.level_id,b.id,b.level_name ')
                ->join('houses_designer_level b', 'b.id=a.level_id')
                ->where('a.id',$data['id'])
                ->find();
            $edit_goods['type']=1;
            $this->assign("edit_goods", $edit_goods);
        }else{
            $edit_goods=[
                'designer_name'=>'',
                'designer_describe'=>'',
                'telephone'=>'',
                'city'=>'',
                'exp'=>'',
                'designer_logo'=>'',
                'background_logo'=>'',
                'type'=>0,
                'level_id'=>'',
                'id'=>'',
            ];
            $this->assign("edit_goods", $edit_goods);
        }
        if(isset($data['type'])){
            $goods_show=$data['type'];
            $this->assign('goods_show',$goods_show);
        }
        $house_cate=Db::name('houses_designer_level')->select();
        $this->assign('house_cate',$house_cate);
        return $this->fetch();
    }

    //新增户型操作 添加 修改
    public function save_designer(){
        if (request()->isPost()) {
            $data = request()->post();
            if (!$data['name']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写设计师姓名', 'data' => []]);
            }
            if (!$data['designer_describe']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写描述', 'data' => []]);
            }
            if (!$data['telephone']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写手机号码', 'data' => []]);
            }
            if (!$data['city']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写地址', 'data' => []]);
            }
            if (!$data['exp']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写工作经验', 'data' => []]);
            }
            if (!$data['brand']) {
                ajaxReturn(['status' => 0, 'msg' => '请选择设计师级别', 'data' => []]);
            }
            if (!$data['designer_logo']) {
                ajaxReturn(['status' => 0, 'msg' => '请上传设计师logo', 'data' => []]);
            }
            if (!$data['background_logo']) {
                ajaxReturn(['status' => 0, 'msg' => '请上传设计师背景图logo', 'data' => []]);
            }
            if(!$data['editid']){
                $save_content=[
                    'designer_name'=>$data['name'],
                    'designer_describe'=>$data['designer_describe'],
                    'telephone'=>$data['telephone'],
                    'city'=>$data['city'],
                    'exp'=>$data['exp'],
                    'level_id'=>$data['brand'],
                    'designer_logo'=>$data['designer_logo'],
                    'background_logo'=>$data['background_logo'],
                    'create_time'=>time()
                ];
                $save=Db::name('houses_designer')->insertGetId($save_content);
                $houses_id = Db::name('goods')->getLastInsID();
                if ($save) {
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
            if(isset($data['editid'])){
                $edit_content=[
                    'designer_name'=>$data['name'],
                    'designer_describe'=>$data['designer_describe'],
                    'telephone'=>$data['telephone'],
                    'city'=>$data['city'],
                    'exp'=>$data['exp'],
                    'level_id'=>$data['brand'],
                    'designer_logo'=>$data['designer_logo'],
                    'background_logo'=>$data['background_logo'],
                    'update_time'=>time()
                ];
                $edit=Db::name('houses_designer')->where('id',$data['editid'])->update($edit_content);
                if ($edit) {
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
        }
    }

    public function del_designer(){
        if (request()->isPost()) {
            $data=input();
            $del_id=explode('-',$data['id']);
            foreach ($del_id as $k=>$v){
                $del = model('houses_designer')->destroy($v);
            }
            if ($del) {
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }

}