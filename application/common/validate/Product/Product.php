<?php

namespace app\common\validate\Product;

use think\Validate;

class Product extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => ['require', 'unique:product'],
        'content' => ['require'],
        'thumbs' => ['require'],
        'status' => ['require', 'number', 'in:0,1'],
        'flag' => ['require', 'number', 'in:1,2,3'],
        'typeid' => ['require'],
        'unitid' => ['require'],
        'price' => ['require', 'egt:0'],
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '商品名称必填',
        'name.unique' => '该商品名称已存在，请重新输入',
        'content.require' => '商品描述必填',
        'thumbs.require' => '请上传商品图集',
        'status.require' => '商品状态必填',
        'status.in' => '商品状态未知',
        'flag.require' => '商品标签必填',
        'flag.in' => '商品标签未知',
        'typeid.require' => '商品分类未知',
        'unitid.require' => '商品单位未知',
        'price.require' => '商品价格必填',
        'price.egt' => '商品价格不能小于0',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add' => [],
        'edit' => [],
    ];

}
