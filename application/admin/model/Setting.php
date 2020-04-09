<?php


namespace app\admin\model;

use think\Model;

class Setting extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'tb_setting';

    protected static function init()
    {
        Setting::afterUpdate(function ($setting) {
            $where = $setting->updateWhere;
            $name = 'setting_info_'.$where['name'];
            clearCache($name);
        });

    }
}