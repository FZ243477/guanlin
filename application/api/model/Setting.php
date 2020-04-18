<?php


namespace app\api\model;

use think\Model;

class Setting extends Model
{
    protected static function init()
    {
        Setting::afterUpdate(function ($setting) {
            $where = $setting->updateWhere;
            $name = 'setting_info_'.$where['name'];
            clearCache($name);
        });

    }
}