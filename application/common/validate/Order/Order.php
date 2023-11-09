<?php
namespace app\common\validate\Order;

use think\Validate;

// 商品订单的验证器
class Order extends Validate
{
    protected $rule = [
        'busid' => 'require',
        'businessaddrid' => 'require',
        'amount' => 'require',
        'code' => ['require', 'unique:order'],
    ];

    protected $message = [
        'busid.require' => '用户必须填写',
        'businessaddrid.require' => '收货地址信息不存在',
        'amount.require' => '消费金额必须填写',
        'code.require' => '订单号必须填写',
        'code.unique' => '订单号已重复',
    ];
}