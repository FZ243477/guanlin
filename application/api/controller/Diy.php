<?php


namespace app\api\controller;


use app\common\constant\CartConstant;
use app\common\constant\PreferentialConstant;
use app\common\constant\SystemConstant;
use app\common\helper\CartHelper;
use app\common\helper\GoodsHelper;
use app\common\helper\KujialeHelper;
use app\common\helper\PreferentialHelper;
use app\common\helper\UserHelper;
use app\common\helper\VerificationHelper;


class Diy extends Base
{
    use GoodsHelper;
    use PreferentialHelper;
    use KujialeHelper;
    use CartHelper;
    use VerificationHelper;
    use UserHelper;

    public function __construct()
    {
        parent::__construct();
    }

    public function getDiy($page, $list_row, $search)
    {
        $url = "https://openapi.kujiale.com/v2/design/list?";
        $first_row = $list_row * ($page - 1);
        $post_data = [];
        $get_data = [];
        $get_data["start"] = $first_row; //拉取列表的偏移量，从0开始。
        $get_data["num"] = $list_row; //一次拉取的数量上限，从数据安全以及性能上考虑，num最大值限制为50，
        //如果有拉取更多数据的需求，请发起多次请求。如果剩余数据量小于num，则会返回全部剩余数据。
        //比如start=0&num=10表示拉取第1到第10个数据，start=10&num=10表示拉取第11个到第20个数据。
        //$get_data["status"]   = 0;//如果不指定status，则获取所有方案（包括户型阶段及装修阶段的方案），如果指定为0，则获取户型阶段方案，
        //如果指定为1，则获取装修阶段方案；除了0和1以外没有其他取值。
        $get_data["sort"] = 0;//排序。默认是按照创建时间倒排。取值0表示按照创建时间倒排，取值1表示按照最后修改时间倒排。
        $get_data["keyword"] = $search; //关键字查询。如果设置这个字段，将会以这个词对方案名字进行模糊匹配。
        $get_data["appuid"] = 115; //第三方用户的ID。
        $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
        return $json_arr;
    }

    /**
     * 方案列表
     */
    public function diyList()
    {
        $page = input('post.page', 1);

        $search = input('post.search', '');

        $list_row = input('post.list_row', 100);

        $json_arr = $this->getDiy($page, $list_row, $search);
        $result = $json_arr['d']['result'];
        $list = [];
        if ($json_arr['c'] == 0) {
            foreach ($result as $k => $v) {
                $list[$k] = [
                    'planId' => $v['planId'],
                    'commName' => $v['commName'],
                    //'city' => $v['city'],
                    'name' => $v['name'],
                    'srcArea' => $v['srcArea'],
                    'specName' => $v['specName'],
                    'area' => $v['area'],
                    'planPic' => $v['planPic'],
                ];
            }
        }


        $totalCount = $json_arr['d']['totalCount'];
        $pageCount = $json_arr['d']['count'];

        $data = [
            'list' => $list ? $list : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);
    }


    public function getHouse($page, $list_row, $city_id, $search)
    {
        $url = "https://openapi.kujiale.com/v2/floorplan/standard?";
        $first_row = $list_row*($page-1);
        $post_data = [];
        $get_data = [];
        $get_data["start"]  = $first_row; //拉取列表的偏移量，从0开始。
        $get_data["num"]   = $list_row; //一次拉取的数量上限，从数据安全以及性能上考虑，num最大值限制为50，
        //如果有拉取更多数据的需求，请发起多次请求。如果剩余数据量小于num，则会返回全部剩余数据。
        //比如start=0&num=10表示拉取第1到第10个数据，start=10&num=10表示拉取第11个到第20个数据。
        $get_data["q"]  = $search; //查询关键字。
        $get_data["city_id"] = $city_id; //酷家乐的城市ID，用来指定城市查询户型图
        //$data[ "room_count"]   = 3; //卧室数量筛选条件。比如3表示筛选所有前置条件下的三室的户型。目前只支持取值为1、2、3、4、5。
        //$data["area_min"]   = 0; //建筑面积筛选条件，指定这个参数表示要过滤建筑面积最小为这个值的户型。
        //$data["area_max"]   = 0; //建筑面积筛选条件，指定这个参数表示要过滤建筑面积最大为这个值的户型。
        $get_data["is_standard"]   = true; //是否只查询标准户型数据，不传或者传false会保持和原来一样的返回，传true只会返回标准户型库的户型。
        $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
        return $json_arr;
    }
    /**
     * 户型列表
     */
    public function houseType()
    {
        $page = input('post.page',1);

        $search = input('post.search', '');

        $city_id = input('post.city_id', 175);

        $list_row = input('post.list_row',10);
        $json_arr = $this->getHouse($page, $list_row, $city_id, $search);
        $result = $json_arr['d']['result'];
        $list = [];
        if ($json_arr['c'] == 0) {
            foreach ($result as $k => $v) {
                $list[$k] = [
                    'planId' => $v['planId'],
                    'commName' => $v['commName'],
                    'city' => $v['city'],
                    'name' => $v['name'],
                    'srcArea' => $v['srcArea'],
                    'specName' => $v['specName'],
                    'area' => $v['area'],
                    'planPic' => $v['planPic'],
                ];
            }
        }

        $totalCount = $json_arr['d']['totalCount'];
        $pageCount = $json_arr['d']['count'];

        $data = [
            'list' => $list ? $list : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);
    }


