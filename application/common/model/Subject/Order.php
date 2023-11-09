<?php

namespace app\common\model\Subject;

use think\Model;

// 引入软删除的模型
use traits\model\SoftDelete;

class Order extends Model
{
    // 设置表名
    protected $name = "subject_order";

    use SoftDelete;

    // 指定一个自动设置的时间字段
    // 开启自动写入
    protected $autoWriteTimestamp = true;

    // 设置字段的名字
    protected $createTime = "createtime"; //插入的时候设置的字段名

    // 禁止写入的时间字段
    protected $updateTime = false;

    // 定义软删除的字段
    protected $deleteTime = "deletetime";

    // 定义一个关联查询的方法
    // 查询课程
    public function subject()
    {
        // subject.cateid = category.id
        // $this->belongsTo(关联外键模型,外键字段,关联表的主键,废弃参数,链表方式);
        // setEagerlyType(0)  采用join的方式来做查询
        return $this->belongsTo('app\common\model\Subject\Subject', 'subid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 定义一个关联查询的方法
    // 查询用户
    public function business()
    {

        // order.busid= business.id
        // $this->belongsTo(关联外键模型,外键字段,关联表的主键,废弃参数,链表方式);
        // setEagerlyType(0)  采用join的方式来做查询
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 创建时间获取器
    protected $append = [
        'createtime_text',
    ];

    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = isset($data['createtime']) ? trim($data['createtime']) : '';

        if (empty($createtime)) {
            return '';
        }

        return date('Y-m-d H:i', $createtime);
    }
}