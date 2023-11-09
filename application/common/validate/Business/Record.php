<?php
namespace app\common\validate\Business;

use think\Validate;

// 用户消费记录的验证器
class Record extends Validate
{
    protected $rule =   [
        'total'  => 'require',
        'busid'   => 'require',
        'content' => 'require', 
    ];

    protected $message  =   [
        'total.require' => '消费金额必填',
        'busid.require'     => '用户必须填写',
        'content.require'   => '消费描述必须填写', 
    ];
}