<?php


namespace app\admin\controller;
use app\admin\helper\ManagerHelper;
use app\common\helper\OriginalSqlHelper;
use app\common\constant\SystemConstant;

class HousesCase extends Base
{
    use ManagerHelper;
    use OriginalSqlHelper;

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 方案列表
     */
    public function housesCaseList()
    {
        $houses_case_model = model('houses_case');
        $keyword = request()->param('keyword');
        $where = [];
        if ($keyword) {
            $where['houses_case_name'] = ['like', "%{$keyword}%"];
        }
        $this->assign('keyword', $keyword);
        $list = $houses_case_model->where($where)->order('sort desc, id desc')->paginate(10,false,['query'=>request()->param()]);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 添加方案
     */
    public function housesCaseAdd()
    {
        $houses_case_model = model('houses_case');
        $id = request()->param('id');
        $where = ['id' => $id];
        $cache = $houses_case_model->where($where)->find();
        $this->assign("cache", $cache);
        $houses_case_cate = model('houses')->order('sort desc')->select();
        $this->assign("houses_case_cate", $houses_case_cate);
        return $this->fetch();
    }


    /**
     * 操作方案添加修改
     */
    public function housesCaseHandle()
    {
        if (request()->isPost()) {
            $data = request()->post();
            $id = request()->post('id', 0);
            $houses_case_model = model('houses_case');
            $this->housesCaseVerification($data);
            $data['sort'] = $this->getSort($data['sort'], $houses_case_model, $id, [], 'id');
            if ($id) {
                $data['update_time'] = time();
                $content = '修改houses_case信息';
                $field = array_keys($data);
                $field[] = 'id';
                $before_json = $houses_case_model->field($field)->where(['id' =>  $id])->find();
                $result = $houses_case_model->save($data, ['id' => $id]);
                $data['id'] = $id;
                $after_json = $data;
            } else {
                $data['create_time'] = time();
                $content = '添加houses_case信息';
                $before_json = [];
                $result = $houses_case_model->save($data);
                $data['id'] = $houses_case_model->getLastInsID();
                $after_json = $data;
            }
            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($result) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }

    /**
     * 添加判断
     * @param $data
     */
    protected function housesCaseVerification($data)
    {
        if (!$data['type']) {
            ajaxReturn(['status' => 0, 'msg' => '请选择类型']);
        }
        if (!$data['designer_id']) {
            ajaxReturn(['status' => 0, 'msg' => '请选择设计师']);
        }
        if (!$data['houses_type_id']) {
            ajaxReturn(['status' => 0, 'msg' => '请选择户型']);
        }
        if (!$data['name']) {
            ajaxReturn(['status' => 0, 'msg' => '请填写名称']);
        }
        if (!$data['logo']) {
            ajaxReturn(['status' => 0, 'msg' => '请填上传logo']);
        }
        if ($data['type'] == 1 && !$data['vr']) {
            ajaxReturn(['status' => 0, 'msg' => '请填全景图链接']);
        }
        if (($data['type'] == 2 || $data['type'] == 3) && !$data['background']) {
            ajaxReturn(['status' => 0, 'msg' => '请填上传背景图']);
        }
        if (($data['type'] == 2 || $data['type'] == 3) && !$data['content']) {
            ajaxReturn(['status' => 0, 'msg' => '请填文章内容']);
        }
    }

    /**
     * 操作方案状态
     */
    public function housesCaseStatus()
    {
        if (request()->isPOST()) {
            $id = request()->post('id');
            $item = request()->post('item');
            if (!$id) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }
            $data =['id' => $id];
            $coupon = model('houses_case')->where($data)->field($item)->find();
            $result = model('houses_case')->where($data)->setField($item, 1-$coupon[$item]);
            if($result){
                $content = '修改houses_case显示状态';
                $before_json = ['id' => $id, $item => $coupon[$item]];
                $after_json = ['id' => $id, $item => 1-$coupon[$item]];

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $json_arr = ["status"=>1, "msg"=> SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => [$item => 1-$coupon[$item]]];
            }else{
                $json_arr = ["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            }
            return $json_arr;
        }
    }

    /**
     * 删除方案
     */
    public function delHousesCase()
    {
        if (request()->isPOST()) {

            $ids = request()->post('id');

            $houses_case_model = model('houses_case');
            if (!$ids) {
                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }

            $arr = array_unique(explode('-',($ids)));

            $data = $houses_case_model->where(['id' => ['in', $arr]])->find();

            $del = $houses_case_model->destroy($arr);

            if($del){
                $before_json = $data;
                $after_json = [];
                $content = '删除houses_case';

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            }else{
                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }

        }
    }

    /**
     * 移动方案排序
     */
    public function upDown()
    {
        if (request()->isPOST()) {
            $data   = request()->post('');
            $id = $data['id'];
            $num = $data['num'];
            $search = isset($data['search'])?$data['search']:[];

            $result = $this->getUpDown('houses_case', $id, $num, $search,'id', 'desc');

            if ($result['status']  == 1) {

                $content = 'houses_case排序移动';
                $before_json = ['id' => $id, 'sort' => $result['data']['old_sort']];
                $after_json = ['id' => $id, 'sort' => $result['data']['new_sort']];
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else if ($result['status'] == 2) {
                ajaxReturn(['status' => 0, 'msg' => $result['msg'], 'data' => []]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }

    public function selectDesigner()
    {
        $houses_designer_model = model('houses_designer');
        $keyword = request()->param('keyword');
        $where = [];
        if ($keyword) {
            $where['designer_name'] = ['like', "%{$keyword}%"];
        }
        $this->assign('keyword', $keyword);
        $list = $houses_designer_model->where($where)->order('id desc')->paginate(10,false,['query'=>request()->param()]);
        $this->assign('list', $list);
        return $this->fetch();
    }
}