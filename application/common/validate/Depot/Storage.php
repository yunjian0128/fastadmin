<?php

namespace app\common\validate\Depot;

use think\Validate;

class Storage extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'code' => ['require', 'unique:depot_storage'],
        'supplierid' => ['require', 'number', 'gt:0'], // 检查供应商是否存在
        'type' => ['require', 'in:1,2'],
        'amount' => ['require', 'float', 'gt:0'],
        'status' => ['require', 'in:0,1,2,3'],
        'remark' => ['max:255'],
        'adminid' => ['number', 'gt:0'],
        'reviewerid' => ['number', 'gt:0'],
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'code.require' => '入库单号不能为空',
        'code.unique' => '入库单号已存在',
        'supplierid.require' => '供应商不能为空',
        'supplierid.number' => '供应商格式错误',
        'supplierid.gt' => '供应商格式错误',
        'type.require' => '入库类型不能为空',
        'type.in' => '入库类型格式错误',
        'amount.require' => '入库金额不能为空',
        'amount.float' => '入库金额格式错误',
        'amount.gt' => '入库金额格式错误',
        'status.require' => '入库状态不能为空',
        'status.in' => '入库状态格式错误',
        'remark.max' => '备注最多不能超过255个字符',
        'adminid.number' => '操作人格式错误',
        'adminid.gt' => '操作人格式错误',
        'reviewerid.number' => '审批人格式错误',
        'reviewerid.gt' => '审批人格式错误',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add' => [],
        'edit' => ['type', 'amount', 'status'],
        'back_edit' => ['type', 'amount', 'status'],
        'back' => ['code', 'type', 'amount', 'status'],
    ];


}