    /**
     * 获取城市列表
     */
    public function cityNum()
    {
        $keyfile = './static/index/js/city.json';
        $contents = '';
        if (file_exists($keyfile)) {
            $fp = fopen($keyfile,"r");
            if($fp == NULL) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
            fseek($fp,0,SEEK_END);
            $filelen=ftell($fp);
            fseek($fp,0,SEEK_SET);
            $contents = fread($fp,$filelen);
            fclose($fp);
        }
        $city_list = json_decode($contents, true);
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['city_list' => $city_list]]);
    }


    public function hotCommunity()
    {
        $content_model = model('community');
        $keyword = request()->param('keyword', '', 'trim');
        $where = ['is_display' => 1];
        if ($keyword) {
            $where['comm_name'] = ['like', "%{$keyword}%"];
        }
        $city_id = request()->post('city_id');
        if ($city_id) {
            $where['city_id'] = $city_id;
        }
        $list = $content_model->field('city_id,province,city, comm_name')->where($where)->order('sort desc')->select();
        $city_list = $this->getDiyCityList();
        foreach ($list as $k => $v) {
            foreach ($city_list as $k1 => $v1) {
                if ($v1['province'] == $v['province']) {
                    $list[$k]['province_id'] = $k1;
                }
            }
        }
        $data = [
            'list' => $list
        ];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data]);
    }


    /**
     * 获得小区
     */
    public function community()
    {
        $keywords = input('post.comm_name');
        if (!$keywords) {
            ajaxReturn(['status' => 0, 'msg' => '请输入关键字', 'data'  => []]);
        }

        $url = "https://openapi.kujiale.com/v2/community?";
        $post_data = [];
        $get_data = [];
        $get_data["num"] = input('post.num', 20); //只会返回前20条
        $get_data["comm_name"] = $keywords;//查询关键字。
        $get_data["city_id"] = input('post.city_id', 175);//查询关键字。
//        $get_data["city_id"] = 175; //酷家乐的城市ID。
        $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
        $result = $json_arr['d']['result'];
        $list = [];
        if ($json_arr['c'] == 0) {
            $list = $result;
        }
        $data = ['list' => $list];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);
    }

    /**
     * 搜索小区
     */
    public function citySearch()
    {
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }
        $keywords = input('post.comm_name');
        if (!$keywords) {
            ajaxReturn(['status' => 0, 'msg' => '请输入关键字', 'data'  => []]);
        }
        $this->searchList($keywords, $this->user_id, 3);
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
    }
    /**
     * 搜索
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function searchLists()
    {
        $search_list_model = model('search_list');
        $cate = request()->post('cate');
        $type = request()->post('type', 3);
        $page = request()->post('page', 1);
        $list_row = request()->post('list_row', 10); //每页数据
        $where = ['type' => $type, 'partner_id' => 0];
        $first_row = ($page-1)*$list_row;
        if ($cate == 1) {
            if (!$this->user_id) {
                ajaxReturn(['status' => -1, 'msg' => '请登录']);
            }
            $where['user_id'] = $this->user_id;
            $search_list = $search_list_model->field('id, keywords, nums s_nums')->order('update_time desc')->where($where)->limit($first_row.','.$list_row)->select();
        } else {
            $search_list = $search_list_model->where($where)->field('keywords, sum(nums) s_nums')->group('keywords')->order('s_nums desc')->limit($first_row.','.$list_row)->select();
        }
        $totalCount = $search_list_model->where($where)->count();
        $pageCount = ceil($totalCount/$list_row);
        $data = [
            'list' => $search_list ? $search_list : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);
    }

    public function delSearch()
    {
        $data = request()->post();
        $search_list_model = model('search_list');
        $id_arr = [];
        if ($data['type'] == 1) {
            if (!isset($data['id']) || empty($data['id'])) {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
            }
            $id_arr = $data['id'];
        } else {
            if (!$this->user_id) {
                ajaxReturn(['status' => -1, 'msg' => '请登录']);
            }
            $list = $search_list_model->where(['user_id' => $this->user_id])->field('id')->select();
            foreach ($list as $k => $v) {
                $id_arr[] = $v['id'];
            }
            if (!$id_arr) {
                ajaxReturn(['status' => 0, 'msg' => '您已经全部清空了']);
            }
        }

        /* $id = request()->post('id');
         $search_list = $search_list_model->where(['keywords' => $keywords])->select();
         $id_arr = [];
         foreach ($search_list as $k => $v) {
             $id_arr[] = $v['id'];
         }*/
        $result = $search_list_model->destroy($id_arr);
        if ($result) {
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        }
    }

    /**
     * 查询轻设计方案
     * @param $search
     * @return array
     */
    public function diyDesignList($search)
    {
        $where = [];
        if ($search) {
            $where['name'] = ['like', '%'.$search.'%'];
        }
        $lists = model('design_list')->where($where)->select();
        $list = [];
        foreach ($lists as $k => $v) {
            $list[$k] = [
                'planId' => $v['plan_id'],
                'designId' => $v['design_id'],
                'commName' => $v['comm_name'],
                //'city' => $v['city'],
                'name' => $v['name'],
                'img' => $v['img'],
                'srcArea' => round($v['src_area'], 2),
                'specName' => $v['spec_name'],
                'area' => $v['area'],
                'planPic' => $v['plan_pic'],
                'easyDesignId' => $v['easy_design_id'],
            ];
        }
      /*  $page = 1;
        $list_row = 50;
        $result = cache('diy_list_'.base64_encode($search));
        if (empty($result)) {
            $json_arr = $this->getDiy($page, $list_row, '轻设计'.$search);
            $result = $json_arr['d']['result'];
            while ($list_row == count($result)) {
                $json_arr = $this->getDiy($page++, $list_row, '轻设计'.$search);
                $result = array_merge($result, $json_arr['d']['result']);
                cache('hose_list'.base64_encode($search), $result);
            }
        }
        $list = [];
        if ($result) {
            foreach ($result as $k => $v) {
                $list[$k] = [
                    'planId' => $v['planId'],
                    'designId' => $v['designId'],
                    'commName' => $v['commName'],
                    //'city' => $v['city'],
                    'name' => $v['name'],
                    'srcArea' => round($v['srcArea'], 2),
                    'specName' => $v['specName'],
                    'area' => $v['area'],
                    'planPic' => $v['planPic'],
                ];
            }
        }*/
        return $list;
        /*$totalCount = $json_arr['d']['totalCount'];
        $pageCount = $json_arr['d']['count'];

        $data = [
            'list' => $list ? $list : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];

        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);*/
    }

    /**
     * 查询轻设计户型
     */
    public function houseDesignType()
    {
        $page = 1;

        $search = input('post.search', '');

        $city_id = input('post.city_id', 175);

        $list_row = 50;

        $result = cache('hose_list_'.base64_encode($search) .$city_id);
        if (empty($result)) {
            $json_arr = $this->getHouse($page, $list_row, $city_id, $search);
            $result = $json_arr['d']['result'];
            while ($list_row == count($result)) {
                $json_arr = $this->getHouse($page++, $list_row, $city_id, $search);
                $result = array_merge($result, $json_arr['d']['result']);
            }
            cache('hose_list'.base64_encode($search).$city_id, $result);
        }

        $diy_design = $this->diyDesignList($search);
        //dump($diy_design);
        $top_list = [];
        $down_list = [];
        if ($result) {
            $result = array_unique_fb($result);
            foreach ($result as $k => $v) {
                //dump($v);
                $flag = false;
                $design = [];
                foreach ($diy_design as $key => $val) {
                    if ($v['commName'] == $val['commName'] && $v['specName'] == $val['specName']) {
                        $flag = true;
                        $design = $val;
                    }
                }
                if ($flag == true) {
                    $top_list[] = [
                        'planId' => $v['planId'],
                        'commName' => $v['commName'],
                        'city' => $v['city'],
                        'name' => $v['name'],
                        'srcArea' => $v['srcArea'],
                        'specName' => $v['specName'],
                        'area' => $v['area'],
                        'planPic' => $v['planPic'],
                        'is_design' => 1,
                        'design' => $design,
                    ];
                } else {
                    $down_list[] = [
                        'planId' => $v['planId'],
                        'commName' => $v['commName'],
                        'city' => $v['city'],
                        'name' => $v['name'],
                        'srcArea' => $v['srcArea'],
                        'specName' => $v['specName'],
                        'area' => $v['area'],
                        'planPic' => $v['planPic'],
                        'is_design' => 0,
                        'design' => [],
                    ];
                }

            }
        }

        $list = array_merge($top_list, $down_list);
        //$totalCount = $json_arr['d']['totalCount'];
        //$pageCount = $json_arr['d']['count'];

        $data = [
            'list' => $list ? $list : [],
            //'totalCount' => $totalCount ? $totalCount : 0,
            //'pageCount' => $pageCount ? $pageCount : 0,
        ];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);
    }


    /**
     * 获取轻设计列表
     */
    public function designPlanList()
    {
        $search = input('post.search', '');
        $list = $this->diyDesignList($search);
        foreach ($list as $k => $v) {
            $where = ['partner_id' => 0, 'design_id' => $v['easyDesignId']];
            $package = model('package')
                ->field('design_id,style_id,package_title,package_brief,package_logo,area,package_price')
                ->where($where)
                ->find();
            if ($package) {
                $list[$k]['package'] = [
                    'design_id' => $package['design_id'],
                    'style_id' => $package['style_id'],
                    'package_title' => $package['package_title'],
                    'package_brief' => $package['package_brief'],
                    'package_logo' => $package['package_logo'],
                    'area' => $package['area'],
                    'package_price' => $package['package_price'],
                    'style_name' => $package['style_name'],
                ];
                $list[$k]['design_plan'] = [
                    'specName' => $v['specName'],
                    'srcArea' => $v['srcArea'],
                    'img' => $v['img'],
                ];
            } else {
                unset($list[$k]);
            }
        }
        /*foreach ($diy_design as $k => $v) {
            $list_one = $this->diyLightList($v['planId']);
            foreach ($list_one as $key => $val) {
                $val['design_plan'] = $v;
                $list_one[$key] = $val;
            }
            if ($list) {
                $list =  array_merge($list, $list_one);
            } else {
                $list = $list_one;
            }
        }*/
        $list = array_unique_fb($list);
        $data = ['list' => $list];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);
    }

    /**
     * 获取轻设计列表
     */
    public function designListAdmin()
    {

        $page = input('post.page', 1);
        $list_row = input('post.list_row', 50);

        $totalCount = model('design_list')->count();

        $pageCount = ceil($totalCount/$list_row);
        $first_row = ($page-1)*$list_row;

        $search = input('post.search', '');
        $where = [];
        if ($search) {
            $where['name'] = ['like', '%'.$search.'%'];
        }
        $lists = model('design_list')->where($where)->limit($first_row, $list_row)->select();

        $data = [
            'list' => $lists ? $lists : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);
    }

    /**
     * 获取轻设计列表
     */
    public function designList()
    {
        $design_id = request()->post('design_id');
        $where = [];
        if ($design_id == '') {
            $search = input('post.search', '');
            if ($search) {
                $where['name'] = ['like', '%'.$search.'%'];
            }
            /*$page = input('post.page', 1);
            $list_row = input('post.list_row', 20);
            $url = "https://openapi.kujiale.com/v2/design/list?";
            $first_row = $list_row * ($page - 1);
            $post_data = [];
            $get_data = [];
            $get_data["start"] = $first_row; //拉取列表的偏移量，从0开始。
            $get_data["num"] = $list_row; //一次拉取的数量上限，从数据安全以及性能上考虑，num最大值限制为50，
            //如果有拉取更多数据的需求，请发起多次请求。如果剩余数据量小于num，则会返回全部剩余数据。
            //比如start=0&num=10表示拉取第1到第10个数据，start=10&num=10表示拉取第11个到第20个数据。
            //$get_data["status"]   = 0;//如果不指定status，则获取所有方案（包括户型阶段及装修阶段的方案），如果指定为0，则获取户型阶段方案，
            //如果指定为1，则获取装修阶段方案；除了0和1以外没有其他取值。
            $get_data["sort"] = 0;//排序。默认是按照创建时间倒排。取值0表示按照创建时间倒排，取值1表示按照最后修改时间倒排。
            $get_data["keyword"] = '轻设计'.$search; //关键字查询。如果设置这个字段，将会以这个词对方案名字进行模糊匹配。
            $get_data["appuid"] = 115; //第三方用户的ID。

            $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
            $result = $json_arr['d']['result'];
            $list = [];
            if ($json_arr['c'] == 0) {
                foreach ($result as $k => $v) {
                    $list_result = $this->diyLightList($v['planId']);
                    foreach ($list_result as $k1 => $v1) {
                        $list[] = $v1;
                    }
                }
            }*/
        } else {
            $where['easy_design_id'] = $design_id;
        }
        $list = model('design_list')->where($where)->select();
        foreach ($list as $k => $v) {
            $where = ['partner_id' => 0, 'design_id' => $v['easy_design_id']];
            $package = model('package')
                ->field('design_id,style_id,package_title,package_brief,package_logo,area,package_price')
                ->where($where)
                ->find();
            if ($package) {
                $list[$k]['package'] = [
                    'design_id' => $package['design_id'],
                    'style_id' => $package['style_id'],
                    'package_title' => $package['package_title'],
                    'package_brief' => $package['package_brief'],
                    'package_logo' => $package['package_logo'],
                    'area' => $package['area'],
                    'package_price' => $package['package_price'],
                    'style_name' => $package['style_name'],
                ];
            } else {
                unset($list[$k]);
            }
        }
        $data = ['list' => $list];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);
    }
    /**
     * 轻设计列表
     * @param $design_id
     * @return array
     */
    public function diyLightList($design_id)
    {
        $url = "https://openapi.kujiale.com/v2/design/easy-design?";
        $post_data = [];
        $get_data = [];
        $get_data["design_id"]  = $design_id;
        $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
        $result = $json_arr['d']['pics'];
        $list = [];
        $where = ['partner_id' => 0];
        $package = model('package')->field('design_id,style_id,package_title,package_brief,package_logo,area,package_price')->where($where)->select();
        if ($json_arr['c'] == 0) {
            if ($result) {
                foreach ($result as $k => $v) {
                    if (isset($v['easyDesignId'])){
                        foreach ($package as $key => $val) {
                            if ($val['design_id'] == $v['easyDesignId']) {
                                $v['package'] = $val;
                                $v['package']['style_name'] = $val['style_name'];
                                $list[] = $v;
                            }
                        }
                    }
                }
            }
        }
        return $list;
    }

    public function designDetail()
    {
        $design_id = request()->post('design_id');
        if (!$design_id) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $where['design_id'] = $design_id;
        $package = model('package')->field('package_title,package_brief,package_logo,package_price')->where($where)->find();
        if (!$package) {
            ajaxReturn(['status' => 0, 'msg' => '轻设计不存在']);
        }
        $package['share_logo'] = getSetting('system.host').'/static/common/images/weixin_share.jpg';
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $package]);
    }
    /**
     * 轻设计匹配单个商品信息
     */
    public function onProductDetail()
    {
        $goods_code = request()->post('goods_code');
        $goods_id = 0;
        $sku_id = 0;
        if ($goods_code) {
            $spec_goods = model('spec_goods_price')->where(['bar_code' => $goods_code])->find();

            if ($spec_goods) {
                $goods_id = $spec_goods['goods_id'];
                $sku_id = $spec_goods['key'];
            } else {
                $goods = model('goods')->where(['goods_code' => $goods_code])->find();
                if ($goods) {
                    $goods_id = $goods['id'];
                }
            }
        }

        if ($goods_id) {
            $json_arr = [
                'status' => 1,
                'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS,
                'data' => [
                    'goods_id' => $goods_id,
                    'sku_id' => $sku_id
                ]
            ];
        } else {
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE];
        }
        ajaxReturn($json_arr);
    }
    /**
     * 轻设计匹配商品清单价格信息
     */
    public function getProductInfo()
    {
        $post = request()->post();
        $goods_code_arr = $post['goods_code'];
        $goods_list = [];
        foreach ($goods_code_arr as $goods_code) {
            $goods_list[$goods_code] = [
                'price' => "—",
            ];
            if ($goods_code) {
                $goods = model('goods')->where(['goods_code' => $goods_code])->find();
                if ($goods) {
                    $goods_list[$goods_code] = [
                        'price' => (float)$goods['price'].'.00',
                    ];
                }
                $spec_goods = model('spec_goods_price')->where(['bar_code' => $goods_code])->find();
                if ($spec_goods) {
                    $goods_list[$goods_code] = [
                        'price' => (float)$spec_goods['price'].'.00',
                    ];
                }
                if ($goods['is_audit'] == 0 || $goods['is_sale'] == 0) {
                    $goods_list[$goods_code] = [
                        'price' => "—",
                    ];
                }
            }
        }
        if ($goods_list) {
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $goods_list];
        } else {
            $json_arr = ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE];
        }
        ajaxReturn($json_arr);
    }

    /**
     * 生成轻设计清单
     */
    public function onBuy()
    {
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }
        $post = request()->post();
        $goods_code_arr = $post['goods_code'];
        $goods_list_new = [];
        foreach ($goods_code_arr as $goods_code) {
            if ($goods_code) {
                $goods_id = 0;
                $sku_id = 0;
                $spec_goods = model('spec_goods_price')->where(['bar_code' => $goods_code])->find();
                if ($spec_goods) {
                    $goods_id = $spec_goods['goods_id'];
                    $sku_id = $spec_goods['key'];
                } else {
                    $goods = model('goods')->where(['goods_code' => $goods_code])->find();
                    if ($goods) {
                        $goods_id = $goods['id'];
                    }
                }
                if ($goods_id) {
                    if (isset($goods_list_new[$goods_id.$sku_id])) {
                        $goods_list_new[$goods_id.$sku_id]['goods_num'] ++;
                    } else {
                        $goods_list_new[$goods_id.$sku_id] = [
                            'goods_id' => $goods_id,
                            'goods_num' => 1,
                            'sku_id' => $sku_id,
                        ];
                    }
                }
            }
        }
        if ($goods_list_new) {
            //$goods_list_new = array_values($goods_list_new);
            $where = [
                'partner_id' => 0,
                'user_id' => $this->user_id,
                'cart_type' => CartConstant::CART_TYPE_LIGHT_BUY,
            ];
            model('Cart')->where($where)->delete(); // 查找购物车是否已经存在该商品
            foreach ($goods_list_new as $k => $v) {
                $this->addCartHandleS($this->user_id, $v['goods_id'], $v['goods_num'], $v['sku_id'], CartConstant::CART_TYPE_LIGHT_BUY);
            }
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => '该方案未选定可购买商品，无法生成清单']);
        }
    }

    public function gic()
    {
        $obsEasyDesignId = request()->post('obsEasyDesignId');
        if (!$obsEasyDesignId) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM]);
        }
        $token = request()->post('token');
        $url = getSetting('system.host').'/api/Api/diyLight?planid='.$obsEasyDesignId.'&token='.$token;
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['url' => $url]]);
    }
    /**
     * 轻设计预约
     */
    public function designAppointment()
    {
        $data = request()->post();
        if (!isset($data['comm_name']) || !$data['comm_name']) {
            ajaxReturn(['status' => 0, 'msg' => '请输入小区', 'data'  => []]);
        }
        if (!isset($data['spec_name']) || !$data['spec_name']) {
            ajaxReturn(['status' => 0, 'msg' => '请输入户型', 'data'  => []]);
        }
        if (!isset($data['area']) || !$data['area']) {
            ajaxReturn(['status' => 0, 'msg' => '请输入面积', 'data'  => []]);
        }
        if (!isset($data['money']) || !$data['money']) {
            ajaxReturn(['status' => 0, 'msg' => '请输入预算', 'data'  => []]);
        }
        if (!isset($data['style']) || !$data['style']) {
            ajaxReturn(['status' => 0, 'msg' => '请输入风格', 'data'  => []]);
        }
        if (!isset($data['username']) || !$data['username']) {
            ajaxReturn(['status' => 0, 'msg' => '请输入姓名', 'data'  => []]);
        }
        if (!isset($data['telephone']) || !$data['telephone']) {
            ajaxReturn(['status' => 0, 'msg' => '请输入手机号', 'data'  => []]);
        }
        if (!$this->VerifyTelephone($data['telephone'])) {
            ajaxReturn(['status' => 0, 'msg' => '手机号格式不正确', 'data'  => []]);
        }
        $data['user_id'] = $this->user_id;
        $res = model('design_appointment')->create($data, 'comm_name,spec_name,area,money,style,username,telephone');
        if ($res) {
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => []]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data'  => []]);
        }
    }
    /**
     * 获得轻设计方案ID
     */
    /*public function goDesign()
    {

        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }
        $planid = input('post.planid');

        $url = "https://openapi.kujiale.com/v2/design/creation?";
        $post_data = [];
        $get_data = [];
        $get_data["plan_id"]  = $planid;
        $get_data["appuid"]   = $this->user_id;
        $json_arr = $this->backDataInfo($url, $post_data, 'post', $get_data);

        if ($json_arr['c'] == 0) {
            $accessToken =  model('user')->where('id', $this->user_id)->value('kujiale_token');
            if (!$accessToken) {
                ajaxReturn(['status' => -1, 'msg' => '请重新登录']);
            }
            $iframe = "https://www.kujiale.com/v/auth?accesstoken={$accessToken}&dest=8&designid={$json_arr['d']}";
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => ['iframe' => $iframe]]);
            //ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => ['planId' => $json_arr['d']]]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => $json_arr['m'], 'data'  => []]);
        }
    }*/

    /**
     * 获得轻设计方案ID
     */
    /* public function goDesignPlay()
     {

         $planid = input('post.planid');

         $url = "https://openapi.kujiale.com/v2/design/easy-design?";
         $post_data = [];
         $get_data = [];
         $get_data["design_id"]  = $planid;
         $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
         if ($json_arr['c'] == 0) {
             ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => ['pics' => $json_arr['d']]]);
         } else {
             ajaxReturn(['status' => 0, 'msg' => $json_arr['m'], 'data'  => []]);
         }

     }*/

    /**
     *酷家乐3D建模
     */
    public function diyPlan()
    {
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }
        $planid = input('post.planid');

        $url = "https://openapi.kujiale.com/v2/design/creation?";
        $post_data = [];
        $get_data = [];
        $get_data["plan_id"]  = $planid;
        $get_data["appuid"]   = $this->user_id;
        $json_arr = $this->backDataInfo($url, $post_data, 'post', $get_data);

        $iframe = '';

        if ($json_arr['c'] == 0) {

            $user_info = model('user')->where(['id' => $this->user_id])->find();
            $accessToken =  $user_info['kujiale_token'];
            $url = 'https://openapi.kujiale.com/v2/login?';
            $post_data = array(
                'name'   => $user_info['nickname'],
                //'email'  => $user_info['email'],
                'telephone'  => $user_info['telephone'],
                'avatar' => picture_url_dispose($user_info['head_img']),
                'type'   => 0,
            );
            $get_data['appuid']   = $this->user_id;
            $json_arr_a = $this->backDataInfo($url, $post_data, 'post', $get_data);
            if ($json_arr_a['c'] == 0) {
                $accessToken = $json_arr_a['d'];
                //model('user')->isUpdate(true)->save(['is_child' => 1, 'is_kujiale' => 1, 'kujiale_token' => $json_arr['d']], ['id' => $user_id]);
            }

            if (!$accessToken) {
                ajaxReturn(['status' => -1, 'msg' => '请重新登录']);
            }
            $iframe = "https://www.kujiale.com/v/auth?accesstoken={$accessToken}&dest=6&designid={$json_arr['d']}";
        } else {
            ajaxReturn(['status' => 0, 'msg' => $json_arr['m'], 'data'  => []]);
        }

        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => ['iframe' => $iframe]]);

    }

    /**
     * 我的方案
     */
    public function myDiyPlan()
    {
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }
        $page = input('post.page', 1);

        $list_row = input('post.list_row', 10);

        $url = "https://openapi.kujiale.com/v2/design/list?";

        $start = $list_row*($page-1);
        $post_data = [];
        $get_data = [];
        $get_data["start"]  = $start; //拉取列表的偏移量，从0开始。
        $get_data["num"]   = $list_row; //一次拉取的数量上限，从数据安全以及性能上考虑，num最大值限制为50，
        //如果有拉取更多数据的需求，请发起多次请求。如果剩余数据量小于num，则会返回全部剩余数据。
        //比如start=0&num=10表示拉取第1到第10个数据，start=10&num=10表示拉取第11个到第20个数据。
        //$get_data["status"]   = 0;//如果不指定status，则获取所有方案（包括户型阶段及装修阶段的方案），如果指定为0，则获取户型阶段方案，
        //如果指定为1，则获取装修阶段方案；除了0和1以外没有其他取值。
        $get_data["sort"]   = 0;//排序。默认是按照创建时间倒排。取值0表示按照创建时间倒排，取值1表示按照最后修改时间倒排。
        $get_data["keyword"]   = ''; //关键字查询。如果设置这个字段，将会以这个词对方案名字进行模糊匹配。
        $get_data["appuid"]   = $this->user_id; //第三方用户的ID。
        $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);

        $result = $json_arr['d']['result'];
        $list = [];
        if ($json_arr['c'] == 0) {
            foreach ($result as $k => $v) {
                $list[$k] = [
                    'planId' => $v['planId'],
                    'commName' => $v['commName'],
                    //'city' => $v['city'],
                    'name' => $v['name'],
                    'srcArea' => $v['srcArea'],
                    'specName' => $v['specName'],
                    'area' => $v['area'],
                    'planPic' => $v['planPic'],
                ];
            }
        }
        $totalCount = $json_arr['d']['totalCount'];
        $pageCount = $json_arr['d']['count'];

        $data = [
            'list' => $list ? $list : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);
    }

    /**
     * 删除方案
     */
    public function myDiyPlanDel()
    {
        $planId = input('post.planId');
        if(!$this->user_id){
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }

        $url = "https://openapi.kujiale.com/v2/design/deletion?";

        $post_data = [];
        $get_data = [];
        $get_data['plan_id'] = $planId;
        $json_arr = $this->backDataInfo($url, $post_data, 'post', $get_data);

        if ($json_arr['c'] == 0) {
            ajaxReturn(['status' => 1, 'msg' => '删除成功']);
        } else {
            ajaxReturn(['status' => 0, 'msg' => '删除失败']);
        }

    }

    public function diyOrderInit()
    {
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }

        $design_id = input('post.design_id');

        if (!$design_id) {
            ajaxReturn(['status' => 0, 'msg' => '缺少参数design_id']);
        }

        //初始化清单
        $url = "https://openapi.kujiale.com/v2/listing/init?";
        $post_data = [];
        $get_data = [];
        $get_data["design_id"]  = $design_id; //方案ID
        $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
        $listingId = $json_arr['d'];//清单id，初始化清单返回的listingId字段

        //获取清单状态
        $url = "https://openapi.kujiale.com/v2/listing/{$listingId}/state?";
        $post_data = [];
        $get_data = [];
        $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['order_status' => $json_arr['d'], 'listingId' => $listingId]]);
    }

    /**
     * 拉取清单
     */
    public function diyOrder()
    {

        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }

        $listingId = input('post.listingId');

        $order_status = input('post.order_status');

        if (!$listingId) {
            ajaxReturn(['status' => 0, 'msg' => '缺少参数listingId']);
        }

        if ($order_status != 3) {
            ajaxReturn(['status' => 0, 'msg' => '请重新初始化接口']);
        }

        /* //初始化清单
         $url = "https://openapi.kujiale.com/v2/listing/init?";
         $post_data = [];
         $get_data = [];
         $get_data["design_id"]  = $design_id; //方案ID
         $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
         $listingId = $json_arr['d'];//清单id，初始化清单返回的listingId字段

         //获取清单状态
         $url = "https://openapi.kujiale.com/v2/listing/{$listingId}/state?";
         $post_data = [];
         $get_data = [];
         $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
         $status = $json_arr['d'];*/

        //同步清单
        $url = "https://openapi.kujiale.com/v2/listing/{$listingId}/sync?";
        $post_data = [];
        $get_data = [];
        $get_data["appuid_special"]  = $this->user_id; //清单id，初始化清单返回的listingId字段
        $this->backDataInfo($url, $post_data, 'post', $get_data);

        //获取清单家具软装数据
        $url = "https://openapi.kujiale.com/v2/listing/{$listingId}/soft/outfit/detail?";
        $post_data = [];
        $get_data = [];
        $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
        $goods_list = $json_arr['d'];
        $goods_all_money = 0;
        $goods_list_new = [];
        foreach ($goods_list as $k => $v) {
            //$goods_all_money += $v['totalPrice'];
            if ($v['totalPrice'] == 0) {
                unset($goods_list[$k]);
                continue;
            } else {
//                $goods_all_money += $v['totalPrice'];
                //$goods_all_nums += $v['totalCount'];
            }
            foreach ($goods_list[$k]['softOutfits'] as $key => $val) {
                if ($val['realPrice'] == 0) {
//                        unset($goods_list[$k]['softOutfits'][$key]);
                } else {

                    if ($val['code']) {
                        $spec_info = model('spec_goods_price')->where(['bar_code' => $val['code']])->find();
                        if ($spec_info)  {
                            $sku_id = $spec_info['key'];
                            $goods_info = model('Goods')->where(['id' => $spec_info['goods_id']])->find();
                        } else {
                            $sku_id = 0;
                            $goods_info = model('Goods')->where(['goods_code' => $val['code']])->find();
                        }

                        if ($goods_info) {
                            $goods_list_new[] = [
                                'goods_id' => $goods_info['id'],
                                'goods_num' => $val['number'],
                                'sku_id' => $sku_id,
                            ];

//                                    $link = '/Goods/goodsDetail/id/' . $goods_info['id'];
                        }
//                                $goods_list[$k]['softOutfits'][$key]['unitPrice'] = $goods_info['price'];
//                                $goods_list[$k]['softOutfits'][$key]['realPrice'] = $goods_info['price'] * $val['number'];
                    }
                    //$goods_all_nums += $val['number'];
                    $goods_all_money += $goods_list[$k]['softOutfits'][$key]['realPrice'];
                    //$goods_list[$k]['softOutfits'][$key]['link_url'] = $link;
                }
            }
        }

        if ($goods_list_new) {
            $where = [
                'partner_id' => 0,
                'user_id' => $this->user_id,
                'cart_type' => CartConstant::CART_TYPE_KUJIALE_BUY,
            ];
            model('Cart')->where($where)->delete(); // 查找购物车是否已经存在该商品
            foreach ($goods_list_new as $k => $v) {
                $this->addCartHandleS($this->user_id, $v['goods_id'], $v['goods_num'], $v['sku_id'], CartConstant::CART_TYPE_KUJIALE_BUY);
            }
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => '该方案未选定可购买商品，无法生成清单']);
        }

        /*if ($goods_all_money == 0) {
            ajaxReturn(['status' => 0, 'msg' => '该方案商品价格未设定,或未选定可购买商品，无法生成清单']);
//                $this->ajaxReturn(array('status' => 0, 'info' => '该方案商品价格未设定,或未选定可购买商品，无法生成清单'));
        }*/
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);


    }

    public function orderList()
    {
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录']);
        }

        $cart_type = request()->post('cart_type');

        if (!in_array($cart_type , [CartConstant::CART_TYPE_KUJIALE_BUY, CartConstant::CART_TYPE_SANVJIA_BUY, CartConstant::CART_TYPE_LIGHT_BUY])) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
        }

        $where = ['user_id' => $this->user_id, 'cart_type' => $cart_type, 'partner_id' => 0];
        $list = model('cart')->where($where)->field('id,goods_id,goods_num,goods_price,selected,sku_id,goods_skuinfo')->select();

        $total_num = $total_price = $goods_price = 0;

        $cartList = [];
        $k = 0;

        foreach ($list as $key => $val){

            $goods = model('goods')->field('id,goods_name,goods_logo,price,goods_unit,weight,prom_type,prom_id')->where(['id' => $val['goods_id']])->find();
            if (!$goods) {
                model('cart')->destroy($val['id']);
                unset($cartList[$key]);
                continue;
            }

            $cartList[$k] = $val;
            $cartList[$k]['goods_fee'] = $val['goods_num'] * $val['goods_price'];
            $total_num += $val['goods_num'];
            if ($val['selected'] == 1) {
                $total_price += $cartList[$k]['goods_fee'];
            }
            $goods_price += $cartList[$k]['goods_fee'];
            //$cartList[$k]['goodsInfo'] = $goods;
            $cartList[$k]['goods_logo'] = $goods['goods_logo'];
            $cartList[$k]['goods_name'] = $goods['goods_name'];
            $cartList[$k]['goods_unit'] = $goods['goods_unit'];
            $cartList[$k]['weight'] = $goods['weight'];
            $price = $val['goods_price'];
            if($goods['prom_type'] != PreferentialConstant::PREFERENTIAL_TYPE_NORMAL_GOODS)
            {
                $prom = $this->get_goods_promotion($goods['prom_type'], $goods['prom_id'], $goods['id'], $val['sku_id']);
                if ($prom['price']) {
                    $price = $prom['price'];
                }
                $goods['prom_type'] = $prom['prom_type'];
                $goods['prom_id']   = $prom['prom_id'];
            }
            if ($price != $val['goods_price']) {
                $data = [
                    'goods_price' => $price,
                    'prom_type' => $goods['prom_type'],
                    'prom_id' => $goods['prom_id'],
                ];
                model('cart')->save($data, ['id' => $val['id']]);
            }
            $k++;
        }
        $total_price =  sprintf('%.2f', $total_price);
        $total_price = ['total_price' =>$total_price , 'total_num'=> $total_num,]; // 总计

        $data = [
            'cartList' => $cartList,
            'total_price' => $total_price,
        ];

        $result =['status'=>1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS ,'data'=> $data];
        ajaxReturn($result);


    }
}