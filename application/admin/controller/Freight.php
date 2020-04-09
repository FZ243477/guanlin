<?php


namespace app\admin\controller;
use app\admin\helper\ManagerHelper;
use app\common\helper\OriginalSqlHelper;
use app\common\constant\SystemConstant;

class Freight extends Base
{
    use ManagerHelper;
    use OriginalSqlHelper;

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 运费规则
     */
    public function freightList()
    {
        $freight_model = model('freight');
        $where = [];
        $keyword = request()->param('keyword');
        if ($keyword) {
            $where['title'] = ['like', "%{$keyword}%"];
        }

        $list = $freight_model->where($where)->order('id desc')->paginate(10,false,['query'=>request()->param()]);
        foreach ($list as $k => $v) {
            $city_list = model('region')->where(['id' => ['in', explode(',', $v['city_id'])]])->field('name,parent_id')->select();
            $city_info = '';
            foreach ($city_list as $k1 => $v1) {
                $province = model('region')->where(['id' => $v1['parent_id']])->value('name');
                $city_info .= '&nbsp;&nbsp;'.$province.'-'.$v1['name'];
                if ($k1 % 4 == 3) {
                    $city_info .= '<br>';
                }
            }
            $list[$k]['province_info'] = $city_info;
        }
        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 添加运费规则
     */
    public function freightAdd()
    {
        $freight_model = model('freight');
        $freight_id = request()->param('freight_id');
        $where = ['id' => $freight_id];
        $cache = $freight_model->where($where)->find();
        $this->assign("cache", $cache);

        $where = [];
        if ($freight_id) {
            $where['id'] = ['neq', $freight_id];
        }
        $freight = $freight_model->where($where)->field('province_id,city_id')->select();
        $province_id = '';
        $city_id = '';
        foreach ($freight as $k => $v) {
            $province_id .= $province_id?','.$v['province_id']:$v['province_id'];
            $city_id .= $city_id?','.$v['city_id']:$v['city_id'];
        }

        $where = ['parent_id' => 0];
        if ($freight_id) {
            $where['id'] = ['neq', $freight_id];
        }

        $where = ['parent_id' => 0];
        if ($province_id) {
            $where['id'] = ['notin', explode(',', $province_id)];
        };
        if ($city_id) {
            $whereCity['id'] = ['notin', explode(',', $city_id)];
        };
        $province = model('region')->where($where)->select();
        foreach ($province as $k => $v) {
            if ($cache && in_array($v['id'], explode(',', $cache['province_id']))) {
                $province[$k]['selected'] = 1;
            } else {
                $province[$k]['selected'] = 0;
            }
            $whereCity['parent_id'] = $v['id'];
            $city = model('region')->where($whereCity)->select();
            foreach ($city as $key => $val) {
                if ($cache && in_array($val['id'], explode(',', $cache['city_id']))) {
                    $city[$key]['selected'] = 1;
                } else {
                    $city[$key]['selected'] = 0;
                }
            }
            $province[$k]['city'] = $city;
        }

        $this->assign('province', $province);
//        $cate_list = model('freight_cate')->order('sort desc, id desc')->select();
//        $this->assign("cate_list", $cate_list);
        return $this->fetch();
    }


    /**
     * 操作freight
     */
    public function freightHandle()
    {
        if (request()->isPost()) {
            $data = request()->post();
            $id = request()->post('id', 0);
            $freight_model = model('freight');

            if (!$data['title']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写标题', 'data' => []]);
            }

            if (!isset($data['data'])) {
                ajaxReturn(['status' => 0, 'msg' => '请选择省市', 'data' => []]);
            }
            $province_id = isset($data['data']['province'])?implode(',', $data['data']['province']):'';
            $city_id = isset($data['data']['city'])?implode(',', $data['data']['city']):'';
            $data['province_id'] = $province_id;
            $data['city_id'] = $city_id;
            unset($data['data']);
            if ($id) {
                $data['update_time'] = time();
                $content = '修改运费规则信息';
                $field = array_keys($data);
                $field[] = 'id';
                $before_json = $freight_model->field($field)->where(['id' =>  $id])->find();
                $result = $freight_model->save($data, ['id' => $id]);
                $data['id'] = $id;
                $after_json = $data;

            } else {
                $data['create_time'] = time();
                $content = '添加运费规则信息';
                $before_json = [];
                $result = $freight_model->save($data);
                $data['id'] = $freight_model->getLastInsID();
                $after_json = $data;
            }

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($result) {
                return ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
            } else {
                return ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            }
        }
    }

    public function freightStatus()
    {
        if (request()->isPOST()) {
            $id = request()->post('id');
            $item = request()->post('item');
            if (!$id) {
                exit(json_encode(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]));
            }
            $data =['id' => $id];
            $coupon = model('freight')->where($data)->field($item)->find();
            $result = model('freight')->where($data)->setField($item, 1-$coupon[$item]);
            if($result){
                $content = '修改运费规则显示状态';
                $before_json = ['id' => $id, $item => $coupon[$item]];
                $after_json = ['id' => $id, $item => 1-$coupon[$item]];

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $json_arr = ["status"=>1, "msg"=> SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => [$item => 1-$coupon[$item]]];
                ajaxReturn($json_arr);
            }else{
                $json_arr = ["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                ajaxReturn($json_arr);
            }
        }
    }
    /**
     * 删除News
     */
    public function delFreight()
    {
        if (request()->isPOST()) {

            $ids = request()->post('id');

            $freight_model = model('freight');
            if (!$ids) {
                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }

            $arr = array_unique(explode('-',($ids)));

            $data = $freight_model->where(['id' => ['in', $arr]])->find();

            $del = $freight_model->destroy($arr);

            if($del){
                $before_json = $data;
                $after_json = [];
                $content = '删除运费规则';
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            }else{
                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }

        }
    }

    /**
     * 运费规则
     */
    public function serviceList()
    {
        $cover_model = model('cover');
        $where = [];
        $keyword = request()->param('keyword');
        if ($keyword) {
            $where['title'] = ['like', "%{$keyword}%"];
        }

        $list = $cover_model->where($where)->order('id desc')->paginate(10,false,['query'=>request()->param()]);
        foreach ($list as $k => $v) {
            $city_list = model('region')->where(['id' => ['in', explode(',', $v['city_id'])]])->field('name,parent_id')->select();
            $city_info = '';
            foreach ($city_list as $k1 => $v1) {
                $province = model('region')->where(['id' => $v1['parent_id']])->value('name');
                $city_info .= '&nbsp;&nbsp;'.$province.'-'.$v1['name'];
                if ($k1 % 4 == 3) {
                    $city_info .= '<br>';
                }
            }
            $list[$k]['province_info'] = $city_info;
        }
        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 添加运费规则
     */
    public function serviceAdd()
    {
        $service_model = model('cover');
        $service_id = request()->param('cover_id');
        $where = ['id' => $service_id];
        $cache = $service_model->where($where)->find();
        $this->assign("cache", $cache);

        $where = [];
        if ($service_id) {
            $where['id'] = ['neq', $service_id];
        }
        $service = $service_model->where($where)->field('province_id,city_id')->select();
        $province_id = '';
        $city_id = '';
        foreach ($service as $k => $v) {
            $province_id .= $province_id?','.$v['province_id']:$v['province_id'];
            $city_id .= $city_id?','.$v['city_id']:$v['city_id'];
        }

        $where = ['parent_id' => 0];
        if ($service_id) {
            $where['id'] = ['neq', $service_id];
        }

        $where = ['parent_id' => 0];
        if ($province_id) {
            $where['id'] = ['notin', explode(',', $province_id)];
        };
        if ($city_id) {
            $whereCity['id'] = ['notin', explode(',', $city_id)];
        };
        $province = model('region')->where($where)->select();
        foreach ($province as $k => $v) {
            if ($cache && in_array($v['id'], explode(',', $cache['province_id']))) {
                $province[$k]['selected'] = 1;
            } else {
                $province[$k]['selected'] = 0;
            }
            $whereCity['parent_id'] = $v['id'];
            $city = model('region')->where($whereCity)->select();
            foreach ($city as $key => $val) {
                if ($cache && in_array($val['id'], explode(',', $cache['city_id']))) {
                    $city[$key]['selected'] = 1;
                } else {
                    $city[$key]['selected'] = 0;
                }
            }
            $province[$k]['city'] = $city;
        }

        $this->assign('province', $province);
//        $cate_list = model('service_cate')->order('sort desc, id desc')->select();
//        $this->assign("cate_list", $cate_list);
        return $this->fetch();
    }


    /**
     * 操作service
     */
    public function serviceHandle()
    {
        if (request()->isPost()) {
            $data = request()->post();
            $id = request()->post('id', 0);
            $service_model = model('cover');

            if (!$data['title']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写标题', 'data' => []]);
            }

            if (!isset($data['data'])) {
                ajaxReturn(['status' => 0, 'msg' => '请选择省市', 'data' => []]);
            }
            $province_id = isset($data['data']['province'])?implode(',', $data['data']['province']):'';
            $city_id = isset($data['data']['city'])?implode(',', $data['data']['city']):'';
            $data['province_id'] = $province_id;
            $data['city_id'] = $city_id;
            unset($data['data']);
            if ($id) {
                $data['update_time'] = time();
                $content = '修改运费规则信息';
                $field = array_keys($data);
                $field[] = 'id';
                $before_json = $service_model->field($field)->where(['id' =>  $id])->find();
                $result = $service_model->save($data, ['id' => $id]);
                $data['id'] = $id;
                $after_json = $data;

            } else {
                $data['create_time'] = time();
                $content = '添加运费规则信息';
                $before_json = [];
                $result = $service_model->save($data);
                $data['id'] = $service_model->getLastInsID();
                $after_json = $data;
            }

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($result) {
                return ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
            } else {
                return ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            }
        }
    }

    public function serviceStatus()
    {
        if (request()->isPOST()) {
            $id = request()->post('id');
            $item = request()->post('item');
            if (!$id) {
                exit(json_encode(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]));
            }
            $data =['id' => $id];
            $coupon = model('cover')->where($data)->field($item)->find();
            $result = model('cover')->where($data)->setField($item, 1-$coupon[$item]);
            if($result){
                $content = '修改运费规则显示状态';
                $before_json = ['id' => $id, $item => $coupon[$item]];
                $after_json = ['id' => $id, $item => 1-$coupon[$item]];

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $json_arr = ["status"=>1, "msg"=> SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => [$item => 1-$coupon[$item]]];
                ajaxReturn($json_arr);
            }else{
                $json_arr = ["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                ajaxReturn($json_arr);
            }
        }
    }
    /**
     * 删除News
     */
    public function delService()
    {
        if (request()->isPOST()) {

            $ids = request()->post('id');

            $cover_model = model('cover');
            if (!$ids) {
                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }

            $arr = array_unique(explode('-',($ids)));

            $data = $cover_model->where(['id' => ['in', $arr]])->find();

            $del = $cover_model->destroy($arr);

            if($del){
                $before_json = $data;
                $after_json = [];
                $content = '删除运费规则';
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            }else{
                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }

        }
    }


    /**
     * 运费规则
     */
    public function rulesList()
    {
        $rules_model = model('rules');
        $where = [];
        $keyword = request()->param('keyword');
        if ($keyword) {
            $where['title'] = ['like', "%{$keyword}%"];
        }

        $list = $rules_model->where($where)->order('id desc')->paginate(10,false,['query'=>request()->param()]);
        foreach ($list as $k => $v) {
            $city_list = model('region')->where(['id' => ['in', explode(',', $v['city_id'])]])->field('name,parent_id')->select();
            $city_info = '';
            foreach ($city_list as $k1 => $v1) {
                $province = model('region')->where(['id' => $v1['parent_id']])->value('name');
                $city_info .= '&nbsp;&nbsp;'.$province.'-'.$v1['name'];
                if ($k1 % 4 == 3) {
                    $city_info .= '<br>';
                }
            }
            $list[$k]['province_info'] = $city_info;
        }
        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 添加运费规则
     */
    public function rulesAdd()
    {
        $rules_model = model('rules');
        $rules_id = request()->param('rules_id');
        $where = ['id' => $rules_id];
        $cache = $rules_model->where($where)->find();
        $this->assign("cache", $cache);

        $where = [];
        if ($rules_id) {
            $where['id'] = ['neq', $rules_id];
        }
        $rules = $rules_model->where($where)->field('province_id,city_id')->select();
        $province_id = '';
        $city_id = '';
        foreach ($rules as $k => $v) {
            $province_id .= $province_id?','.$v['province_id']:$v['province_id'];
            $city_id .= $city_id?','.$v['city_id']:$v['city_id'];
        }

        $where = ['parent_id' => 0];
        if ($rules_id) {
            $where['id'] = ['neq', $rules_id];
        }

        $where = ['parent_id' => 0];
        if ($province_id) {
            $where['id'] = ['notin', explode(',', $province_id)];
        };
        if ($city_id) {
            $whereCity['id'] = ['notin', explode(',', $city_id)];
        };
        $province = model('region')->where($where)->select();
        foreach ($province as $k => $v) {
            if ($cache && in_array($v['id'], explode(',', $cache['province_id']))) {
                $province[$k]['selected'] = 1;
            } else {
                $province[$k]['selected'] = 0;
            }
            $whereCity['parent_id'] = $v['id'];
            $city = model('region')->where($whereCity)->select();
            foreach ($city as $key => $val) {
                if ($cache && in_array($val['id'], explode(',', $cache['city_id']))) {
                    $city[$key]['selected'] = 1;
                } else {
                    $city[$key]['selected'] = 0;
                }
            }
            $province[$k]['city'] = $city;
        }

        $this->assign('province', $province);
//        $cate_list = model('rules_cate')->order('sort desc, id desc')->select();
//        $this->assign("cate_list", $cate_list);
        return $this->fetch();
    }


    /**
     * 操作rules
     */
    public function rulesHandle()
    {
        if (request()->isPost()) {
            $data = request()->post();
            $id = request()->post('id', 0);
            $rules_model = model('rules');

            if (!$data['title']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写标题', 'data' => []]);
            }

            if (!isset($data['data'])) {
                ajaxReturn(['status' => 0, 'msg' => '请选择省市', 'data' => []]);
            }
            $province_id = isset($data['data']['province'])?implode(',', $data['data']['province']):'';
            $city_id = isset($data['data']['city'])?implode(',', $data['data']['city']):'';
            $data['province_id'] = $province_id;
            $data['city_id'] = $city_id;
            unset($data['data']);
            if ($id) {
                $data['update_time'] = time();
                $content = '修改运费规则信息';
                $field = array_keys($data);
                $field[] = 'id';
                $before_json = $rules_model->field($field)->where(['id' =>  $id])->find();
                $result = $rules_model->save($data, ['id' => $id]);
                $data['id'] = $id;
                $after_json = $data;

            } else {
                $data['create_time'] = time();
                $content = '添加运费规则信息';
                $before_json = [];
                $result = $rules_model->save($data);
                $data['id'] = $rules_model->getLastInsID();
                $after_json = $data;
            }

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($result) {
                return ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
            } else {
                return ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            }
        }
    }

    public function rulesStatus()
    {
        if (request()->isPOST()) {
            $id = request()->post('id');
            $item = request()->post('item');
            if (!$id) {
                exit(json_encode(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]));
            }
            $data =['id' => $id];
            $coupon = model('rules')->where($data)->field($item)->find();
            $result = model('rules')->where($data)->setField($item, 1-$coupon[$item]);
            if($result){
                $content = '修改运费规则显示状态';
                $before_json = ['id' => $id, $item => $coupon[$item]];
                $after_json = ['id' => $id, $item => 1-$coupon[$item]];

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $json_arr = ["status"=>1, "msg"=> SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => [$item => 1-$coupon[$item]]];
                ajaxReturn($json_arr);
            }else{
                $json_arr = ["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                ajaxReturn($json_arr);
            }
        }
    }
    /**
     * 删除News
     */
    public function delRules()
    {
        if (request()->isPOST()) {

            $ids = request()->post('id');

            $rules_model = model('rules');
            if (!$ids) {
                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }

            $arr = array_unique(explode('-',($ids)));

            $data = $rules_model->where(['id' => ['in', $arr]])->find();

            $del = $rules_model->destroy($arr);

            if($del){
                $before_json = $data;
                $after_json = [];
                $content = '删除运费规则';
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            }else{
                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }

        }
    }


    /**
     * 运费规则
     */
    public function transferList()
    {
        $transfer_model = model('transfer');
        $where = [];
        $keyword = request()->param('keyword');
        if ($keyword) {
            $where['title'] = ['like', "%{$keyword}%"];
        }

        $list = $transfer_model->where($where)->order('id desc')->paginate(10,false,['query'=>request()->param()]);
        foreach ($list as $k => $v) {
            $city_list = model('region')->where(['id' => ['in', explode(',', $v['city_id'])]])->field('name,parent_id')->select();
            $city_info = '';
            foreach ($city_list as $k1 => $v1) {
                $province = model('region')->where(['id' => $v1['parent_id']])->value('name');
                $city_info .= '&nbsp;&nbsp;'.$province.'-'.$v1['name'];
                if ($k1 % 4 == 3) {
                    $city_info .= '<br>';
                }
            }
            $list[$k]['province_info'] = $city_info;
        }
        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 添加运费规则
     */
    public function transferAdd()
    {
        $transfer_model = model('transfer');
        $transfer_id = request()->param('transfer_id');
        $where = ['id' => $transfer_id];
        $cache = $transfer_model->where($where)->find();
        $this->assign("cache", $cache);

        $where = [];
        if ($transfer_id) {
            $where['id'] = ['neq', $transfer_id];
        }
        $transfer = $transfer_model->where($where)->field('province_id,city_id')->select();
        $province_id = '';
        $city_id = '';
        foreach ($transfer as $k => $v) {
            $province_id .= $province_id?','.$v['province_id']:$v['province_id'];
            $city_id .= $city_id?','.$v['city_id']:$v['city_id'];
        }

        $where = ['parent_id' => 0];
        if ($transfer_id) {
            $where['id'] = ['neq', $transfer_id];
        }

        $where = ['parent_id' => 0];
        if ($province_id) {
            $where['id'] = ['notin', explode(',', $province_id)];
        };
        if ($city_id) {
            $whereCity['id'] = ['notin', explode(',', $city_id)];
        };
        $province = model('region')->where($where)->select();
        foreach ($province as $k => $v) {
            if ($cache && in_array($v['id'], explode(',', $cache['province_id']))) {
                $province[$k]['selected'] = 1;
            } else {
                $province[$k]['selected'] = 0;
            }
            $whereCity['parent_id'] = $v['id'];
            $city = model('region')->where($whereCity)->select();
            foreach ($city as $key => $val) {
                if ($cache && in_array($val['id'], explode(',', $cache['city_id']))) {
                    $city[$key]['selected'] = 1;
                } else {
                    $city[$key]['selected'] = 0;
                }
            }
            $province[$k]['city'] = $city;
        }

        $this->assign('province', $province);
//        $cate_list = model('transfer_cate')->order('sort desc, id desc')->select();
//        $this->assign("cate_list", $cate_list);
        return $this->fetch();
    }


    /**
     * 操作transfer
     */
    public function transferHandle()
    {
        if (request()->isPost()) {
            $data = request()->post();
            $id = request()->post('id', 0);
            $transfer_model = model('transfer');

            if (!$data['title']) {
                ajaxReturn(['status' => 0, 'msg' => '请填写标题', 'data' => []]);
            }

            if (!isset($data['data'])) {
                ajaxReturn(['status' => 0, 'msg' => '请选择省市', 'data' => []]);
            }
            $province_id = isset($data['data']['province'])?implode(',', $data['data']['province']):'';
            $city_id = isset($data['data']['city'])?implode(',', $data['data']['city']):'';
            $data['province_id'] = $province_id;
            $data['city_id'] = $city_id;
            unset($data['data']);
            if ($id) {
                $data['update_time'] = time();
                $content = '修改运费规则信息';
                $field = array_keys($data);
                $field[] = 'id';
                $before_json = $transfer_model->field($field)->where(['id' =>  $id])->find();
                $result = $transfer_model->save($data, ['id' => $id]);
                $data['id'] = $id;
                $after_json = $data;

            } else {
                $data['create_time'] = time();
                $content = '添加运费规则信息';
                $before_json = [];
                $result = $transfer_model->save($data);
                $data['id'] = $transfer_model->getLastInsID();
                $after_json = $data;
            }

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($result) {
                return ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
            } else {
                return ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            }
        }
    }

    public function transferStatus()
    {
        if (request()->isPOST()) {
            $id = request()->post('id');
            $item = request()->post('item');
            if (!$id) {
                exit(json_encode(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]));
            }
            $data =['id' => $id];
            $coupon = model('transfer')->where($data)->field($item)->find();
            $result = model('transfer')->where($data)->setField($item, 1-$coupon[$item]);
            if($result){
                $content = '修改运费规则显示状态';
                $before_json = ['id' => $id, $item => $coupon[$item]];
                $after_json = ['id' => $id, $item => 1-$coupon[$item]];

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                $json_arr = ["status"=>1, "msg"=> SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => [$item => 1-$coupon[$item]]];
                ajaxReturn($json_arr);
            }else{
                $json_arr = ["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
                ajaxReturn($json_arr);
            }
        }
    }
    /**
     * 删除News
     */
    public function delTransfer()
    {
        if (request()->isPOST()) {

            $ids = request()->post('id');

            $transfer_model = model('transfer');
            if (!$ids) {
                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }

            $arr = array_unique(explode('-',($ids)));

            $data = $transfer_model->where(['id' => ['in', $arr]])->find();

            $del = $transfer_model->destroy($arr);

            if($del){
                $before_json = $data;
                $after_json = [];
                $content = '删除运费规则';
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            }else{
                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }

        }
    }

}