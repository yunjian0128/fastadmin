<?php
namespace app\common\validate\Product;

use think\Validate;

// 购物车
class Cart extends Validate
{
    protected $rule = [
        'busid' => 'require',
        'proid' => 'require',
        'nums' => ['require', 'number', '>:0'],
        'price' => ['require', 'number', '>=:0'],
        'total' => ['require', 'number', '>=:0'],
    ];

    protected $message = [
        'busid.require' => '用户未知',
        'proid.require' => '商品未知',
        'nums.require' => '商品数量未知',
        'nums.number' => '商品数量必须是数字',
        'nums.>' => '商品数量必须是大于0',
        'price.require' => '商品价格未知',
        'price.number' => '商品价格必须是数字',
        'price.>=' => '商品价格必须等于是大于0',
        'total.require' => '商品总价未知',
        'total.number' => '商品总价必须是数字',
        'total.>=' => '商品总价必须等于是大于0',
    ];

    /**
     * 设置验证器的场景
     */
    protected $scene = [
        'edit' => ['nums', 'price', 'total'],
    ];
}