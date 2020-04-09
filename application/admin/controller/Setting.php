<?php

namespace app\admin\controller;
use app\common\constant\SystemConstant;

class Setting extends Base {

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 系统配置
     * */
    public function index()
    {
        if (request()->isPost()) {
            $setting_msg = getSetting('system');
            if ($setting_msg) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['setting_system' => $setting_msg]]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        } else {
            return $this->fetch();
        }
    }

    public function handle()
    {

        if (request()->isPost()) {

            $setting_msg = getSetting('system');
            $data = $setting_msg;
            $host = input('post.host','','trim');
            $title = input('post.title','','trim');
            $copyright = input('post.copyright','','trim');
            $link_logo = input('post.link_logo','','trim');
            $header_logo = input('post.header_logo','','trim');
            $token_key = input('post.token_key','','trim');
            $records = input('post.records','','trim');
            $re_username = input('post.re_username','','trim');
            $re_account = input('post.re_account','','trim');
            $re_bank = input('post.re_bank','','trim');

            if (!$title) {
                ajaxReturn(['status' => 0, 'msg' => '请填写网站标题']);
            }

            if (!$copyright) {
                ajaxReturn(['status' => 0, 'msg' => '请填写网站版权']);
            }

            if (!$header_logo) {
                ajaxReturn(['status' => 0, 'msg' => '请上传网站logo图']);
            }

            $data['host'] = $host;
            $data['title'] = $title;
            $data['re_username'] = $re_username;
            $data['re_account'] = $re_account;
            $data['re_bank'] = $re_bank;
            $data['copyright'] = $copyright;
            $data['link_logo'] = $link_logo;
            $data['header_logo'] = $header_logo;
            $data['records'] = $records;
            $data['token_key'] = $token_key;
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);

            $setting_db = model('Setting');

            if ($setting_msg) {
                $setting_db->save(['value' => $data], ['name' => 'system']);
                $res = true;
            } else {
                $res =$setting_db->save(['name' => 'system', 'value' => $data]);
            }

            if ($res) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }


        }
    }


    public function alioss()
    {
        $setting_msg = getSetting('alioss');


        if (!$setting_msg) {

            $setting_msg = array();

        }

        if (request()->isAjax()) {

            $data = $setting_msg;

            $key_id = input('post.key_id','','trim');

            $key_secret = input('post.key_secret','','trim');

            $bucket = input('post.bucket','','trim');

            $endpoint = input('post.endpoint','','trim');

            $is_oss = input('post.is_oss','','trim');

            $data['key_id'] = $key_id;

            $data['key_secret'] = $key_secret;

            $data['bucket'] = $bucket;

            $data['endpoint'] = $endpoint;

            $data['is_oss'] = $is_oss;


            $data = json_encode($data, JSON_UNESCAPED_UNICODE);

            $setting_db = model('Setting');

            if ($setting_msg) {

                $setting_db->save(['value' => $data], ['name' => 'alioss']);
                $res = true;
            } else {

                $res =$setting_db->save(['name' => 'alioss', 'value' => $data]);

            }

            if ($res) {

                return ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS];

            } else {
                return ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE];
            }


        } else {
            isset($setting_msg['is_oss'])?false: $setting_msg['is_oss'] = 0;
            $this->assign('setting_info', $setting_msg);

            return $this->fetch();
        }
    }

    public function activity()
    {
		
        $setting_msg = getSetting('activity');

        if (!$setting_msg) {

            $setting_msg = [];

        }

        if (request()->isAjax()) {

            $data = $setting_msg;


            $is_activity = input('post.is_activity');
            $is_banner = input('post.is_banner');
            $is_coupon = input('post.is_coupon');
            $is_limit = input('post.is_limit');
            $is_hall = input('post.is_hall');
            $logo = input('post.logo');
            $logo_phone = input('post.logo_phone');

            $data['is_activity'] = $is_activity;
            $data['is_banner'] = $is_banner;
            $data['is_coupon'] = $is_coupon;
            $data['is_limit'] = $is_limit;
            $data['is_hall'] = $is_hall;
            $data['logo'] = $logo;
            $data['logo_phone'] = $logo_phone;

            $data = json_encode($data, JSON_UNESCAPED_UNICODE);

            $setting_db = model('Setting');

            if ($setting_msg) {
                $setting_db->save(['value' => $data], ['name' => 'activity']);
                $res = true;
            } else {

                $res =$setting_db->save(['name' => 'activity', 'value' => $data]);

            }

            if ($res) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }


        } else {

            $this->assign('setting_info', $setting_msg);

            return $this->fetch();
        }
    }


    public function order()
    {
        $setting_msg = getSetting('order');

        if (!$setting_msg) {

            $setting_msg = [];

        }

        if (request()->isAjax()) {

            $data = $setting_msg;


            $auto_cancel_order = input('post.auto_cancel_order');
            $order_distribution_percentage = input('post.order_distribution_percentage');
            $order_distribution_first = input('post.order_distribution_first');
            $order_distribution_second = input('post.order_distribution_second');
            $order_distribution_third = input('post.order_distribution_third');
            $order_full_amount = input('post.order_full_amount');
            $order_full_amount_freight = input('post.order_full_amount_freight');
            $deposit_order_money = input('post.deposit_order_money');
            $deposit_order_percent = input('post.deposit_order_percent');
            $auto_confirm_order = input('post.auto_confirm_order');


            $data['deposit_order_money'] = $deposit_order_money;
            $data['deposit_order_percent'] = $deposit_order_percent;
            $data['auto_cancel_order'] = $auto_cancel_order;
            $data['auto_confirm_order'] = $auto_confirm_order;
            $data['order_distribution_percentage'] = $order_distribution_percentage;
            $data['order_distribution_first'] = $order_distribution_first;
            $data['order_distribution_second'] = $order_distribution_second;
            $data['order_distribution_third'] = $order_distribution_third;
            $data['order_full_amount'] = $order_full_amount;
            $data['order_full_amount_freight'] = $order_full_amount_freight;

            $data = json_encode($data, JSON_UNESCAPED_UNICODE);

            $setting_db = model('Setting');

            if ($setting_msg) {
                $setting_db->save(['value' => $data], ['name' => 'order']);
                $res = true;
            } else {

                $res =$setting_db->save(['name' => 'order', 'value' => $data]);

            }

            if ($res) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }


        } else {

            $this->assign('setting_info', $setting_msg);

            return $this->fetch();
        }
    }
    public function wechat()
    {
        $setting_msg = getSetting('wechat');

        if (!$setting_msg) {

            $setting_msg = [];

        }

        if (request()->isAjax()) {

            $data = $setting_msg;


            $we_chat_mch_id = input('post.we_chat_mch_id');
            $we_chat_appid = input('post.we_chat_appid');
            $we_chat_key = input('post.we_chat_key');
            $we_chat_secret = input('post.we_chat_secret');
            $we_cgi_appid = input('post.we_cgi_appid');
            $we_cgi_secret = input('post.we_cgi_secret');


            $data['we_chat_mch_id'] = $we_chat_mch_id;
            $data['we_chat_appid'] = $we_chat_appid;
            $data['we_chat_key'] = $we_chat_key;
            $data['we_chat_secret'] = $we_chat_secret;
            $data['we_cgi_appid'] = $we_cgi_appid;
            $data['we_cgi_secret'] = $we_cgi_secret;

            $data = json_encode($data, JSON_UNESCAPED_UNICODE);

            $setting_db = model('Setting');

            if ($setting_msg) {
                $setting_db->save(['value' => $data], ['name' => 'wechat']);
                $res = true;
            } else {

                $res =$setting_db->save(['name' => 'wechat', 'value' => $data]);

            }

            if ($res) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }


        } else {

            $this->assign('setting_info', $setting_msg);

            return $this->fetch();
        }
    }
    public function alipay()
    {
        $setting_msg = getSetting('alipay');

        if (!$setting_msg) {

            $setting_msg = [];

        }

        if (request()->isAjax()) {

            $data = $setting_msg;


            $partner = input('post.partner');
            $seller_id = input('post.seller_id');
            $seller_email = input('post.seller_email');
            $key = input('post.key');
            $transport = input('post.transport');


            $data['partner'] = $partner;
            $data['seller_id'] = $seller_id;
            $data['seller_email'] = $seller_email;
            $data['key'] = $key;
            $data['transport'] = $transport;

            $data = json_encode($data, JSON_UNESCAPED_UNICODE);

            $setting_db = model('Setting');

            if ($setting_msg) {
                $setting_db->save(['value' => $data], ['name' => 'alipay']);
                $res = true;
            } else {
                $res =$setting_db->save(['name' => 'alipay', 'value' => $data]);
            }

            if ($res) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }


        } else {

            $this->assign('setting_info', $setting_msg);

            return $this->fetch();
        }
    }
    public function unionPay()
    {
        $setting_msg = getSetting('unionPay');

        if (!$setting_msg) {
            $setting_msg = [];
        }
        if (request()->isAjax()) {
            $data = $setting_msg;
            $appId = input('post.appId');
            $mer_id = input('post.mer_id');
            $mer_acct = input('post.mer_acct');
            $key = input('post.key');
            $crt = input('post.crt');
            $password = input('post.password');
            $icbcPulicKey = input('post.icbcPulicKey');

            $data['appId'] = $appId;
            $data['mer_id'] = $mer_id;
            $data['mer_acct'] = $mer_acct;
            $data['key'] = $key;
            $data['crt'] = $crt;
            $data['password'] = $password;
            $data['icbcPulicKey'] = $icbcPulicKey;

            $data = json_encode($data, JSON_UNESCAPED_UNICODE);

            $setting_db = model('Setting');

            if ($setting_msg) {
                $setting_db->save(['value' => $data], ['name' => 'unionPay']);
                $res = true;
            } else {
                $res =$setting_db->save(['name' => 'unionPay', 'value' => $data]);
            }

            if ($res) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }

        } else {

            $this->assign('setting_info', $setting_msg);

            return $this->fetch();
        }
    }

    public function integral()
    {
        $setting_msg = getSetting('integral');

        if (!$setting_msg) {

            $setting_msg = [];

        }

        if (request()->isAjax()) {

            $data = $setting_msg;


            $sign_give_integral = input('post.sign_give_integral');
            $register_give_integral = input('post.register_give_integral');
            $money_exchange_integral_one = input('post.money_exchange_integral_one');
            $money_exchange_integral_all = input('post.money_exchange_integral_all');
            $integral_exchange_money_one = input('post.integral_exchange_money_one');
            $integral_exchange_money_all = input('post.integral_exchange_money_all');

            $data['sign_give_integral'] = $sign_give_integral;
            $data['register_give_integral'] = $register_give_integral;
            $data['money_exchange_integral_one'] = $money_exchange_integral_one;
            $data['money_exchange_integral_all'] = $money_exchange_integral_all;
            $data['integral_exchange_money_one'] = $integral_exchange_money_one;
            $data['integral_exchange_money_all'] = $integral_exchange_money_all;

            $data = json_encode($data, JSON_UNESCAPED_UNICODE);

            $setting_db = model('Setting');

            if ($setting_msg) {
                $setting_db->save(['value' => $data], ['name' => 'integral']);
                $res = true;
            } else {

                $res =$setting_db->save(['name' => 'integral', 'value' => $data]);

            }

            if ($res) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }


        } else {

            $this->assign('setting_info', $setting_msg);

            return $this->fetch();
        }
    }

    /**
     * 系统配置
     * */
    public function sms()
    {
        if (request()->isPost()) {
            $setting_msg = getSetting('sms');
            if ($setting_msg) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['setting_system' => $setting_msg]]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        } else {
            return $this->fetch();
        }
    }

    public function smsHandle()
    {

        if (request()->isPost()) {

            $setting_msg = getSetting('sms');
            $data = $setting_msg;
            $user = input('post.user','','trim');
            $pwd = input('post.pwd','','trim');
            $mid = input('post.mid','','trim');
            $url = input('post.url','0','trim');
            $max_send = input('post.max_send','','trim');
            $over_time = input('post.over_time','','trim');
            $code_template = input('post.code_template','','trim');
            $shipping_template = input('post.shipping_template','','trim');

            if (!$user) {
                ajaxReturn(['status' => 0, 'msg' => "请填写短信账号"]);
            }

            if (!$pwd) {
                ajaxReturn(['status' => 0, 'msg' => "请填写短信密码"]);
                $this->ajaxReturn(array('status' => 0, 'info' => '请填写短信密码'));
            }

            if (!$url) {
                ajaxReturn(['status' => 0, 'msg' => "请填写用户每日短信上限"]);
            }

            if (!$over_time) {
                ajaxReturn(['status' => 0, 'msg' => "请填写短信过期时间"]);
            }

            $data['user'] = $user;
            $data['pwd'] = $pwd;
            $data['mid'] = $mid;
            $data['url'] = $url;
            $data['max_send'] = $max_send;
            $data['over_time'] = $over_time;
            $data['code_template'] = $code_template;
            $data['shipping_template'] = $shipping_template;
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);

            $setting_db = model('Setting');

            if ($setting_msg) {
                $setting_db->save(['value' => $data], ['name' => 'sms']);
                $res = true;
            } else {
                $res =$setting_db->save(['name' => 'system', 'value' => $data]);
            }

            if ($res) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }


        }
    }

    /**
     * 系统配置
     * */
    public function kujiale()
    {
        if (request()->isPost()) {
            $setting_msg = getSetting('kujiale');
            if ($setting_msg) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['setting_system' => $setting_msg]]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        } else {
            return $this->fetch();
        }
    }

    public function kujialeHandle()
    {

        if (request()->isPost()) {

            $setting_msg = getSetting('kujiale');
            $data = $setting_msg;
            $appkey = input('post.appkey','','trim');
            $data['appkey'] = $appkey;
            $appsecret = input('post.appsecret','','trim');
            $data['appsecret'] = $appsecret;
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);

            $setting_db = model('Setting');

            if ($setting_msg) {
                $setting_db->save(['value' => $data], ['name' => 'kujiale']);
                $res = true;
            } else {
                $res =$setting_db->save(['name' => 'system', 'value' => $data]);
            }

            if ($res) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }


        }
    }

    /**
     * 系统配置
     * */
    public function svjia()
    {
        if (request()->isPost()) {
            $setting_msg = getSetting('svjia');
            if ($setting_msg) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['setting_system' => $setting_msg]]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }
        } else {
            return $this->fetch();
        }
    }

    public function svjiaHandle()
    {

        if (request()->isPost()) {

            $setting_msg = getSetting('svjia');
            $data = $setting_msg;
            $appid = input('post.appid','','trim');
            $appkey = input('post.appkey','','trim');
            $client_id = input('post.client_id','','trim');
            $client_secret = input('post.client_secret','','trim');
            $grant_type = input('post.grant_type','','trim');
            $redirect_uri = input('post.redirect_uri','','trim');

            $data['appid'] = $appid;
            $data['appkey'] = $client_id;
            $data['client_id'] = $client_secret;
            $data['client_secret'] = $appkey;
            $data['grant_type'] = $grant_type;
            $data['redirect_uri'] = $redirect_uri;


            $data = json_encode($data, JSON_UNESCAPED_UNICODE);

            $setting_db = model('Setting');

            if ($setting_msg) {
                $setting_db->save(['value' => $data], ['name' => 'svjia']);
                $res = true;
            } else {
                $res =$setting_db->save(['name' => 'svjia', 'value' => $data]);
            }

            if ($res) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE]);
            }


        }
    }
}