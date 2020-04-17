<?php


namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Houses extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = true;

    public function getIsHotAttr($value){
        if($value ==1){
            return '是';
        }else{
            return '否';
        }
    }
}