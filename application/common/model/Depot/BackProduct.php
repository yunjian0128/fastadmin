<?php

namespace app\common\model\Depot;

use think\Model;

class BackProduct extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'depot_back_product';

    // 联表查询产品信息
    public function products()
    {
        return $this->belongsTo('app\common\model\Product\Product', 'proid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
