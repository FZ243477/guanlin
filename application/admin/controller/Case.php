<?php


namespace app\admin\controller;
use app\admin\helper\ManagerHelper;
use app\common\helper\EncryptionHelper;
use app\common\helper\KujialeHelper;
use app\common\helper\PHPExcelHelper;
use app\common\constant\SystemConstant;
use app\common\constant\UserConstant;
use Think\Db;

class Case extends Base
{
    use ManagerHelper;
    use EncryptionHelper;
    use PHPExcelHelper;
    use KujialeHelper;

    public function __construct()
    {
        parent::__construct();
    }

    public function search()
    {
            $keyword = request()->post('keyword');
            if ($keyword) {
                $where .= " AND manager_name like '%".$keyword."%'";
            }
            $case = $this->manager_model->field('id, manager_cate_id')->where(['id' => $this->manager_id])->find();
    }

      public function goodsList()
    {
            //商品分类
            $goods_cate = model('goods_cate')->field('id,classname,pid,banner_pic,describe,')->where(['pid' => 0, 'status' => '1'])->order('sort desc')->select();
    }


  
}