<?php


namespace app\admin\controller;
use app\common\helper\EncryptionHelper;
use app\admin\helper\MenuHelper;
use app\common\helper\OriginalSqlHelper;
use app\admin\helper\ManagerHelper;
use app\common\constant\SystemConstant;
use app\common\constant\ManagerConstant;

class Manager extends Base
{
    use EncryptionHelper;
    use MenuHelper;
    use OriginalSqlHelper;
    use ManagerHelper;
    protected $manager_model;
    protected $manager_cate_model;

    public function __construct()
    {
        parent::__construct();
        $this->manager_model = model('manager');
        $this->manager_cate_model = model('ManagerCate');
    }

    /**
     * 管理员列表
     */
    public function managerList()
    {


        if (request()->isPost()) {
            $where = 'is_del = 0';
            $keyword = request()->post('keyword');
            if ($keyword) {
                $where .= " AND manager_name like '%".$keyword."%'";
            }

            $manager = $this->manager_model->field('id, manager_cate_id')->where(['id' => $this->manager_id])->find();

            if ($manager['id'] != ManagerConstant::SUPREME_AUTHORITY) {
                $where .= ' AND ((manager_cate_id ='.$manager['manager_cate_id'].' AND id = '.$manager['id'];
                $where .= ') OR manager_cate_id > '.$manager['manager_cate_id'].')';
            }

            $list_row = input('post.list_row', 10); //每页数据
            $page = input('post.page', 1); //当前页

            $totalCount = $this->manager_model->where($where)->count();
            $first_row = ($page-1)*$list_row;
            $field = [
                'id','manager_cate_id','manager_name','work_no',
                'telephone','status',
            ];

            $list = $this->manager_model->field($field)->where($where)->limit($first_row, $list_row)->order('id asc')->select();
            foreach($list as $k => $v) {
                $list[$k]['manager_cate_name'] = model('ManagerCate')->where(['is_del' => 0, 'id' => $v['manager_cate_id']])->value('manager_cate_name');
            }

            $pageCount = ceil($totalCount/$list_row);


            $data = [
                'manager' => $manager,
                'list' => $list ? $list : [],
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);

        }

        return $this->fetch();
    }

