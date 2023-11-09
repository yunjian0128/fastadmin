<?php

namespace app\common\validate\Business;

use think\Validate;

class Visit extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'content' => ['require'],
        'busid' => 'require',
        'adminid' => 'require',
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'content.require' => '回访内容必填',
        'busid.require' => '回访用户必填',
        'adminid.require' => '管理员id必填',
    ];


}
