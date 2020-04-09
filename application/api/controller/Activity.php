<?php

namespace app\api\controller;

use app\common\constant\PreferentialConstant;
use app\common\helper\OriginalSqlHelper;
use app\common\helper\ManagerHelper;
use app\common\constant\SystemConstant;
use app\common\helper\PreferentialHelper;

class Activity extends Base
{
    use OriginalSqlHelper;
    use ManagerHelper;
    use PreferentialHelper;
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 活动页
     */
    public function activityList()
    {
        $activity = model('activity')->find();
        $activity_info['act_name'] = $activity['act_name'];
        $activity_info['act_rule'] = $activity['act_rule'];
        $activity_info['act_logo'] = $activity['act_logo'].'?x-oss-process=image/resize,m_fill,h_650,w_1366';
        $activity_info['act_gic_logo'] = picture_url_dispose($activity['act_gic_logo']);
        $activity_info['act_coupon_title'] = $activity['act_coupon_title'];
        $activity_info['act_coupon_use'] = $activity['act_coupon_use'];

        $start_time_y = date('Y', $activity['start_time']['val']);
        $end_time_y = date('Y', $activity['end_time']['val']);
        if ($start_time_y == $end_time_y) {
            $act_time =  date('Y年m月d日', $activity['start_time']['val']). '——'
                .date('m月d日', $activity['end_time']['val']);
        } else {
            $act_time =  date('Y年m月d日', $activity['start_time']['val']). '——'
                .date('Y年m月d日', $activity['end_time']['val']);
        }
        $activity_info['act_time'] = $act_time;

        $use_start_y = date('Y', $activity['start_time']['val']);
        $use_end_y = date('Y', $activity['end_time']['val']);
        if ($use_start_y == $use_end_y) {
            $coupon_time = date('m月d日', $activity['use_start']['val']). '-'
                .date('m月d日', $activity['use_end']['val']);
        } else {
            $coupon_time = date('Y年m月d日', $activity['use_start']['val']). '-'
                .date('Y年m月d日', $activity['use_end']['val']);
        }
        $activity_info['coupon_time'] = $coupon_time;

        $list = model('activity_coupon')->where(['status' => 0])->select();
        $coupon = [];
        foreach ($list as $k => $v) {
            if ($this->user_id) {
                $res = model('coupon_data')
                    ->field('coupon_id')
                    ->where(['user_id' => $this->user_id, 'coupon_id' => $v['id']])
                    ->find();
                if ($res) {
                    $coupon[$k]['yh_status'] = 1;
                } else {
                    $coupon[$k]['yh_status'] = 2;
                }
            } else {
                $coupon[$k]['yh_status'] = 2;
            }

            $coupon[$k]['id'] = $v['id'];
            $coupon[$k]['coupon_no'] = $v['coupon_no'];
            $coupon[$k]['title'] = $v['title'];
            $coupon[$k]['coupon_type'] = $v['coupon_type'];
            $coupon[$k]['deduct'] = $v['deduct'];
            $coupon[$k]['limit_money'] = $v['limit_money'];
            $coupon[$k]['starttime'] = date('Y-m-d H:i:s', $v['starttime']);
            $coupon[$k]['endtime'] = date('Y-m-d H:i:s', $v['endtime']);
        }

        $appointment = model('appointment_hall_des')
            ->field('title,des,des2,pic,address,appointment_time,visit_time')
            ->find();
        $appointment['pic'] = picture_url_dispose($appointment['pic']);

        $package_id = explode(',', $activity['package_id']);

        $field = ['activity_package_title'=>'package_title', 'activity_package_pic'=>'package_logo', 'package_id','activity_package_price'];
        $packageList = model('activity_package')->where(['activity_id' => $activity['id']])->field($field)->select();
        foreach ($packageList as $k => $v) {
            $package = model('package')->where(['id' => $v['package_id'], 'partner_id' => 0])->field(['estate_id','max_price','package_brief','package_price'])->find();
            if ($package) {
                $packageList[$k]['estate_id']=$package['estate_id'];
                $packageList[$k]['max_price']=$package['package_price'];
                $packageList[$k]['package_brief']=$package['package_brief'];
                $packageList[$k]['package_price']=$v['activity_package_price'];
                $packageList[$k]['package_logo'] = picture_url_dispose($v['package_logo']);
            } else {
                unset($packageList[$k]);
            }
        }

        //package_logo package_title
        $limit_sales_id = explode(',', $activity['limit_sales_id']);
        $limitSales = [];
        $goods_id = [];
        foreach ($limit_sales_id as $k => $v) {
            $list = model('limit_sales')
                ->field('limit_sales_id,goods_id,spec_price,sku_id')
                ->where(['limit_sales_id' => $v])
                ->find();
            if (!in_array($list['goods_id'], $goods_id)) {
                $limitSales[] = $list;
                $goods_id[] = $list['goods_id'];
            }
        }

      /*  $exp = new \think\db\Expression('field(limit_sales_id,'.$activity['limit_sales_id'].')');
        $limitSales = model('limit_sales')
            ->field('limit_sales_id,goods_id,spec_price,sku_id')
            ->where(['limit_sales_id' => ['in', $limit_sales_id]])
            ->group('goods_id')
            ->order($exp)
            ->select();*/

        foreach ($limitSales as $k => $v) {
            $goods = model('goods')->where(['id' => $v['goods_id']])->find();
            $limitSales[$k]['goods_name'] = $goods['goods_name'];
            $limitSales[$k]['goods_desc'] = $goods['goods_desc'];
            $limitSales[$k]['goods_logo'] = $goods['goods_logo'];

            if ($v['sku_id']) {
                $prom = $this->get_goods_promotion(
                    PreferentialConstant::PREFERENTIAL_TYPE_LIMIT_SALES,
                    $v['limit_sales_id'],
                    $v['goods_id'],
                    $v['sku_id']
                );
                if ($prom['price']) {
                    $price = $prom['price'];
                } else {
                    $price = $goods['price'];
                }
                $limitSales[$k]['price'] = $price;
            } else {
                $limitSales[$k]['price'] = $goods['price'];
            }
        }
        $data = [
            'activity' => $activity_info,
            'coupon' => $coupon,
            'appointment' => $appointment,
            'packageList' => $packageList,
            'limitSales' => $limitSales,
        ];
        ajaxReturn(['status' => 1,'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data]);
    }

    /**
     * 领取优惠劵
     */
    public function couponReceive()
    {
        if (request()->isPost()) {
            $user_id = $this->user_id;
            $coupon_id = input('post.id');
            $coupon_info = model('coupon_data')
                ->where(['coupon_id' => $coupon_id, 'user_id' => $user_id, 'use_type' => 3])
                ->find();
            if ($coupon_info) {
                $json_arr = ['status' => 0, 'msg' => '您已经领取过了，不可以重复领取', 'data' => []];
                ajaxReturn($json_arr);
            }
            $coupon = model('activity_coupon')->where(['id' => $coupon_id])->find();
            if ($coupon['star_receive_time'] > time()) {
                $json_arr = ['status' => 0, 'msg' => '未到领取时间，不可领取', 'data' => []];
                ajaxReturn($json_arr);
            }
            if ($coupon['end_receive_time'] < time()) {
                $json_arr = ['status' => 0, 'msg' => '已经超过领取时间，不可领取', 'data' => []];
                ajaxReturn($json_arr);
            }
            $add_arr = [
                'user_id' => $user_id,
                'coupon_id' => $coupon_id,
                'goods_info' => $coupon['goods_info'],
                'use_type' => $coupon['use_type'],
                'des' => $coupon['des'],
                'action' => 2,
                'add_time' => date("Y-m-d H:i:d"),
                'deduct' => $coupon['deduct'],
                'limit_money' => $coupon['limit_money'],
                'title' => $coupon['title'],
                'canal' => 1,
                'coupon_type' => $coupon['coupon_type'],
                'starttime' => $coupon['starttime'],
                'endtime' => $coupon['endtime'],
                'partner_id' => 0
            ];
            $coupon_no = 'VIP'.strtoupper(uniqid());
            while(model('coupon_data')->where(['coupon_no' => $coupon_no])->find()) {
                $coupon_no = 'VIP'.strtoupper(uniqid());
            }
            $add_arr['coupon_no'] = $coupon_no;
            model('coupon_data')->insert($add_arr);

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS];
            ajaxReturn($json_arr);
        }
    }
	
	
}