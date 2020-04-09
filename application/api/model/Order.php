<?php


namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class Order extends Model
{
    protected $deleteTime = 'delete_time';

    protected $updateTime = 'update_at';

}