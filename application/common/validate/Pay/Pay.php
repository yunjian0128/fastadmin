<?php

namespace app\common\validate\pay;

use think\Validate;

class Pay extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'code'   => ['require','unique:pay'],
        'name' => ['require'],
        'paytype' => ['require', 'number', 'in:0,1'],
        'originalprice' => ['require','number', '>:0'],
        'price' => ['require','number', '>:0'],
        'reurl' => ['require'],
        'callbackurl' => ['require'],
        'status' => ['require','number', 'in:0,1,2'],
        // 'wxcode' => ['require'],
        'zfbcode' => ['require'],
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'code.require' => '订单号必须填写',
        'code.unique' => '订单号已重复',
        'name.require' => '订单名称必须填写',
        'paytype.require' => '支付方式必须填写',
        'paytype.number' => '支付方式的类型有误',
        'paytype.in' => '支付方式的值有误',
        'originalprice.require' => '订单原价必须填写',
        'originalprice.>' => '订单原价必须大于0',
        'originalprice.number' => '订单原价的类型有误',
        'price.>' => '实际支付金额必须大于0',
        'price.require' => '实际支付金额必须填写',
        'price.number' => '实际支付金额的类型有误',
        'reurl.require' => '订单支付完成后跳转的网页地址必须填写',
        'callbackurl.require' => '回调地址必须填写',
        'status.require' => '订单状态必须填写',
        'status.number' => '订单状态的类型有误',
        'status.in' => '订单状态的值有误',
        // 'wxcode' => '微信收款码必须填写',
        'zfbcode' => '支付宝收款码必须填写'
    ];

}