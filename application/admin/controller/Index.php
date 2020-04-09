<?php

namespace app\admin\controller;
use app\admin\helper\MenuHelper;
use app\common\constant\SystemConstant;

class Index extends Base
{

    use MenuHelper;
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (request()->isPost()) {
            $title = getSetting('system.title');
            $prefix = config('prefix');
            $manager = model('manager')
                ->alias('m')
                ->join($prefix . 'manager_cate m_c', 'm.manager_cate_id = m_c.id', 'INNER')
                ->field('m.*,m_c.act_list')
                ->where(['m.id' => $this->manager_id])
                ->find();
            if ($manager['act_list'] == 'all') {
                $act_list = $manager['act_list'];
            } else {
                $act_list = explode(',', $manager['act_list']);
            }

            $leftMenu = $this->getMenuList($act_list);
            $data = [
                'title' => $title,
                'manager' => $manager,
                'left_menu' => $leftMenu,
            ];
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
        return $this->fetch();
    }

    public function main()
    {

        if (request()->isPost()) {
            $title = getSetting('system.title');
            $manager = model('manager')->field('manager_name')->where(['id' => $this->manager_id])->find();
            $data = [
                'title' => $title,
                'manager' => $manager,
            ];
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
        return $this->fetch();
    }

    /**
     * 图片上传 20180702 cx
     * @return array
     */
    public function addImage()
    {
        $type = input('post.type');

        switch ($type) {

            case 'mp3': //上传文件
                $ext = 'mp3';
                // 移动到框架应用根目录/public/uploads/picture 目录下
                $save_path = ROOT_PATH . 'public' . DS . 'uploads/mp3';
                $path = '/uploads/mp3/';
                break;
            case 'video': //上传视频
                $ext = 'mp4,swf,flv,webm,ogv';
                // 移动到框架应用根目录/public/uploads/picture 目录下
                $save_path = ROOT_PATH . 'public' . DS . 'uploads/video';
                $path =  '/uploads/video/';
                break;
            case 'multiple': //多图
                $ext = 'jpg,png,gif,jpeg,ico';
                // 移动到框架应用根目录/public/uploads/picture 目录下
                $save_path = ROOT_PATH . 'public' . DS . 'uploads/picture';
                $path =  '/uploads/picture/';
                break;
            case 'cer_key':
                $ext = 'key,crt';
                // 移动到框架应用根目录/public/uploads/picture 目录下
                $save_path = ROOT_PATH . 'public' . DS . 'uploads/unionPay';
                $path = '/uploads/unionPay/';
                break;
            default: //单图
                $ext = 'jpg,png,gif,jpeg,ico';
                // 移动到框架应用根目录/public/uploads/picture 目录下
                $save_path = ROOT_PATH . 'public' . DS . 'uploads/picture';
                $path = '/uploads/picture/';
        }
        $file = $upload = request()->file('upload_pic');

        if (!$file) {
            //$return_json = ['status' => 0, 'msg' => '请选择上传文件', 'data' => []];
            //ajaxReturn($return_json);
        }

        if ($type == 'multiple') {
            // 获取表单上传文件 例如上传了001.jpg
            $file_name = [];
            foreach ($file as  $upload) {
                $upload->validate(['size' => 1920 * 1024 * 10000000, 'ext' => $ext]); //设置附件上传类型
                $info = $upload->move($save_path);
                if ($info) {
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    //echo $info->getExtension();
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    //echo $info->getSaveName();;
                    // 输出 42a79759f284b767dfcb2a0197904287.jpg
                    //echo $info->getFilename();
                    $save_name  = explode('\\', $info->getSaveName());
                    $name_file = $path . $save_name[0] . '/' . $save_name[1];
                    if (getSetting('alioss.is_oss') == 1) {
                        $name_file = oss_upload($name_file);
                    }
                    $file_name[] = $name_file;
                    //return ['status' => 1, 'info' => '/uploads/picture/' . $save_name[0] . '/' . $save_name[1]];
                } else {
                    // 上传失败获取错误信息
                    //echo $upload->getError();
                    $return_json = ['status' => 0, 'msg' => $upload->getError(), 'data' => []];
                }
            }
            $return_json = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['file_name' => $file_name]];
        } else {
            $upload = $file;
            $upload->validate(['size' => 1920 * 1024 * 10000000, 'ext' => $ext]); //设置附件上传类型
            $info = $upload->move($save_path);

            if ($info) {
                // 成功上传后 获取上传信息
                // 输出 jpg
                //echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                //echo $info->getSaveName();;
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                //echo $info->getFilename();
                $save_name = explode('\\', $info->getSaveName());
                $file_name = $path . $save_name[0] . '/' . $save_name[1];
                if (getSetting('alioss.is_oss') == 1) {
                    $file_name = oss_upload($file_name);
                }
                $return_json = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['file_name' => $file_name]];
            } else {
                // 上传失败获取错误信息
                //echo $upload->getError();
                $return_json = ['status' => 0, 'msg' => $upload->getError(), 'data' => []];
            }
        }

