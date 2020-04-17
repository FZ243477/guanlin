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

    public function goodsListref()
    {
        $goods_cate=db('goods_cate')->where('delete_time','null')->where('pid',0)->field('id,pid,name')->select();
        foreach ($goods_cate as $k=> $v){
            $children=db('goods_cate')->where('delete_time','null')->where('pid',$v['id'])->field('id,pid,name')->select();
            if($children){
                $cateres[$k]['id']=$v['id'];
                $cateres[$k]['name']=$v['name'];
                $cateres[$k]['children']=$children;
            }else{
                $cateres[$k]['children']=0;
            }
        }
        $this->assign("cateres", $cateres);
        $this->assign("goods_cate", $goods_cate);
        $name = request()->param('keyword');
        $cate_id = request()->param('cate_id');
        $where = [];
        if ($name) {
            $where['a.goods_name'] = ['like', "%{$name}%"];
        }
        if ($cate_id) {
            $where['b.id'] = $cate_id;
        }
        $this->assign('keyword', $name);
        $this->assign('cate_id', $cate_id);
        $goods = Db::name('goods')->alias('a')
            ->field('a.id,a.cate_id,a.goods_name,a.goods_describe,a.goods_price,a.goods_oprice,a.collection_num,a.goods_logo,b.name')
            ->join('goods_cate b', 'b.id=a.cate_id')
            ->where('a.delete_time','null')
            ->order('a.id desc')
            ->where($where)
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign("goods", $goods);
        return $this->fetch();
    }

    //新增商品页面
    public function goods_addrea(){
        $data=input();
        if(isset($data['id'])){
            $edit_goods = Db::name('goods')->alias('a')
                ->field('a.*,b.name')
                ->join('goods_cate b', 'b.id=a.cate_id')
                ->order('sort desc,a.id desc')
                ->where('b.delete_time','null')
                ->where('a.id',$data['id'])
                ->find();
            $detail_tujilogo=Db::name('goods_images')
                ->where('goods_id',$edit_goods['id'])
                ->where('type',0)
                ->field('goods_id,type,logo')
                ->select();
            $detail_xqlogo=Db::name('goods_images')
                ->where('goods_id',$edit_goods['id'])
                ->where('type',1)
                ->field('goods_id,type,logo')
                ->select();
            $edit_goods['type']=1;
            $this->assign("edit_goods", $edit_goods);
            $this->assign("detail_tujilogo", $detail_tujilogo);
            $this->assign("detail_xqlogo", $detail_xqlogo);
        }else{
            $edit_goods=[
                'goods_name'=>'',
                'goods_describe'=>'',
                'cate_id'=>'',
                'goods_price'=>'',
                'goods_oprice'=>'',
                'goods_unit'=>'',
                'goods_logo'=>'',
                'goods_size'=>'',
                'name'=>'',
                'type'=>0,
                'sort'=>0,
                'id'=>'',
            ];
            $this->assign("edit_goods", $edit_goods);
        }
        if(isset($data['type'])){
            $goods_show=$data['type'];
            $this->assign('goods_show',$goods_show);
        }
        $goods_cate=db('goods_cate')->where('delete_time','null')->where('pid',0)->field('id,pid,name')->select();
        foreach ($goods_cate as $k=> $v){
            $children=db('goods_cate')->where('delete_time','null')->where('pid',$v['id'])->field('id,pid,name')->select();
            if($children){
                $cateres[$k]['id']=$v['id'];
                $cateres[$k]['name']=$v['name'];
                $cateres[$k]['children']=$children;
            }else{
                $cateres[$k]['children']=0;
            }
        }
        $this->assign("cateres", $cateres);
        return $this->fetch();
    }

    //新增商品操作 修改商品操作
    public function save_product(){
        if (request()->isPost()) {
            $data = request()->post();
            if (!$data['goods_name']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写商品名称', 'data' => []]);
            }
            if (!$data['goods_describe']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写商品描述', 'data' => []]);
            }
            if (!$data['goods_price']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写商品原价', 'data' => []]);
            }
            if (!$data['goods_oprice']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写商品售价', 'data' => []]);
            }
            if (!$data['goods_unit']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写商品单位', 'data' => []]);
            }
            if (!$data['goods_size']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写商品规格', 'data' => []]);
            }
            if (!$data['brand']) {
                ajaxReturn(['status' => 0, 'msg' => '请选择商品分类', 'data' => []]);
            }
            if (!isset($data['express_logo']) || !$data['express_logo']) {
                ajaxReturn(['status' => 0, 'msg' => '请上传商品logo', 'data' => []]);
            }
            if(!isset($data['multiple_logo']) ||!$data['multiple_logo']){
                ajaxReturn(['status' => 0, 'msg' => '请上传商品图集', 'data' => []]);
            }
            if(!isset($data['detail_logo']) || !$data['detail_logo']){
                ajaxReturn(['status' => 0, 'msg' => '请上传商品图集', 'data' => []]);
            }

            $cate_pid=Db::name('goods_cate')->where('id',$data['brand'])->field('id,pid')->find();
            $save_content=[
                'goods_name'=>$data['goods_name'],
                'goods_describe'=>$data['goods_describe'],
                'goods_price'=>$data['goods_price'],
                'goods_oprice'=>$data['goods_oprice'],
                'goods_unit'=>$data['goods_unit'],
                'goods_size'=>$data['goods_size'],
                'sort'=>$data['sort'],
                'cate_id'=>$data['brand'],
                'goods_logo'=>$data['express_logo'],
                'pid'=>$cate_pid['pid'],

            ];

            if(!$data['editid']){
                $save_content['create_time'] = time();

                $data['create_time'] = time();
                $content = '添加houses_case信息';
                $before_json = [];

                $save = Db::name('goods')->insertGetId($save_content);
                $goods_id = Db::name('goods')->getLastInsID();

                $save_content['id'] = $goods_id;
                $after_json = $save_content;

            } else {
                $save_content['update_time'] = time();

                $content = '修改商品信息';
                $field = array_keys($save_content);
                $field[] = 'id';
                $before_json = Db::name('goods')->field($field)->where(['id' =>  $data['editid']])->find();

                $save = Db::name('goods')->where('id',$data['editid'])->update($save_content);

                $save_content['id'] = $data['editid'];
                $after_json = $save_content;

                $goods_id = $data['editid'];
                $delete_logo = Db::name('goods_images')->where('goods_id',$goods_id)->delete();
            }
            $logo=$data['multiple_logo'];
            foreach($logo as $k=>$v){
                $save_images=[
                    'goods_id'=>$goods_id,
                    'type'=>0,
                    'logo'=>$v
                ];
                $save_logo=Db::name('goods_images')->insertGetId($save_images);
            }
            $detail=$data['detail_logo'];
            foreach($detail as $k=>$v){
                $detail_images=[
                    'goods_id'=>$goods_id,
                    'type'=>1,
                    'logo'=>$v
                ];
                $save_detail=Db::name('goods_images')->insertGetId($detail_images);
            }
            if ($save) {
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }
    
    /**
     * 删除商品 1
     */
    public function del_goods(){
        if (request()->isPost()) {
            $data = input();
            $del_id = explode('-',$data['id']);
            $del = model('goods')->destroy($del_id);
            if ($del) {
                $content = '删除商品';
                $before_json = $del_id;
                $after_json = [];
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }

    // 商品分类列表
    public function goodsCate()
    {
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

        $this->assign('lists', $lists);
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
                ajaxReturn(['status' => 0, 'msg' => "请填写分类名称！"]);
            }
            if ($res) {
//                ajaxReturn(['status' => 0, 'msg' => "类名已存在！"]);
            }

            //$idpid=explode("+",$data['pid']);
            if ($data['id'] > 0) {

                $parid = $m->where(["id" => $data['id'],])->value("pid");
                if ($data['pid'] == $data['id']) {
                    $data['pid'] = 0;
                }
                if ($parid == 0 && $data['pid'] != 0) {
                    ajaxReturn(['status' => 0, 'msg' => "顶级分类无法改变分类！"]);
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
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
    }

    /**
     * 删除商品分类
     */
    public function delCate()
    {
        $id = input("id");
        $m = model("goods_cate");
        if (!$id) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $data = $m->find($id);
        if (!$data) {
            ajaxReturn(['status' => 0, 'msg' => "分类不存在！"]);
        }
        if ($data['pid']) {

            $res = $m->destroy($id);
            if ($res) {
                $before_json = $data;
                $after_json = [];
                $content = '删除商品分类';
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        } else {
            $res1 = $m->destroy($id);
            $id_arr = $m->where(['pid' => $id])->field('id')->select();
            $data['cate_list'] = $id_arr;
            $id_a = [];
            foreach ($id_arr as $k => $v) {
                $id_a[] = $v['id'];
            }
            if ($id_a) {
                $m->destroy($id_a);
            }
            if ($res1 !== false) {
                $before_json = $data;
                $after_json = [];
                $content = '删除商品分类';
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
    }

    /**
     * 操作方案状态
     */
    public function goodsStatus()
    {
        if (request()->isPOST()) {
            $id = request()->post('id');
            $item = request()->post('item');
            if (!$id) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }
            $data =['id' => $id];
            $coupon = model('goods')->where($data)->field($item)->find();
            $result = model('goods')->where($data)->setField($item, 1-$coupon[$item]);
            if($result){
                $content = '修改商品显示状态';
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
}