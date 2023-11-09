<?php

namespace app\common\validate\Depot;

use think\Validate;

class Back extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'code' => ['require', 'unique:depot_back'],
        'ordercode' => ['require', 'unique:depot_back'],
        'busid' => ['require'],
        'contact' => ['require'],
        'phone' => ['require'],
        'amount' => ['require'],
        'status' => ['require', 'in:0,1,2,3,-1'],
        'adminid' => ['require'],
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'code.require' => '退货单号必填',
        'code.unique' => '退货单号已存在，请重新输入',
        'ordercode.require' => '订单号必填',
        'ordercode.unique' => '订单已申请退货，请重新输入',
        'busid.require' => '客户必填',
        'contact.require' => '联系人必填',
        'phone.require' => '联系人必填',
        'amount.require' => '总价必填',
        'status.require' => '退货状态必填',
        'status.in' => '退货状态未知',
        'adminid.require' => '销售员必填'
    ];

    /**
     * 验证场景
     */
    protected $scene = [

    ];
}