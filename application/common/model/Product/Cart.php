<?php

namespace app\common\model\Product;

use think\Model;

class Cart extends Model
{
    // 表名
    protected $name = 'cart';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 忽略数据表不存在的字段
    protected $field = true;

    // 追加属性
    protected $append = [
    ];

    public function product()
    {
        return $this->belongsTo('app\common\model\Product\Product','proid','id',[],'LEFT')->setEagerlyType(0);
    }

}
