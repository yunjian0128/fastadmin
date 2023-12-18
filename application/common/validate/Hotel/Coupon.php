<?php

namespace app\common\validate\Hotel;

// 引入Thinkphp底层验证器进来
use think\Validate;

/**
 * 定义客户验证器
 */
class Coupon extends Validate {
    /**
     * 设置我们要验证字段的规则
     */
    protected $rule = [
        'title' => ['require', 'unique:hotel_coupon'],
        'rate' => ['require', 'number', '>:0', '<:1'],
        'total' => ['require', 'number', '>:0', 'integer'],
        'createtime' => ['require', 'number'],
        'endtime' => ['require', 'number', 'egt:createtime'],
        'thumb' => ['require'],
    ];

    /**
     * 设置错误的提醒信息
     */
    protected $message = [
        'title.require' => '优惠券名称必填',
        'title.unique' => '优惠券名称已存在，请重新输入',
        'rate.require' => '优惠券折扣必填',
        'rate.number' => '优惠券折扣必须是数字类型',
        'rate.>' => '优惠券折扣必须大于0',
        'rate.<' => '优惠券折扣必须小于1',
        'total.require' => '优惠券数量必填',
        'total.number' => '优惠券数量必须是数字类型',
        'total.>' => '优惠券数量必须大于0',
        'total.integer' => '优惠券数量必须是整数',
        'createtime.require' => '优惠券开始时间必填',
        'createtime.number' => '优惠券开始时间必须是数字类型',
        'endtime.require' => '优惠券结束时间必填',
        'endtime.number' => '优惠券结束时间必须是数字类型',
        'endtime.egt' => '优惠券结束时间必须大于等于开始时间',
        'thumb.require' => '优惠券图片必填',
    ];

    /**
     * 设置验证场景
     */
    protected $scene = [
        'add' => ['title', 'rate', 'total', 'createtime', 'endtime', 'thumb'],
        'edit' => ['title.require', 'rate', 'total', 'createtime', 'endtime', 'thumb'],
    ];
}