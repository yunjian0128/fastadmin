<?php

namespace app\common\validate\Hotel;

// 引入Thinkphp底层验证器进来
use think\Validate;

/**
 * 定义客户验证器
 */
class Room extends Validate {
    /**
     * 设置我们要验证字段的规则
     */
    protected $rule = [
        'name' => ['require', 'unique:hotel_room'],
        'thumb' => ['require'],
        'price' => ['require', 'number', '>:0'],
        'content' => ['require'],
        'total' => ['require', 'number', '>:0'],
        'flag' => ['require', 'number', '>=:0'],
    ];

    /**
     * 设置错误的提醒信息
     */
    protected $message = [
        'name.require' => '房间名称必填',
        'name.unique' => '房间名称已存在，请重新输入',
        'thumb.require' => '房间图片必填',
        'price.require' => '房间价格必填',
        'price.number' => '房间价格必须是数字类型',
        'price.>' => '房间价格必须大于0元',
        'content.require' => '房间描述必填',
        'total.require' => '房间数量必填',
        'total.number' => '房间数量必须是数字类型',
        'total.>' => '房间数量必须大于0',
    ];

    /**
     * 设置验证场景
     */
    protected $scene = [
        'add' => ['name', 'thumb', 'price', 'content', 'total', 'flag'],
        'edit' => ['name', 'thumb', 'price', 'content', 'total', 'flag'],
    ];
}

?>