<?php


namespace app\admin\controller;

use app\admin\helper\ManagerHelper;
use app\common\constant\OrderConstant;
use app\common\helper\EncryptionHelper;
use app\common\helper\PHPExcelHelper;
use app\common\helper\GoodsHelper;
use app\common\constant\SystemConstant;
use app\common\constant\GoodsConstant;
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

    public function goodsListrea()
    {
        $data=input();
        $keyword = request()->param('keyword');
        $this->assign('keyword', $keyword);
        if($keyword){
            dump($keyword);exit;
        }
//        $name=$data['name'];
//        $cate_id=$data['cate'];
//        $where=[];
//        if($name!=""){
//            $where['a.goods_name']=array('like',"$name%");
//        }else{}
//        if($cate_id!=""){
//            $where['b.id']=$cate_id;
//        }else{}
//        $goods = Db::name('goods')->alias('a')
//            ->field('a.id,a.cate_id,a.goods_name,a.goods_describe,a.goods_price,a.goods_oprice,a.collection_num,a.goods_logo,b.name')
//            ->join('goods_cate b', 'b.id=a.cate_id')
//            //->whereLike('a.goods_name',"%".$name."%")
//            ->order('a.id desc')
//            ->where($where)
//            ->select();
//        $list = [];
//        foreach ($goods as $v => $k) {
//            $list[$v]['id'] = $k['id'];
//            $list[$v]['goods_name'] = $k['goods_name'];
//            $list[$v]['goods_describe'] = $k['goods_describe'];
//            $list[$v]['cate_name'] = $k['name'];
//            $list[$v]['goods_price'] = $k['goods_price'];
//            $list[$v]['goods_oprice'] = $k['goods_oprice'];
//            $list[$v]['collection_num'] = $k['collection_num'];
//            $list[$v]['goods_logo'] = $k['goods_logo'];
//        }
//        $this->assign("list", $list);

        $goods_cate=db('goods_cate')->where('pid',0)->field('id,pid,name')->select();
        foreach ($goods_cate as $k=> $v){
            $children=db('goods_cate')->where('pid',$v['id'])->field('id,pid,name')->select();
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

    public function goodsListref()
    {
        $banner_model = model('banner');

        $goods_cate=db('goods_cate')->where('pid',0)->field('id,pid,name')->select();
        foreach ($goods_cate as $k=> $v){
            $children=db('goods_cate')->where('pid',$v['id'])->field('id,pid,name')->select();
            if($children){
                $cateres[$k]['id']=$v['id'];
                $cateres[$k]['name']=$v['name'];
                $cateres[$k]['children']=$children;
            }else{
                $cateres[$k]['children']=0;
            }
        }
        $this->assign("cateres", $cateres);
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
        $goods_model = model('goods');
        $goods = Db::name('goods')->alias('a')
            ->field('a.id,a.cate_id,a.goods_name,a.goods_describe,a.goods_price,a.goods_oprice,a.collection_num,a.goods_logo,b.name')
            ->join('goods_cate b', 'b.id=a.cate_id')
            ->order('a.id desc')
            ->where($where)
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign("goods", $goods);
        //dump($goods);exit;
       // $goods = $goods ? $goods->toArray() : [];

        return $this->fetch();
    }

    public function search(){
        if(request()->isPost()) {
            $data=input();
            $name=$data['name'];
            $cate_id=$data['cate'];
            $where=[];
            if($name!=""){
                $where['a.goods_name']=array('like',"$name%");
            }else{}
            if($cate_id!=""){
                $where['b.id']=$cate_id;
            }else{}
                $goods = Db::name('goods')->alias('a')
                    ->field('a.id,a.cate_id,a.goods_name,a.goods_describe,a.goods_price,a.goods_oprice,a.collection_num,a.goods_logo,b.name')
                    ->join('goods_cate b', 'b.id=a.cate_id')
                    //->whereLike('a.goods_name',"%".$name."%")
                    ->order('a.id desc')
                    ->where($where)
                    ->select();
            $list = [];
            foreach ($goods as $v => $k) {
                $list[$v]['id'] = $k['id'];
                $list[$v]['goods_name'] = $k['goods_name'];
                $list[$v]['goods_describe'] = $k['goods_describe'];
                $list[$v]['cate_name'] = $k['name'];
                $list[$v]['goods_price'] = $k['goods_price'];
                $list[$v]['goods_oprice'] = $k['goods_oprice'];
                $list[$v]['collection_num'] = $k['collection_num'];
                $list[$v]['goods_logo'] = $k['goods_logo'];
            }
            $this->assign("list", $list);
            return $this->success('success','',$list);
        }
    }

    //新增商品页面
    public function goods_addrea(){
        $data=input();
        if(isset($data['id'])){
            $edit_goods = Db::name('goods')->alias('a')
                ->field('a.id,a.cate_id,a.goods_name,a.goods_describe,a.goods_price,a.goods_oprice,a.sort,
                a.collection_num,a.goods_logo,b.name')
                ->join('goods_cate b', 'b.id=a.cate_id')
                ->order('sort desc,a.id desc')
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
                'collection_num'=>'',
                'goods_logo'=>'',
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
        $goods_cate=db('goods_cate')->where('pid',0)->field('id,pid,name')->select();
        foreach ($goods_cate as $k=> $v){
            $children=db('goods_cate')->where('pid',$v['id'])->field('id,pid,name')->select();
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
            if (!$data['collection_num']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写商品数量', 'data' => []]);
            }
            if (!$data['brand']) {
                ajaxReturn(['status' => 0, 'msg' => '请选择商品分类', 'data' => []]);
            }
            if (!$data['express_logo']) {
                ajaxReturn(['status' => 0, 'msg' => '请上传商品logo', 'data' => []]);
            }
            if(!$data['multiple_logo']){
                ajaxReturn(['status' => 0, 'msg' => '请上传商品图集', 'data' => []]);
            }
            if(!$data['detail_logo']){
                ajaxReturn(['status' => 0, 'msg' => '请上传商品图集', 'data' => []]);
            }
            if(!$data['editid']){
                $cate_pid=Db::name('goods_cate')->where('id',$data['brand'])->field('id,pid')->find();
            $save_content=[
                'goods_name'=>$data['goods_name'],
                'goods_describe'=>$data['goods_describe'],
                'goods_price'=>$data['goods_price'],
                'goods_oprice'=>$data['goods_oprice'],
                'collection_num'=>$data['collection_num'],
                'sort'=>$data['sort'],
                'cate_id'=>$data['brand'],
                'goods_logo'=>$data['express_logo'],
                'pid'=>$cate_pid['pid'],
                'create_time'=>time()
            ];
            $save=Db::name('goods')->insertGetId($save_content);
            $goods_id = Db::name('goods')->getLastInsID();
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
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
            }
            if(isset($data['editid'])){
                $edit_content=[
                    'goods_name'=>$data['goods_name'],
                    'goods_describe'=>$data['goods_describe'],
                    'goods_price'=>$data['goods_price'],
                    'goods_oprice'=>$data['goods_oprice'],
                    'collection_num'=>$data['collection_num'],
                    'sort'=>$data['sort'],
                    'cate_id'=>$data['brand'],
                    'goods_logo'=>$data['express_logo'],
                    'update_time'=>time()
                ];
                $edit=Db::name('goods')->where('id',$data['editid'])->update($edit_content);
                if ($edit) {
                    ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
                } else {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
            }
        }
    }

    public function del_goods(){
        if (request()->isPost()) {
            $data=input();
            $del=Db::name('goods')->where('id',$data['id'])->delete();
            if ($del) {
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }
    public function goodsList()
    {

        if (request()->isPost()) {
            $map = [];
            $status = request()->post('status', 0, 'intval');
            $type = request()->post('type', 0, 'intval');
            $audit = request()->post('audit', -1, 'intval');

            $map0 = [];

            $map1 = ['is_del' => 0, 'is_sale' => 1, 'stores' => ['gt', 0]];
            $map2 = ['is_del' => 0, 'is_sale' => 1, 'stores' => 0];
            $map3 = ['is_del' => 0, 'is_sale' => 0];
            $map4 = ['is_del' => 1];
            $map5 = ['is_audit' => 0];

            if ($type == 1) {
                if ($audit != -1) {
                    $map0['is_audit'] = $audit;
                    $map1['is_audit'] = $audit;
                    $map2['is_audit'] = $audit;
                    $map3['is_audit'] = $audit;
                    $map4['is_audit'] = $audit;
                    $map['is_audit'] = $audit;
                }
            } else {
                $map0['is_audit'] = 0;
                $map1['is_audit'] = 0;
                $map2['is_audit'] = 0;
                $map3['is_audit'] = 0;
                $map4['is_audit'] = 0;
                $map['is_audit'] = 0;
            }

            if ($status == 0) {//全部
            } elseif ($status == 1) {//出售中
                $map = $map1;
            } elseif ($status == 2) {//已售罄
                $map = $map2;
            } elseif ($status == 3) {//仓库中
                $map = $map3;
            } elseif ($status == 4) {//回收站
                $map = $map4;
            }

            $keyword = request()->post('keyword', '', 'trim');

            if ($keyword) {
                $map0['goods_name|goods_code'] = ['like', "%$keyword%"];
                $map1['goods_name|goods_code'] = ['like', "%$keyword%"];
                $map2['goods_name|goods_code'] = ['like', "%$keyword%"];
                $map3['goods_name|goods_code'] = ['like', "%$keyword%"];
                $map4['goods_name|goods_code'] = ['like', "%$keyword%"];
                $map5['goods_name|goods_code'] = ['like', "%$keyword%"];
                $map['goods_name|goods_code'] = ['like', "%$keyword%"];
            }

            //商品分类
            $cate = request()->post('cate');

            if ($cate) {
                $map0['cate_id|cate_two_id'] = $cate;
                $map1['cate_id|cate_two_id'] = $cate;
                $map2['cate_id|cate_two_id'] = $cate;
                $map3['cate_id|cate_two_id'] = $cate;
                $map4['cate_id|cate_two_id'] = $cate;
                $map5['cate_id|cate_two_id'] = $cate;
                $map['cate_id|cate_two_id'] = $cate;
            }
            //商品分类
            $brand = request()->post('brand');

            if ($brand) {
                $map0['brand_id|brand_two_id'] = $brand;
                $map1['brand_id|brand_two_id'] = $brand;
                $map2['brand_id|brand_two_id'] = $brand;
                $map3['brand_id|brand_two_id'] = $brand;
                $map4['brand_id|brand_two_id'] = $brand;
                $map5['brand_id|brand_two_id'] = $brand;
                $map['brand_id|brand_two_id'] = $brand;
            }
            $id = request()->post('id', '', 'trim');

            if ($id) {
                $map0['id'] = $id;
                $map1['id'] = $id;
                $map2['id'] = $id;
                $map3['id'] = $id;
                $map4['id'] = $id;
                $map5['id'] = $id;
                $map['id'] = $id;
            }

            $goods_model = model('Goods');

            $count0 = $goods_model->where($map0)->count();
            //出售中
            $count1 = $goods_model->where($map1)->count();

            //已售馨
            $count2 = $goods_model->where($map2)->count();

            // 仓库中
            $count3 = $goods_model->where($map3)->count();

            // 回收站
            $count4 = $goods_model->where($map4)->count();

            //待审核
            $count5 = $goods_model->where($map5)->count();

            $list_row = input('post.list_row', 10); //每页数据
            $page = input('post.page', 1); //当前页

            $totalCount = $goods_model->where($map)->count();
            $first_row = ($page - 1) * $list_row;
            $field = [
                'id', 'oprice','goods_name', 'brand_id', 'goods_code', 'goods_logo', 'price', 'cost_price','p_price', 'stores', 'sort', 'is_sale', 'is_new', 'is_hot', 'is_del', 'is_recommend', 'is_audit'
            ];
            $lists = $goods_model->where($map)->field($field)->limit($first_row, $list_row)->order('sort desc, id desc')->select();

            foreach ($lists as $k => $v) {
                $lists[$k]['sku_info'] = model('spec_goods_price')->where(['goods_id' => $v['id']])->select();
            }
            $pageCount = ceil($totalCount / $list_row);
            //商品分类
            $goods_cate = model('goods_cate')->field('id,classname,pid')->where(['pid' => 0, 'status' => '1'])->order('sort desc')->select();
            $goods_cate_new = [];
            foreach ($goods_cate as $key => $item) {
                $goods_cate_new[] = $item;
                $cate_list = model('goods_cate')->field('id,classname,pid')->where(['pid' => $item['id'], 'status' => '1'])->order('sort desc')->select();
                foreach ($cate_list as $k => $v) {
                    $v['classname'] = '&nbsp;&nbsp;|--' . $v['classname'];
                    $goods_cate_new[] = $v;
                }
            }
            $manager_cate_id = model('manager')->where(['id' => $this->manager_id])->value('manager_cate_id');
            if ($manager_cate_id <= 3) {
                $is_cost_price = 1;
            } else {
                $is_cost_price = 0;
            }
            $data = [
                'list' => $lists ? $lists : [],
                'pageCount' => $pageCount ? $pageCount : 0,
                'totalCount' => $totalCount ? $totalCount : 0,
                'count0' => $count0 ? $count0 : 0,
                'count1' => $count1 ? $count1 : 0,
                'count2' => $count2 ? $count2 : 0,
                'count3' => $count3 ? $count3 : 0,
                'count4' => $count4 ? $count4 : 0,
                'count5' => $count5 ? $count5 : 0,
                'goods_cate_new' => $goods_cate_new ? $goods_cate_new : [],
                'is_cost_price' => $is_cost_price,
            ];
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        } else {
            return $this->fetch();
        }
    }
    /**
     * 商品状态修改
     */
    public function goodsStatus()
    {
        if (request()->isPOST()) {
            $id = request()->post('id');
            $item = request()->post('item');
            $type = request()->post('type');
            $val = request()->post('val');
            if (!$id || !$item) {
                exit(json_encode(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]));
            }

            $data = ['id' => $id];

            $goods = model('goods')->where($data)->field($item)->find();

            $result = model('goods')->where($data)->setField($item, 1 - $goods[$item]);
            $new_item = 1 - $goods[$item];
            $before_json = ['id' => $id, $item => $goods[$item]];
            $after_json = ['id' => $id, $item => $new_item];


            if ($result) {
                $content = '修改商品状态';

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $json_arr = ["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => [$item => $new_item]];
                ajaxReturn($json_arr);
            } else {
                $json_arr = ["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                ajaxReturn($json_arr);
            }
        }
    }

    /**
     * 删除商品
     */
    public function delGoods()
    {
        if (request()->isPOST()) {

            $ids = input('post.id');

            if (!$ids) {
                $json_arr = ["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
                ajaxReturn($json_arr);
            }

            $arr = array_unique(explode('-', ($ids)));

            $data = [];
            foreach ($arr as $k => $v) {
                $data[$k] = model('Goods')->where(['id' => $v])->find();
                $del = model('Goods')->destroy($v);
                if (!$del) {
                    $json_arr = ["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                    ajaxReturn($json_arr);
                }

            }
            $before_json = $data;
            $after_json = [];
            $content = '删除商品';

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);

            $json_arr = ["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
            ajaxReturn($json_arr);
        }
    }


    public function goodsAdd()
    {
        return $this->fetch();
    }

    public function goodsDetail()
    {
        if (request()->isPost()) {
            $goods = model('goods');
            $id = request()->post('id');
            /*if (!$id) {
                $json_arr = ["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
                ajaxReturn($json_arr);
            }*/
            $goods_list = $goods->where(['id' => $id])->find();

            #商品参数
            $goods_param = explode(";", $goods_list['goods_param']);
            $goods_param_new = [];

            foreach ($goods_param as $key => $value) {
                $str = explode(':', $value);
                if (count($str) == 2) {
                    $goods_param_new[$key] = ['param_name' => $str[0], 'param_val' => $str[1]];
                }
            }
            $goods_list['goods_param'] = $goods_param_new;
            /*$msl = model('SkuList');
            $skulist = [];
            if($goods_list['is_sku']){
                $skulist = $msl->where(['goods_id'=>$id,'is_del'=>'0'])->select();
            }

            if($skulist){
                //$skulist = json_encode($skulist);
                $guigeshuxing = json_decode($goods_list['goods_sku_info'],true);
                $goods_list['guigeshuxing']=$guigeshuxing;
            }else{
                $goods_list['is_sku'] = 0;
            }
            $goods_list['skulist'] = $skulist;
            */

            if (!$id) {
                $goods_list['is_sale'] = 1;
                $goods_list['is_exp'] = 1;
            }

            $goods_cate = model('goods_cate')->field('id,classname,sort,pid')->where(['pid' => 0, 'status' => '1'])->order('sort desc')->select();
            $goods_cate_new = [];
            foreach ($goods_cate as $key => $item) {
                //$goods_cate_new[] = $item;
                $cate_list = model('goods_cate')->field('id,classname,pid')->where(['pid' => $item['id'], 'status' => '1'])->order('sort desc')->select();
                $cate_list_two = [];
                foreach ($cate_list as $k => $v) {
                    $v['cate_list'] = model('goods_cate')->field('id,classname,pid')->where(['pid' => $v['id'], 'status' => '1'])->order('sort desc')->select();
                    $cate_list_two[$v['id']] = [
                        'id' => $v['id'],
                        'classname' => $v['classname'],
                        'pid' => $v['pid'],
                        'cate_list' => $v['cate_list'],
                    ];
                }
                $item['cate_list'] = $cate_list_two;
                $goods_cate_new[$item['id']] = [
                    'id' => $item['id'],
                    'sort' => $item['sort'],
                    'classname' => $item['classname'],
                    'pid' => $item['pid'],
                    'cate_list' => $item['cate_list'],
                ];
            }


            $goods_brand = model('goods_brand')->field('id,classname,pid')->where(['pid' => 0, 'status' => '1'])->order('sort desc')->select();
            $goods_brand_new = [];
            foreach ($goods_brand as $key => $item) {
                //$goods_brand_new[] = $item;
                $brand_list = model('goods_brand')->field('id,classname,pid')->where(['pid' => $item['id'], 'status' => '1'])->order('sort desc')->select();
                $item['brand_list'] = $brand_list;
                $goods_brand_new[$item['id']] = [
                    'id' => $item['id'],
                    'classname' => $item['classname'],
                    'pid' => $item['pid'],
                    'brand_list' => $item['brand_list'],
                ];
            }
            $manager_cate_id = model('manager')->where(['id' => $this->manager_id])->value('manager_cate_id');
            if ($manager_cate_id <= 3) {
                $is_cost_price = 1;
            } else {
                $is_cost_price = 0;
            }
            $store = model('store')->select();
            $data = [
                'list' => $goods_list,
                'store' => $store,
                'goods_cate' => $goods_cate_new,
                'goods_brand' => $goods_brand_new,
                'is_cost_price' => $is_cost_price,
            ];
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
        return $this->fetch();
    }

    public function goodsHandle()
    {
        if (request()->isPost()) {
            $goods = model('goods');
            $id = request()->post('id');
            if (input('post.cate_id') == '') {
                $json_arr = ["status" => 0, "msg" => "请选择商品一级分类", 'data' => []];
                ajaxReturn($json_arr);
            }
            if (!input('post.brand_id')) {
                $json_arr = ["status" => 0, "msg" => "请选择品牌", 'data' => []];
                ajaxReturn($json_arr);
            }
            if (input('post.goods_name') === '') {
                $json_arr = ["status" => 0, "msg" => "请填写商品名称", 'data' => []];
                ajaxReturn($json_arr);
            }
            if (input('post.price') === '') {
                $json_arr = ["status" => 0, "msg" => "请填写价价格", 'data' => []];
                ajaxReturn($json_arr);
            }
            if (input('post.price') < input('post.cost_price')) {
                $json_arr = ["status" => 0, "msg" => "商品价格不可低于供货价", 'data' => []];
                ajaxReturn($json_arr);
            }
            $stores = input('post.stores');
            if (empty($stores)) {
                $json_arr = ["status" => 0, "msg" => "请填写库存", 'data' => []];
                ajaxReturn($json_arr);
            }

            if (!is_numeric($stores) || strpos($stores, '.') !== false) {
                $json_arr = ["status" => 0, "msg" => "库存只能为正整数", 'data' => []];
                ajaxReturn($json_arr);
            }

            if (100000000 - $stores < 0) {
                $json_arr = ["status" => 0, "msg" => "库存超出最大限制", 'data' => []];
                ajaxReturn($json_arr);
            }

            if (input('post.goods_unit') == '') {
                //$json_arr = ["status" => 0, "msg" => "请填写商品单位", 'data' => []];
                //ajaxReturn($json_arr);
            }

            if (input('post.goods_logo') == '') {
                $json_arr = ["status" => 0, "msg" => "请上传商品logo图片", 'data' => []];
                ajaxReturn($json_arr);
            }

            $data = request()->post();

            if (!isset($data['goods_banner_pic'])) {
                $json_arr = ["status" => 0, "msg" => "请上传商品主图", 'data' => []];
                ajaxReturn($json_arr);
            }
            $goods_detail_pic = $data['goods_banner_pic'];
            unset($data['goods_banner_pic']);
            $data['goods_big_banner'] = implode(',', $goods_detail_pic);
            if (!isset($data['goods_detail_pic'])) {
                $json_arr = ["status" => 0, "msg" => "请上传商品详情图片", 'data' => []];
                ajaxReturn($json_arr);
            }
            $goods_detail_pic = $data['goods_detail_pic'];
            unset($data['goods_detail_pic']);
            $data['goods_detail_pic'] = implode(',', $goods_detail_pic);
            $str = '';

            foreach ($data['param_name'] as $k => $v) {
                $str .= $v . ':' . $data['param_val'][$k] . ';';
            }

            $item = isset($data['item']) ? $data['item'] : '';
            $item_img = isset($data['item_img']) ? $data['item_img'] : '';
            $spec_info = isset($data['spec_info']) ? $data['spec_info'] : '';
            unset($data['param_name']);
            unset($data['param_val']);
            unset($data['item_img']);
            unset($data['item']);
            unset($data['spec_info']);
            $data['is_audit'] = 0; //待审核
            //dump($str);die;
            $data['goods_param'] = $str;
            $data['store_id'] = model('goods_brand')->where(['id' => $data['brand_id']])->value('store_id');
            if ($id) {
                $goods = $goods->where(['id' => $id])->find();
                if ($goods['is_import'] == 1 && $goods['is_audit'] == 1) {
                    $data['is_import'] = 0;
                    unset($data['id']);
                    $id = 0;
                }
            }

            Db::startTrans();
            if ($id) {
                $goods_code = $goods->where(['id' => ['neq', $id], 'is_import' => 0,'goods_code' => $data['goods_code']])->find();
                if ($goods_code) {
                    $json_arr = ["status" => 0, "msg" => "编辑商品编码已存在", 'data' => []];
                    ajaxReturn($json_arr);
                }
                $data['update_time'] = date("Y-m-d H:i:s");
                $content = '修改商品信息';
                $field = array_keys($data);
                $field[] = 'id';
                $before_json = $goods->field($field)->where(['id' => $id])->find();
                $result1 = $goods->isUpdate(true)->save($data, ['id' => $id]);
                $goods->afterSave($id, $item, $item_img);
                $data['id'] = $id;
                $after_json = $data;

            } else {
                $data['add_time'] = date('Y-m-d H:i:s');
                $data['update_time'] = date("Y-m-d H:i:s");
                $goods_code = $goods->where(['is_import' => 0, 'goods_code' => $data['goods_code']])->find();
                if ($goods_code) {
                    $json_arr = ["status" => 0, "msg" => "添加商品编码已存在", 'data' => []];
                    ajaxReturn($json_arr);
                }
                $data['create_time'] = time();
                $data['sale_time'] = date('Y-m-d H:i:s');
                $content = '添加商品信息';
                $before_json = [];
                $result1 = $goods->insert($data);
                $data['id'] = $goods->getLastInsID();
                $goods->afterSave($data['id'], $item, $item_img);
                $after_json = $data;
            }

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($result1) {
                if ($spec_info) {
                    model('spec')->save(['goods_id' => $data['id']], ['id' => ['in', $spec_info]]);
                }
                Db::commit();
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                Db::rollback();
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }

        }
        return $this->fetch();
    }

    /**
     * 商品模板导出
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function goodsTemplate()
    {
        $subject = '商品模板';
        $title = ['排序', '一级分类', '二级分类','三级分类', '品牌', '系列', '商品编码', '商品名称',
            '商品简介', '商品关键词', '发货地', '服务承诺', '成本价', '零售价','B端价', '库存'
        ];
        $objPHPExcel = new \PHPExcel();
        $titleRow = array('A1', 'B1', 'C1', 'D1', 'E1', 'F1', 'G1', 'H1', 'I1', 'J1', 'K1', 'L1', 'M1', 'N1', 'O1',
            'P1', 'Q1', 'R1', 'S1', 'T1', 'U1', 'V1', 'W1', 'X1', 'Y1', 'Z1', 'AA1', 'AB1', 'AC1', 'AD1');
        for ($a = 0; $a < count($title); $a++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($titleRow[$a], $title[$a]);
        }
        $objPHPExcel->setactivesheetindex(0);
        $objPHPExcel->getActiveSheet()->setTitle('sheet1');
        $goods_cate = model('goods_cate')->where(['status' => 1, 'pid' => 0])->column('classname');
        $this->getSelect($objPHPExcel, $goods_cate, 'B');
//        $goods_cate = model('goods_cate')->where(['status' => 1, 'level' => 2])->column('classname');
//        $this->getSelect($objPHPExcel, $goods_cate, 'C');
//        $goods_cate = model('goods_cate')->where(['status' => 1, 'level' => 3])->column('classname');
//        $this->getSelect($objPHPExcel, $goods_cate, 'D');
        /*$data = [];
        foreach ($goods_cate as $k => $v) {
            $data[$k]['name'] = $v['classname'];
            $children = model('goods_cate')->field('id,classname')->where(['status' => 1, 'pid' => $v['id']])->select();
            $data2 = [];
            foreach ($children as $k1 => $v1) {
                $data2[$k1]['name'] = $v1['classname'];
                $children2 = model('goods_cate')
                    ->field('id,classname')
                    ->where(['status' => 1, 'pid' => $v1['id']])
                    ->select();
                $data3 = [];
                foreach ($children2 as $k2 => $v2) {
                    $data3[$k1]['name'] = $v2['classname'];
                }
                $data2[$k1]['children'] = $data3;
            }
            $data[$k]['children'] = $data2;
        }*/


        /* $col = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
             'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

         $sheet_name = 'support1';
         $supportSheet = new \PHPExcel_Worksheet($objPHPExcel, $sheet_name); //创建一个工作表
         $objPHPExcel->addSheet($supportSheet); //插入工作表
         $char = ['B','C','D'];
         $this->getMySheetInfo($objPHPExcel, $data, $char, $col, $sheet_name);*/
//        $goods_brand = model('goods_brand')->where(['status' => 1, 'pid' => 0])->column('classname');
//        $this->getSelect($objPHPExcel, $goods_brand, 'C');
//        $goods_cate = model('goods_brand')->where(['status' => 1, 'level' => 2])->column('classname');
//        $this->getSelect($objPHPExcel, $goods_cate, 'F');
        /*$data = [];
        foreach ($goods_cate as $k => $v) {
            $data[$k]['name'] = $v['classname'];
            $children = model('goods_brand')->field('id,classname')->where(['status' => 1, 'pid' => $v['id']])->select();
            $data2 = [];
            foreach ($children as $k1 => $v1) {
                $data2[$k1]['name'] = $v1['classname'];
                $data2[$k1]['children'] = [];
            }
            $data[$k]['children'] = $data2;
        }
        $sheet_name = 'support2';
        $supportSheet = new \PHPExcel_Worksheet($objPHPExcel, $sheet_name); //创建一个工作表
        $objPHPExcel->addSheet($supportSheet); //插入工作表
        $char = ['E', 'F'];
        $this->getMySheetInfo($objPHPExcel, $data, $char, $col, $sheet_name);*/

        $objPHPExcel->setActiveSheetIndex(0);
        //输出表格
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $subject . '' . date('Ymd') . '.xlsx');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

    }

    /**
     * 商品模板导出
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function goodsExportTemplate()
    {
        $subject = '商品模板';
        $title = ['排序', '一级分类', '二级分类','三级分类', '品牌', '系列', '商品编码', '商品名称',
            '商品简介', '商品关键词', '发货地', '服务承诺', '成本价', '零售价','B端价', '库存'
        ];
        $objPHPExcel = new \PHPExcel();
        $objActSheet = $objPHPExcel->getActiveSheet();
        $titleRow = array('A1', 'B1', 'C1', 'D1', 'E1', 'F1', 'G1', 'H1', 'I1', 'J1', 'K1', 'L1', 'M1', 'N1', 'O1',
            'P1', 'Q1', 'R1', 'S1', 'T1', 'U1', 'V1', 'W1', 'X1', 'Y1', 'Z1', 'AA1', 'AB1', 'AC1', 'AD1');
        for ($a = 0; $a < count($title); $a++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($titleRow[$a], $title[$a]);
        }
        $objPHPExcel->setactivesheetindex(0);
        $objPHPExcel->getActiveSheet()->setTitle('sheet1');
        $goods_cate = model('goods_cate')->where(['status' => 1, 'pid' => 0])->column('classname');
        $this->getSelect($objPHPExcel, $goods_cate, 'B');
        $objPHPExcel->setActiveSheetIndex(0);
        $lists = model('goods')->where(['is_import' => 1])->select();
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objActSheet->getDefaultStyle()->getAlignment()->setWrapText(TRUE);
        $i = 2;
        foreach($lists as $k=>$v){
            $v['class_two_name'] = model('goods_cate')->where(['id' => $v['cate_two_id']])->value('classname');
            $v['class_tree_name'] = model('goods_cate')->where(['id' => $v['cate_three_id']])->value('classname');
            $v['brand_two_name'] = model('goods_brand')->where(['id' => $v['brand_two_id']])->value('classname');
            $objActSheet->setCellValue('A'.$i,$v['sort']);
            $objActSheet->setCellValue('B'.$i, $v['class_name']);
            $objActSheet->setCellValue('C'.$i, $v['class_two_name']);
            $objActSheet->setCellValue('D'.$i, $v['class_tree_name']);
            $objActSheet->setCellValue('E'.$i, $v['brand_name']);
            $objActSheet->setCellValue('F'.$i, $v['brand_two_name']);
            $objActSheet->setCellValue('G'.$i, $v['goods_code']);
            $objActSheet->setCellValue('H'.$i, $v['goods_name']);
            $objActSheet->setCellValue('I'.$i, $v['goods_desc']);
            $objActSheet->setCellValue('J'.$i, $v['goods_keywords']);
            $objActSheet->setCellValue('K'.$i, $v['product']);
            $objActSheet->setCellValue('L'.$i, $v['service']);
            $objActSheet->setCellValue('M'.$i, $v['cost_price']);
            $objActSheet->setCellValue('N'.$i, $v['price']);
            $objActSheet->setCellValue('O'.$i, $v['b_price']);
            $objActSheet->setCellValue('P'.$i, $v['stores']);
            // 表格高度
            $objActSheet->getRowDimension($i)->setRowHeight(20);
            $i++;
        }
        $width = [10, 15, 15, 10, 10, 10, 15, 10, 15, 15, 10, 15];
        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth($width[0]);
        $objActSheet->getColumnDimension('B')->setWidth($width[1]);
        $objActSheet->getColumnDimension('C')->setWidth($width[2]);
        $objActSheet->getColumnDimension('D')->setWidth($width[3]);
        $objActSheet->getColumnDimension('E')->setWidth($width[4]);
        $objActSheet->getColumnDimension('F')->setWidth($width[5]);
        $objActSheet->getColumnDimension('G')->setWidth($width[6]);
        $objActSheet->getColumnDimension('H')->setWidth($width[7]);
        $objActSheet->getColumnDimension('I')->setWidth($width[8]);
        $objActSheet->getColumnDimension('J')->setWidth($width[9]);
        $objActSheet->getColumnDimension('K')->setWidth($width[10]);
        $objActSheet->getColumnDimension('L')->setWidth($width[11]);
        $objActSheet->getColumnDimension('J')->setWidth($width[11]);
        $objActSheet->getColumnDimension('K')->setWidth($width[11]);
        $objActSheet->getColumnDimension('L')->setWidth($width[11]);
        $objActSheet->getColumnDimension('M')->setWidth($width[11]);
        $objActSheet->getColumnDimension('N')->setWidth($width[11]);
        $objActSheet->getColumnDimension('O')->setWidth($width[11]);
        $objActSheet->getColumnDimension('P')->setWidth($width[11]);
        //输出表格
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $subject . '' . date('Ymd') . '.xlsx');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

    }
    /**
     * 商品导入
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function goodsImportAll()
    {
        if (request()->isPost()) {
            header("content-type:text/html;charset=utf-8");
            //上传excel文件
            $file = request()->file('excel');
            $a = request()->post('a');

            if ($a > 1) {
                $filePath = request()->post('filePath');
                if (!$filePath) {
                    $json_arr = ['status' => 0, 'msg' => '选择模板（上传文件,格式xls,xlsx）', 'data' => []];
                    ajaxReturn($json_arr);
                }

            } else {
                if (!$file) {
                    $json_arr = ['status' => 0, 'msg' => '选择模板（上传文件,格式xls,xlsx）', 'data' => []];
                    ajaxReturn($json_arr);
//                $this->error('缺少导入文件');
//                ajaxReturn(['status' => 0, 'msg' => '缺少导入文件', 'data' => []];
                }

                //将文件保存到public/uploads目录下面
                $info = $file->validate(['size' => 10485760000, 'ext' => 'xls,xlsx'])->move('./uploads/excel');
                if ($info) {
                    //获取上传到后台的文件名
                    $fileName = $info->getSaveName();
                    //获取文件路径
                    $filePath = ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . 'uploads/excel' . DIRECTORY_SEPARATOR . $fileName;
                    //获取文件后缀
                    $suffix = $info->getExtension();
                    //判断哪种类型
//                if ($suffix == "xlsxss") {
//                    $reader = \PHPExcel_IOFactory::createReader('Excel2007');
//                } else {
//                    $reader = \PHPExcel_IOFactory::createReader('Excel5');
//                }
                } else {
                    $json_arr = ['status' => 0, 'msg' => '文件过大或格式不正确导致上传失败', 'data' => []];
                    ajaxReturn($json_arr);
//                $this->error('文件过大或格式不正确导致上传失败-_-!');
                }
            }

            $reader = \PHPExcel_IOFactory::createReader('Excel5');
            //载入excel文件
            $excel = $reader->load("$filePath", $encode = 'utf-8');
            //读取第一张表
            $sheet = $excel->getSheet(0);
            //获取总行数
            $row_num = $sheet->getHighestRow();
            //每次执行
            $list_row = 2000;
            //总次数
            $total_num = ceil(($row_num-2)/$list_row);

            if ($total_num == 1) {
                $list_row = $row_num-2;
            }
            //获取总列数
            $col_num = $sheet->getHighestColumn();

            $run_row = ($a-1) * $list_row;
            $data = []; //数组形式获取表格数据
            for ($i = 2+$run_row; $i <= $list_row*$a+2; $i++) {
                $data[$i]['sort'] = (string)$sheet->getCell("A" . $i)->getValue();
                $data[$i]['cate_name'] = (string)$sheet->getCell("B" . $i)->getValue();
                $data[$i]['cate_two_name'] = (string)$sheet->getCell("C" . $i)->getValue();
                $data[$i]['cate_three_name'] = (string)$sheet->getCell("D" . $i)->getValue();
                $data[$i]['brand_name'] = (string)$sheet->getCell("E" . $i)->getValue();
                $data[$i]['brand_two_name'] = (string)$sheet->getCell("F" . $i)->getValue();
                $data[$i]['goods_code'] = (string)$sheet->getCell("G" . $i)->getValue();
                $data[$i]['goods_name'] = (string)$sheet->getCell("H" . $i)->getValue();
                $data[$i]['goods_desc'] = (string)$sheet->getCell("I" . $i)->getValue();
                $data[$i]['goods_keywords'] = (string)$sheet->getCell("J" . $i)->getValue();
                $data[$i]['product'] = (string)$sheet->getCell("K" . $i)->getValue();
                $data[$i]['service'] = (string)$sheet->getCell("L" . $i)->getValue();
                $data[$i]['cost_price'] = (string)$sheet->getCell("M" . $i)->getValue();
                $data[$i]['price'] = (string)$sheet->getCell("N" . $i)->getValue();
                $data[$i]['b_price'] = (string)$sheet->getCell("O" . $i)->getValue();
                $data[$i]['stores'] = (string)$sheet->getCell("P" . $i)->getValue();
                //将数据保存到数据库
            }

            $cate = model('goods_cate')->column('classname,id');
            $brand = model('goods_brand')->column('classname,id');
            $success = [];
            $failure = [];
            $before_json = [];
            foreach ($data as $k => $v) {
                $data[$k]['is_audit'] = 1;
                $data[$k]['is_sale'] = 0;
                $data[$k]['is_import'] = 1;

                $data[$k]['cate_id'] = isset($cate[$v['cate_name']])?$cate[$v['cate_name']]:0;
                $data[$k]['cate_two_id'] = isset($cate[$v['cate_two_name']])?$cate[$v['cate_two_name']]:0;
                $data[$k]['cate_three_id'] = isset($cate[$v['cate_three_name']])?$cate[$v['cate_three_name']]:0;
                $data[$k]['brand_id'] = isset($brand[$v['brand_name']])?$brand[$v['brand_name']]:0;
                $data[$k]['brand_two_id'] = isset($brand[$v['brand_two_name']])?$brand[$v['brand_two_name']]:0;
                if ($data[$k]['brand_id']) {
                    $store_id = model("goods_brand")->where(['id' => $data[$k]['brand_id']])->value('store_id');
                    $data[$k]['store_id'] = $store_id;
                }
                unset($data[$k]['cate_name']);
                unset($data[$k]['cate_two_name']);
                unset($data[$k]['cate_three_name']);
                unset($data[$k]['brand_name']);
                unset($data[$k]['brand_two_name']);


                if ($v['goods_code']) {
                    $goods_code = model('goods')->where(['goods_code' => $v['goods_code'], 'is_import' => 1])->find();
                    if (!isset($cate[$v['cate_name']])) {
                        $msg['msg'] = '第' . $k . '商品分类不正确';
                        $failure[] = $msg;
                        unset($data[$k]);
                        continue;
                    }
                    if (!isset($brand[$v['brand_name']])) {
                        $msg['msg'] = '第' . $k . '商品品牌不正确';
                        $failure[] = $msg;
                        unset($data[$k]);
                        continue;
                    }
                    if ($goods_code) {
                        $msg['msg'] = '第' . $k . '商品修改成功';
                        $success[] = $msg;
                        model('goods')->update($data[$k], ['id' => $goods_code['id']]);
                        unset($data[$k]);
                        continue;
                    }
                } else {
                    //$msg['msg'] = '第' . $k . '商品编码不能为空';
                    //$failure[] = $msg;
                    unset($data[$k]);
                    continue;
                }
                $msg['msg'] = '第' . $k . '行商品导入成功';
                $success[] = $msg;
            }
            model('goods')->insertAll($data);
            $content = '导入商品-修改商品价格';
            $after_json = $data;
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($total_num > $a) {
                $json_arr = ['status' => 40001, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => [
                    'success' => $success,
                    'failure' => $failure,
                    'filePath' => $filePath,
                ]];
            } else {
                $json_arr = ['status' => 1, 'msg' => '导入完成', 'data' => [
                    'success' => $success,
                    'failure' => $failure,
                ]];
            }

            ajaxReturn($json_arr);
        } else {
            return $this->fetch();
        }
    }


    /**
     * 导出商品
     */
    public function goodsExport()
    {
        $map = [];
        $status = request()->param('status', 0, 'intval');
        $type = request()->param('type', 0, 'intval');
        $audit = request()->param('audit', 1, 'intval');

        $map0 = [];

        $map1 = ['is_del' => 0, 'is_sale' => 1, 'stores' => ['gt', 0]];
        $map2 = ['is_del' => 0, 'is_sale' => 1, 'stores' => 0];
        $map3 = ['is_del' => 0, 'is_sale' => 0];
        $map4 = ['is_del' => 1];

        if ($type == 1) {
            if ($audit != -1) {
                $map0['is_audit'] = $audit;
                $map1['is_audit'] = $audit;
                $map2['is_audit'] = $audit;
                $map3['is_audit'] = $audit;
                $map4['is_audit'] = $audit;
                $map['is_audit'] = $audit;
            }
        } else {
            $map0['is_audit'] = 0;
            $map1['is_audit'] = 0;
            $map2['is_audit'] = 0;
            $map3['is_audit'] = 0;
            $map4['is_audit'] = 0;
            $map['is_audit'] = 0;
        }

        if ($status == 0) {//全部
        } elseif ($status == 1) {//出售中
            $map = $map1;
        } elseif ($status == 2) {//已售罄
            $map = $map2;
        } elseif ($status == 3) {//仓库中
            $map = $map3;
        } elseif ($status == 4) {//回收站
            $map = $map4;
        }

        $keyword = request()->param('keyword', '', 'trim');

        if ($keyword) {
            $map['goods_name|goods_code'] = ['like', "%$keyword%"];
        }

        //商品分类
        $cate = request()->param('cate');

        if ($cate) {
            $map['cate_id|cate_two_id'] = $cate;
        }
        $brand = request()->param('brand');

        if ($brand) {
            $map['brand_id|brand_two_id'] = $brand;
        }
        $id = request()->param('id', '', 'trim');

        if ($id) {
            $map['id'] = $id;
        }
        $field = ['id', 'goods_name','brand_id', 'goods_code', 'cost_price', 'goods_logo', 'price', 'stores', 'sort', 'is_sale', 'sale_time', 'sales'];
        $lists = model('goods')->where($map)->field($field)->order('sort desc, id desc')->select();
        foreach ($lists as $k => $v) {
            $spec_goods_price = model('spec_goods_price')->where(['goods_id' => $v['id']])->select();
            if ($spec_goods_price) {
                $lists[$k]['spec_goods_price'] = $spec_goods_price;
            } else {
                $lists[$k]['spec_goods_price'] = [];
            }
        }
        $this->exportGoodsNew($lists, '商品信息表');
    }

    /**
     * 导出商品goodsExportExtra
     */
    public function goodsExportExtra()
    {
        set_time_limit(0);
        $map = [];
        $status = request()->param('status', 0, 'intval');
        $audit = request()->param('audit', 1, 'intval');

        $map1 = ['is_del' => 0, 'is_sale' => 1, 'stores' => ['gt', 0]];
        $map2 = ['is_del' => 0, 'is_sale' => 1, 'stores' => 0];
        $map3 = ['is_del' => 0, 'is_sale' => 0];
        $map4 = ['is_del' => 1];
        $map5 = ['is_audit' => 0];


        if ($audit != -1) {
            $map1['is_audit'] = $audit;
            $map2['is_audit'] = $audit;
            $map3['is_audit'] = $audit;
            $map4['is_audit'] = $audit;
            $map['is_audit'] = $audit;
        }

        if ($status == 0) {//全部
        } elseif ($status == 1) {//出售中
            $map = $map1;
        } elseif ($status == 2) {//已售罄
            $map = $map2;
        } elseif ($status == 3) {//仓库中
            $map = $map3;
        } elseif ($status == 4) {//回收站
            $map = $map4;
        }

        $keyword = request()->param('keyword', '', 'trim');

        if ($keyword) {
            $map1['goods_name|goods_code'] = ['like', "%$keyword%"];
            $map2['goods_name|goods_code'] = ['like', "%$keyword%"];
            $map3['goods_name|goods_code'] = ['like', "%$keyword%"];
            $map4['goods_name|goods_code'] = ['like', "%$keyword%"];
            $map5['goods_name|goods_code'] = ['like', "%$keyword%"];
            $map['goods_name|goods_code'] = ['like', "%$keyword%"];
        }

        //商品分类
        $cate = request()->param('cate');

        if ($cate) {
            $map1['cate_id|cate_two_id'] = $cate;
            $map2['cate_id|cate_two_id'] = $cate;
            $map3['cate_id|cate_two_id'] = $cate;
            $map4['cate_id|cate_two_id'] = $cate;
            $map5['cate_id|cate_two_id'] = $cate;
            $map['cate_id|cate_two_id'] = $cate;
        }
        $brand = request()->post('brand');

        if ($brand) {
            $map0['brand_id|brand_two_id'] = $brand;
            $map1['brand_id|brand_two_id'] = $brand;
            $map2['brand_id|brand_two_id'] = $brand;
            $map3['brand_id|brand_two_id'] = $brand;
            $map4['brand_id|brand_two_id'] = $brand;
            $map5['brand_id|brand_two_id'] = $brand;
            $map['brand_id|brand_two_id'] = $brand;
        }
        $id = request()->param('id', '', 'trim');

        if ($id) {
            $map1['id'] = $id;
            $map2['id'] = $id;
            $map3['id'] = $id;
            $map4['id'] = $id;
            $map5['id'] = $id;
            $map['id'] = $id;
        }
        $field = ['id', 'goods_name', 'goods_code', 'goods_logo', 'price', 'cost_price', 'stores', 'sort', 'is_sale', 'sale_time', 'sales', 'brand_id'];
        $lists = model('goods')->where($map)->field($field)->order('sort desc, id desc')->select();
        foreach ($lists as $k => $v) {
            $lists[$k]['final_price'] = $v['price'];
            $lists[$k]['goods_num'] = 0;
            $lists[$k]['goods_brand'] = model('goods_brand')->where(['id' => $v['brand_id']])->value('classname');
            $spec_goods_price = model('spec_goods_price')->where(['goods_id' => $v['id']])->select();
            if ($spec_goods_price) {
                foreach ($spec_goods_price as $k1 => $v1) {
                    $spec_goods_price[$k1]['final_price'] = $v1['price'];
                    $spec_goods_price[$k1]['goods_num'] = 0;
                }
                $lists[$k]['spec_goods_price'] = $spec_goods_price;
            } else {
                $lists[$k]['spec_goods_price'] = [];
            }
        }
        $this->exportGoodsSale($lists);

    }

    /**
     * 导入商品价格视图
     */
    public function goodsImport()
    {
        return $this->fetch();
    }

    /**
     * 导入商品价格模板
     */
    public function goodsImportTampOld()
    {
        $field = ['id', 'goods_name', 'goods_code', 'cost_price', 'goods_logo', 'price', 'stores', 'sort', 'is_sale', 'sale_time', 'sales', 'store_id'];
        $lists = model('goods')->where([])->field($field)->order('sort desc, id desc')->select();
        foreach ($lists as $k => $v) {
            $spec_goods_price = model('spec_goods_price')->where(['goods_id' => $v['id']])->select();
            if ($spec_goods_price) {
                $lists[$k]['spec_goods_price'] = $spec_goods_price;
            } else {
                $spec_goods_price = [
                    0 => [
                        'key' => '',
                        'key_name' => '',
                        'bar_code' => '',
                        'price' => $v['price'],
                        'cost_price' => $v['cost_price'],
                    ]
                ];
                $lists[$k]['spec_goods_price'] = $spec_goods_price;
            }
            $lists[$k]['store_name'] = model('store')->where(['id' => $v['store_id']])->value('store_name');
        }
        $this->exportGoodsTamp($lists, '商品价格模板');
    }

    public function goodsImportTamp()
    {
        $map = [];
        $field = ['id', 'goods_name','brand_id', 'goods_code', 'cost_price', 'goods_logo', 'price', 'stores', 'sort', 'is_sale', 'sale_time', 'sales'];
        $lists = model('goods')->where($map)->field($field)->order('sort desc, id desc')->select();
        foreach ($lists as $k => $v) {
            $spec_goods_price = model('spec_goods_price')->where(['goods_id' => $v['id']])->select();
            if ($spec_goods_price) {
                $lists[$k]['spec_goods_price'] = $spec_goods_price;
            } else {
                $lists[$k]['spec_goods_price'] = [];
            }
        }
        $this->exportGoodsNew($lists, '商品价格模板', 1);
    }

    /**
     * 导入商品价格操作
     */
    public function goodsImportHandle()
    {
        if (request()->isPost()) {
            header("content-type:text/html;charset=utf-8");
            //上传excel文件
            $file = request()->file('excel');

            if (!$file) {
                $json_arr = ['status' => 0, 'msg' => '选择已修改模板（上传文件）', 'data' => []];
                ajaxReturn($json_arr);
//                $this->error('缺少导入文件');
//                ajaxReturn(['status' => 0, 'msg' => '缺少导入文件', 'data' => []];
            }

            //将文件保存到public/uploads目录下面
            $info = $file->validate(['size' => 10485760000, 'ext' => 'xls,xlsx'])->move('./uploads/excel');
            if ($info) {
                //获取上传到后台的文件名
                $fileName = $info->getSaveName();
                //获取文件路径
                $filePath = ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . 'uploads/excel' . DIRECTORY_SEPARATOR . $fileName;
                //获取文件后缀
                $suffix = $info->getExtension();
                //判断哪种类型
                //if ($suffix == "xlsx") {
                // $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                //} else {
                $reader = \PHPExcel_IOFactory::createReader('Excel5');
                //}
            } else {
                $json_arr = ['status' => 0, 'msg' => '文件过大或格式不正确导致上传失败', 'data' => []];
                ajaxReturn($json_arr);
//                $this->error('文件过大或格式不正确导致上传失败-_-!');
            }
            //载入excel文件
            $excel = $reader->load("$filePath", $encode = 'utf-8');
            //读取第一张表
            $sheet = $excel->getSheet(0);
            //获取总行数
            $row_num = $sheet->getHighestRow();
            //获取总列数
            $col_num = $sheet->getHighestColumn();
            $data = []; //数组形式获取表格数据
            for ($i = 4; $i <= $row_num; $i++) {
                $data[$i]['goods_id'] = (string)$sheet->getCell("A" . $i)->getValue();
                $data[$i]['goods_price'] = (string)$sheet->getCell("P" . $i)->getValue();
                $data[$i]['cost_price'] = (string)$sheet->getCell("H" . $i)->getValue();
                $data[$i]['brand_name'] = (string)$sheet->getCell("B" . $i)->getValue();
                /* if ($data[$i]['goods_id'] == '') {
                     $data[$i]['goods_id'] = $data[$i - 1]['goods_id'];
                 }
                 if ($data[$i]['goods_price'] == '') {
                     $data[$i]['goods_price'] = $data[$i - 1]['goods_price'];
                 }
                 if ($data[$i]['cost_price'] == '') {
                     $data[$i]['cost_price'] = $data[$i - 1]['cost_price'];
                 }
                 if ($data[$i]['store_name'] == '') {
                     $data[$i]['store_name'] = $data[$i - 1]['store_name'];
                 }*/
                $data[$i]['sku_id'] = (string)$sheet->getCell("E" . $i)->getValue();
//                $data[$i]['sku_price'] = (string)$sheet->getCell("H" . $i)->getValue();
//                $data[$i]['sku_cost'] = (string)$sheet->getCell("I" . $i)->getValue();
                //将数据保存到数据库
            }
            $success = [];
            $failure = [];
            $before_json = [];
            foreach ($data as $k => $v) {

                $where = ['id' => $v['goods_id']];
                if ($data[$k]['brand_name']) {
                    $brand = model('goods_brand')->where(['classname' => $data[$k]['brand_name']])->find();
                    if (!$brand) {
                        $msg['msg'] = '第' . $k . '行商品品牌不存在';
                        $failure[] = $msg;
                    } else {
                        $update_data['store_id'] = $brand['store_id'];
                        $update_data['brand_id'] = $brand['id'];
                    }
                }

                if ($data[$k]['sku_id']) {
                    $spec_goods_price = model('spec_goods_price')->where(['goods_id' => $v['goods_id'], 'key' => $v['sku_id']])->find();
                    if (!$spec_goods_price) {
                        $msg['msg'] = '第' . $k . '行商品SKU不存在';
                        $failure[] = $msg;
                    } else {
                        $update_data = [
                            'price' => $v['goods_price'],
                            'cost_price' => $v['cost_price'],
                        ];
                        $where = ['goods_id' => $v['goods_id'], 'key' => $v['sku_id']];
                        $result = model('spec_goods_price')->isUpdate(true)->save($update_data, $where);
                        if ($result) {
                            $before_json[$k]['sku'] = $result;
                            $msg['msg'] = '第' . $k . '行商品SKU价格，修改成功';
                            $success[$k] = $msg;
                        } else {
                            $msg['msg'] = '第' . $k . '行商品SKU价格，没有修改';
                            $failure[] = $msg;
                        }
                    }
                } else {
                    $goods = model('goods')->where('id', $v['goods_id'])->find();
                    if (!$goods) {
                        $msg['msg'] = '第' . $k . '行商品不存在';
                        $failure[] = $msg;
                        continue;
                    } else {
                        $update_data = [
                            'price' => $v['goods_price'],
                            'cost_price' => $v['cost_price'],
                        ];
                    }
                    $brand = model('goods_brand')->where(['id' => $goods['brand_id']])->value('classname');
                    if ($goods['price'] == $v['goods_price']
                        && $goods['cost_price'] == $v['cost_price']
                        && $brand == $v['brand_name']) {
                        $msg['msg'] = '第' . $k . '行商品，没有修改';
                        $failure[] = $msg;
                    } else {
                        $result = model('goods')->update($update_data, $where);
                        if ($result) {
                            $before_json[$k]['goods'] = $goods;
                            $msg['msg'] = '第' . $k . '行商品，修改成功';
                            $success[$k] = $msg;
                        } else {
                            $msg['msg'] = '第' . $k . '行商品，没有修改';
                            $failure[] = $msg;
                        }
                    }
                }
            }
            //$result = model('goods')->save($data);
            $content = '导入商品-修改商品价格';
            $after_json = $data;
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['success' => $success, 'failure' => $failure]];
            ajaxReturn($json_arr);
        }
    }



    // 商品分类列表
    public function goodsCate()
    {
        $c = model("goods_cate");
        $where = [];
        $where['is_del'] = 0;
        $where['pid'] = 0;
        // $count=$c->where($where)->order('sort desc')->count();
        // $p=getpage1($count,10);
        // $page=$p->show1();
        $goods_cate = $c->where($where)->order('sort desc')->select();
        $list = [];
        foreach ($goods_cate as $key => $item) {
            $list[] = $item;
            $cate_list = model('goods_cate')->field('id,classname,pid')->where(['pid' => $item['id'], 'status' => '1'])->order('sort desc')->select();
            foreach ($cate_list as $k => $v) {
                $v['classname'] = '&nbsp;&nbsp;|--' . $v['classname'];
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

            $res = $m->where(["classname" => $data['classname'], "pid" => $data['pid'], "id" => ["neq", $data['id']]])->find();

            if (!$data['classname']) {
                ajaxReturn(["status" => 0, "msg" => "请填写分类名称！"]);
            }

            if ($res) {
                ajaxReturn(["status" => 0, "msg" => "类名已存在！"]);
            }

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
                $res = $m->save($data);
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

    /**
     * 删除商品分类
     */
    public function delCate()
    {
        $id = input("id");
        $m = model("goods_cate");
        if (!$id) {
            ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $data = $m->find($id);
        if (!$data) {
            ajaxReturn(["status" => 0, "msg" => "分类不存在！"]);
        }
        if ($data['pid']) {

            $res = $m->destroy($id);
            if ($res) {
                $before_json = $data;
                $after_json = [];
                $content = '删除商品分类';
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
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
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
    }


    // 商品分类列表
    public function goodsBrand()
    {
        $c = model("goods_brand");
        $where = [];
        $where['is_del'] = 0;
        $where['pid'] = 0;

        $lists = $c->where($where)->order('sort desc')->paginate(10, false, ['query' => request()->param()]);
        foreach ($lists as $key => $value) {
            $data = $c->where(["pid" => $value['id']])->order('sort desc')->paginate(10, false, ['query' => request()->param()]);
            $lists[$key]['brand_list'] = $data;
        }

        $this->assign("lists", $lists);

        return $this->fetch();
    }

    public function goodsBrandAdd()
    {
        $where['is_del'] = 0;
        $goods_brand_model = model('goods_brand');
        $goods_brand_id = request()->param('goods_brand_id');
        $where['id'] = $goods_brand_id;
        $cache = $goods_brand_model->where($where)->find();
        if ($cache) {
            if (!$cache['province_id'] && !$cache['city_id']) {
                $cache['selected'] = 1;
            } else {
                $cache['selected'] = 0;
            }
        }
        //分类
        $where['pid'] = 0;
        unset($where['id']);
        $lists = $goods_brand_model->where($where)->order('sort desc')->select();

        //地址
        $this->assign("lists", $lists);
        $this->assign("cache", $cache);

        $where = ['parent_id' => 0];
        $province = model('region')->where($where)->select();
        foreach ($province as $k => $v) {
            if ($cache && ($cache['selected'] == 1 || in_array($v['id'], explode(',', $cache['province_id'])))) {
                $province[$k]['selected'] = 1;
            } else {
                $province[$k]['selected'] = 0;
            }
            $whereCity['parent_id'] = $v['id'];
            $city = model('region')->where($whereCity)->select();
            foreach ($city as $key => $val) {
                if ($cache && ($cache['selected'] == 1 || in_array($val['id'], explode(',', $cache['city_id'])))) {
                    $city[$key]['selected'] = 1;
                } else {
                    $city[$key]['selected'] = 0;
                }
            }
            $province[$k]['city'] = $city;
        }

        $this->assign('province', $province);

        $store = model('store')->field('id,store_name')->select();
        $this->assign('store', $store);
        return $this->fetch();
    }

    // 增加商品分类
    public function addBrand()
    {
        if (request()->isPost()) {
            $data = input("post.");

            $m = model("goods_brand");

            $res = $m->where(["classname" => $data['classname'], "pid" => $data['pid'], "id" => ["neq", $data['id']]])->find();
            if (!$data['classname']) {
                ajaxReturn(["status" => 0, "msg" => "请填写分类名称！"]);
            }
            if ($res) {
                ajaxReturn(["status" => 0, "msg" => "类名已存在！"]);
            }
            /*if (!isset($data['data'])) {
                ajaxReturn(['status' => 0, 'msg' => '请选择省市', 'data' => []]);
            } */
            if (isset($data['data'])) {
                $province_id = isset($data['data']['province']) ? implode(',', $data['data']['province']) : '';
                $city_id = isset($data['data']['city']) ? implode(',', $data['data']['city']) : '';
                if (isset($data['data']['county']) && $data['data']['county'] == 1) {
                    $data['province_id'] = null;
                    $data['city_id'] = null;
                } else {
                    $data['province_id'] = $province_id;
                    $data['city_id'] = $city_id;
                }
                unset($data['data']);
            } else {
                ajaxReturn(['status' => 0, 'msg' => '请选择地区', 'data' => []]);
            }
            if ($data['id'] > 0) {
                $parid = $m->where(["id" => $data['id'],])->value("pid");
                if ($data['pid'] == $data['id']) {
                    $data['pid'] = 0;
                }
                if ($parid == 0 && $data['pid'] != 0) {
                    ajaxReturn(["status" => 0, "msg" => "顶级分类无法改变分类！"]);
                }
                $content = '修改商品品牌系列分类';
                $field = array_keys($data);
                $field[] = 'id';
                $id = $data['id'];
                unset($data['id']);
                $data['update_time'] = date('Y-m-d H:i:s');
                $before_json = $m->field($field)->where(['id' => $id])->find();
                $res = $m->save($data, ['id' => $id]);
                $data['id'] = $id;
                $after_json = $data;
            } else {
                unset($data['id']);
                $before_json = [];
                $content = '添加商品品牌系列分类';
                $res = $m->save($data);
                $data['id'] = $m->getLastInsID();
                $after_json = $data;

            }
            if ($res) {
                if ($data['store_id']) {
                    model('goods')->save(['store_id' => $data['store_id']],['brand_id' => $data['id']]);
                }
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
    }

    /**
     * 删除商品分类
     */
    public function delBrand()
    {
        $id = input("post.id");
        $m = model("goods_brand");
        if (!$id) {
            ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $data = $m->find($id);
        if (!$data) {
            ajaxReturn(["status" => 0, "msg" => "分类不存在！"]);
        }
        if ($data['pid']) {

            $res = $m->destroy($id);
            if ($res) {
                $before_json = $data;
                $after_json = [];
                $content = '删除商品品牌系列分类';
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        } else {
            $res1 = $m->destroy($id);
            $id_arr = $m->where(['pid' => $id])->field('id')->select();
            if ($id_arr) {
                $data['cate_list'] = $id_arr;
            }

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
                $content = '删除商品品牌系列分类';
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
    }


    /**
     * goods列表
     */
    public function searchGoods()
    {
        $prom_id = request()->param('prom_id', '', 'intval');
        $goods_model = model('goods');
        $keyword = request()->param('keyword');
        $goods_cate_id = request()->param('goods_cate_id');
        $key_info = request()->param('key');
        $where = ['a.is_sale' => 1, 'a.is_audit' => 1, 'a.delete_time' => null];
        if ($prom_id != '') {
            $where['a.prom_id|b.prom_id'] = $prom_id;
        }
        if ($keyword) {
            $where['a.goods_name|a.goods_code'] = ['like', "%{$keyword}%"];
        }
        if ($goods_cate_id) {
            $where['a.cate_id|a.cate_two_id|a.cate_three_id'] = $goods_cate_id;
        }

        $this->assign('keyword', $keyword);
        $this->assign('goods_cate_id', $goods_cate_id);

        $goods_cate = model('goods_cate')->order('sort desc')->select();
        $goods_cate_new = [];
        foreach ($goods_cate as $key => $item) {
            $goods_cate_new[] = $item;
            $cate_list = model('goods_cate')->field('id,classname,pid')->where(['pid' => $item['id'], 'status' => '1'])->select();
            foreach ($cate_list as $k => $v) {
                $v['classname'] = '&nbsp;&nbsp;|--' . $v['classname'];
                $goods_cate_new[] = $v;
                $cate_list1 = model('goods_cate')->field('id,classname,pid')->where(['pid' => $v['id'], 'status' => '1'])->select();
                foreach ($cate_list1 as $k1 => $v1) {
                    $v1['classname'] = '&nbsp;&nbsp;&nbsp;&nbsp;|--' . $v1['classname'];
                    $goods_cate_new[] = $v1;
                }
            }
        }
        $this->assign("goods_cate", $goods_cate_new);
        if ($key_info == 1) {
            $list = $goods_model->alias('a')
                ->where($where)
                ->field('a.id, a.is_sale, a.goods_name, a.goods_logo, a.stores, a.price, a.price spec_price')
                ->order('a.sort desc')
                ->paginate(10, false, ['query' => request()->param()]);
        } else {
            $whereOr = $where;
            $whereOr['b.key'] = null;
            $list = model('spec_goods_price')
                ->alias('b')
                ->where($where)
                ->whereOr(function ($query) use ($whereOr) {
                    $query->where($whereOr);
                })
                ->join('tb_goods a', 'a.id = b.goods_id', 'RIGHT')
                ->field('a.id, a.is_sale, a.goods_name, a.goods_logo, a.stores, a.price, b.price spec_price, b.key, b.key_name')
                ->order('a.sort desc')
                ->paginate(10, false, ['query' => request()->param()]);
        }

        foreach ($list as $k => $v) {
            $list[$k]['key'] = isset($v['key']) ? $v['key'] : 0;
            $list[$k]['key'] = $v['key'] ? $v['key'] : 0;
            $list[$k]['key_name'] = isset($v['key_name']) ? $v['key_name'] : '';
            $list[$k]['key_name'] = $v['key_name'] ? $v['key_name'] : '';
        }
        /* $list_new = [];
         $i = 0;
         foreach ($list as $key=>$val) {
             $val['sku_id'] = 0;
             $val['sku_info'] = '';
             if ($val['is_sku'] == 1) {
                 $spec_goods = $this->get_spec_list($val['id']);
 //                $spec_goods = $this->get_goods_sku_list($val['id']);
                 foreach ($spec_goods as $k => $v) {
                     $list_new[$i] = $val;
                     $list_new[$i]['sku_id'] = $v['spec_key'];
                     $list_new[$i]['price'] = $v['price'];
                     $list_new[$i]['sku_info'] = $v['sku_info'];
                     $i++;
                 }
             } else {
                 $val['sku_id'] = 0;
                 $val['sku_info'] = '无';
                 $list_new[$i] = $val;
             }
             $i++;

         }

         $this->assign('list_new', $list_new);*/
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 商品评论管理
     * */
    public function comment()
    {
        $goodsname = request()->param('goodsname', '', 'trim');
        $keywords = request()->param('keyword', '', 'trim');
        $content = request()->param('content', '', 'trim');
        $id = request()->param('id', 0, 'intval');

        $map = array();
        $map['a.is_del'] = 0;
        if ($keywords) {
            $map['b.goods_name|a.content'] = array('like', "%{$keywords}%");
        }

        // if($keywords){
        //     $map['c.nickname|c.realname|c.telephone'] = array('like',"%{$keywords}%");
        //     $map['is_virtual'] = 0;
        // }
        if ($id) {
            $map['a.id'] = $id;
        }

        /*$count = model('goods_comment')
            ->alias('a')
            ->join('tb_goods as b ','on a.goods_id = b.id', 'left')
            // ->join('tb_member as c on a.user_id = c.id')
            ->where($map)->count();*/

        $lists = model('goods_comment')
            ->alias('a')
            ->join('goods b', 'a.goods_id = b.id', 'left')
            ->join('order c', 'a.order_id = c.id', 'left')
            ->where($map)
            ->field('a.*,c.order_no')
            ->order('a.id desc')
            ->paginate(10, false, ['query' => request()->param()]);

        if ($lists) {
            foreach ($lists as $k => $v) {
                $goods = model('goods')->find($v['goods_id']);
                $lists[$k]['goods_name'] = $goods['goods_name'];
                $lists[$k]['goods_logo'] = $goods['goods_logo'];
                if ($v['is_virtual'] == 0) {
                    $mem = model('user')->find($v['user_id']);
                    $lists[$k]['nickname'] = $mem['nickname'];
                    $lists[$k]['head_img'] = $mem['head_img'];
                    $lists[$k]['telephone'] = $mem['telephone'];
                }
            }
        }

        $this->assign('lists', $lists);
        return $this->fetch();
    }


    /**
     * 评论详情页
     * */
    public function commentDetail()
    {
        $id = request()->param('id', 0, 'intval');
        $info = model('goods_comment')->where(array('id' => $id))->find();
        if (!$info) {
            $this->error('参数错误！');
        }

        $goods = model('goods')->find($info['goods_id']);
        $info['goods_name'] = $goods['goods_name'];
        $info['goods_logo'] = $goods['goods_logo'];
        if ($info['is_virtual'] == 0) {
            $mem = model('user')->find($info['user_id']);
            $info['nickname'] = $mem['nickname'];
            $info['head_img'] = $mem['head_img'];
            $info['telephone'] = $mem['telephone'];
        }
        /* if($info['slide_img']){
             $slide_img_arr = explode(',', $info['slide_img']);
             $info['slide_img_arr'] = $slide_img_arr;
         }else{
             $info['slide_img_arr'] = array();
         }*/

        //标签
        $info['tag'] = array();
        if ($info['label']) {
//            $label_list = array();
            $tag_arr = explode(',', $info['label']);
            foreach ($tag_arr as $k => $v) {
                $map = array();
                $map['id'] = $v;
                $map['is_del'] = 0;
                /*$label = model('goods_comment_label')->where($map)->find();
                if($label){
                    $label_list[$k] = $label['label_name'];
                }*/
            }
//            $label_list = array_values($label_list);
//            $info['tag'] = $label_list;
        }
        $this->assign('cache', $info);

        //商品标签列表
//        $label_list = model('goods_comment_label')->where(array('is_del'=>0))->order('sort desc')->select();
//        $this->assign('label_list',$label_list);

        return $this->fetch();
    }

    /*状态操作*/
    public function changeCommentStatus()
    {
        if (request()->isPost()) {
            $ids = request()->post('ids', '', 'trim');

            if ($ids) {
                $value = model('goods_comment')->where(["id" => $ids])->value('is_show');
                $value = $value ? 0 : 1;
                $res = model('goods_comment')->where(["id" => $ids])->setField('is_show', $value);
                if (!$res) {
                    ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
                }
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            }
        }
    }

    public function delComment()
    {
        if (request()->isPOST()) {

            $ids = input('post.id');

            if (!$ids) {
                $json_arr = ["status" => 0, "msg" => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
                ajaxReturn($json_arr);
            }

            $arr = explode('-', ($ids));

            $data = [];
            foreach ($arr as $k => $v) {
                $data[$k] = model('GoodsComment')->where(['id' => $v])->find();
                $del = model('GoodsComment')->destroy($v);
                if (!$del) {
                    $json_arr = ["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                    ajaxReturn($json_arr);
                }

            }
            $before_json = $data;
            $after_json = [];
            $content = '删除商品评论';

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);

            $json_arr = ["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
            ajaxReturn($json_arr);
        }
    }


    /**
     * 商品类型  用于设置商品的属性
     */
    public function goodsTypeList()
    {
        $model = model("goods_type");
        $goodsTypeList = $model->order("id desc")->paginate(10, false, ['query' => request()->param()]);
        $this->assign('goodsTypeList', $goodsTypeList);
        return $this->fetch('goodsTypeList');
    }


    /**
     * 添加修改编辑  商品属性类型
     */
    public function addEditGoodsType()
    {
        $id = $this->request->param('id', 0);
        $model = model("GoodsType");
        if (request()->isPost()) {
            $data = request()->post();
            if ($id > 0) {
                $content = '修改商品类型';
                $field = array_keys($data);
                $field[] = 'id';
                $id = $data['id'];
                unset($data['id']);
                $before_json = $model->field($field)->where(['id' => $id])->find();
                $data['update_time'] = time();
                $res = $model->save($data, ['id' => $id]);
                $data['id'] = $id;
                $after_json = $data;
            } else {
                unset($data['id']);
                $before_json = [];
                $data['create_time'] = time();
                $content = '添加商品类型';
                $res = $model->save($data);
                $data['id'] = $model->getLastInsID();
                $after_json = $data;

            }
            if ($res) {

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
        $goodsType = $model->find($id);
        $this->assign('goodsType', $goodsType);
        return $this->fetch('_goodsType');
    }

    /**
     * 商品属性列表
     */
    public function goodsAttributeList()
    {

        $type_id = input('type_id');
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 '; // 搜索条件
        $type_id && $where = "$where and type_id = " . $type_id;
        // 关键词搜索
        $model = model('GoodsAttribute');
        $goodsAttributeList = $model->where($where)->order('order desc, attr_id desc')->paginate(10, false, ['query' => request()->param()]);
        $attr_input_type = array(0 => '手工录入', 1 => ' 从列表中选择', 2 => ' 多行文本框');
        $this->assign('attr_input_type', $attr_input_type);
        $this->assign('type_id', $type_id);
        $this->assign('goodsAttributeList', $goodsAttributeList);
        $goodsTypeList = model("GoodsType")->select(); // 规格分类
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');
        $this->assign('goodsTypeList', $goodsTypeList);
        return $this->fetch('goodsAttributeList');
    }


    /**
     * 添加修改编辑  商品属性
     */
    public function addEditGoodsAttribute()
    {

        $model = model("GoodsAttribute");
        $type = input('attr_id'); // 标识自动验证时的 场景 1 表示插入 2 表示更新
        $attr_values = str_replace('_', '', input('attr_values')); // 替换特殊字符
        $attr_values = str_replace('@', '', $attr_values); // 替换特殊字符
        $attr_values = trim($attr_values);

        $post_data = input('post.');
        $post_data['attr_values'] = $attr_values;

        if (request()->isPost())//ajax提交验证
        {
            // 数据验证
            $model->data($post_data, true); // 收集数据

            if ($type) {
                $content = '修改商品属性';
                $field = array_keys($post_data);
                $field[] = 'attr_id';
                $id = $post_data['attr_id'];
                unset($post_data['attr_id']);
                $post_data['update_time'] = time();
                $before_json = $model->field($field)->where(['attr_id' => $type])->find();
                $res = $model->save($post_data, ['attr_id' => $type]); // 写入数据到数据库
                $post_data['attr_id'] = $id;
                $after_json = $post_data;
            } else {
                unset($post_data['attr_id']);
                $before_json = [];
                $post_data['create_time'] = time();
                $content = '添加商品属性';
                $res = $model->save($post_data); // 写入数据到数据库
                $post_data['attr_id'] = $model->getLastInsID();
                $after_json = $post_data;
            }

            if ($res) {
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }

        }

        // 点击过来编辑时
        $attr_id = input('attr_id/d', 0);
        $goodsTypeList = model("GoodsType")->select();
        $goodsAttribute = $model->find($attr_id);
        $this->assign('goodsTypeList', $goodsTypeList);
        $this->assign('goodsAttribute', $goodsAttribute);
        return $this->fetch('_goodsAttribute');
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput()
    {
        $goods_id = request()->param('goods_id');
        $type_id = request()->param('type_id');
        $str = $this->getAttrInput($goods_id, $type_id);
        exit($str);
    }

    /**
     * 删除商品类型
     */
    public function delGoodsType()
    {
        // 判断 商品规格
        $id = $this->request->param('id');
        $count = model("Spec")->where("type_id = {$id}")->count("1");
        if ($count > 0) {
            ajaxReturn(["status" => 0, "msg" => '该类型下有商品规格不得删除']);
        }
        // 判断 商品属性
        $count = model("GoodsAttribute")->where("type_id = {$id}")->count("1");
        if ($count > 0) {
            ajaxReturn(["status" => 0, "msg" => '该类型下有商品属性不得删除']);
        }
        $data = model('GoodsType')->where(['id' => $id])->find();
        // 删除分类
        $result = model('GoodsType')->destroy($id);
        if ($result) {
            $before_json = $data;
            $after_json = [];
            $content = '删除商品类型';
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        } else {
            ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
        }
    }

    /**
     * 删除商品属性
     */
    public function delGoodsAttribute()
    {
        // 判断 商品规格
        $id = input('id');

        // 判断 商品规格项
        $count = model("GoodsAttr")->where("attr_id = {$id}")->count("1");
        if ($count > 0) {
            ajaxReturn(["status" => 0, "msg" => '有商品使用该属性,不得删除!']);
        }
        $data = model('GoodsAttribute')->where(['attr_id' => $id])->find();
        // 删除分类
        $result = model('GoodsAttribute')->destroy($id);
        if ($result) {
            $before_json = $data;
            $after_json = [];
            $content = '删除商品属性';
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        } else {
            ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
        }
    }

    /**
     * 删除商品规格
     */
    public function delGoodsSpec()
    {
        // 判断 商品规格
        $id = input('id');

        // 判断 商品规格项
        $count = model("SpecItem")->where("spec_id = {$id}")->count("1");
        if ($count > 0) {
            ajaxReturn(["status" => 0, "msg" => '清空规格项后才可以删除']);
        }
        $data = model('Spec')->where(['id' => $id])->find();
        // 删除分类
        $result = model('Spec')->destroy($id);
        if ($result) {
            $before_json = $data;
            $after_json = [];
            $content = '删除商品规格';
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        } else {
            ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
        }
    }

    /**
     * 商品规格列表
     */
    public function specList()
    {
        $goodsTypeList = model("GoodsType")->select();
        $this->assign('goodsTypeList', $goodsTypeList);
        $type_id = input('type_id');
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 '; // 搜索条件
        $type_id && $where = "$where and type_id = " . $type_id;
        // 关键词搜索
        $model = model('spec');
        $specList = $model->where($where)->order('type_id desc')->paginate(10, false, ['query' => request()->param()]);

        foreach ($specList as $k => $v) {       // 获取规格项
            $arr = $this->getSpecItem($v['id']);
            $specList[$k]['spec_item'] = implode(' , ', $arr);
        }

        $this->assign('type_id', $type_id);
        $this->assign('specList', $specList);
        $goodsTypeList = model("GoodsType")->select(); // 规格分类
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');
        $this->assign('goodsTypeList', $goodsTypeList);
        return $this->fetch('specList');
    }


    /**
     * 添加修改编辑  商品规格
     */
    public function addEditSpec()
    {

        $model = model("spec");
        $id = request()->param('id', 0);
        if (request()->isPost())//ajax提交验证
        {
            // 数据验证
            $data = request()->post();

            if (empty($data['name'])) {
                $return_arr = ['status' => 0, 'msg' => '请填写规格名称'];
                ajaxReturn($return_arr);
            }
            if (empty($data['type_id'])) {
                $return_arr = ['status' => 0, 'msg' => '请填写商品类型'];
                ajaxReturn($return_arr);
            }
            if (empty($data['items'])) {
                //$return_arr = ['status' => 0, 'msg' => '请填写规格项'];
                //ajaxReturn($return_arr);
            }

            $post_data['name'] = $data['name'];
            $post_data['type_id'] = $data['type_id'];

            if ($id) {
                $content = '修改商品规格';
                $field = array_keys($post_data);
                $field[] = 'id';
                $post_data['update_time'] = time();
                $before_json = $model->field($field)->where(['id' => $id])->find();
                $res = $model->save($post_data, ['id' => $id]); // 写入数据到数据库
                $model->afterSave($id, $data['items']);
                $post_data['id'] = $id;
                $after_json = $post_data;
            } else {
                unset($id);
                $before_json = [];
                $post_data['create_time'] = time();
                $content = '添加商品规格';
                $res = $model->save($post_data); // 写入数据到数据库
                $post_data['id'] = $model->getLastInsID();
                $model->afterSave($post_data['id'], $data['items']);
                $after_json = $post_data;
            }

            if ($res) {
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }

        }
        // 点击过来编辑时
        $type_id = input('type_id');
        $spec = $model->where(['id' => $id])->find();
        if ($spec) {
            $spec['items'] = $this->getSpecItem($id);
            //dump($spec['items']);
        }
        $this->assign('spec', $spec);
        $where = [];
        if ($type_id) {
            $where['id'] = $type_id;
        }
        $goodsTypeList = model("GoodsType")->where($where)->select();
        $this->assign('goodsTypeList', $goodsTypeList);
        return $this->fetch('_spec');
    }

    public function add_spec()
    {
        if (request()->isPost()) {
            $name = request()->post('name');
            $goods_id = request()->post('goods_id', 0);
            model('spec')->save(['name' => $name, 'goods_id' => $goods_id]);
            $spec_id = model('spec')->getLastInsID();
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['spec_id' => $spec_id]]);
        }
    }

    public function del_spec()
    {
        if (request()->isPost()) {
            $spec_id = request()->post('spec_id');
            $result = model('spec')->destroy($spec_id);
            if ($result) {
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
    }

    public function del_spec_item()
    {
        if (request()->isPost()) {
            $spec_id = request()->post('spec_id');
            $result = model('spec_item')->destroy($spec_id);
            if ($result) {
                ajaxReturn(["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(["status" => 0, "msg" => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        }
    }

    public function add_spec_item()
    {
        if (request()->isPost()) {
            $spec_id = request()->post('spec_id');
            $name = request()->post('item_name');
            model('spec_item')->save(['item' => $name, 'spec_id' => $spec_id]);
            $spec_id = model('spec_item')->getLastInsID();
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['spec_id' => $spec_id]]);
        }
    }

    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect()
    {
        $goods_id = input('get.goods_id/d') ? input('get.goods_id/d') : 0;
        /*$type = input('get.spec_type/d');
        if (!$type) {
            return false;
        }*/

        //$_GET['spec_type'] =  13;
        if ($goods_id) {
            $specList = model('Spec')->where('goods_id', $goods_id)->order('order desc')->select();
        } else {
            $specList = [];
        }

        foreach ($specList as $k => $v) {
            $item = model('SpecItem')->where("spec_id = " . $v['id'])->order('id')->field('id,item')->select(); // 获取规格项
            $specList[$k]['spec_item'] = get_id_val($item, 'id', 'item');
        }

        $items_id = model('SpecGoodsPrice')->where('goods_id = ' . $goods_id)->value("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);
        $specImageList = [];
        // 获取商品规格图片
        if ($goods_id) {
            $item = model('SpecImage')->where("goods_id = $goods_id")->field('spec_image_id,src')->select();
            $specImageList = get_id_val($item, 'spec_image_id', 'src');
        }
        $this->assign('specImageList', $specImageList);

        $this->assign('items_ids', $items_ids);
        $this->assign('specList', $specList);
        $this->assign('goods_id', $goods_id);
        return $this->fetch('ajax_spec_select');
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput()
    {

        $goods_id = input('goods_id/d') ? input('goods_id') : 0;
        $data = request()->post();
        if (isset($data['spec_arr'])) {
            $spec_arr = $data['spec_arr'];
        } else {
            $spec_arr = [];
        }
        $str = $this->getSpecInput($goods_id, $spec_arr, $this->manager_id);
        exit($str);
    }

}