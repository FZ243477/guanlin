<?php


namespace app\api\controller;


use app\common\constant\CartConstant;
use app\common\constant\SystemConstant;
use app\common\helper\CartHelper;

class Svjia extends Base
{

    use CartHelper;

    public function __construct()
    {
        parent::__construct();

    }

    /*  public function svjia_login()
      {
          $user_info = model('user')->where(["id" => $this->user_id])->find();
          if ($user_info['is_svjia'] != 1 && !$user_info['svjia_id']) {
              $url = "https://open.3vjia.com/api/outerapi/outerUser/addUser?sysCode=outerapi&access_token=".$this->access_token;
              $userName = 'lc'.time().rand(1000,9999);
              while(model('user')->where(["svjia_name"=>$userName])->find()) {
                  $userName = 'lc'.time().rand(1000,9999);
              }
              //dump($userName);die;
              $passWord = 'lc'.$user_info['telephone'];
              $post_data = array();
              $post_data["departmentId"]   = "1315448";
              $post_data["mobile"]   = $user_info['telephone'];
              $post_data["userName"]   = $userName;
              $post_data["name"]   = $user_info['nickname'];
              $post_data["passWord"]   = $passWord;
              $post_data["outUserId"]   = $this->user_id;
              $post_data["outAppId"]   = "17175613594";
              $json_arr = $this->curlSend($url, json_encode($post_data));
              $data = json_decode($json_arr, true);

              if ($data['success']) {
                  $save = [
                      'is_svjia' => 1,
                      'svjia_id' => $data['result']['swjId'],
                      'svjia_name' => $data['result']['userName'],
                      'svjia_password' => $user_info['telephone'],
                  ];
                  model('user')->save($save, ["id" => $this->user_id]);
                  ajaxReturn(['status' => 1, 'msg' => '绑定酷家乐成功', 'data' => []]);
              } else {
                  // echo '错误码：'.$data['errorCode'];
                  // echo '<br>错误提示：'.$data['errorMessage'];
                  ajaxReturn(['status' => 0, 'msg' => '已绑定过酷家乐', 'data' => []]);
              }
          } else {
              ajaxReturn(['status' => 1, 'msg' => '已绑定过酷家乐', 'data' => []]);
          }

      }*/

    /*public function _initialize(){
        $this->cid  =   5;

        parent::_initialize();
        $this->checkLogin();
        $is_svjia = I('get.is_svjia');

        $expires_in = session('expires_in');
        if ($expires_in < time()) {
            session('access_token', null);
        }

        $this->access_token = session('access_token');
        $this->get_user_info();
        if ($this->user_id == 17) {
            //dump($this->access_token);die;
        }
        if (!$this->access_token) {
            //if ($is_svjia == 1) {
            $url = "https://graph.3vjia.com/oauth/token";
            $post_data = array();
            $post_data["grant_type"]  = "client_credentials"; //取固定值client_credentials
            $post_data["client_id"]   = "17175613594"; //应用id
            $post_data["client_secret"]   = "5b48cfaa2c9e43889c3334b1f3637b52"; //应用密钥

            $json_arr = $this->curl_access_token($url, http_build_query($post_data));
            $data = json_decode($json_arr, true);
            if ($data['access_token']) {

                $this->access_token = $data['access_token'];
                session('expires_in', $data['expires_in']+time());
                session('access_token', $data['access_token']);
            } else {
                $this->error('网络繁忙');
            }
            //} else {
            //$this->svjia_login();
            //}
        }
    }*/


