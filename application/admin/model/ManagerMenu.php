<?php


namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class ManagerMenu extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'tb_manager_menu';

    use SoftDelete;
    protected $deleteTime = 'delete_time';
}