        ajaxReturn($return_json);
    }

    /**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换的
     * table,id_name,id_value,field,value
     */
    public function changeTableVal(){
        $table = input('table'); // 表名
        $id_name = input('id_name'); // 表主键id名
        $id_value = input('id_value'); // 表主键id值
        $field  = input('field'); // 修改哪个字段
        $value  = input('value'); // 修改字段值                        
        model($table)->save([$field=>$value], [$id_name => $id_value]); // 根据条件保存修改的数据
    }


    /*
    * 获取地区
    */
    public function getRegion(){
        $parent_id = input('get.parent_id/d');
        $selected = input('get.selected',0);
        $data = model('region')->where("parent_id",$parent_id)->select();
        $html = '';
        if($data){
            foreach($data as $h){
                if($h['id'] == $selected){
                    $html .= "<option value='{$h['id']}' selected>{$h['name']}</option>";
                }
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        echo $html;
    }


    /**
     * 清空系统缓存
     */
    public function cleanCache(){

        if(request()->isPost())
        {
            $clear = request()->post('clear');
            if ($clear == 1) {
                delFile('../runtime/cache/');// 模板缓存
            }
            //in_array('data',$clear)  && delFile('./Application/Runtime/Data');// 项目数据
            //in_array('logs',$clear)  && delFile('./Application/Runtime/Logs');// logs日志
            //in_array('temp',$clear)  && delFile('./Application/Runtime/Temp');// 临时数据
            //in_array('cacheAll',$clear)  && delFile('./Application/Runtime');// 清除所有
            //in_array('goods_thumb',$clear)  && delFile('./public/upload/goods/thumb'); // 删除缩略图

            // 删除静态文件
           /* $html_arr = glob("./Application/Runtime/Html/*.html");
            foreach ($html_arr as $key => $val)
            {
                in_array('index',$clear) && strstr($val,'Home_Index_index.html') && unlink($val); // 首页
                in_array('goodsList',$clear) && strstr($val,'Home_Goods_goodsList') && unlink($val); // 列表页
                in_array('channel',$clear) && strstr($val,'Home_Channel_index') && unlink($val);  // 频道页
                in_array('articleList',$clear) && strstr($val,'Index_Article_articleList') && unlink($val);  // 文章列表页
                in_array('detail',$clear) && strstr($val,'Index_Article_detail') && unlink($val);  // 文章详情
                in_array('articleList',$clear) && strstr($val,'Doc_Index_index_') && unlink($val);  // 文章列表页
                in_array('detail',$clear) && strstr($val,'Doc_Index_article_') && unlink($val);  // 文章详情
                // 详情页
                if(in_array('goodsInfo',$clear))
                {
                    if(strstr($val,'Home_Goods_goodsInfo') || strstr($val,'Home_Goods_ajaxComment') || strstr($val,'Home_Goods_ajax_consult'))
                        unlink($val);
                }
            }*/
            ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS]);
        }
    }
}