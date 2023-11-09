<?php

namespace app\common\model\Order;

use think\Model;

class Product extends Model
{
    // 数据表
    protected $name = 'order_product';

    // 关联用户查询
    public function products()
    {
        return $this->belongsTo('app\common\model\Product\Product','proid','id',[],'LEFT')->setEagerlyType(0);
    }
}
