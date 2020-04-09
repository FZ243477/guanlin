<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-08-08
 * Time: 15:26
 */
namespace app\api\model;

use think\Model;
use traits\model\SoftDelete;

class StoreOrder extends Model
{
    protected $deleteTime = 'delete_time';


}