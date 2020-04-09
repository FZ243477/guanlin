<?php


namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Goods extends Model
{

    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $updateTime = 'update_at';

    public function goods()
    {
        /*
         * 参数一:关联的模型名
         * 参数二:关联的模型的id
         * 参数三:当前模型的关联字段
         * */
        return $this->hasMany('SpecGoodsPrice','goods_id','id');
    }

    //获取器 用于读取字段值的修改
    protected function getClassNameAttr($value, $data)
    {
        if ($data['cate_id']) {
            $classname = model('goods_cate')->where(['id' => $data['cate_id']])->value('classname');
        } else {
            $classname = '';
        }

        return $classname;
    }

    protected function getBrandNameAttr($value, $data)
    {
        if ($data['brand_id']) {
            $classname = model('goods_brand')->where(['id' => $data['brand_id']])->value('classname');
        } else {
            $classname = '';
        }

        return $classname;
    }

    protected function getGoodsBigBannerAttr($value)
    {
        if ($value) {
            $value = explode(',', $value);
        }
        return $value;
    }
    protected function getGoodsDetailPicAttr($value)
    {
        if ($value) {
            $value = explode(',', $value);
        }
        return $value;
    }

    /**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $goods_id 商品id
     */
    public function afterSave($goods_id, $item, $item_img)
    {
        // 商品规格价钱处理
        model("SpecGoodsPrice")->where('goods_id = '.$goods_id)->delete(); // 删除原有的价格规格对象
        if($item)
        {
            //$spec = model('Spec')->getField('id,name'); // 规格表
            //$specItem = model('SpecItem')->getField('id,item');//规格项
            $dataList = [];
            foreach($item as $k => $v)
            {

                // 批量添加数据
                $v['price'] = trim($v['price']);
                $store_count = $v['store_count'] = trim($v['store_count']); // 记录商品总库存
                $v['bar_code'] = trim($v['bar_code']);
                $dataList[] = [
                    'goods_id'=>$goods_id,
                    'key'=>$k,
                    'key_name'=>$v['key_name'],
                    'price'=>$v['price'],
                    'cost_price'=>$v['cost_price'],
                    'store_count'=>$v['store_count'],
                    'bar_code'=>$v['bar_code']
                ];
                // 修改商品后购物车的商品价格也修改一下
                model('cart')->save(['goods_price'=>$v['price']], ["goods_id" => $goods_id, "spec_key" => $k]);
            }
            model("SpecGoodsPrice")->insertAll($dataList);
           // $store_count && model('goods')->where(['id' => $goods_id])->setField('stores', $store_count);
        }

        // 商品规格图片处理
        if($item_img)
        {
            model('SpecImage')->where("goods_id = $goods_id")->delete(); // 把原来是删除再重新插入
            foreach ($item_img as $key => $val)
            {
                model('SpecImage')->insert(array('goods_id'=>$goods_id ,'spec_image_id'=>$key,'src'=>$val));
            }
        }
        refresh_stock($goods_id); // 刷新商品库存
    }


}