<?php

namespace app\common\validate\Product;

use think\Validate;

class Unit extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => ['require', 'unique:product_unit']
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '商品单位名称必填',
        'name.unique' => '商品单位名称已存在'
    ];
    /**
     * 验证场景
     */
    protected $scene = [
    ];


}