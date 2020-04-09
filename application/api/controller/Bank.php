<?php

namespace app\api\controller;

use app\common\constant\SystemConstant;

class Bank extends Base
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->user_id) {
            ajaxReturn(['status' => -1, 'msg' => '请登录', 'data' => []]);
        }
    }

    //银行卡增加和修改
    public function addBankCard()
    {
        if (request()->isPost()) {
            $bankCardId = input('post.bank_cardid');
            if (!$bankCardId) {
                exit(json_encode(['status' => 0, 'msg' => '卡号不能为空', 'data' => []]));
            }
            $data['cardNo'] = $bankCardId;
            $data['cardBinCheck'] = 'true';
            $bankCardCheck = $this->postCurl('https://ccdcapi.alipay.com/validateAndCacheCardInfo.json', $data);
            $bankCardCheck = json_decode($bankCardCheck, true);
            if (!$bankCardCheck['validated']) {
                exit(json_encode(['status' => 0, 'msg' => '请输入正确的银行卡号', 'data' => []]));
            }
            $bankCardInfo['add_time'] = date('Y-m-d H:i:s', time());
            $id = input('post.id', 0);
            $bankDetail = model('BankList')->where(['user_id' => $this->user_id, 'bank_cardid' => $bankCardId, 'is_del' => 0])->find();
            if ($bankDetail) {
                if ($id) {
                    if ($bankDetail['user_id'] != $this->user_id) {
                        exit(json_encode(['status' => 0, 'msg' => '银行卡已存在', 'data' => []]));
                    }
                } else {
                    exit(json_encode(['status' => 0, 'msg' => '银行卡已存在', 'data' => []]));
                }
            }


            $bankInfo = ["CDB" => "国家开发银行", "ICBC" => "中国工商银行", "ABC" => "中国农业银行", "BOC" => "中国银行", "CCB" => "中国建设银行", "PSBC" => "中国邮政储蓄银行", "COMM" => "交通银行", "CMB" => "招商银行", "SPDB" => "上海浦东发展银行", "CIB" => "兴业银行", "HXBANK" => "华夏银行", "GDB" => "广东发展银行", "CMBC" => "中国民生银行", "CITIC" => "中信银行", "CEB" => "中国光大银行", "EGBANK" => "恒丰银行", "CZBANK" => "浙商银行", "BOHAIB" => "渤海银行", "SPABANK" => "平安银行", "SHRCB" => "上海农村商业银行", "YXCCB" => "玉溪市商业银行", "YDRCB" => "尧都农商行", "BJBANK" => "北京银行", "SHBANK" => "上海银行", "JSBANK" => "江苏银行", "HZCB" => "杭州银行", "NJCB" => "南京银行", "NBBANK" => "宁波银行", "HSBANK" => "徽商银行", "CSCB" => "长沙银行", "CDCB" => "成都银行", "CQBANK" => "重庆银行", "DLB" => "大连银行", "NCB" => "南昌银行", "FJHXBC" => "福建海峡银行", "HKB" => "汉口银行", "WZCB" => "温州银行", "QDCCB" => "青岛银行", "TZCB" => "台州银行", "JXBANK" => "嘉兴银行", "CSRCB" => "常熟农村商业银行", "NHB" => "南海农村信用联社", "CZRCB" => "常州农村信用联社", "H3CB" => "内蒙古银行", "SXCB" => "绍兴银行", "SDEB" => "顺德农商银行", "WJRCB" => "吴江农商银行", "ZBCB" => "齐商银行", "GYCB" => "贵阳市商业银行", "ZYCBANK" => "遵义市商业银行", "HZCCB" => "湖州市商业银行", "DAQINGB" => "龙江银行", "JINCHB" => "晋城银行JCBANK", "ZJTLCB" => "浙江泰隆商业银行", "GDRCC" => "广东省农村信用社联合社", "DRCBCL" => "东莞农村商业银行", "MTBANK" => "浙江民泰商业银行", "GCB" => "广州银行", "LYCB" => "辽阳市商业银行", "JSRCU" => "江苏省农村信用联合社", "LANGFB" => "廊坊银行", "CZCB" => "浙江稠州商业银行", "DYCB" => "德阳商业银行", "JZBANK" => "晋中市商业银行", "BOSZ" => "苏州银行", "GLBANK" => "桂林银行", "URMQCCB" => "乌鲁木齐市商业银行", "CDRCB" => "成都农商银行", "ZRCBANK" => "张家港农村商业银行", "BOD" => "东莞银行", "LSBANK" => "莱商银行", "BJRCB" => "北京农村商业银行", "TRCB" => "天津农商银行", "SRBANK" => "上饶银行", "FDB" => "富滇银行", "CRCBANK" => "重庆农村商业银行", "ASCB" => "鞍山银行", "NXBANK" => "宁夏银行", "BHB" => "河北银行", "HRXJB" => "华融湘江银行", "ZGCCB" => "自贡市商业银行", "YNRCC" => "云南省农村信用社", "JLBANK" => "吉林银行", "DYCCB" => "东营市商业银行", "KLB" => "昆仑银行", "ORBANK" => "鄂尔多斯银行", "XTB" => "邢台银行", "JSB" => "晋商银行", "TCCB" => "天津银行", "BOYK" => "营口银行", "JLRCU" => "吉林农信", "SDRCU" => "山东农信", "XABANK" => "西安银行", "HBRCU" => "河北省农村信用社", "NXRCU" => "宁夏黄河农村商业银行", "GZRCU" => "贵州省农村信用社", "FXCB" => "阜新银行", "HBHSBANK" => "湖北银行黄石分行", "ZJNX" => "浙江省农村信用社联合社", "XXBANK" => "新乡银行", "HBYCBANK" => "湖北银行宜昌分行", "LSCCB" => "乐山市商业银行", "TCRCB" => "江苏太仓农村商业银行", "BZMD" => "驻马店银行", "GZB" => "赣州银行", "WRCB" => "无锡农村商业银行", "BGB" => "广西北部湾银行", "GRCB" => "广州农商银行", "JRCB" => "江苏江阴农村商业银行", "BOP" => "平顶山银行", "TACCB" => "泰安市商业银行", "CGNB" => "南充市商业银行", "CCQTGB" => "重庆三峡银行", "XLBANK" => "中山小榄村镇银行", "HDBANK" => "邯郸银行", "KORLABANK" => "库尔勒市商业银行", "BOJZ" => "锦州银行", "QLBANK" => "齐鲁银行", "BOQH" => "青海银行", "YQCCB" => "阳泉银行", "SJBANK" => "盛京银行", "FSCB" => "抚顺银行", "ZZBANK" => "郑州银行", "SRCB" => "深圳农村商业银行", "BANKWF" => "潍坊银行", "JJBANK" => "九江银行", "JXRCU" => "江西省农村信用", "HNRCU" => "河南省农村信用", "GSRCU" => "甘肃省农村信用", "SCRCU" => "四川省农村信用", "GXRCU" => "广西省农村信用", "SXRCCU" => "陕西信合", "WHRCB" => "武汉农村商业银行", "YBCCB" => "宜宾市商业银行", "KSRB" => "昆山农村商业银行", "SZSBK" => "石嘴山银行", "HSBK" => "衡水银行", "XYBANK" => "信阳银行", "NBYZ" => "鄞州银行", "ZJKCCB" => "张家口市商业银行", "XCYH" => "许昌银行", "JNBANK" => "济宁银行", "CBKF" => "开封市商业银行", "WHCCB" => "威海市商业银行", "HBC" => "湖北银行", "BOCD" => "承德银行", "BODD" => "丹东银行", "JHBANK" => "金华银行", "BOCY" => "朝阳银行", "LSBC" => "临商银行", "BSB" => "包商银行", "LZYH" => "兰州银行", "BOZK" => "周口银行", "DZBANK" => "德州银行", "SCCB" => "三门峡银行", "AYCB" => "安阳银行", "ARCU" => "安徽省农村信用社", "HURCB" => "湖北省农村信用社", "HNRCC" => "湖南省农村信用社", "NYNB" => "广东南粤银行", "LYBANK" => "洛阳银行", "NHQS" => "农信银清算中心", "CBBQS" => "城市商业银行资金清算中心"];
            $bankCardInfo['bank_logo'] = 'https://apimg.alipay.com/combo.png?d=cashier&t='.$bankCardCheck['bank'];
            $bankCardInfo['bank_cardid'] = $bankCardId;
            $bankCardInfo['user_id'] = $this->user_id;
            $bankCardInfo['bank_name'] = $bankInfo[$bankCardCheck['bank']];
            $bankCardInfo['bank_branch'] = input('post.bank_branch');
            if (!$bankCardInfo['bank_branch']) {
                exit(json_encode(['status' => 0, 'msg' => '请填写支行名称', 'data' => []]));
            }
            $bankCardInfo['realname'] = input('post.realname');
            if (!$bankCardInfo['realname']) {
                exit(json_encode(['status' => 0, 'msg' => '请填写真实姓名', 'data' => []]));
            }
            $bankCardInfo['telephone'] = input('post.telephone');
            if (!preg_match("/^1[23456789]\d{9}$/", $bankCardInfo['telephone'])) {
                exit(json_encode(['status' => 0, 'msg' => '请填写正确的手机号', 'data' => []]));
            }
            if (!$id) {
                $res = model('BankList')->insert($bankCardInfo);
            } else {
                $res = model('BankList')->where(['id' => $id])->update($bankCardInfo);
            }
            if ($res) {
                exit(json_encode(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]));
            } else {
                exit(json_encode(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]));
            }
        }
    }

    //银行卡列表
    public function bankCardList()
    {
        $userId = $this->user_id;
        $bankList = model('bank_list')->where(['user_id' => $userId, 'is_del' => 0, 'partner_id' => 0])->select();
        if ($bankList) {
            foreach ($bankList as $k => $v) {
                $bankList[$k]['new_bank_cardid'] = substr($v['bank_cardid'], -4);
            }
            exit(json_encode(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['banklist' => $bankList]]));
        } else {
            exit(json_encode(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]));
        }
    }

    //删除银行卡
    public function bankDel()
    {
        if (request()->isPost()) {
            $id = input('post.id');
            if ($id) {
                $ids = explode(',', $id);
                $result = true;
                foreach ($ids as $k => $id) {
                    $result = model('bank_list')->where(['id' => $id])->update(['is_del' => 1, 'delete_time' => time()]);
                    if (!$result) {
                        break;
                    }
                }
                if ($result) {
                    $return_arr = ['status' => 1, 'msg' => '删除成功', 'data' => []];
                } else {
                    $return_arr = ['status' => 0, 'msg' => '删除失败', 'data' => []];
                }
            } else {
                $return_arr = ['status' => 0, 'msg' => '操作失败', 'data' => []];
            }
            exit(json_encode($return_arr));
        }
    }

    //银行卡详情
    public function cardInfo()
    {
        if (request()->isPost()) {
            $id = input('post.id');
            $field = 'id,bank_cardid,bank_logo,bank_name, bank_branch,realname, telephone';
            $bankCard = model('bank_list')->field($field)->where(['id' => $id, 'user_id' => $this->user_id])->find();
            if ($bankCard) {
                $bankCard['new_bank_cardid'] = substr($bankCard['bank_cardid'], -4);
                exit(json_encode(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['bank_info' => $bankCard]]));
            } else {
                exit(json_encode(['status' => 0, 'msg' => '银行卡不存在', 'data' => []]));
            }
        }
    }

    // 模拟提交数据函数
    function postCurl($url, $data)
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno' . curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }


}