<?php


namespace app\api\controller;

use app\common\constant\OrderConstant;
use app\common\constant\SystemConstant;
use app\common\helper\KujialeHelper;
use app\common\helper\OrderHelper;
use app\common\helper\PayHelper;
use app\common\helper\IntegralHelper;
use app\common\helper\TokenHelper;
use app\common\assist\PayAssist;
use \think\Controller;

class Plan extends Controller
{
    use OrderHelper;
    use PayHelper;
    use IntegralHelper;
    use TokenHelper;
    use KujialeHelper;
    protected $user_id;

    public function planListFive()
    {
        $this->cancelOrder();
        $this->confirmOrder();
        $this->diyList();
    }

    /**
     * 取消订单定时任务
     */
    public function cancelOrder()
    {
        $now_time = date('Y-m-d H:i:s', time()-getSetting('order.auto_cancel_order'));
        $order = model('order')->where(['order_status' => '1', 'pay_status' => 0, 'order_time' => ['elt', $now_time]])->select();
        foreach ($order as $v) {
            $res =  $this->cancel_order($v['order_no']);
            $this->log_result('cancel.txt','取消订单', $res);
        }
    }

    /**
     * 确认收货定时任务
     */
    public function confirmOrder()
    {
        $now_time = date('Y-m-d H:i:s', time()+getSetting('order.auto_confirm_order'));
        $order = model('order')->where(['order_status' => 3, 'pay_status' => 1, 'order_time' => ['egt', $now_time]])->select();
        foreach ($order as $v) {
            $res =  $this->confirm_order($v['order_no']);
            $this->log_result('confirm.txt','确认收货', $res);
        }
    }

    /**
     * 轻设计
     */
    public function diyList()
    {
        $setting = model('setting')->where(['name' => 'design_create_time'])->find();
        if ($setting) {
            $time = $setting['value'];
        } else {
            $time = '';
        }
        $this->getDiy(1, 50, '', $time);
    }

    public function getDiy($page, $list_row, $search, $time)
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
        if ($time) {
            $get_data["time"] = $time;
        }
        $json_arr = $this->backDataInfo($url, $post_data, 'get', $get_data);
        $result = $json_arr['d']['result'];
        if ($result) {
            $list = [];
            $i = 0;
            foreach ($result as $k => $v) {
                $list_result = $this->diyLightList($v['planId']);
                foreach ($list_result as $k1 => $v1) {
                    if (isset($v1['easyDesignId'])) {
                        $design = model('design_list')->where(['easy_design_id' =>$v1['easyDesignId'] ])->select();
                        if (!$design) {
                            $list[$i] = [
                                'plan_id' => $v['planId'],
                                'design_id' => $v['designId'],
                                'comm_name' => $v['commName'],
                                'city' => $v['city'],
                                'name' => $v['name'],
                                'src_area' => $v['srcArea'],
                                'spec_name' => $v['specName'],
                                'area' => $v['area'],
                                'plan_pic' => $v['planPic'],
                                'create_time' => $v['created']/1000,
                                'update_time' => $v['modifiedTime']/1000,
                                'easy_design_id' => $v1['easyDesignId'],
                                'easy_design_link' => str_replace('kujiale.com', 'pano6.p.kujiale.com', $v1['easyDesignLink']),
                                'img' => $v1['img'],
                                'pano_link' => str_replace('pano.kujiale.com', 'pano6.p.kujiale.com', $v1['panoLink']),
                            ];
                        }
                        $i++;
                    }
                }
            }
            $res = model('design_list')->insertAll($list);
            if ($time) {
                model('setting')->save(['value' => time()*1000], ['name' => 'design_create_time']);
            } else {
                model('setting')->save(['name' => 'design_create_time', 'value' => time()*1000]);
            }
            $this->log_result('design.txt','轻设计录入', $list);
        }
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
        return $json_arr['d']['pics'];
    }

}