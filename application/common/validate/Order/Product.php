<?php

namespace app\common\validate\Order;

use think\Validate;

class Product extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'orderid' => ['require'],
        'proid' => ['require'],
        'pronum' => ['require'],
        'price' => ['require'],
        'total' => ['require'],
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'orderid.require' => '订单信息未知',
        'proid.require' => '商品的信息未知',
        'pronum.require' => '商品数量未知',
        'price.require' => '商品单价未知',
        'total.require' => '商品总价未知',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
    ];


}
