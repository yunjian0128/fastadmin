<?php

namespace app\common\validate\Product;

use think\Validate;

class Category extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => ['require', 'unique:product_type'],
        // 权重必须大于0
        'weigh' => ['require', 'number', 'egt:0']
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '商品分类名称必填',
        'name.unique' => '商品分类名称已存在',
        'weigh.require' => '排序权重必填',
        'weigh.number' => '排序权重必须为数字',
        'weigh.egt' => '排序权重必须大于等于0',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
    ];


}