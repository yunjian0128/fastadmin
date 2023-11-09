<?php

namespace app\common\validate\Business;

use think\Validate;

class Receive extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'applyid' => ['require'],
        'status' => ['require'],
        'busid' => 'require',
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'applyid.require' => '申请人未知',
        'status.require' => '未知状态',
        'busid.require' => '客户id',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
    ];

}
