<?php


namespace app\admin\controller;
use app\admin\helper\ManagerHelper;
use app\common\helper\OriginalSqlHelper;
use app\common\constant\SystemConstant;

class Banner extends Base
{
    use ManagerHelper;
    use OriginalSqlHelper;

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * banner列表
     */
    public function bannerList()
    {
        $banner_model = model('banner');
        $keyword = request()->param('keyword');
        $banner_cate_id = request()->param('banner_cate_id');
        $where = [];
        if ($keyword) {
            $where['banner_name'] = ['like', "%{$keyword}%"];
        }
        if ($banner_cate_id) {
            $where['banner_cate_id'] = $banner_cate_id;
        }

        $this->assign('keyword', $keyword);
        $this->assign('banner_cate_id', $banner_cate_id);

        $banner_cate = model('banner_cate')->order('sort desc')->select();
        $this->assign("banner_cate", $banner_cate);

        $list = $banner_model->where($where)->order('sort desc, banner_id desc')->paginate(10,false,['query'=>request()->param()]);
       dump($list);exit;
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 添加banner列表
     */
    public function bannerAdd()
    {
        $banner_model = model('banner');
        $banner_id = request()->param('banner_id');
        $where = ['banner_id' => $banner_id];
        $cache = $banner_model->where($where)->find();
        if ($cache['goods_id']) {
            $cache['goods_name'] = model('goods')->where(['id' => $cache['goods_id']])->value('goods_name');
        }
        $this->assign("cache", $cache);
        $banner_cate = model('banner_cate')->order('sort desc')->select();
        $this->assign("banner_cate", $banner_cate);
        return $this->fetch();
    }

    /**
     * 改变banner分类
     */
    public function bannerChange()
    {
        if (request()->isPOST()) {
            $id = request()->post('banner_id');
            $cate_id = request()->post('banner_cate_id');

            if (!$id || !$cate_id) {
                exit(json_encode(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]));
            }
            $arr = array_unique(explode('-',($id)));
            $data =['banner_id' => ['in', $arr]];
            $coupon = model('banner')->where($data)->field('banner_cate_id')->find();
            $result = model('banner')->where($data)->setField('banner_cate_id', $cate_id);
            if($result){
                $content = '修改所属分类';
                $before_json = ['banner_id' => $id, 'banner_cate_id' => $coupon['banner_cate_id']];
                $after_json = ['banner_id' => $id, 'banner_cate_id' => $cate_id];
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $json_arr = ["status"=>1, "msg"=> SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
                ajax_return($json_arr);
            }else{
                $json_arr = ["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                ajax_return($json_arr);
            }
        }
    }


    /**
     * 操作banner
     */
    public function bannerHandle()
    {
        if (request()->isPost()) {
            $data = request()->post();
            $id = request()->post('banner_id', 0);
            $banner_model = model('banner');

            if (!$data['banner_cate_id']) {
                ajax_return(['status' => 0, 'msg' => '请选择banner分类', 'data' => []]);
            }

            /* if (!$data['banner_name']) {
                 ajax_return(['status' => 0, 'msg' => '请填写banner名称', 'data' => []]);
             }

             if (!$data['banner_describe']) {
                 ajax_return(['status' => 0, 'msg' => '请填写banner描述', 'data' => []]);
             }*/


            if (!isset($data['banner_pic'])) {
                ajax_return(['status' => 0, 'msg' => '请上传banner图片', 'data' => []]);
            }

            if ($data['link_type'] == 0) {
                $data['link_url'] = '';
                $data['goods_id'] = 0;
            } else if ($data['link_type'] == 1) {
                $data['goods_id'] = 0;
                if (!$data['link_url']) {
                    ajax_return(['status' => 0, 'msg' => '请填写http链接', 'data' => []]);
                }
            } else if ($data['link_type'] == 2){
                $data['link_url'] = '';
                if (!$data['goods_id']) {
                    ajax_return(['status' => 0, 'msg' => '请选择商品', 'data' => []]);
                }
            }

            $data['sort'] = $this->getSort($data['sort'], $banner_model, $id, [], 'banner_id');

            if ($id) {
                $data['update_time'] = time();
                $content = '修改banner信息';
                $field = array_keys($data);
                $field[] = 'banner_id';
                $before_json = $banner_model->field($field)->where(['banner_id' =>  $id])->find();
                $result = $banner_model->save($data, ['banner_id' => $id]);
                $data['banner_id'] = $id;
                $after_json = $data;
            } else {
                $data['create_time'] = time();
                $content = '添加banner信息';
                $before_json = [];
                $result = $banner_model->save($data);
                $data['banner_id'] = $banner_model->getLastInsID();
                $after_json = $data;
            }
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($result) {
                ajax_return(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajax_return(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }
    /**
     * 操作banner状态
     */
    public function bannerStatus()
    {
        if (request()->isPOST()) {
            $id = request()->post('banner_id');
            $item = request()->post('item');
            if (!$id) {
                ajax_return(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }
            $data =['banner_id' => $id];
            $coupon = model('banner')->where($data)->field($item)->find();
            $result = model('banner')->where($data)->setField($item, 1-$coupon[$item]);
            if($result){
                $content = '修改Banner显示状态';
                $before_json = ['banner_id' => $id, $item => $coupon[$item]];
                $after_json = ['banner_id' => $id, $item => 1-$coupon[$item]];

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $json_arr = ["status"=>1, "msg"=> SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => [$item => 1-$coupon[$item]]];
            }else{
                $json_arr = ["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            }
            return $json_arr;
        }
    }
    /**
     * 删除Banner
     */
    public function delBanner()
    {
        if (request()->isPOST()) {

            $ids = request()->post('banner_id');

            $banner_model = model('banner');
            if (!$ids) {
                ajax_return(["status"=>0, "msg"=>SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }

            $arr = array_unique(explode('-',($ids)));

            $data = $banner_model->where(['banner_id' => ['in', $arr]])->find();

            $del = $banner_model->destroy($arr);

            if($del){
                $before_json = $data;
                $after_json = [];
                $content = '删除Banner';

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajax_return(["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            }else{
                ajax_return(["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }

        }
    }

    /**
     * 移动banner排序
     */
    public function upDown()
    {
        if (request()->isPOST()) {
            $data   = request()->post('');
            $id = $data['banner_id'];
            $num = $data['num'];
            $search = isset($data['search'])?$data['search']:[];

            $result = $this->getUpDown('Banner', $id, $num, $search,'banner_id', 'desc');

            if ($result['status']  == 1) {

                $content = 'banner排序移动';
                $before_json = ['banner_id' => $id, 'sort' => $result['data']['old_sort']];
                $after_json = ['banner_id' => $id, 'sort' => $result['data']['new_sort']];
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajax_return(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else if ($result['status'] == 2) {
                ajax_return(['status' => 0, 'msg' => $result['msg'], 'data' => []]);
            } else {
                ajax_return(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }

    /**
     * banner列表
     */
    public function bannerCateList()
    {
        $banner_model = model('BannerCate');
        $keyword = request()->param('keyword');
        $where = [];
        if ($keyword) {
            $where['banner_cate_name'] = ['like', "%{$keyword}%"];
        }

        $list = $banner_model->where($where)->order('sort desc, banner_cate_id desc')->paginate(10,false,['query'=>request()->param()]);

        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 添加banner列表
     */
    public function bannerCateAdd()
    {
        $banner_model = model('BannerCate');
        $banner_id = request()->param('banner_cate_id');
        $where = ['banner_cate_id' => $banner_id];
        $cache = $banner_model->where($where)->find();

        $this->assign("cache", $cache);
        return $this->fetch();
    }


    /**
     * 操作banner
     */
    public function bannerCateHandle()
    {
        if (request()->isPost()) {
            $data = request()->post();
            $id = request()->post('banner_cate_id', 0);
            $banner_model = model('BannerCate');


            $data['sort'] = $this->getSort($data['sort'], $banner_model, $id, [], 'banner_cate_id');

            if ($id) {
                $data['update_time'] = time();
                $content = '修改banner分类信息';
                $field = array_keys($data);
                $field[] = 'banner_cate_id';
                $before_json = $banner_model->field($field)->where(['banner_cate_id' =>  $id])->find();
                $result = $banner_model->save($data, ['banner_cate_id' => $id]);
                $data['banner_cate_id'] = $id;
                $after_json = $data;

            } else {
                $data['create_time'] = time();
                $content = '添加banner分类信息';
                $before_json = [];
                $result = $banner_model->save($data);
                $data['banner_cate_id'] = $banner_model->getLastInsID();
                $after_json = $data;
            }

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($result) {
                ajax_return(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajax_return(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }

    /**
     * 删除Banner
     */
    public function delBannerCate()
    {
        if (request()->isPOST()) {

            $ids = request()->post('banner_cate_id');

            $banner_model = model('BannerCate');
            if (!$ids) {
                ajax_return(["status"=>0, "msg"=>SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }

            $arr = array_unique(explode('-',($ids)));

            $data = $banner_model->where(['banner_cate_id' => ['in', $arr]])->find();

            $del = $banner_model->destroy($arr);

            if($del){
                $before_json = $data;
                $after_json = [];
                $content = '删除banner分类';

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajax_return(["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            }else{
                ajax_return(["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }

        }
    }

    /**
     * 移动banner分类排序
     */
    public function upDownCate()
    {
        if (request()->isPOST()) {
            $data   = request()->post('');
            $id = $data['banner_cate_id'];
            $num = $data['num'];
            $search = isset($data['search'])?$data['search']:[];

            $result = $this->getUpDown('BannerCate', $id, $num, $search,'banner_cate_id', 'desc');

            if ($result['status']  == 1) {

                $content = '首页banner分类分类排序移动';
                $before_json = ['banner_cate_id' => $id, 'sort' => $result['data']['old_sort']];
                $after_json = ['banner_cate_id' => $id, 'sort' => $result['data']['new_sort']];
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajax_return(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else if ($result['status'] == 2) {
                ajax_return(['status' => 0, 'msg' => $result['msg'], 'data' => []]);
            } else {
                ajax_return(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }
}