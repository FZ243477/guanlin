<?php
namespace app\api\controller;

use app\common\constant\SystemConstant;
use app\common\helper\VerificationHelper;
use think\Db;

class Collection extends Base
{
    use VerificationHelper;

    public function __construct()
    {
        parent::__construct();
    }

    public function collectionNum()
    {
        $collection_model = model('collection');
        $where['user_id'] = $this->user_id;
        $where['type'] = 1;
        $where['status'] = 1;
        $collection_num1 = $collection_model->where($where)->count();
        $where['type'] = 2;
        $collection_num2 = $collection_model->where($where)->count();
        $where['type'] = 3;
        $collection_num3 = $collection_model->where($where)->count();
        $data = [
            'collection_num1' => $collection_num1,
            'collection_num2' => $collection_num2,
            'collection_num3' => $collection_num3,
        ];
        $json_arr = ['status' => 1, 'msg' => '操作成功', 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 收藏列表
     */
    function collectionList(){
        $map = [];
        $map['user_id'] = $this->user_id;
        $collection_model = model('collection');
        $list_row = request()->post('list_row', 10); //每页数据
        $page = request()->post('page', 1); //当前页
        $type = request()->post('type', 1); //当前页
        if(!$type || !in_array($type, [1,2,3])){
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM];
            ajaxReturn($json_arr);
        }
        $where['user_id'] = $this->user_id;
        $where['type'] = $type;
        $where['status'] = 1;
        $totalCount = $collection_model->where($where)->count();
        $first_row = ($page-1)*$list_row;
        $field = ['param_id,type'];
        $lists = $collection_model->where($where)->field($field)->limit($first_row, $list_row)->order('id asc')->select();
        if ($type == 1) {
            foreach ($lists as $k => $v){
                $lists[$k]['detail'] = model('houses_case')
                    ->alias('hc')
                    ->join('houses_type ht', 'ht.id = hc.houses_type_id', 'left')
                    ->where(['hc.id' => $v['param_id']])
                    ->field('hc.id,hc.name,hc.logo,hc.total_price,ht.area,ht.space,ht.style')
                    ->find();
            }
        } else if ($type == 2) {
            foreach ($lists as $k => $v){
                $lists[$k]['detail'] = model('houses_designer')
                    ->alias('hd')
                    ->join('houses_designer_level hdl', 'hdl.id = hd.level_id', 'left')
                    ->where(['hd.id' => $v['param_id']])
                    ->field('hd.id,hd.designer_name,hd.telephone,hd.designer_logo,hd.exp,hd.city,hdl.level_name')
                    ->find();
            }
        } else {
            foreach ($lists as $k => $v){
                $lists[$k]['detail'] = model('goods')
                    ->where(['id' => $v['param_id']])
                    ->field('id,goods_name,goods_logo,goods_price,goods_oprice,collection_num')
                    ->find();
            }
        }

        $pageCount = ceil($totalCount/$list_row);
        $data = [
            'list' => $lists ? $lists : [],
            'pageInfo' => [
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
                'page' => $page,
            ]
        ];
        $json_arr = ['status' => 1, 'msg' => '操作成功', 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 添加/取消收藏
     */
    function collectionAdd(){
        $param_id = request()->post('param_id');
        $type = request()->post('type');
        $collection_model = model('collection');
        if(!$param_id || !$type || !in_array($type, [1,2,3])){
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM];
            ajaxReturn($json_arr);
        }
        $list = $collection_model->where([
            'user_id' => $this->user_id,
            'param_id' => $param_id,
            'type' => $type
        ])->field('id,status')->find();
        Db::startTrans();
        if($list){
            $status = 1 - $list['status'];
            $res = $collection_model->save(['status' => $status], ['id' => $list['id']]);
            if ($status == 0) {
                if($res){
                    $json_arr = ['status' => 1, 'msg' => '取消收藏成功'];
                }else{
                    $json_arr = ['status' => 0, 'msg' => '取消收藏失败'];
                }
            } else {
                if($res){
                    $json_arr = ['status' => 1, 'msg' => '收藏成功'];
                }else{
                    $json_arr = ['status' => 1, 'msg' => '收藏失败'];
                }
            }
        }else{
            $status = 1;
            $res = $collection_model->save([
                'user_id' => $this->user_id,
                'param_id' => $param_id,
                'type' => $type,
                'status' => $status,
            ]);
            if($res){
                $json_arr = ['status' => 1, 'msg' => '收藏成功'];
            }else{
                $json_arr = ['status' => 1, 'msg' => '收藏失败'];
            }
        }
        if (!$res) {
            Db::rollback();
        }
        if ($type == 1) {
            $model = model('houses_case');
        } else if ($type == 2) {
            $model = model('houses_designer');
        } else {
            $model = model('goods');
        }
        if ($status == 1) {
            $res = $model->where(['id' => $param_id])->setInc('collection_num', 1);
        } else {
            $res = $model->where(['id' => $param_id])->setDec('collection_num', 1);
        }
        if (!$res) {
            Db::rollback();
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE];
        } else {
            Db::commit();
        }
        ajaxReturn($json_arr);
    }

}