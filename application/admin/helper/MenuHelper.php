<?php


namespace app\admin\helper;


trait MenuHelper
{

    private function leftMenu()
    {
        $left_menu = [
            'Index' =>['name'=>'主页','icon'=>'fa-index','sub_menu'=>[],'act'=>'main','control'=>'Index'],
            'Banner' =>['name'=>'广告位管理','icon'=>'fa fa-sticky-note','sub_menu'=>[
                ['name'=>'广告位列表','act'=>'bannerList','control'=>'Banner'],
                ['name'=>'广告位分类','act'=>'bannerCateList','control'=>'Banner'],
            ]],
            'Goods' =>['name'=>'商品管理','icon'=>'iconfont icon-shangpinguanli','sub_menu'=>[
                ['name'=>'商品列表','act'=>'goodsListrea','control'=>'Goods'],
                ['name'=>'商品分类','act'=>'goodsCate','control'=>'Goods'],
            ]],

            'User' =>['name'=>'用户管理','icon'=>'iconfont icon-yonghu','sub_menu'=>[
                ['name'=>'用户列表','act'=>'userList','control'=>'User'],
            ]],
            'Order' =>['name'=>'订单管理','icon'=>'iconfont icon-tuanduicankaoxian-','sub_menu'=>[
                ['name'=>'订单列表','act'=>'orderList','control'=>'Order'],
                ['name'=>'物流列表','act'=>'expressList','control'=>'Order'],
            ]],
            'Bill' =>['name'=>'账单管理','icon'=>'iconfont icon-zhangdan','sub_menu'=>[
                ['name'=>'财务统计','act'=>'finance','control'=>'Finance'],
                ['name'=>'资金明细','act'=>'moneyDetail','control'=>'Bill'],
            ]],
            'Setting' =>['name'=>'系统配置','icon'=>'fa fa-envelope','sub_menu'=>[
                ['name'=>'系统配置','act'=>'index','control'=>'Setting'],
                ['name'=>'订单配置','act'=>'order','control'=>'Setting'],
                ['name'=>'短信配置','act'=>'sms','control'=>'Setting'],
                ['name'=>'微信配置','act'=>'wechat','control'=>'Setting'],
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