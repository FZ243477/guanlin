<?php


namespace app\api\controller;

use app\common\constant\OrderConstant;
use app\common\constant\SystemConstant;
use app\common\helper\CartHelper;
use app\common\helper\OrderHelper;
use app\common\helper\PayHelper;
use app\common\helper\TokenHelper;
use \think\Controller;

class Pay extends Controller
{
    use OrderHelper;
    use PayHelper;
    use TokenHelper;
    use CartHelper;
    protected $user_id;

    public function __construct()
    {
        $token = request()->post('token');
        $result = $this->getUserInfoByToken($token);
        if ($result) {
            $this->user_id = $result;
        } else {
            $this->user_id = 0;
//            ajaxReturn(['status' => -1, 'msg' => '无效token']);
        }
    }

    /**
     * 微信支付
     * @param token
     * @param out_trade_no      支付号
     * @param type              类型：0订单支付  1余额充值  2积分充值
     */
    public function wxPay()
    {
        // TODO: Implement wxPay() method.
        $out_trade_no    = request()->post('order_id');
        $type    = request()->post('type', 0);
        if(!$out_trade_no){
            ajaxReturn(['status' => 0, 'msg' => '支付单号为空', 'data' => []]);
        }
        $map = [];
        $map['order_id'] = $out_trade_no;
        $map['uid']      = $this->user_id;
        $res = model('order')->where($map)->find();

        if(!$res){
            ajaxReturn(['status' => 0, 'msg' => '此订单不存在了', 'data' => []]);
        }

        if ($res['paid'] == OrderConstant::PAY_STATUS_DOING ) {
            ajaxReturn(['status' => 0, 'msg' => '此订单已支付', 'data' => []]);
        }

        $out_trade_no = $res['order_id'];

        //付款金额
        $payMoney = $res['price'];
        //$payMoney = 0.01;
        //参数值
        $total_fee    = $payMoney*100;  //订单金额
        $subject      = "转运助手订单微信支付";

        $notify_url = getSetting('system.host').'/api/Pay/wxPayNotifyUrl';

        $timestamp = time();

        $config = [
            'mch_id' => getSetting('wechat.we_chat_mch_id'),
            'appid' => getSetting('wechat.we_cgi_appid'),
            'key' => getSetting('wechat.we_cgi_secret'),
        ];

        $trade_type = 'JSAPI';
        $arr = $this->createJsBizPackage($total_fee, $out_trade_no, $subject, $notify_url, $timestamp, $this->user_id, $trade_type, $config);

        $arr_json['status'] = '1';
        $arr_json['msg'] = '去支付';
       /* if ($arr['code_url']) {
            $arr['code_url'] =  '<img alt="模式二扫码支付" src="'.getSetting('system.host').'/api/Pay/qr_code/?code_url='.urlencode($arr['code_url']).'" />';
        }*/
        $arr_json['data'] = $arr;
        ajaxReturn($arr_json);
    }

    /**
     * 微信支付回调地址
     */
    public function wxPayNotifyUrl()
    {
        $config_key =getSetting('wechat.we_chat_key');
        $postStr = file_get_contents('php://input');
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $this->log_result("./wxpay_log.txt","【接收到的notify通知sucess1】:", $postObj);
        if ($postObj === false) {
            $this->log_result("./wxpay_log.txt","【error1】:", $postObj);
            return ['status' => 0, 'msg' => 'parse xml error'];
        }
        if ($postObj->return_code != 'SUCCESS') {
            $this->log_result("./wxpay_log.txt","【error2】:", $postObj);
            return ['status' => 0, 'msg' => $postObj->return_msg];
        }
        if ($postObj->result_code != 'SUCCESS') {
            $this->log_result("./wxpay_log.txt","【error3】:", $postObj);
            return ['status' => 0, 'msg' => $postObj->err_code];
        }
        $arr = (array)$postObj;
        unset($arr['sign']);
        $this->log_result("./wxpay_log.txt","sucess2:", $arr);
        if ($this->getSign($arr, $config_key) == $postObj->sign) {
            $this->log_result("./wxpay_log.txt","sucess3:", $arr);
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
                //订单支付回调
            $res = $this->successPayOperation($arr['out_trade_no'],$arr['transaction_id'],$arr['total_fee']/100,2);
            $this->log_result("./wxpay_log.txt","sucess3:", $res);
            die;
        }
    }

    /**
     * 银联支付回调地址
     */
    public function unionPayNotifyUrl()
    {
        $result = $this->notify();
        $data = $result['data'];
        $log_name = "./log/wxpay_log.txt"; // 文本日志保存路径
        if ($result['status'] == 1) {
            $transaction_id = $data['transaction_id'];   //流水账号
            $out_trade_no   = $data['out_trade_no'];
            $total_fee      = ($data['total_fee'])*0.01; //支付金额
            $this->successPayOperation($out_trade_no,$transaction_id,$total_fee,2);
            $this->log_result($log_name,"【接收到的notify通知】:\n".$data."\n");
        } else {
            $this->log_result($log_name,"【接收到的notify通知】:\n".$data."\n");
        }
    }


    public function c_x_o_r_d_e_r()
    {
        $order_no = request()->post('order_no');
        $transaction_id = request()->post('transaction_id');
        $pay_way = request()->post('pay_way');
        $total_fee = request()->post('total_fee');
        $json_arr = $this->successPayOperation($order_no, $transaction_id, $total_fee, $pay_way);
        exit(json_encode($json_arr));
    }


    public function qr_code()
    {
        $code_url = request()->param('code_url');
        // vendor('phpqrcode.phpqrcode');
        import('phpqrcode.phpqrcode', EXTEND_PATH,'.php');
        $url = urldecode($code_url);
        ob_clean();
        \QRcode::png($url);
        die;
    }

}