    /**
     * 添加管理员列表
     */
    public function managerAdd()
    {
        if (request()->isPost()) {
            $manager_id = request()->post('manager_id');
            $data['id'] = 0;
            $data['head_image'] = '';
            $data['manager_cate_id'] = '';
            $data['manager_name'] = '';
            $data['telephone'] = '';
            $cache = $this->manager_model->field(array_keys($data))->where(['is_del' => 0, 'id' => $manager_id])->find();
            if (!$cache) {
                $cache = $data;
            }

            $manager_cate = $this->manager_cate_model->where(['is_del' => 0])->select();

            $uid = $this->manager_id;

            $manager = $this->manager_model->field('id, manager_cate_id')->where(['id' => $uid])->find();

            foreach ($manager_cate as $k => $v) {

                if ($manager['id'] == ManagerConstant::SUPREME_AUTHORITY) {
                    if ($manager_id) {
                        if ($manager_id == $manager['id']
                            && $cache['manager_cate_id'] == $manager['manager_cate_id']
                            && $v['id'] != $manager['manager_cate_id']
                        ) {
                            unset($manager_cate[$k]);
                        }
                    }
                } else {
                    if ($manager_id) {
                        if ($cache['manager_cate_id'] == $manager['manager_cate_id'] && $v['id'] != $manager['manager_cate_id']) {
                            unset($manager_cate[$k]);
                        } else if ($cache['manager_cate_id'] > $manager['manager_cate_id'] && $v['id'] <= $manager['manager_cate_id']) {
                            unset($manager_cate[$k]);
                        }
                    } else {
                        if ($v['id'] <= $manager['manager_cate_id']) {
                            unset($manager_cate[$k]);
                        }
                    }
                }

            }
            $data = [
                'list' => $cache,
                'manager_cate' => $manager_cate,
            ];
            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
        //$this->assign('manager_cate', $manager_cate);
        return $this->fetch();
    }

    /**
     * 删除管理员
     */
    public function managerHandle()
    {
        if (request()->isPost()) {
            $data = [];
            $data['manager_name'] = request()->post('manager_name');
            $data['telephone'] = request()->post('telephone');
            $password = request()->post('password');
            $data['manager_cate_id'] = request()->post('manager_cate_id');
            $data['head_image'] = request()->post('head_image');
//            $data['work_no'] = request()->post('work_no');

            /*if (!$data['head_image']) {
                return ['status' => 0, 'msg' => '请上传头像', 'data' => []];
            }*/
            !isset($data['head_image'])?$data['head_image']='/static/common/images/user_logo.png':false;
            if (!$data['manager_cate_id']) {
                return ['status' => 0, 'msg' => '请选择角色', 'data' => []];
            }
            /* if (!$data['work_no']) {
                 return ['status' => 0, 'msg' => '请填写工号', 'data' => []];
             }*/
            if (!$data['manager_name']) {
                return ['status' => 0, 'msg' => '请填写用户名', 'data' => []];
            }

            if (!$data['telephone']) {
                return ['status' => 0, 'msg' => '请填写手机号', 'data' => []];
            }

            if(!preg_match("/^1[345789]\d{9}$/", $data['telephone'])){
                return ['status' => 0, 'msg' => '手机号格式不正确', 'data' => []];
            }

            $id = request()->post('id');

            if ($password) {
                $data['password'] = $this->md5_encryption($password);
            }
            if ($id) {
                $manager = $this->manager_model->where(['id' => ['neq', $id], 'telephone' => $data['telephone']])->find();
                if ($manager) {
                    return ['status' => 0, 'msg' => '手机号已存在', 'data' => []];
                }
                $data['update_time'] = time();
                $content = '修改管理员信息';
                $field = array_keys($data);
                $field[] = 'id';
                $before_json = $this->manager_model->field($field)->where(['id' =>  $id])->find();
                $result = $this->manager_model->save($data, ['id' => $id]);
                $data['id'] = $id;
                $after_json = $data;

            } else {
                $manager = $this->manager_model->where(['telephone' => $data['telephone']])->find();
                if ($manager) {
                    return ['status' => 0, 'msg' => '手机号已存在', 'data' => []];
                }
                $data['create_time'] = time();
                $content = '添加管理员信息';
                $before_json = [];
                $result = $this->manager_model->save($data);
                $data['id'] = $this->manager_model->getLastInsID();
                $after_json = $data;
            }

            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($result) {
                return ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
            } else {
                return ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            }
        }
    }


    /**
     * 删除管理员
     */
    public function delManager()
    {
        if (request()->isPOST()) {

            $ids = input('post.id');

            if (!$ids) {
                $this->error(SystemConstant::SYSTEM_NONE_PARAM);
            }

            $arr = array_unique(explode('-',($ids)));

            $data = [];
            foreach ($arr as $k => $v) {
                $data[$k] = $this->manager_model->where(['id' => $v])->find();
            }


            $del = $this->mySql_del($ids, 'manager');

            if($del){

                $before_json = $data;
                $after_json = [];
                $content = '删除管理员';

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);

                return ["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];

            }else{

                return ["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];

            }

        }
    }

    public function managerLog()
    {
        if (request()->isPost()) {
            $where = 'is_del = 0';
            $keyword = request()->post('keyword');
            if ($keyword) {
                $where .= " AND content like '%".$keyword."%'";
            }
            $list_row = input('post.list_row', 10); //每页数据
            $page = input('post.page', 1); //当前页

            $totalCount = model('manager_log')->where($where)->count();
            $first_row = ($page-1)*$list_row;
            $field = [
                'id','manager_id','content','login_ip',
                'add_time','control','act'
            ];

            $list = model('manager_log')->field($field)->where($where)->limit($first_row, $list_row)->order('id desc')->select();
            foreach($list as $k => $v) {
                $list[$k]['manager_name'] = model('Manager')->where(['is_del' => 0, 'id' => $v['manager_id']])->value('manager_name');
            }

            $pageCount = ceil($totalCount/$list_row);

            $data = [
                'list' => $list ? $list : [],
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);

        }
        return $this->fetch();
    }

    /**
     * 管理员分类列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function managerCateList()
    {
        if (request()->isPost()) {

            $where = ['is_del' => 0];
            $list_row = input('post.list_row', 10); //每页数据
            $page = input('post.page', 1); //当前页\
            $name = request()->post('name');
            if($name){
                $where['manager_cate_name|manager_cate_desc'] = ['like',"%$name%"];
            }

            $totalCount = $this->manager_cate_model->where($where)->count();
            $first_row = ($page-1)*$list_row;
            $field = [
                'id','manager_cate_name','manager_cate_desc'
            ];

            $manager = $this->manager_model->field('id, manager_cate_id')->where(['id' => $this->manager_id])->find();

            $list = $this->manager_cate_model->field($field)->where($where)->limit($first_row, $list_row)->order('id asc')->select();
            foreach ($list as $k => $v) {
                $is_edit = 1;
                if ($manager['id'] != ManagerConstant::SUPREME_AUTHORITY
                    && ($v['id'] <= $manager['manager_cate_id'] || $v['id'] == ManagerConstant::SUPREME_AUTHORITY)
                ) {
                    $is_edit = 0;
                }
                $list[$k]['is_edit'] = $is_edit;
            }

            $pageCount = ceil($totalCount/$list_row);

            $data = [
                'list' => $list ? $list : [],
                'manager' => $manager ? $manager : [],
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }
        return $this->fetch();
    }

    /**
     * 修改管理员分类
     */
    public function managerCateAdd()
    {
        if (request()->isPost()) {

            $manager_id = request()->post('manager_id');
            $detail = $this->manager_cate_model->where(['is_del' => 0, 'id' => $manager_id])->find();

            $group = $this->leftMenu();
            $modules = [];
            foreach ($group as $k => $v) {
                $modules[$k] = [];
            }

            $right = model('manager_menu')->where(['is_del' => 0])->order('id')->select();
            foreach ($right as $val){
                $val['enable'] = 0;
                if(!empty($detail) && $detail['act_list'] && $detail['id'] != ManagerConstant::SUPREME_AUTHORITY){
                    $val['enable'] = in_array($val['id'], explode(',', $detail['act_list']));
                }
                $modules[$val['group']]['right_list'][] = $val;
            }
            $is_all_true = 1;
            foreach ($modules as $k => $v) {
                $i = 1;
                if (isset($v['right_list'])) {
                    foreach ($v['right_list'] as $key => $val) {
                        if ($val['enable'] != true) {
                            $i = 0;
                        }

                    }
                }
                if ($i == 0) {
                    $is_all_true = 0;
                    $modules[$k]['all_checked'] = 0;
                } else {
                    $modules[$k]['all_checked'] = 1;
                }
            }

            //权限组

            //$modules = array_merge(array_flip(array_keys($group)), $modules);

            $data = [
                'list' => $detail ? $detail : [],
                'group' => $group ? $group : [],
                'is_all_true' => $is_all_true,
                'modules' => $modules ? $modules : [],
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);
        }

        return $this->fetch();
    }

    /**
     * 添加删除管理员分类
     */
    public function managerCateHandle()
    {
        if (request()->isPost()) {
            $post = request()->post();
            $data = $post['data'];
            if (!$data['manager_cate_name']) {
                return ['status' => 0, 'msg' => '请填写角色名称', 'data' => []];
            }
            $id = request()->post('id');

            if($id == ManagerConstant::SUPREME_AUTHORITY) {
                $data['act_list'] = 'all';
            } else {
                $post['right'] = isset($post['right'])?$post['right']:'';
                $data['act_list'] = is_array($post['right']) ? implode(',', $post['right']) : '';

                if(empty($data['act_list'])) {
                    return ['status' => 0, 'msg' => '请选择权限', 'data' => []];
                }
            }

            if ($id) {
                $admin_role = $this->manager_cate_model->where(['manager_cate_name'=>$data['manager_cate_name'],'id'=>['neq', $id]])->find();
                if($admin_role){
                    return ['status' => 0, 'msg' => '已存在相同的角色名称', 'data' => []];
                }
                $data['update_time'] = time();
                $content = '修改管理员角色信息';
                $field = array_keys($data);
                $field[] = 'id';
                $before_json = $this->manager_cate_model->field($field)->where(['id' =>  $id])->find();
                $result = $this->manager_cate_model->save($data, ['id' => $id]);
                $data['id'] = $id;
                $after_json = $data;
            } else {
                $admin_role = $this->manager_cate_model->where(['manager_cate_name'=>$data['manager_cate_name']])->find();
                if($admin_role){
                    return ['status' => 0, 'msg' => '已存在相同的角色名称', 'data' => []];
                }
                $data['create_time'] = time();
                $before_json = [];
                $content = '添加管理员角色信息';
                $result = $this->manager_cate_model->save($data);
                $data['id'] = $this->manager_cate_model->getLastInsID();
                $after_json = $data;
            }


            $this->managerLog($this->manager_id, $content, $before_json, $after_json);
            if ($result) {
                return ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];
            } else {
                return ['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];
            }
        }
    }

    public function delManagerCate()
    {
        if (request()->isPOST()) {

            $ids = input('post.id');

            $arr = array_unique(explode('-',($ids)));

            $data = [];
            foreach ($arr as $k => $v) {
                $data[$k] = $this->manager_model->where(['id' => $v])->find();
            }


            $del = $this->mySql_del($ids, 'manager_cate');

            if($del){

                $before_json = $data;
                $after_json = [];
                $content = '删除角色';

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);


                return ["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []];

            }else{

                return ["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []];

            }

        }
    }


    /**
     * 权限列表
     * @return mixed
     */
    function rightList(){
        if (request()->isPost()) {

            $group = $this->leftMenu();

            $name = request()->param('name');
            $is_group = request()->param('is_group');

            $where['is_del'] = 0;
            if($name){
                $where['name|right'] = ['like',"%$name%"];
            }
            if($is_group){
                $where['group'] = $is_group;
            }

            $list_row = input('post.list_row', 10); //每页数据
            $page = input('post.page', 1); //当前页

            $totalCount = model('manager_menu')->where($where)->count();
            $first_row = ($page-1)*$list_row;
            $field = [
                'id','name','group','right',
            ];

            $list = model('manager_menu')->field($field)->where($where)->limit($first_row, $list_row)->order('group asc, id desc')->select();
            foreach($list as $k => $v) {
                if ($v['group']) {
                    $list[$k]['group_name'] = $group[$v['group']]['name'];
                }
            }

            $pageCount = ceil($totalCount/$list_row);

            $data = [
                'list' => $list ? $list : [],
                'group' => $group ? $group : [],
                'totalCount' => $totalCount ? $totalCount : 0,
                'pageCount' => $pageCount ? $pageCount : 0,
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);

        }

        return $this->fetch();
    }

    /**
     * 编辑权限列表
     * @return mixed
     */
    public function editRight(){
        if (request()->isPost()) {
            $id = request()->post('id');

            $info['id'] = '';
            $info['name'] = '';
            $info['group'] = '';
            $info['right'] = '';

            if ($id) {
                $info = model('manager_menu')->where(['id' => $id])->find();
                $info['right'] = explode(',', $info['right']);
            }

            $group = $this->leftMenu();
            $planPath = APP_PATH . 'admin/controller';
            $planList = [];
            $dirRes = opendir($planPath);
            while ($dir = readdir($dirRes)) {
                if (!in_array($dir, ['.', '..', '.svn'])) {
                    $planList[] = basename($dir, '.php');
                }
            }

            $data = [
                'list' => $info ? $info : [],
                'group' => $group ? $group : [],
                'planList' => $planList ? $planList : [],
            ];

            $json_arr = ['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => $data];
            ajaxReturn($json_arr);

        }
        return $this->fetch();
    }

    public function rightHandle()
    {
        if(request()->isPost()){
            $data = request()->post();
            $data['right'] = implode(',',$data['right']);
            if(!empty($data['id'])){
                $id = $data['id'];
                $data['update_time'] = time();
                $content = '修改权限';
                $field = array_keys($data);
                $field[] = 'id';
                $before_json =  model('manager_menu')->field($field)->where(['id' =>  $id])->find();
                $result = model('manager_menu')->save($data, ['id' => $data['id']]);
                $data['id'] = $id;
                $after_json = $data;
            }else{
                if(model('manager_menu')->where(['name'=>$data['name']])->count()>0){
                    ajaxReturn(['status' => 0, 'msg' => '该权限名称已添加', 'data' => []]);
                }
                unset($data['id']);
                $data['create_time'] = time();
                $before_json = [];
                $content = '添加权限';
                $result = model('manager_menu')->save($data);
                $data['id'] =  model('manager_menu')->getLastInsID();
                $after_json = $data;
            }


            $this->managerLog($this->manager_id, $content, $before_json, $after_json);

            if ($result) {
                ajaxReturn(['status' => 1, 'msg' => SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);
            } else {
                ajaxReturn(['status' => 0, 'msg' => SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);
            }
        }
    }

    /**
     * 删除权限列表
     * @return mixed
     */
    public function rightDel(){
        if (request()->isPOST()) {

            $ids = input('post.id');

            $arr = array_unique(explode('-',($ids)));

            $data = [];
            foreach ($arr as $k => $v) {
                $data[$k] = $this->manager_model->where(['id' => $v])->find();
            }


            $del = $this->mySql_del($ids, 'manager_menu');

            if($del){

                $before_json = $data;
                $after_json = [];
                $content = '删除权限';

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);


                ajaxReturn(["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => []]);

            }else{

                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_OPERATION_FAILURE, 'data' => []]);

            }

        }
    }

    public function ajax_get_action()
    {
        $control = request()->post('controller');
        $advContrl = get_class_methods("app\\admin\\controller\\".str_replace('.php','',$control));
        $baseContrl = get_class_methods('app\\admin\\controller\\Base');
        $diffArray  = array_diff($advContrl,$baseContrl);
        $act_list = [];
        foreach ($diffArray as $v) {
            $act_list[]['name'] = $v;
        }
        ajaxReturn(["status" => 1, "msg"=> SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['actList' => $act_list]]);
    }

    public function get_manager_info()
    {
        if(request()->isPost()) {

            $map['id'] = $this->manager_id;
            $map['is_del'] = 0;
            $manager = model('manager')->where($map)->find();

            if (!$manager) {
                ajaxReturn(["status"=>0, "msg"=>SystemConstant::SYSTEM_NONE_PARAM, 'data' => []]);
            }
            ajaxReturn(["status"=>1, "msg"=>SystemConstant::SYSTEM_OPERATION_SUCCESS, 'data' => ['list' => $manager]]);
        }
    }

    //修改密码
    public function updatepwd(){
        return $this->fetch();
    }

    public function updatepwdHandle()
    {
        if(request()->isPost()) {
            $oldpwd = request()->post('oldpwd');
            $newpwd = request()->post('newpwd');
            $ppp['id'] = $this->manager_id;

            $ppp['password'] = $this->md5_encryption($oldpwd);
            $res = model('manager')->where($ppp)->find();
            if (!$res) {
                ajaxReturn(['status' => 0, 'msg' => '旧密码不正确']);
            }
            if ($oldpwd == $newpwd) {
                ajaxReturn(['status' => 0, 'msg' => '新密码不能与旧密码一样']);
            }
            $newpwd = $this->md5_encryption($newpwd);
            $reg = model('manager')->save(['password' => $newpwd], ['id' => $this->manager_id]);
            if ($reg) {
                session('manager_id', null);
                $before_json = [];
                $after_json = [];
                $content = '修改密码';

                $this->managerLog($this->manager_id, $content, $before_json, $after_json);
                ajaxReturn(['status' => 1, 'msg' => '密码修改成功,请重新登录']);
            } else {
                ajaxReturn(['status' => 0, 'msg' => '密码修改失败']);
            }
        }
    }


}