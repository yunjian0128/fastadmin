<?php

namespace app\common\validate\Depot;

use think\Validate;

class BackProduct extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'backid' => ['require'],
        'proid' => ['require'],
        'nums' => ['require', 'gt:0'],
        'price' => ['require', 'egt:0'],
        'total' => ['require', 'egt:0'],
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'backid.require' => '退货单ID未知',
        'proid.require' => '商品ID未知',
        'nums.require' => '请填写商品的数量',
        'price.require' => '请填写商品的单价',
        'total.require' => '请填写商品的总价',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add' => [],
        'edit' => ['proid', 'nums', 'price', 'total', 'status'],
    ];
}
