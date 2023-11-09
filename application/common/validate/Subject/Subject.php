<?php

namespace app\common\validate\Subject;

use think\Validate;

class Subject extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'title'   => ['require'],
        'content'   => ['require'],
        'price'   => ['require','regex:/(^[1-9]\d*(\.\d{1,2})?$)|(^0(\.\d{1,2})?$)/'],
        'cateid'   => ['require'],
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'title.require' => '课程名称必填',
        'content.require' => '课程描述重必填',
        'price.require' => '课程价格必填',
        'cateid.require' => '课程分类必填',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
    ];


}
