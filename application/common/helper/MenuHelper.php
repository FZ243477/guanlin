<?php


namespace app\common\helper;


trait MenuHelper
{

    private function leftMenu()
    {
        $left_menu = [
            'Index' =>['name'=>'主页','icon'=>'fa-index','sub_menu'=>[],'act'=>'main','control'=>'Index'],
           /* 'Member' =>['name'=>'用户管理','icon'=>'fa-sticky-note','sub_menu'=>[
                ['name'=>'会员列表','act'=>'memberList','control'=>'Member'],
                ['name'=>'优惠券列表','act'=>'couponList','control'=>'Coupon'],
            ]],*/
            'Banner' =>['name'=>'广告位管理','icon'=>'iconfont icon-shuffling-banner','sub_menu'=>[
                ['name'=>'广告位列表','act'=>'bannerList','control'=>'Banner'],
                ['name'=>'广告位分类','act'=>'bannerCateList','control'=>'Banner'],
            ]],
            'Content' =>['name'=>'内容管理','icon'=>'iconfont icon-fuwuneirong','sub_menu'=>[
                ['name'=>'内容列表','act'=>'contentList','control'=>'Content'],
                ['name'=>'内容分类','act'=>'contentCate','control'=>'Content'],
            ]],
            'Goods' =>['name'=>'商品管理','icon'=>'iconfont icon-shangpinguanli','sub_menu'=>[
                ['name'=>'商品列表','act'=>'goodsList','control'=>'Goods'],
                ['name'=>'商品分类','act'=>'goodsCate','control'=>'Goods'],
//                ['name'=>'商品类型','act'=>'goodsTypeList','control'=>'Goods'],
//                ['name'=>'商品参数','act'=>'goodsAttributeList','control'=>'Goods'],
//                ['name'=>'商品规格','act'=>'specList','control'=>'Goods'],
                ['name'=>'品牌系列','act'=>'goodsBrand','control'=>'Goods'],
                ['name'=>'价格区间','act'=>'goodsPrice','control'=>'Goods'],
                ['name'=>'运费规则','act'=>'freightList','control'=>'Freight'],

            ]],
            'Package' => ['name'=>'全屋套餐','icon'=>'iconfont icon-taocanjilu','sub_menu'=>[
                ['name'=>'软装套餐','act'=>'packageList','control'=>'Package'],
                ['name'=>'套餐分类','act'=>'packageCate','control'=>'Package'],
                ['name'=>'套餐风格','act'=>'packageStyle','control'=>'Package'],
            ]],
            /*'Comment' =>['name'=>'留言管理','icon'=>'fa fa-envelope','sub_menu'=>[
                ['name'=>'留言列表','act'=>'commentList','control'=>'Comment'],
                ['name'=>'发布留言','act'=>'commentAdd','control'=>'Comment'],
            ]],*/
            'Distribut' =>['name'=>'分销管理','icon'=>'fa fa-bar-chart','sub_menu'=>[
                ['name'=>'分销树关系','act'=>'tree','control'=>'Distribut'],
                ['name'=>'分销商列表','act'=>'distributorList','control'=>'Distribut'],
                ['name'=>'分成记录','act'=>'rebate_log','control'=>'Distribut'],
            ]],
            'Order' =>['name'=>'订单管理','icon'=>'iconfont icon-tuanduicankaoxian-','sub_menu'=>[
                ['name'=>'订单列表','act'=>'orderList','control'=>'Order'],
                ['name'=>'物流列表','act'=>'expressList','control'=>'Order'],
                ['name'=>'评价列表','act'=>'comment','control'=>'Goods'],
            ]],
            'Preferential' =>['name'=>'活动管理','icon'=>'iconfont icon-huodong','sub_menu'=>[
                ['name'=>'限时特惠','act'=>'limitSpecial','control'=>'Preferential'],
//                ['name'=>'限时特惠','act'=>'limitSales','control'=>'Preferential'],
                ['name'=>'最新单品','act'=>'newGoods','control'=>'Preferential'],
                ['name'=>'热门推荐','act'=>'popular','control'=>'Preferential'],
            ]],
            'News' =>['name'=>'创意灵感','icon'=>'iconfont icon-chuangyi01','sub_menu'=>[
                ['name'=>'新闻列表','act'=>'newsList','control'=>'News'],
//                ['name'=>'新闻分类','act'=>'newsCateList','control'=>'News'],
            ]],
            'User' =>['name'=>'用户管理','icon'=>'iconfont icon-yonghu','sub_menu'=>[
                ['name'=>'会员列表','act'=>'userList','control'=>'User'],
                ['name'=>'优惠券列表','act'=>'couponList','control'=>'Coupon'],
                ['name'=>'会员优惠券','act'=>'userCoupon','control'=>'Coupon'],
            ]],
            'Bill' =>['name'=>'账单管理','icon'=>'iconfont icon-zhangdan','sub_menu'=>[
                ['name'=>'账单明细','act'=>'moneyList','control'=>'Bill'],
                ['name'=>'资金明细','act'=>'moneyDetail','control'=>'Bill'],
                ['name'=>'积分明细','act'=>'integralDetail','control'=>'Bill'],
            ]],
            'Report' =>['name'=>'统计中心','icon'=>'iconfont icon-tongji','sub_menu'=>[
                ['name'=>'新增会员统计','act'=>'memReport','control'=>'Report'],
                ['name'=>'活跃会员统计','act'=>'memActive','control'=>'Report'],
                ['name'=>'首页访问量统计','act'=>'access','control'=>'Report'],
                ['name'=>'商品详情访问量统计','act'=>'goods','control'=>'Report'],
//                ['name'=>'商品搜索关键词统计','act'=>'goodsSearch','control'=>'Report'],
                ['name'=>'订单统计','act'=>'order','control'=>'Report'],
            ]],
            'Partner' =>['name'=>'城市合伙人','icon'=>'iconfont icon-hehuoren','sub_menu'=>[
                ['name'=>'城市合伙人列表','act'=>'partnerList','control'=>'Partner'],
            ]],
            'Setting' =>['name'=>'系统配置','icon'=>'fa fa-envelope','sub_menu'=>[
                ['name'=>'系统配置','act'=>'index','control'=>'Setting'],
                ['name'=>'订单配置','act'=>'order','control'=>'Setting'],
                ['name'=>'积分配置','act'=>'integral','control'=>'Setting'],
                ['name'=>'短信配置','act'=>'sms','control'=>'Setting'],
                ['name'=>'支付宝配置','act'=>'alipay','control'=>'Setting'],
                ['name'=>'微信配置','act'=>'wechat','control'=>'Setting'],
                ['name'=>'酷家乐配置','act'=>'kujiale','control'=>'Setting'],
                ['name'=>'三维家配置','act'=>'svjia','control'=>'Setting'],
            ]],
            'Manager' =>['name'=>'权限资源管理','icon'=>'fa-cog','sub_menu'=>[
                ['name'=>'管理员列表','act'=>'managerList','control'=>'Manager'],
                ['name'=>'角色列表','act'=>'managerCateList','control'=>'Manager'],
                ['name'=>'操作日志','act'=>'managerLog','control'=>'Manager'],
                ['name'=>'权限列表','act'=>'rightList','control'=>'Manager'],
            ]],
        ];
        return $left_menu;
    }

    private function getMenuList($act_list){
        //根据角色权限过滤菜单
        $menu_list = $this->leftMenu();
        if($act_list != 'all'){
            $right = model('manager_menu')->where(['id' => ['in', $act_list]])->field('right')->select();
            $role_right = '';
            foreach ($right as $val){
                $role_right .= $val['right'].',';
            }
            $role_right = explode(',', $role_right);
            foreach($menu_list as $k=>$mrr){
                $i =0;
                $count = count($mrr['sub_menu']);
                foreach ($mrr['sub_menu'] as $j=>$v){
                    if(!in_array($v['control'].'@'.$v['act'], $role_right)){
                        $i ++;
                        unset($menu_list[$k]['sub_menu'][$j]);//过滤菜单
                    }
                }
                if ($count != 0 && $i == $count) {
                    unset($menu_list[$k]);//过滤菜单
                }
            }
        } else {
//            $menu_list['Comment']['sub_menu'][1]['name'] = '发布通知';
            //array_push($menu_list['Manager']['sub_menu'], ['name'=>'权限列表','act'=>'rightList','control'=>'Manager']);
        }
        return $menu_list;
    }
}