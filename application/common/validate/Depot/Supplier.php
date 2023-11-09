<?php

namespace app\common\validate\Depot;

use think\Validate;

class Supplier extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => ['require', 'unique:depot_supplier'],
        'mobile' => ['require', 'unique:depot_supplier'],
        'province' => ['require'],
        'city' => ['require'],
        'district' => ['require'],
        'address' => ['require'],
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '供应商名称不能为空',
        'name.unique' => '供应商名称已存在',
        'mobile.require' => '供应商名称手机号码不能为空',
        'mobile.unique' => '供应商名称手机号码已存在',
        'province.require' => '供应商名称省份不能为空',
        'city.require' => '供应商名称城市不能为空',
        'district.require' => '供应商名称区县不能为空',
        'address.require' => '供应商名称详细地址不能为空',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
    ];


}