<?php
namespace app\common\validate\Business;

use think\Validate;

class Source extends Validate
{
    protected $rule = [
        'name' => ['require', 'unique:business_source']
    ];

    protected $message = [
        'name.require' => '来源名称必填',
        'name.unique' => '来源名称已存在'
    ];
}

?>