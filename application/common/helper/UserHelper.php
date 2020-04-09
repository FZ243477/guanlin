<?php


namespace app\common\helper;
use app\common\constant\UserConstant;

trait UserHelper
{

    /**
     * 获取用户信息
     * @param $encryptedData
     * @param $iv
     * @param $session_key
     * @return bool|mixed
     */
    private function get_access_token($encryptedData,$iv,$session_key)
    {
        $appid = getSetting('wechat.we_cgi_appid');
        $session_key = $this->base64Decryption($session_key);
        require_once(EXTEND_PATH.'wechat/WXBizDataCrypt.php');
        $pc = new \WXBizDataCrypt ( $appid, $session_key );
        $errCode = $pc->decryptData ( $encryptedData, $iv, $data );
        $result = json_decode($data, true);
        if ($errCode == 0) {
            return $result;
        } else {
            return false;
        }
    }

    // 添加访问量记录
    private function add_access($user_id, $type, $source = UserConstant::REG_SOURCE_PC, $partner_id = 0, $pid = 0){
        if (!$user_id) {
            return false;
        }
        $where = [
            'partner_id' => $partner_id,
            'user_id' => $user_id,
            'type' => $type,
            'pid' => $pid,
            'creat_at' => ['between', [time()-60*60*2, time()]]
        ];
        $res = model("access")->where($where)->find();
        if ($res) {
            return false;
        }
        //更新访问量
        $access['pid'] = $pid;
        $access['partner_id'] = $partner_id;
        $access['user_id'] = $user_id;
        $access['creat_at'] = time();
        $access['source'] = $source;
        $access['title'] = UserConstant::uer_access_value($type);
        $hour = date('H',time());
        if($hour >=0 && $hour < 2){
            $time_day = "0-2点";
        }elseif($hour >=2 && $hour < 4){
            $time_day = "2-4点";
        }elseif($hour >=4 && $hour < 6){
            $time_day = "4-6点";
        }elseif($hour >=6 && $hour < 8){
            $time_day = "6-8点";
        }elseif($hour >=8 && $hour < 10){
            $time_day = "8-10点";
        }elseif($hour >=10 && $hour < 12){
            $time_day = "10-12点";
        }elseif($hour >=12 && $hour < 14){
            $time_day = "12-14点";
        }elseif($hour >=14 && $hour < 16){
            $time_day = "14-16点";
        }elseif($hour >=16 && $hour < 18){
            $time_day = "16-18点";
        }elseif($hour >=18 && $hour < 20){
            $time_day = "18-20点";
        }elseif($hour >=20 && $hour < 22){
            $time_day = "20-22点";
        }else{
            $time_day = "22-24点";
        }
        $access['time_day'] = $time_day;
        $access['type'] = $type;
        //$access['log_ip'] = GetIp();
        /*if($access['log_ip']){
            $json = GetIpLookup($access['log_ip']);
            $access['province'] = $json['province'];
            $access['city'] = $json['city'];
        }*/
        $res = model("access")->save($access);
        if($res){
            return true;
        }else{
            return false;
        }
    }

    private function searchList($keywords, $user_id, $type, $partner_id = 0)
    {
        if ($keywords == '') {
            return false;
        }
        $search_list_model = model('search_list');
        $where = [];
        $where['partner_id'] = $partner_id;
        $where['keywords'] = $keywords;
        $where['type'] = $type;
        if ($user_id) {
            $where['user_id'] = $user_id;
        }
        $search_list = $search_list_model->where($where)->find();
        if($search_list){
            $result = $search_list_model->where($where)->setInc('nums', 1);
            unset($search_list);
        }else{
            $data = $where;
            unset($where);
            $data['nums'] = 1;
            $result = $search_list_model->save($data);
        }
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    //记录用户操作日志
    private function record_uer_log($user_id,$telephone,$content,$module,$controller,$action){
        $map = array();
        $map['user_id']    = $user_id;
        $map['telephone']  = $telephone;
        $map['content']    = $content;
        $map['module']     = $module;
        $map['controller'] = $controller;
        $map['action']     = $action;
        $map['add_time']   = date('Y-m-d H:i:s',time());
        $res = model('user_log')->save($map);
        if($res){
            return true;
        }else{
            return false;
        }
    }

    /*给用户发送系统消息*/
    private function add_message_log($user_id,$title,$content,$type){
        $map = array();
        $map['user_id']    = $user_id;
        $map['title']      = $title;
        $map['content']    = $content;
        $map['type']       = $type;
        $map['add_time']   = date('Y-m-d H:i:s',time());
        $res = model('message')->save($map);
        if($res){
            return true;
        }else{
            return false;
        }
    }

}