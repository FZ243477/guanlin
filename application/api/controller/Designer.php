<?php
/**
 * Created by PhpStorm.
 * User: 111
 * Date: 2020/4/13
 * Time: 17:31
 */

namespace app\api\controller;

use app\common\constant\HousesConstant;
use app\common\constant\SystemConstant;

class Designer extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 设计师信息
     */
    public function designerDetail()
    {
        $designer_id = request()->post('designer_id');
        if (!$designer_id) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $designer = model('houses_designer')
            ->alias('hd')
            ->join('houses_designer_level hdl', 'hdl.id = hd.level_id', 'left')
            ->where(['hd.id' => $designer_id])
            ->field('hd.id,designer_name,telephone,designer_logo,background_logo,city,exp,hdl.level_name')
            ->find();
        if (!$designer) {
            ajaxReturn(['status' => 0, 'msg' => '设计师不存在']);
        }
        //在售商品
        $designer['case_num'] = model('houses_case')->where(['type' => 1, 'designer_id' => $designer_id, 'is_display' => 1])->count();
        $designer['case_num'] = $designer['case_num'] ? $designer['case_num'] : 0;
        if ($this->user_id) {
            $is_collect = model('collection')
                ->where(['param_id' => $designer_id, 'user_id' => $this->user_id, 'type' => 2])
                ->value('status');
            $is_collect = $is_collect ? $is_collect : 0;
        } else {
            $is_collect = 0;
        }
        $designer['is_collect'] = $is_collect;
        $data = [
            'designer' => $designer->toArray(),
        ];
        $json_arr =  ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
        ajaxReturn($json_arr);
    }

    /**
     * 设计师案例
     */
    public function caseList()
    {
        $designer_id = request()->post('designer_id');
        if (!$designer_id) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $list_row = request()->post('list_row', 10); //每页数据
        $page = request()->post('page', 1); //当前页
        $type = request()->post('type', 1); //当前页
        $page = $page > 1 ? $page : 1;
        $start = ($page-1)*$list_row;
        $where = ['hc.is_display' => 1, 'hc.type' => $type];
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