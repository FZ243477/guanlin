<?php


namespace app\admin\controller;
use app\admin\helper\ManagerHelper;
use app\common\helper\OriginalSqlHelper;
use app\common\constant\SystemConstant;
use think\Db;

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
        $keyword = request()->param('keyword', '', 'trim');
        $where = [];
        if ($keyword) {
            $where['hc.name|hd.designer_name|ht.name'] = ['like', "%{$keyword}%"];
        }
        $this->assign('keyword', $keyword);
        $list = $houses_case_model
            ->alias('hc')
            ->join('houses_type ht', 'ht.id = hc.houses_type_id', 'left')
            ->join('houses_designer hd', 'hd.id = hc.designer_id', 'left')
            ->where($where)
            ->field('hc.id,hc.type,hc.name,hc.logo,hc.sort,hc.is_display,hc.is_hot,ht.name as ht_name,hd.designer_name')
            ->order('sort desc, id desc')
            ->paginate(10,false,['query'=>request()->param()]);

        $this->assign('list', $list);

        $hot_vr = $houses_case_model->where(['is_hot' => 1, 'type' => 1])->value('name');
        $hot_article = $houses_case_model->where(['is_hot' => 1, 'type' => 3])->value('name');
        $this->assign('hot_vr', $hot_vr);
        $this->assign('hot_article', $hot_article);
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
        if ($cache) {
            if ($cache['designer_id']) {
                $cache['houses_designer'] = model('houses_designer')
                    ->where(['id' => $cache['designer_id']])
                    ->field('id,designer_name,designer_logo,telephone')
                    ->find();
            }
            if ($cache['houses_type_id']) {
                $cache['houses_type'] = model('houses_type')
                    ->where(['id' => $cache['houses_type_id']])
                    ->field('id,name,logo,area,space,style')
                    ->find();
            }
            $cache['houses_goods'] =  model('houses_goods')
                ->alias('hg')
                ->join('goods g', 'g.id = hg.goods_id', 'left')
                ->where(['hg.houses_case_id' => $cache['id']])
                ->field('g.goods_name,g.goods_logo,g.goods_price,g.cate_id,hg.goods_id,hg.goods_num')
                ->order('hg.sort asc')
                ->select();
        }
        $this->assign('cache', $cache);
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

            $m_goods_id = '';
            $m_goods_num = '';
            $m_cate_id = '';
            if (isset($data['m_goods_id'])) {
                $m_goods_id = $data['m_goods_id'];
                $m_cate_id = $data['m_cate_id'];
                $m_goods_num = $data['m_goods_num'] ? $data['m_goods_num'] : 1;
                unset($data['m_goods_id']);
                unset($data['m_goods_num']);
                unset($data['m_cate_id']);
            }
            Db::startTrans();
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
            if ($result) {
                model('houses_goods')->where(['houses_case_id' => $data['id']])->delete();
                if ($m_goods_id) {
                    $save = [];
                    foreach ($m_goods_id as $k => $v) {
                        $save[$k] = [
                            'houses_case_id' => $data['id'],
                            'goods_id' => $v,
                            'goods_num' => $m_goods_num[$k],
                            'cate_id' => $m_cate_id[$k],
                            'sort' => $k,
                        ];
                    }
                    model('houses_goods')->insertAll($save);
                }
                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                Db::commit();
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                Db::rollback();
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
        if (!isset($data['logo']) || !$data['logo']) {
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
                $json_arr = ['status'=>1, 'msg'=> SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => [$item => 1-$coupon[$item]]];
            }else{
                $json_arr = ['status'=>0, 'msg'=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
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
                ajaxReturn(['status'=>0, 'msg'=>SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }

            $arr = array_unique(explode('-',($ids)));

            $data = $houses_case_model->where(['id' => ['in', $arr]])->find();

            $del = $houses_case_model->destroy($arr);

            if($del){
                $before_json = $data;
                $after_json = [];
                $content = '删除houses_case';

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(['status'=>1, 'msg'=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            }else{
                ajaxReturn(['status'=>0, 'msg'=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
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

    /**
     * 选择设计师
     */
    public function selectDesigner()
    {
        $houses_designer_model = model('houses_designer');
        $keyword = request()->param('keyword');
        $where = [];
        if ($keyword) {
            $where['a.designer_name'] = ['like', "%{$keyword}%"];
        }
        $this->assign('keyword', $keyword);
        $list = $houses_designer_model
            ->alias('a')
            ->join('houses_designer_level b', 'a.level_id = b.id')
            ->field('a.id,a.designer_name,a.designer_logo,a.telephone,a.city,a.exp,b.level_name')
            ->where($where)
            ->order('a.id desc')
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 选择户型
     */
    public function selectType()
    {
        $houses_type_model = model('houses_type');
        $keyword = request()->param('keyword');
        $where = [];
        if ($keyword) {
            $where['name'] = ['like', "%{$keyword}%"];
        }
        $this->assign('keyword', $keyword);
        $list = $houses_type_model
            ->field('id,name,area,space,style,logo')
            ->where($where)
            ->order('id desc')
            ->paginate(10,false,['query'=>request()->param()]);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 选择商品
     */
    public function selectGoods()
    {
        $houses_type_model = model('goods');
        $keyword = request()->param('keyword');
        $cate_id = request()->param('cate_id');
        $goods_id = request()->param('goods_id');
        $where = [];
        if ($keyword) {
            $where['goods_name'] = ['like', "%{$keyword}%"];
        }
        if ($cate_id) {
            $where['pid|cate_id'] = $cate_id;
        }
        if ($goods_id) {
            $where['id'] = ['notin', explode(',', $goods_id)];
        }
        $this->assign('keyword', $keyword);
        $this->assign('cate_id', $cate_id);
        $list = $houses_type_model
            ->field('id,pid,cate_id,goods_name,goods_logo,goods_price,goods_oprice')
            ->where($where)
            ->order('id desc')
            ->paginate(10,false,['query'=>request()->param()]);

        $this->assign('list', $list);
        $goods_pid_array = [];
        $goods_cate_array = [];
        $goods_cate = model('goods_cate')->where(['pid' => 0])->field('id,name')->order('id asc')->select();
        foreach ($goods_cate as $k => $v) {
            $goods_pid_array[$v['id']] = $v['name'];
            $cate = model('goods_cate')->where(['pid' => $v['id']])->field('id,name')->order('id asc')->select();
            foreach ($cate as $kk => $vv) {
                $goods_cate_array[$vv['id']] = $vv['name'];
            }
            $goods_cate[$k]['cate'] = $cate;
        }
        $this->assign('goods_cate', $goods_cate);
        $this->assign('goods_pid_array', $goods_pid_array);
        $this->assign('goods_cate_array', $goods_cate_array);
        return $this->fetch();
    }

    /**
     * 选择方案
     */
    public function selectHousesCase()
    {
        $houses_case_model = model('houses_case');
        $keyword = request()->param('keyword', '', 'trim');
        $type = request()->param('type', '', 'trim');
        $where = ['hc.type' => $type, 'is_display' => 1];
        if ($keyword) {
            $where['hc.name|hd.designer_name|ht.name'] = ['like', "%{$keyword}%"];
        }
        $this->assign('keyword', $keyword);
        $list = $houses_case_model
            ->alias('hc')
            ->join('houses_type ht', 'ht.id = hc.houses_type_id', 'left')
            ->join('houses_designer hd', 'hd.id = hc.designer_id', 'left')
            ->where($where)
            ->field('hc.id,hc.type,hc.name,hc.logo,hc.sort,hc.is_display,hc.is_hot,ht.name as ht_name,hd.designer_name')
            ->order('sort desc, id desc')
            ->paginate(10,false,['query'=>request()->param()]);

        $this->assign('list', $list);
        $this->assign('type', $type);
        return $this->fetch();
    }

    public function selectHousesCaseHot()
    {
        if (request()->isPost()) {
            $id = request()->post('id');
            $type = request()->post('type');
            if (!$id || !$type) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
            }
            model('houses_case')->update(['is_hot' => 0], ['is_hot' => 1, 'type' => $type ]);
            model('houses_case')->update(['is_hot' => 1], ['id' => $id]);
            ajaxReturn(['status' => 1, 'msg'=>SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        }
    }
}