    public function svjiaLogin()
    {
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '无效token']);
        }
        $JointLoginUrl = "https://sso.3vjia.com/JointLogin/Index?";
        $redirectUri = "https://admin.3vjia.com/3D/V3/Default.aspx";
        $appid = getSetting('svjia.appid'); //应用id
        $appkey = getSetting('svjia.appkey');; //应用密钥
        $time = time();
        $sign = md5($this->user_id.$appid.$time.$appkey);

        $data = array(
            'userid' => $this->user_id,
            'appid' => $appid,
            'time' => $time,
            'sign' => $sign,
            'redirect_uri' => $redirectUri,
        );

        foreach ($data as $k => $v) {
            $JointLoginUrl .=  $k."=".$v.'&';
        }
        $JointLoginUrl = rtrim( $JointLoginUrl, '&');
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['JointLoginUrl' => $JointLoginUrl]]);
    }

    public function svjiaPlan()
    {
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '无效token']);
        }
        $SchemeId = request()->post('SchemeId');
        $redirectUri = "https://admin.3vjia.com/3D/V3/Default.aspx";
        if ($SchemeId) {
            $redirectUri .= '?SchemeId='.$SchemeId;
        } else {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => [0]]);
        }
        $JointLoginUrl = "https://sso.3vjia.com/JointLogin/Index?";

        $appid = getSetting('svjia.appid'); //应用id
        $appkey = getSetting('svjia.appkey');; //应用密钥
        $time = time();
        $sign = md5($this->user_id.$appid.$time.$appkey);

        $data = array(
            'userid' => $this->user_id,
            'appid' => $appid,
            'time' => $time,
            'sign' => $sign,
            'redirect_uri' => $redirectUri,
        );

        foreach ($data as $k => $v) {
            $JointLoginUrl .=  $k."=".$v.'&';
        }
        $JointLoginUrl = rtrim( $JointLoginUrl, '&');
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['JointLoginUrl' => $JointLoginUrl]]);
    }


    /**
     * 方案列表
     */
    public function svjiaList()
    {

        $access_token = model('user')->where('id', 17)->value('svja_token');
        $page = request()->post('page');
        $search = request()->post('search');
        $list_row = request()->post('list_row');
        $url = "https://open.3vjia.com/api/outerapi/outerDesignScheme/listByPage?sysCode=outerapi&access_token=".$access_token;

        $post_data = array();
        /* 		kinds	String	否		种类，如建材类、家具类....这里传值为对应的id
                keyword	String	否		关键字,用户来过滤方案名或方案编号
                roomShape	String	否		房型，如凸型、L型,传对应的id
                houseType	String	否		户型，如一居室,两房一厅,传对应的id
                area	String	否		面积，如20m²以下，传对应的id
                style	String	否		风格，如现代、简约、田园,传对应的id
                roomType	String	否		空间类型，如客厅、主卧,这里需要传的值为不同类型对应的id
                beginDate	String	否	10天前	开始日期
                endDate	String	否	当前时间	结束时间
                pageIndex	Integer	否	1	当前页码
                pageSize	Integer	否	10	页大小
                userId	String	否		用户id
                userName	String	否		用户账号
                orderBy	String	否		排序
                categoryCode	String	否		categoryCode = 2时过滤上架发布到新品推荐的方案，这是目前的主要功能
                fullViewFlag	String	否		全景渲染参数，"0"表示过滤出有渲染图（不包括全景）的方案， “1”表示过滤出有全景图的方案，不传时表示不过滤渲染信息 */
        // $post_data["kinds"]  = "";
        $post_data["keyword"]   = $search;
        // $post_data["roomShape"]   = "";
        // $post_data["houseType"]   = "";
        // $post_data["area"]   = "";
        // $post_data["style"]   = "";
        // $post_data["roomType"]   = "";
        // $post_data["beginDate"]   = "";
        // $post_data["endDate"]   = "";
        $post_data["pageIndex"]   = $page;
        $post_data["pageSize"]   = $list_row;
        $post_data["outUserId"]   = 17;
        $post_data["outAppId"]   = getSetting('svjia.appid');
        // $post_data["userName"]   = "";
        // $post_data["orderBy"]   = "";
        // $post_data["categoryCode"]   = "";
        // $post_data["fullViewFlag"]   = "";

        $json_arr = $this->curlSend($url, json_encode($post_data));
        $data = json_decode($json_arr, true);

        $list = $data['result']['result'];
        if (!$data['success']) {
            $list = array();
        }
        $new_list = [];
        if ($list) {
            foreach ($list as $k => $v) {
                $new_list[] = [
                    'schemeId' => $v['schemeId'],
                    'imagePath' => $v['imagePath'],
                    'schemeName' => $v['schemeName'],
                ];
            }
        }

        $totalCount = $data['result']['recordCount'];
        $pageCount = ceil($totalCount/$list_row);

        $data = [
            'list' => $new_list ? $new_list : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);
    }

    /**
     * 我的方案
     */
    public function myPlan()
    {

        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '无效token']);
        }

        $page = request()->post('page');
        $search = request()->post('search', '');
        $list_row = request()->post('list_row');
        $access_token = model('user')->where('id', $this->user_id)->value('svja_token');
        $url = "https://open.3vjia.com/api/outerapi/outerDesignScheme/listByPage?sysCode=outerapi&access_token=".$access_token;

        $post_data = array();
        /* 		kinds	String	否		种类，如建材类、家具类....这里传值为对应的id
        keyword	String	否		关键字,用户来过滤方案名或方案编号
        roomShape	String	否		房型，如凸型、L型,传对应的id
        houseType	String	否		户型，如一居室,两房一厅,传对应的id
        area	String	否		面积，如20m²以下，传对应的id
        style	String	否		风格，如现代、简约、田园,传对应的id
        roomType	String	否		空间类型，如客厅、主卧,这里需要传的值为不同类型对应的id
        beginDate	String	否	10天前	开始日期
        endDate	String	否	当前时间	结束时间
        pageIndex	Integer	否	1	当前页码
        pageSize	Integer	否	10	页大小
        userId	String	否		用户id
        userName	String	否		用户账号
        orderBy	String	否		排序
        categoryCode	String	否		categoryCode = 2时过滤上架发布到新品推荐的方案，这是目前的主要功能
        fullViewFlag	String	否		全景渲染参数，"0"表示过滤出有渲染图（不包括全景）的方案， “1”表示过滤出有全景图的方案，不传时表示不过滤渲染信息 */
        // $post_data["kinds"]  = "";
        $post_data["keyword"]   = $search;
        // $post_data["roomShape"]   = "";
        // $post_data["houseType"]   = "";
        // $post_data["area"]   = "";
        // $post_data["style"]   = "";
        // $post_data["roomType"]   = "";
        // $post_data["beginDate"]   = "";
        // $post_data["endDate"]   = "";
        $post_data["pageIndex"]   = $page;
        $post_data["pageSize"]   = $list_row;
        $post_data["outUserId"]   = $this->user_id;
        $post_data["outAppId"]   = getSetting('svjia.appid');
        // $post_data["userName"]   = "";
        // $post_data["orderBy"]   = "";
        // $post_data["categoryCode"]   = "";
        // $post_data["fullViewFlag"]   = "";

        $json_arr = $this->curlSend($url, json_encode($post_data));
        $data = json_decode($json_arr, true);
        $list = $data['result']['result'];
        if (!$data['success']) {
            $list = array();
        }
        $new_list = [];
        if ($list) {
            foreach ($list as $k => $v) {
                $new_list[] = [
                    'schemeId' => $v['schemeId'],
                    'imagePath' => $v['imagePath'],
                    'schemeName' => $v['schemeName'],
                ];
            }
        }

        $totalCount = $data['result']['recordCount'];
        $pageCount = ceil($totalCount/$list_row);

        $data = [
            'list' => $new_list ? $new_list : [],
            'totalCount' => $totalCount ? $totalCount : 0,
            'pageCount' => $pageCount ? $pageCount : 0,
        ];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data'  => $data]);

    }


    /**
     * 拉取清单
     */
    public function svjiaOrder()
    {

        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '无效token']);
        }
        $schemeId = request()->post('SchemeId');

        if (!$schemeId) {
            ajaxReturn(['status' => 0, 'msg' => '缺少参数schemeId']);
        }
        $access_token = model('user')->where('id', $this->user_id)->value('svja_token');
        $url = "https://open.3vjia.com/api/outerapi/outerDesignScheme/get?sysCode=outerapi&access_token=".$access_token;

        $post_data = array();

        $post_data["schemeId"]   = $schemeId;

        $json_arr = $this->curlSend($url, json_encode($post_data));
        $data = json_decode($json_arr, true);
        if ($data['success'] != true) {
           ajaxReturn(['status' => 0, 'msg' => '网络繁忙', 'data' => $data]);
        }
        $list = $data['result']['DesignMaterialList'];//dump($list);die;
        $goods_id_arr = array();
        foreach ($list as $k => $v) {
            if (isset($v['productCode'])) {
                $spec_info = model('spec_goods_price')->where(['bar_code' => $v['productCode']])->find();
                if ($spec_info)  {
                    $sku_id = $spec_info['key'];
                    $goods = model('Goods')->where(['id' => $spec_info['goods_id']])->find();
                } else {
                    $sku_id = 0;
                    $goods = model('Goods')->where(['goods_code' => $v['productCode']])->find();
                }
                if ($goods) {
                    $goods_id_arr[] = [
                        'goods_id' => $goods['id'],
                        'goods_num' => intval($v['num']),
                        'sku_id' => $sku_id,
                    ];
                }
            }
        }
        if ($goods_id_arr) {
            $where = [
                'user_id' => $this->user_id,
                'cart_type' => CartConstant::CART_TYPE_SANVJIA_BUY,
            ];
            model('Cart')->where($where)->delete(); // 查找购物车是否已经存在该商品
            foreach ($goods_id_arr as $k => $v) {
                $this->addCartHandles($this->user_id, $v['goods_id'], $v['goods_num'], $v['sku_id'], CartConstant::CART_TYPE_SANVJIA_BUY);
            }
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        } else {
            ajaxReturn(['status' => 0, 'msg' => '该方案未选定可购买商品，无法生成清单']);
        }
    }



    /**
    企业账号绑定
     */
    public function bind(){
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '无效token']);
        }
        $url = "https://open.3vjia.com/api/outerBinDingUser/unbindSwjUser?sysCode=outerapi&access_token=".$access_token;
        $post_data = array();
        $appid = '17175613594';
        $appkey = '5b48cfaa2c9e43889c3334b1f3637b52';
        $member_info = D('Member')->where(array("id"=>$this->user_id))->find();

        $post_data["outUserId"]   = $this->user_id;
        $post_data["appId"]   = $appid;
        $post_data["swjUserName"]   = $member_info['svjia_name'];
        $post_data["appkey"]   = $appkey;
        $json_arr = $this->curlSend($url, json_encode($post_data));
        $data = json_decode($json_arr, true);

        if ($data['success']) {
            $save = array(
                'is_svjia' => 1,
                'svjia_id' => $member_info['swjId'],
                'svjia_name' => $member_info['svjia_name'],
                'svjia_password' => $member_info['svjia_password'],
            );
            D('Member')->where(array("id"=>$this->user_id))->save($save);
            echo '成功';
        } else {
            echo '错误码：'.$data['errorCode'];
            echo '<br>错误提示：'.$data['errorMessage'];
        }
    }

    /**
    企业账号解绑
     */
    public function unbind(){
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '无效token']);
        }
        $url = "https://open.3vjia.com/api/outerapi/outerBinDingUser/unbindSwjUser?sysCode=outerapi&access_token=".$access_token;
        $post_data = array();
        $appid = '17175613594';
        $appkey = '5b48cfaa2c9e43889c3334b1f3637b52';
        $member_info = D('Member')->where(array("id"=>$this->user_id))->find();

        $post_data["outUserId"]   = $this->user_id;
        $post_data["appId"]   = $appid;
        $post_data["swjUserName"]   = $member_info['svjia_name'];
        $post_data["appkey"]   = $appkey;
        $json_arr = $this->curlSend($url, json_encode($post_data));
        $data = json_decode($json_arr, true);

        if ($data['success']) {
            $save = array(
                'is_svjia' => 0,
            );
            D('Member')->where(array("id"=>$this->user_id))->save($save);
            echo '成功';
        } else {
            echo '错误码：'.$data['errorCode'];
            echo '<br>错误提示：'.$data['errorMessage'];
        }
    }
    /***
     * curl提交
     */
    public function curlSend($url, $post_data = "")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $headers = array(
            'Accept:application/json;',
            'Content-Type:application/json; charset=UTF-8',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($post_data != "") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /***
     * curl提交
     */
    public function curl_access_token($url, $post_data = "")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $headers = array(
            'Accept:application/json;',
            'Content-Type: application/x-www-form-urlencoded',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($post_data != "") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}