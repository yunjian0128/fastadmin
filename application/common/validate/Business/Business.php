<?php

namespace app\common\validate\Business;

//引入Thinkphp底层验证器进来
use think\Validate;

/**
 * 定义客户验证器
 */
class Business extends Validate
{
    /**
     * 设置我们要验证字段的规则
     */
    protected $rule = [
        'mobile' => ['require', 'number', 'unique:business', 'regex:/(^1[3|4|5|7|8][0-9]{9}$)/'],
        'nickname' => ['require'],
        'password' => ['require'],
        'salt' => ['require'],
        'gender' => ['in:0,1,2'],
        'deal' => ['in:0,1'],
        'money' => ['number', '>=:0'],
        'email' => ['email'],
        'auth' => ['in:0,1']
    ];

    /**
     * 设置错误的提醒信息
     */
    protected $message = [
        'mobile.require' => '手机号必填',
        'mobile.unique' => '手机号已存在，请重新输入',
        'mobile.regex' => '手机号码格式不正确',
        'password.require' => '密码必填',
        'salt.require' => '密码盐必填',
        'money.number' => '余额必须是数字类型',
        'money.>=' => '余额必须大于等于0元',
        'auth.number' => '认证状态的类型有误',
        'auth.in' => '认证状态的值有误',
        'deal.number' => '成交状态的类型有误',
        'deal.in' => '成交状态的值有误',
        'nickname.require' => '昵称必填',
        'email.email' => '邮箱格式错误',
        'email.unique' => '邮箱已存在，请重新输入'
    ];

    /**
     * 设置验证场景
     */
    protected $scene = [
        'register' => ['mobile', 'password', 'salt', 'money', 'auth', 'deal'],
        'profile' => ['mobile', 'nickname', 'gender'],
    ];
}