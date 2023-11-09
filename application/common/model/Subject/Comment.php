<?php

namespace app\common\model\Subject;

use think\Model;

/**
 * 课程评论模型
 */

class Comment extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'subject_comment';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 禁止更新写入的时间字段
    protected $updateTime = false;

    //定义一个关联查询的方法//查询分类
    public function business()
    {
        // comment.busid = business.id
        // $this->belongsTo(关联外键模型,外键字段,关联表的主键,废弃参数,链表方式);
        // setEagerlyType(0)  采用join的方式来做查询
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}