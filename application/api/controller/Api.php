<?php


namespace app\api\controller;


use app\common\helper\TokenHelper;
use think\Controller;

class Api extends Controller
{

    use TokenHelper;

    public function diyLight()
    {
        $token = request()->param('token');
        $planid = request()->param('planid');
        if (!$planid) {
            $this->error('轻设计不存在');
        }
        $easy_design_link = model('design_list')->where(['easy_design_id' => $planid])->value('easy_design_link');
        if ($token) {
            $result = $this->getUserInfoByToken($token);
            if ($result) {
                $user_id = $result;
            } else {
                $user_id = 0;
            }
            $accessToken = model('user')->where('id', $user_id)->value('kujiale_token');
            if (!$accessToken) {
                $this->error('请重新登录');
            }
//            $url = "https://pano6.p.kujiale.com/pub/render/lite-design/main?obsEasyDesignId=" . $planid;
            $iframe = "https://pano6.p.kujiale.com/v/auth?dest=3&accesstoken={$accessToken}&redirecturl={$easy_design_link}";
        } else {
            $iframe = $easy_design_link;
//            $iframe = "https://pano6.p.kujiale.com/pub/render/lite-design/main?obsEasyDesignId=" . $planid;
        }
        $this->assign('iframe', $iframe);
        $this->assign('token', $token);
        $this->assign('planid', $planid);
        return $this->fetch();
    }
}