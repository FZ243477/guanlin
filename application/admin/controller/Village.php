<?php


namespace app\admin\controller;

use app\admin\helper\ManagerHelper;
use app\common\helper\EncryptionHelper;
use app\common\helper\PHPExcelHelper;
use app\common\helper\GoodsHelper;
use app\common\constant\SystemConstant;
use Think\Db;

class Village extends Base
{

    use ManagerHelper;
    use EncryptionHelper;
    use PHPExcelHelper;
    use GoodsHelper;

    public function __construct()
    {
        parent::__construct();
    }

    //小区列表
    public function xiaoquList(){
        $name = request()->param('keyword');
        $where = [];
        if ($name) {
            $where['houses_name'] = ['like', "%{$name}%"];
        }
        $this->assign('keyword', $name);
        $house_list=Db::name('houses')
            ->field('id,houses_name,houses_city,is_hot,sort,hot_sort')
            ->where('delete_time','null')
            ->order('id desc')
            ->where($where)
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('house_list',$house_list);
        return $this->fetch();
    }

    //新增小区页面
    public function xiaoqu_add(){
        $data=input();
        if(isset($data['id'])){
            $edit_goods=Db::name('houses')
                ->field('id,houses_name,houses_city,is_hot,sort,hot_sort')
                ->where('id',$data['id'])
                ->find();
            $edit_goods['type']=1;
            if($edit_goods['is_hot'] ==1){
                $edit_goods['hot_name']="否";
            }else{$edit_goods['hot_name']="是";}
            $this->assign("edit_goods", $edit_goods);
        }else{
            $edit_goods=[
                'name'=>'',
                'area'=>'',
                'space'=>'',
                'style'=>'',
                'logo'=>'',
                'type'=>0,
                'houses_id'=>'',
                'id'=>'',
            ];
            $this->assign("edit_goods", $edit_goods);
        }
        if(isset($data['type'])){
            $goods_show=$data['type'];
            $this->assign('goods_show',$goods_show);
        }
        $house_cate=Db::name('houses')->select();
        $this->assign('house_cate',$house_cate);
        return $this->fetch();
    }

    //户型列表
   public function villageList(){
        $house_cate=Db::name('houses')->select();
       $this->assign('house_cate',$house_cate);
       $name = request()->param('keyword');
       $cate_id = request()->param('cate_id');
       $where = [];
       if ($name) {
           $where['a.name'] = ['like', "%{$name}%"];
       }
       if ($cate_id) {
           $where['b.id'] = $cate_id;
       }
       $this->assign('keyword', $name);
       $this->assign('cate_id', $cate_id);
      $house_list=Db::name('houses_type')->alias('a')
                ->field('a.id,a.houses_id,a.name,a.area,a.space,a.style,a.logo,b.houses_name,
                b.houses_city,b.is_hot,b.sort,b.hot_sort,a.delete_time')
                ->join('houses b', 'b.id=a.houses_id')
                ->where('a.delete_time','null')
                ->order('a.id desc')
                ->where($where)
                ->paginate(10,false,['query'=>request()->param()]);
      $this->assign('house_list',$house_list);
       return $this->fetch();
   }

    //新增户型页面
   public function village_add(){
       $data=input();
       if(isset($data['id'])){
           $edit_goods=Db::name('houses_type')->alias('a')
               ->field('a.id,a.houses_id,a.name,a.area,a.space,a.style,a.logo,b.houses_name,
                b.houses_city,b.is_hot,b.sort,b.hot_sort
                ')
               ->join('houses b', 'b.id=a.houses_id')
               ->where('a.id',$data['id'])
               ->find();
           $edit_goods['type']=1;
           $this->assign("edit_goods", $edit_goods);
       }else{
           $edit_goods=[
               'name'=>'',
               'area'=>'',
               'space'=>'',
               'style'=>'',
               'logo'=>'',
               'type'=>0,
               'houses_id'=>'',
               'id'=>'',
           ];
           $this->assign("edit_goods", $edit_goods);
       }
       if(isset($data['type'])){
           $goods_show=$data['type'];
           $this->assign('goods_show',$goods_show);
       }
       $house_cate=Db::name('houses')->select();
       $this->assign('house_cate',$house_cate);
       return $this->fetch();
   }

    //新增户型操作 添加 修改
    public function save_house(){
        if (request()->isPost()) {
            $data = request()->post();
            if (!$data['name']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写户型名称', 'data' => []]);
            }
            if (!$data['area']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写户型面积', 'data' => []]);
            }
            if (!$data['space']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写户型空间', 'data' => []]);
            }
            if (!$data['style']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写户型风格', 'data' => []]);
            }
            if (!$data['brand']) {
                ajaxReturn(['status' => 0, 'msg' => '请选择户型分类', 'data' => []]);
            }
            if (!$data['express_logo']) {
                ajaxReturn(['status' => 0, 'msg' => '请上传户型logo', 'data' => []]);
            }
            if(!$data['editid']){
                $save_content=[
                    'name'=>$data['name'],
                    'area'=>$data['area'],
                    'space'=>$data['space'],
                    'style'=>$data['style'],
                    'houses_id'=>$data['brand'],
                    'logo'=>$data['express_logo'],
                    'create_time'=>time()
                ];
                $save=Db::name('houses_type')->insertGetId($save_content);
                $houses_id = Db::name('goods')->getLastInsID();
                if ($save) {
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
            if(isset($data['editid'])){
                $edit_content=[
                    'name'=>$data['name'],
                    'area'=>$data['area'],
                    'space'=>$data['space'],
                    'style'=>$data['style'],
                    'houses_id'=>$data['brand'],
                    'logo'=>$data['express_logo'],
                    'update_time'=>time()
                ];
                $edit=Db::name('houses_type')->where('id',$data['editid'])->update($edit_content);
                if ($edit) {
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
        }
    }

    //删除户型
    public function del_houses(){
        if (request()->isPost()) {
            $data=input();
            $del_id=explode('-',$data['id']);
            foreach ($del_id as $k=>$v){
                $del = model('houses_type')->destroy($v);
            }
            if ($del) {
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }


}