<?php


namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class Logistics extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';



}