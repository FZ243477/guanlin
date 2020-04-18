<?php


namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class Customer extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';



}