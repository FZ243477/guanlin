<?php

namespace app\api\controller;

use app\common\constant\UserConstant;
use app\common\constant\BannerConstant;
use app\common\constant\SystemConstant;
use app\common\helper\QrcHelper;
use app\common\helper\UserHelper;

/**
 * @title 首页相关接口
 * @description 首页相关接口
 * # @group 首页相关接口
 *# @header name:key require:1 default: desc:秘钥(区别设置)
 * #@param name:token type:string require:1 default: other: desc:公共参数(区别设置)
 */
class Index extends Base
{
    use UserHelper;
    use QrcHelper;

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        return $this->fetch();
    }


    public function account()
    {
        $system = getSetting('system');
        $list = [
            're_account' => $system['re_account'],
            're_username' => $system['re_username'],
            're_bank' => $system['re_bank'],
        ];
        ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $list]);
    }

    /**
     * @title 首页内容接口
     * @description 首页内容接口
     * @return banner: @
     * @banner banner_pic:图片 banner_name:名称 banner_describe:描述
     * @author LnC
     * @url /api/Index/homePage
     * @method Post
     */
    public function homePage()
    {
        if (request()->isPost()) {
            //$this->add_access($this->user_id, UserConstant::USER_ACCESS_HOME_PAGE);
            $field = 'banner_id,banner_pic,banner_name,banner_describe,link_type';
            $banner = model('banner')
                ->field($field)
                ->where(['banner_cate_id' => BannerConstant::BANNER_TYPE_HOME])
                ->select();
            foreach ($banner as $k => $v) {
                if ($v['link_type'] == 1) {
                    $banner[$k]['link_url'] = getSetting('system.host').'/api/Api/content/id/'.$v['banner_id'];
                } else {
                    $banner[$k]['link_url'] = '';
                }
            }
            $teacher = model('teacher')
                ->field('teacher_id,teacher_name,teacher_logo,describe')
                ->where(['is_display' => 1])
                ->order('sort desc')
                ->select();
            foreach ($teacher as $k => $v) {
                $teacher[$k]['tag'] = model('teacher_des')
                    ->alias('td')
                    ->field('name')
                    ->join('teacher_tag tt', 'td.tag_id = tt.tag_id', 'left')
                    ->where(['td.teacher_id' => $v['teacher_id'], 'tt.type' => 0])
                    ->select();
            }
            $job = model('job')->where(['is_display' => 1])->field('job_id, pic')->order('job_id desc')->find();

            $data = [
                'banner' => $banner,
                'teacher' => $teacher,
                'job' => $job,
            ];
            $json_arr = ["status" => 1, "msg" => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
    }

    /**
     * @title 上传图片
     * @description base64方式上传
     * @param name:upload_pic type:string require:1 default: other: desc:图片名称
     * @return file_name:地址
     * @author LnC
     * @url /api/Index/uploadImg
     * @method Post
     */
    public function uploadImg()
    {

        $upload = request()->file('upload_pic');

        if (!$upload) {
            $return_json = ['status' => 0, 'msg' => '请选择上传文件', 'data' => []];
            ajaxReturn($return_json);
        }
        $save_path = ROOT_PATH . 'public' . DS . 'uploads/picture';
        $path = '/uploads/picture/';
        $info = $upload->move($save_path);
        if ($info) {
            $save_name = explode('\\', $info->getSaveName());
            $file_name = $path . $save_name[0] . '/' . $save_name[1];
            if (getSetting('alioss.is_oss') == 1) {
                $file_name = oss_upload($file_name);
            }
            $return_json = [
                'status' => 1,
                'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS,
                'data' => ['file_name' => $file_name],
            ];
        } else {
            // 上传失败获取错误信息
            //echo $upload->getError();
            $return_json = ['status' => 0, 'msg' => $upload->getError(), 'data' => []];
        }
        ajaxReturn($return_json);
    }

    /**
     * 获取小程序二维码
     */
    public function shareQr()
    {
        $path = request()->post('path');
//        $path = '/pages/DIY/DIY?id='.$planid;
        if (!$path) {
            ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
        }
        $save_prefix = request()->post('save_prefix', 'share');
        $width = request()->post('width', 430);
        $save_dir = "/uploads/share/".$save_prefix.'/';
        if (!file_exists('.'.$save_dir)) {
            mkdir('.'.$save_dir, 777, true);
        }
//        $filename = substr($path,strripos($path,"?")+1)?substr($path,strripos($path,"?")+1):base64_encode($path);
        $filename = md5($path);
        $savePath = $save_dir.$filename.".jpg";
        $data = ['img' => getSetting('system.host').$savePath];
        if (file_exists('.'.$savePath)) {
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data]);
        }
        $res = $this->qrcode('.'.$savePath, $path, $width);
        if ($res['status'] == 1) {
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data]);
        } else {
            ajaxReturn($res);
        }
    }
}