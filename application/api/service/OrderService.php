<?php
namespace app\index\service;
use OrderQueue;
class OrderService{
    public function addOrder(){
        $order_no = time();
        $order = new Order;
        $order->save([
            'order_id' =>$order_no,
            'create_time'=>time(),
            'price'=>100,
            'state'=>1
        ]);

        OrderQueue::pushTolist($order->order_id);
        echo 'success';
    }
}