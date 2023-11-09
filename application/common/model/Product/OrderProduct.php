<?php

namespace app\common\model\Product;

use think\Model;

class OrderProduct extends Model
{
    // 数据表
    protected $name = 'order_product';

    // 关联用户查询
    public function products()
    {
        return $this->belongsTo('app\common\model\Product\Product', 'proid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
