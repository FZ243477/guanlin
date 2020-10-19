<?php
namespace app\common;
use app\model\Order;
use think\facade\Cache;
class OrderQueue
{
    private $order_queue;//订单状态
    private $key;//键名
    private $expire_time=60;//默认时效一分钟
    public $order_id; //订单ID
    public function __construct()
    {
        $this->key="ORDER_QUEUE_KEY";
        $this->order_queue = $this->queryNotReceiveOrder();
    }
    public function queryNotReceiveOrder(){
        $lists=Order::where('order_state=1')->filed('order_id')->page(1,10)->select()->toArray();
        $data=[];
        foreach ($lists as $k =>$v){
            $data[] = $v['order_id'];
        }
        return $data;
    }

    public function pushTolist($order_ids){
        if(!is_array($order_ids)){
            $this->order_id=$order_ids;
            $this->pushToQueue();
            return false;
        }
        foreach ($order_ids as $k =>$v){
            $this->order_id=$v;
            $this->pushToQueue();
        }
        return true;
    }

    public function pushToQueue(){
        if(!$this->order_id){
            return "没有订单不执行方法";
        }
        if($this->order_queue){
            array_unshift($this->order_queue,$this->order_id);
        }else{
            $this->order_queue[]=$this->order_id;
        }

        $this->order_queue=$this->array_unique();
        $redis=Cache::store('redis')->handler();
        foreach ($this->order_queue as $k =>$v){
            $redis->lpush($this->key,$v);
        }
        return true;
    }

    public function array_unique(){
        return array_unique($this->order_queue);
    }

    public function pullFromQueue(){
        $redis=Cache::store('redis')->handler();
        $llen=$redis->Llen($this->key);
        if(!empty($llen)){
            for($i=0;$i<$llen;$i++){
                echo "你的商品已经开始发货啦！！";
                $key=Order::where([
                    'state'=>1,
                    'order_id'=>$redis->rPop($this->key)
                ])->data(['state'=>2])->update();
                echo $key."<br/>";
                echo"继续推送<br>";
            }
        }else{
            echo "没有可以推送的订单";
        }
    }

    public function clearOneOrder(){
        if($this->order_queue){
            $key=array_search($this->order_id,$this->order_queue);
            if(!$key && $key!=0){
                return true;
            }
            unset($this->order_queue[$key]);
            cache($this->key,$this->order_queue,$this->$expire_time);
        }
        return true;
    }
}