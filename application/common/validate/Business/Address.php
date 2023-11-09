<?php
namespace app\common\validate\Business;

use think\Validate;

// 收货地址
class Address extends Validate
{
    protected $rule =   [
        'busid'   => 'require',
        'consignee'   => 'require',
        'mobile'   => 'require',
        'status'  => ['require', 'in:0,1'],
    ];

    protected $message  =   [
        'busid.require'     => '用户未知',
        'consignee.require' => '收件人名称必填',
        'mobile.require'   => '手机号码必填', 
        'status.require'   => '收货地址的状态未知', 
        'status.in'   => '收货地址的状态有误', 
    ];
}