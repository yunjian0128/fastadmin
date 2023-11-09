<?php

namespace app\common\model\Subject;

use think\Model;

class Chapter extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'subject_chapter';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 禁止更新写入的时间字段
    protected $updateTime = false;
}