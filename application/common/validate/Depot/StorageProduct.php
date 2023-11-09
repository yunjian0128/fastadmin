<?php

namespace app\common\validate\Depot;

use think\Validate;

class StorageProduct extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'storageid' => ['require', 'number', 'gt:0'],
        'proid' => ['require', 'number', 'gt:0'],
        'nums' => ['require', 'number', 'gt:0'],
        'price' => ['require', 'float', 'egt:0'], // 大于等于0
        'total' => ['require', 'float', 'egt:0'], // 大于等于0
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'storageid.require' => '入库单id不能为空',
        'storageid.number' => '入库单id格式错误',
        'storageid.gt' => '入库单id格式错误',
        'proid.require' => '商品id不能为空',
        'proid.number' => '商品id格式错误',
        'proid.gt' => '商品id格式错误',
        'nums.require' => '商品数量不能为空',
        'nums.number' => '商品数量格式错误',
        'nums.gt' => '商品数量必须大于0',
        'price.require' => '商品单价不能为空',
        'price.float' => '商品单价格式错误',
        'price.egt' => '商品单价必须大于等于0',
        'total.require' => '商品总价不能为空',
        'total.float' => '商品总价格式错误',
        'total.egt' => '商品总价必须大于等于0',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add' => [],
        'edit' => ['proid', 'nums', 'price', 'total', 'status'],
    ];


}