<?php


namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Manager extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'tb_manager';

    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //获取器 用于读取字段值的修改
    protected function getManagerCateNameAttr($value, $data)
    {
        if ($data['manager_cate_id']) {
            $manager_cate_name = model('ManagerCate')->where(['is_del' => 0, 'id' => $data['manager_cate_id']])->value('manager_cate_name');
        } else {
            $manager_cate_name = '';
        }

        return $manager_cate_name;
    }
}