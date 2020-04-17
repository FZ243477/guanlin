<?php


namespace app\admin\controller;

use app\admin\helper\ManagerHelper;
use app\admin\model\Houses;
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
        $type = request()->param('type', 1);
        if ($type == 1) {
            $order = 'sort desc';
        } else {
            $order = 'hot_sort desc';
        }
        $this->assign('keyword', $name);
        $house_list=Houses::field('id,houses_name,houses_city,is_hot,sort,hot_sort')
            ->where('delete_time','null')
            ->order($order)
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
                ->where('delete_time','null')
                ->where('id',$data['id'])
                ->find();
            $edit_goods['type']=1;
            if($edit_goods['is_hot'] ==1){
                $edit_goods['hot_name']="是";
            }else{$edit_goods['hot_name']="否";}
            $this->assign("edit_goods", $edit_goods);
        }else{
            $edit_goods=[
                'houses_name'=>'',
                'houses_city'=>'',
                'sort'=>'',
                'is_hot'=>'',
                'hot_sort'=>'',
                'type'=>0,
                'id'=>'',
            ];
            $this->assign("edit_goods", $edit_goods);
        }
        if(isset($data['type'])){
            $goods_show=$data['type'];
            $this->assign('goods_show',$goods_show);
        }
        $house_cate=Db::name('houses')->where('delete_time','null')->select();
        $this->assign('house_cate',$house_cate);
        return $this->fetch();
    }

    //新增小区操作 添加 修改
    public function save_xiaoqu(){
        if (request()->isPost()) {
            $data = request()->post();
            if (!$data['houses_name']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写楼盘名称', 'data' => []]);
            }
            if (!$data['houses_city']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写楼盘', 'data' => []]);
            }
            if(!$data['editid']){
                $save_content=[
                    'houses_name'=>$data['houses_name'],
                    'houses_city'=>$data['houses_city'],
                    'sort'=>$data['sort'],
                    'is_hot'=>$data['brand'],
                    'hot_sort'=>$data['hot_sort'],
                    'create_time'=>time()
                ];
                $save=Db::name('houses')->insertGetId($save_content);
                $houses_id = Db::name('houses')->getLastInsID();
                if ($save) {
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
            if(isset($data['editid'])){
                $edit_content=[
                    'houses_name'=>$data['houses_name'],
                    'houses_city'=>$data['houses_city'],
                    'sort'=>$data['sort'],
                    'is_hot'=>$data['brand'],
                    'hot_sort'=>$data['hot_sort'],
                    'update_time'=>time()
                ];
                $edit=Db::name('houses')->where('id',$data['editid'])->update($edit_content);
                if ($edit) {
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
        }
    }

    //删除小区
    public function del_xiaoqu(){
        if (request()->isPost()) {
            $data=input();
            $del_id=explode('-',$data['id']);
            foreach ($del_id as $k=>$v){
                $del = model('houses')->destroy($v);
            }
            if ($del) {
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }
    /**
     * 操作方案状态
     */
    public function status()
    {
        if (request()->isPOST()) {
            $id = request()->post('id');
            $item = request()->post('item');
            if (!$id) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }
            $data =['id' => $id];
            $coupon = model('houses')->where($data)->field($item)->find();
            $result = model('houses')->where($data)->setField($item, 1-$coupon[$item]);
            if($result){
                $content = '修改楼盘显示状态';
                $before_json = ['id' => $id, $item => $coupon[$item]];
                $after_json = ['id' => $id, $item => 1-$coupon[$item]];

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $json_arr = ['status'=>1, 'msg'=> SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => [$item => 1-$coupon[$item]]];
            }else{
                $json_arr = ['status'=>0, 'msg'=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            }
            return $json_arr;
        }
    }
    //户型列表
   public function villageList(){
        $house_cate=Db::name('houses')->where('delete_time','null')->field('id,houses_name')->select();
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
               ->where('b.delete_time','null')
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
       $house_cate=Db::name('houses') ->where('delete_time','null')->select();
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

    //户型分类
    public function villageCate(){
        $c = model("goods_cate");
        $where = [];
        $where['pid'] = 0;
        $goods_cate = $c->where($where)->order('sort desc')->select();
        $list = [];
        foreach ($goods_cate as $key => $item) {
            $list[] = $item;
            $cate_list = model('goods_cate')->field('id,name,pid')->where(['pid' => $item['id']])->order('sort desc')->select();
            foreach ($cate_list as $k => $v) {
                $v['name'] = '&nbsp;&nbsp;|--' . $v['name'];
                $list[] = $v;
            }
        }
        $lists = $c->where($where)->order('sort desc')->paginate(10, false, ['query' => request()->param()]);
        foreach ($lists as $key => $value) {
            $data = $c->where(["pid" => $value['id']])->select();
            foreach ($data as $k => $v) {
                $data[$k]['cate_list'] = $c->where(["pid" => $v['id']])->select();
            }
            $lists[$key]['cate_list'] = $data;
        }

        $this->assign("lists", $lists);
        $this->assign("list", $list);

        return $this->fetch();
    }

    // 增加商品分类
    public function addCate()
    {
        if (request()->isPost()) {
            $data = input("post.");
            if (isset($data['img_url'])) {
                $data['logo_pic'] = $data['img_url'];
                unset($data['img_url']);
            }
            $m = model("goods_cate");
            //$res = $m->where(["name" => $data['name'], "pid" => $data['pid']])->find();
            $res= Db::name('goods_cate')
                ->where(["name" => $data['name'], "pid" => $data['pid']])
                ->find();
            if (!$data['name']) {
                ajaxReturn(["status" => 0, "msg" => "请填写分类名称！"]);
            }

            if ($res) {
                ajaxReturn(["status" => 0, "msg" => "类名已存在！"]);
            }
            //$idpid=explode("+",$data['pid']);
            if ($data['id'] > 0) {
                $parid = $m->where(["id" => $data['id'],])->value("pid");
                if ($data['pid'] == $data['id']) {
                    $data['pid'] = 0;
                }
                if ($parid == 0 && $data['pid'] != 0) {
                    ajaxReturn(["status" => 0, "msg" => "顶级分类无法改变分类！"]);
                }
                $content = '修改商品分类';
                $field = array_keys($data);
                $field[] = 'id';
                $id = $data['id'];
                unset($data['id']);
                $data['update_time'] = date('Y-m-d');
                $before_json = $m->field($field)->where(['id' => $id])->find();
                $res = $m->save($data, ['id' => $id]);
                $data['id'] = $id;
                $after_json = $data;
            } else {
                unset($data['id']);
                $before_json = [];
                $content = '添加商品分类';
                //$res = $m->save($data);
                $max_id=Db::name('goods_cate')->max('id');
                $insert_id=$max_id+1;
                $data['id']=$insert_id;
                $res =Db::name('goods_cate')->insert($data);
                $data['id'] = $m->getLastInsID();
                $after_json = $data;
            }
            if ($res) {
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
    }

}