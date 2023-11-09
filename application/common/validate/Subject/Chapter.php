<?php

namespace app\common\validate\Subject;

use think\Validate;

class Chapter extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'title' => ['require', 'unique:subject_chapter'],

        // 必须上传视频
        'url' => ['require', 'unique:subject_chapter']
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'title.require' => '章节标题必填',
        'title.unique' => '章节标题已存在',
        'url.require' => '视频必须上传',
        'url.unique' => '视频已存在'
    ];
    /**
     * 验证场景
     */
    protected $scene = [
    ];


}