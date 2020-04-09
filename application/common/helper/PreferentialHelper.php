<?php


namespace app\common\helper;


use app\common\constant\PreferentialConstant;

trait PreferentialHelper
{

    private function afterSave($goods_id, $spec_id, $prom_type, $prom_id, $before_json, $type = 1) {
        $goods_model = model('goods');
        if ($type == 1) {
            $spec = model('spec_goods_price')->where(['goods_id' => $before_json['goods_id']])->select();
            if ($spec) {
				if ($goods_id != $before_json['goods_id'] && $spec_id != $before_json['sku_id']) {
					model('spec_goods_price')->update(
						['prom_type' => 0, 'prom_id' => 0],
						['goods_id' => $before_json['goods_id'], 'key' => $before_json['sku_id']]
					);
					$length = 1;
					foreach ($spec as $k => $v) {
						if (!$v['prom_id']) {
							$length++;
						}
					}
					if (count($spec) == $length) {
						$goods_model->update(['prom_type' => 0, 'prom_id' => 0], ['id' => $before_json['goods_id']]);
					}
				}
            } else {
				if ($goods_id != $before_json['goods_id']) {
                    $goods_model->update(['prom_type' => 0, 'prom_id' => 0], ['id' => $before_json['goods_id']]);
				}
            }

        }
        $goods_model->update(['prom_type' => $prom_type, 'prom_id' => $prom_id], ['id' => $goods_id]);
        if ($spec_id) {
            model('spec_goods_price')->update(
                ['prom_type' => $prom_type, 'prom_id' => $prom_id],
                ['goods_id' => $goods_id, 'key' => $spec_id]
            );
        }
    }

    private function get_goods_promotion($prom_type, $prom_id, $goods_id, $sku_id = 0, $partner_id = 0)
    {

        $sku_id = $sku_id?$sku_id:0;

        if ($prom_type == PreferentialConstant::PREFERENTIAL_TYPE_LIMIT_SALES) {
            if ($sku_id) {
                $where = ['goods_id' => $goods_id, 'sku_id' => $sku_id];
            } else {
                $where = ['limit_sales_id' => $prom_id];
            }
            $limit_sales = model('limit_sales')->where($where)->order('limit_sales_id desc')->find();
            $limit_sales?$limit_sales['prom_id'] = $limit_sales['limit_sales_id']:false;
        } else if ($prom_type == PreferentialConstant::PREFERENTIAL_TYPE_POPULAR) {
          /*  if ($sku_id) {
                $where = ['goods_id' => $goods_id, 'sku_id' => $sku_id];
            } else {
                $where = ['popular_id' => $prom_id];
            }
            $limit_sales = model('popular')->where($where)->find();*/
            $limit_sales = [];
            $limit_sales?$limit_sales['prom_id'] = $limit_sales['popular_id']:false;
        } else if ($prom_type == PreferentialConstant::PREFERENTIAL_TYPE_NEW_GOODS) {
            /*if ($sku_id) {
                $where = ['goods_id' => $goods_id, 'sku_id' => $sku_id];
            } else {
                $where = ['new_goods_id' => $prom_id];
            }
            $limit_sales = model('new_goods')->where($where)->find();*/
            $limit_sales = [];
            $limit_sales?$limit_sales['prom_id'] = $limit_sales['new_goods_id']:false;
        }
        
        if (empty($limit_sales)) {
            $promotionInfo['prom_type'] = 0;//已结束
            $promotionInfo['prom_id'] = 0;//已结束
            $promotionInfo['price'] = '';
            $promotionInfo['stores'] = '';
            $promotionInfo['start_time'] = 0;
            $promotionInfo['end_time'] = 0;
        } else {
            $promotionInfo['prom_type'] = $prom_type;
            $promotionInfo['prom_id'] = $limit_sales['prom_id'];
            if ($prom_type == PreferentialConstant::PREFERENTIAL_TYPE_LIMIT_SALES) {
                $promotionInfo['start_time'] = $limit_sales['start_time'];
                $promotionInfo['end_time'] = $limit_sales['end_time'];
				//dump(time()-$limit_sales['start_time']);
				//dump($limit_sales['end_time']);
                if (time() > $limit_sales['start_time'] && time() < $limit_sales['end_time']) {
                    $promotionInfo['price'] = $limit_sales['spec_price'];
                    $stores = $limit_sales['max_buy_num'] - $limit_sales['sales_num'];
                    $promotionInfo['stores'] = $stores;//已结束
                } else {
                    //model('goods')->save(['prom_type' => 0, 'prom_id' => 0], ['prom_type' => $prom_type, 'prom_id' => $prom_id]);
                    $promotionInfo['price'] = '';//原价
                    $promotionInfo['prom_type'] = 0;//已结束
                    $promotionInfo['prom_id'] = 0;//已结束
                    $promotionInfo['stores'] = 0;//已结束
                }
            } else {
                $promotionInfo['price'] = $limit_sales['spec_price'];
                $promotionInfo['start_time'] = 0;
                $promotionInfo['end_time'] = 0;
                $promotionInfo['stores'] = '';
            }

        }

        return $promotionInfo;
    }

}