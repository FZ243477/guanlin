<?php
namespace app\api\controller;
use app\common\constant\UserConstant;
use app\common\helper\UserHelper;
use app\common\constant\SystemConstant;
use app\common\helper\VerificationHelper;

class Collection extends Base
{
    use VerificationHelper;
    use UserHelper;
    public function __construct()
    {
        parent::__construct();
    }


    //收藏列表
    function collectionList(){
        if(request()->isPost()){
            $map = [];
            $map['user_id'] = $this->user_id;
            $collection_model = model('collection');
            $list_row = request()->post('list_row', 2); //每页数据
            $page = request()->post('page', 1); //当前页
            $where['user_id'] = $this->user_id;
            $where['status'] = 1;
            $totalCount = $collection_model->where($where)->count();
            $first_row = ($page-1)*$list_row;
            $field = ['id','goods_id'];
            $lists = $collection_model->where($where)->field($field)->limit($first_row, $list_row)->order('id asc')->select();
            foreach ($lists as $k=>$val){
                $lists1 = model('goods')->field('goods_pic,goods_name,price')->where(['id' => $val['goods_id']])->find();
                $lists[$k]['goods_pic'] = $lists1['goods_pic'];
                $lists[$k]['goods_name'] = $lists1['goods_name'];
		$lists[$k]['price'] = $lists1['price'];
            }
            $pageCount = ceil($totalCount/$list_row);
            $data = [
                'list' => $lists ? $lists : [],
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
            ];

            $json_arr = ['status' => 1, 'msg' => '操作成功', 'data' => $data];

            ajaxReturn($json_arr);
        }
    }

    //添加收藏
    function collectionAdd(){
        if(request()->isPost()){
            $map = [];
            $map['user_id'] = $this->user_id;
            $map['goods_id'] = input('goods_id');
            $map1 = [];
            $map1['id'] = $map['goods_id'];
            $collection_model = model('collection');
            if($map['goods_id']){
                $list = $collection_model->where($map)->find();
                if($list){
                    $addr_data['update_time'] = strtotime(date('Y-m-d H:i:s',time()));
                    if($list['status']==1){
                        $data['status'] = 0;
                        $res = $collection_model->save($data,['id' => $list['id']]);
                        if($res){
                            $json_arr = ['status' => 1, 'msg' => '取消收藏成功', 'data' => []];
                        }else{
                             $json_arr = ['status' => 0, 'msg' => '取消收藏失败', 'data' => []];
                        }
                    }else{
                        $data['status'] = 1;
                        $res = $collection_model->save($data,['id' => $list['id']]);
                        if($res){
                            $json_arr = ['status' => 1, 'msg' => '收藏成功', 'data' => []];
                        }else{
                             $json_arr = ['status' => 0, 'msg' => '收藏失败', 'data' => []];
                        }
                    }     
                }else{
                    $list1 = model('goods')->where($map1)->find();
                    if($list1){
                        $data['user_id'] = $map['user_id'];
                        $data['goods_id'] = $map['goods_id'];
                        $data['goods_price'] = $list1['price'];
                        $data['status'] = 1;
                        $data['create_time'] = time();
                        $addC_id = $collection_model->save($data);
                        if($addC_id){
                            $json_arr = ['status' => 1, 'msg' => '收藏成功', 'data' => []];
                        }else{
                            $json_arr = ['status' => 1, 'msg' => '收藏失败', 'data' => []];
                        }
                    }else{
                        if($list1['is_sale']==0){
                            $json_arr = ['status' => 0, 'msg' => '该商品已下架', 'data' => []];
                        }
                        $json_arr = ['status' => 0, 'msg' => '该商品不存在或已下架', 'data' => []];
                    } 
                }
            }else{
                $json_arr = ['status' => 0, 'msg' => '参数错误', 'data' => []];
            }
            ajaxReturn($json_arr);
            
        }
    }

    //删除收藏
    function collectionDel(){
        if(request()->isPost()){
            $collection_id = input('id'); // 商品 ids

            if (!$collection_id) {
                $return_arr = ['status' => 0, 'msg' => '参数错误','data'=> []]; // 返回结果状态
                ajaxReturn($return_arr);
            }
            $id_arr = explode(',', $collection_id);
            $result = model('collection')->destroy($id_arr); 

            if ($result) {
                $return_arr = ['status'=>1, 'msg' => '删除成功' ,'data'=> []];
            } else {
                $return_arr = ['status'=>0, 'msg' => '删除成功' ,'data'=> []];
            }
            ajaxReturn($return_arr);
        }
    }
}