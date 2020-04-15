<?php
namespace app\api\controller;
use app\common\constant\HousesConstant;
use app\common\constant\SystemConstant;

class HousesCase extends Base
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 大师案例
     */
    public function caseList()
    {
        if (request()->isPost()) {
            $list_row = request()->post('list_row', 10); //每页数据
            $page = request()->post('page', 1); //当前页
            $page = $page > 1 ? $page : 1;
            $start = ($page-1)*$list_row;
            $where = ['hc.is_display' => 1, 'hc.is_hot' => 0];
            $field = 'hc.id,hc.name,hc.logo,hc.type,hc.collection_num,ht.area,ht.space,ht.style,h.houses_name';
            $houses_case = model('houses_case')
                ->alias('hc')
                ->join('houses_type ht', 'ht.id = hc.houses_type_id', 'left')
                ->join('houses h', 'h.id = ht.houses_id', 'left')
                ->where($where)
                ->field($field)
                ->order('hc.sort desc')
                ->limit($start, $list_row)
                ->select();

            foreach ($houses_case as $k => $v) {
                $houses_case[$k]['type_name'] = HousesConstant::house_type_array_value($v['type']);
                if ($this->user_id) {
                    $v['is_collection'] = model('collection')->where([
                        'user_id' => $this->user_id,
                        'param_id' => $v['id'],
                        'type' => 1,
                    ])->value('status');
                    $houses_case[$k]['is_collection'] = $v['is_collection']?$v['is_collection']:0;
                } else {
                    $houses_case[$k]['is_collection'] = 0;
                }
            }
            $totalCount = model('houses_case')
                ->alias('hc')
                ->join('houses_type ht', 'ht.id = hc.houses_type_id', 'left')
                ->join('houses h', 'h.id = ht.houses_id', 'left')
                ->where($where)
                ->count();
            $pageCount = ceil($totalCount/$list_row);
            $data = [
                'list' => $houses_case ? $houses_case : [],
                'pageInfo' => [
                    'totalCount' => $totalCount ? $totalCount : 0,
                    'pageCount' => $pageCount ? $pageCount : 0,
                    'page' => $page,
                ]
            ];
            $json_arr =  ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
    }

    /**
     * 精品案例
     */
    public function boutiqueCase()
    {
        $where = ['is_display' => 1, 'is_hot' => 1, 'type' => 1];
        $field = 'id,name,logo,type,collection_num';
        $vr = model('houses_case')->where($where)->field($field)->find();
        $where = ['is_display' => 1, 'is_hot' => 1, 'type' => 3];
        $field = 'id,name,logo,type,collection_num';
        $article = model('houses_case')->where($where)->field($field)->find();
        if ($this->user_id) {
            $vr['is_collection'] = model('collection')->where([
                'user_id' => $this->user_id,
                'param_id' => $vr['id'],
                'type' => 1,
            ])->value('status');
            $article['is_collection'] = model('collection')->where([
                'user_id' => $this->user_id,
                'param_id' => $article['id'],
                'type' => 1,
            ])->value('status');
            $vr['is_collection'] = $vr['is_collection']?$vr['is_collection']:0;
            $article['is_collection'] = $article['is_collection']?$article['is_collection']:0;
        } else {
            $vr['is_collection'] = 0;
            $article['is_collection'] = 0;
        }
        $data = [
            'vr' => $vr,
            'article' => $article,
        ];
        $json_arr =  ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 热门小区
     */
    public function houses()
    {
        $where = ['is_hot' => 1];
        $houses = model('houses')->where($where)->field('houses_name')->limit(10)->order('hot_sort desc')->select();
        $data = [
            'houses' => $houses,
        ];
        $json_arr =  ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 搜索列表
     */
    public function searchList()
    {
        $where = ['user_id' => $this->user_id];
        $searchList = model('houses_search')
            ->where($where)
            ->field('id,keyword')
            ->order('num desc, id desc')
            ->limit(20)
            ->select();
        $data = [
            'searchList' => $searchList,
        ];
        $json_arr =  ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 删除搜索
     */
    public function delSearch()
    {
        $id = request()->post('id');
        if (!$id) {
            $json_arr =  ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM];
            ajaxReturn($json_arr);
        }
        $id = explode(',', $id);
        $res = model('houses_search')->where(['id' => ['in', $id]])->delete();
        if ($res) {
            $json_arr =  ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS];
        } else {
            $json_arr =  ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE];
        }
        ajaxReturn($json_arr);
    }

    /**
     * 搜索小区
     */
    public function searchHouses()
    {
        $keyword = request()->post('keyword');
        if (!$keyword) {
            $json_arr =  ['status' => 0, 'msg' => '请输入关键词'];
            ajaxReturn($json_arr);
        }
        $where = ['h.houses_name' => ['like', '%'.$keyword.'%']];
        $housesType = model('houses')
            ->alias('h')
            ->join('houses_type ht', 'ht.houses_id = h.id', 'left')
            ->where($where)
            ->field('ht.id,ht.name,ht.logo,ht.area,space')
            ->select();

        $where = ['user_id' => $this->user_id, 'keyword' => $keyword];
        $search_list = model('houses_search')->where($where)->field('id')->find();
        if ($search_list) {
            model('houses_search')->where(['id' => $search_list['id']])->setInc('num', 1);
        } else {
            $where['num'] = 1;
            model('houses_search')->save($where);
        }
        $data = [
            'housesType' => $housesType,
        ];
        $json_arr =  ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 搜索小区
     */
    public function searchHousesCase()
    {

        $houses_type_id = request()->post('houses_type_id');
        if (!$houses_type_id) {
            $json_arr =  ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM];
            ajaxReturn($json_arr);
        }

        $housesType = model('houses_type')->where(['id' => $houses_type_id])->field('name,area,space,style')->find();
        $housesCase = model('houses_case')
            ->where(['houses_type_id' => $houses_type_id, 'is_display' => 1])
            ->field('id,name,logo,designer_id,type,collection_num')
            ->order('sort desc')
            ->select();
        foreach ($housesCase as $k => $v) {
            $housesCase[$k]['area'] = $housesType['area'];
            $housesCase[$k]['space'] = $housesType['space'];
            $housesCase[$k]['style'] = $housesType['style'];
            $housesCase[$k]['designer'] = model('houses_designer')
                ->where(['id' => $v['designer_id']])
                ->field('designer_name,designer_logo,city,exp')
                ->find();
            $housesCase[$k]['designer']['designer_id'] = $v['designer_id'];
            unset($housesCase[$k]['designer_id']);
        }
        $data = [
            'housesType' => $housesType['name'],
            'housesCase' => $housesCase,
        ];
        $json_arr =  ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 案例详情
     */
    public function housesCaseDetail()
    {
        $houses_case_id = request()->post('houses_case_id');
        if (!$houses_case_id) {
            $json_arr =  ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM];
            ajaxReturn($json_arr);
        }
        $field = 'id,name,vr,content,type,designer_id,houses_type_id';
        $housesCase = model('houses_case')->where(['id' => $houses_case_id])->field($field)->find();
        $field = 'area,space,style';
        $housesType = model('houses_type')->where(['id' => $housesCase['houses_type_id']])->field($field)->find();
        $detail = [];
        $detail['id'] = $housesCase['id'];
        $detail['name'] = $housesCase['name'];
        $detail['vr'] = $housesCase['vr'];
        $detail['content'] = $housesCase['content'];
        $detail['area'] = $housesType['area'];
        $detail['space'] = $housesType['space'];
        $detail['style'] = $housesType['style'];
        $detail['designer'] = model('houses_designer')
            ->where(['id' => $housesCase['designer_id']])
            ->field('id,designer_name,designer_logo,designer_describe,city,exp')
            ->find();
        if ($this->user_id) {
            $detail['is_collection'] = model('collection')->where([
                'user_id' => $this->user_id,
                'param_id' => $detail['id'],
                'type' => 1,
            ])->value('status');
            $detail['is_collection'] = $detail['is_collection']?$detail['is_collection']:0;
        } else {
            $detail['is_collection'] = 0;
        }
        $data = [
            'housesCase' => $detail,
        ];
        $json_arr =  ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 装修清单
     */
    public function installList()
    {
        $houses_case_id = request()->post('houses_case_id');
        if (!$houses_case_id) {
            $json_arr =  ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM];
            ajaxReturn($json_arr);
        }
        $field = 'hc.id,hc.logo,deposit,total_price,finish_date,houses_city,houses_name,area,space,style';
        $housesCase = model('houses_case')
            ->alias('hc')
            ->join('houses_type ht', 'ht.id = hc.houses_type_id', 'left')
            ->join('houses h', 'h.id = ht.houses_id', 'left')
            ->where(['hc.id' => $houses_case_id])
            ->field($field)
            ->find();

        $list = db('goods_cate')->where(['pid' => 0])->field('id,name')->select();

        foreach ($list as $k => $v) {
            $list[$k]['cate'] = db('goods_cate')->where(['pid' => $v['id']])->field('id,name')->select();
            foreach ($list[$k]['cate'] as $k1 => $v1) {
                $list[$k]['cate'][$k1]['goods_info'] = model('houses_goods')
                    ->alias('hg')
                    ->join('goods g', 'hg.goods_id = g.id', 'left')
                    ->where(['hg.houses_case_id' => $houses_case_id, 'hg.cate_id' => $v1['id']])
                    ->field('g.id,goods_name,goods_logo,goods_price,goods_oprice,
                             goods_size,goods_unit,express_fee,install_fee,hg.goods_num')
                    ->select();
            }
        }
        $data = [
            'housesCase' => $housesCase,
            'list' => $list,
        ];
        $json_arr =  ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 商品详情
     */
    public function goodsDetail()
    {
        if (request()->isPost()) {

            $goods_model = model('goods');
            $goods_id = request()->post('goods_id', 0, 'intval'); //每页数据

            if (!$goods_id) {
                $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []];
                ajaxReturn($json_arr);
            }

            $where = [ 'id' => $goods_id];
            $field = ['id,goods_name,goods_describe,goods_price,goods_oprice,goods_size,express_fee'];
            $lists = $goods_model->where($where)->field($field)->order('sort desc')->find();

            if (!$lists) {
                $json_arr = ['status' => 0, 'msg' => '该商品不存在或已下架', 'data' => []];
                ajaxReturn($json_arr);
            }
            if ($this->user_id) {
                $is_collect = model('collection')
                    ->where(['param_id' => $goods_id, 'user_id' => $this->user_id, 'type' => 3])
                    ->value('status');
                $is_collect = $is_collect?$is_collect:0;
            } else {
                $is_collect = 0;
            }
            $lists['is_collect'] = $is_collect;
            $lists['banner_top'] = model('goods_images')
                ->where(['type' => 0, 'goods_id' => $goods_id])
                ->field('logo')
                ->select();
            $lists['banner_detail'] =  model('goods_images')
                ->where(['type' => 1, 'goods_id' => $goods_id])
                ->field('logo')
                ->select();
            $data = [
                'list' => $lists->toArray(),
            ];
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
    }
}