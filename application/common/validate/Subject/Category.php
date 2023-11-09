<?php

namespace app\common\validate\Subject;

use think\Validate;

class Category extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => ['require', 'unique:subject_category'],
        // 权重必须大于0
        'weight' => ['require', 'number', 'unique:subject_category', 'gt:0']
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '课程分类名称必填',
        'name.unique' => '课程分类名称已存在',
        'weight.require' => '排序权重必填',
        'weight.number' => '排序权重必须为数字',
        'weight.unique' => '排序权重已存在',
        'weight.gt' => '排序权重必须大于0'
    ];
    /**
     * 验证场景
     */
    protected $scene = [
    ];


}