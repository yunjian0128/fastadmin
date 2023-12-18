<?php

namespace app\common\model\Post;

use think\Model;

class Collect extends Model
{
    //标志当前模型操作的是哪张表
    protected $name = "post_collect";

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 关闭自动写入update_time字段
    protected $updateTime = false;

    public function post()
    {
        return $this->belongsTo('app\common\model\Post\Post', 'postid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
