<?php


namespace app\admin\helper;


trait MenuHelper
{

    private function leftMenu()
    {
        $left_menu = [
            'Index' =>['name'=>'主页','icon'=>'fa-index','sub_menu'=>[],'act'=>'main','control'=>'Index'],
            'Logistics' =>['name'=>'物流公司','icon'=>'fa-print','sub_menu'=>[
                ['name'=>'物流公司分类','act'=>'logisticsCate','control'=>'Logistics'],
            ]],
            'Goods' =>['name'=>'物品分类','icon'=>'iconfont icon-shangpinguanli','sub_menu'=>[
                ['name'=>'物品分类','act'=>'goodsCate','control'=>'Goods'],
            ]],
            'Transfer' =>['name'=>'中转站收货人信息','icon'=>'fa-book','sub_menu'=>[
                ['name'=>'中转站收货信息列表','act'=>'transferList','control'=>'Transfer'],
            ]],
            'Message' =>['name'=>'用户消息提醒列表','icon'=>'fa-list','sub_menu'=>[
                ['name'=>'用户消息提醒列表','act'=>'messageList','control'=>'Message'],
            ]],
            'User_address' =>['name'=>'用户收货人信息','icon'=>'fa-road','sub_menu'=>[
                ['name'=>'用户收货信息列表','act'=>'addressList','control'=>'User_address'],
            ]],
            'User' =>['name'=>'用户管理','icon'=>'iconfont icon-yonghu','sub_menu'=>[
                ['name'=>'用户列表','act'=>'userList','control'=>'User'],
            ]],
            'Order' =>['name'=>'订单管理','icon'=>'iconfont icon-tuanduicankaoxian-','sub_menu'=>[
                ['name'=>'订单列表','act'=>'orderList','control'=>'Order'],
                //['name'=>'物流列表','act'=>'expressList','control'=>'Order'],
            ]],
            'Report' => ['name' => '实名登记', 'icon' => 'fa-user', 'sub_menu' => [
                ['name' => '新增用户统计', 'act' => 'memReport', 'control' => 'Report'],
                //['name'=>'活跃用户统计','act'=>'memActive','control'=>'Report'],
            ]],
            'Bill' =>['name'=>'账单管理','icon'=>'iconfont icon-zhangdan','sub_menu'=>[
                ['name'=>'财务统计','act'=>'finance','control'=>'Finance'],
//                ['name'=>'资金明细','act'=>'moneyDetail','control'=>'Bill'],
            ]],
            'Setting' =>['name'=>'系统配置','icon'=>'fa fa-envelope','sub_menu'=>[
                ['name'=>'系统配置','act'=>'index','control'=>'Setting'],
//                ['name'=>'订单配置','act'=>'order','control'=>'Setting'],
                ['name'=>'短信配置','act'=>'sms','control'=>'Setting'],
                ['name'=>'微信配置','act'=>'wechat','control'=>'Setting'],
            ]],
            'Manager' =>['name'=>'权限资源管理','icon'=>'fa-cog','sub_menu'=>[
                ['name'=>'管理员列表','act'=>'managerList','control'=>'Manager'],
                ['name'=>'角色列表','act'=>'managerCateList','control'=>'Manager'],
                ['name'=>'操作日志','act'=>'managerLogs','control'=>'Manager'],
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