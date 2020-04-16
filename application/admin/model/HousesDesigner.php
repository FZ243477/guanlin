<?php


namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class HousesDesigner extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $updateTime = 'update_at';
}