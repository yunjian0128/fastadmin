<?php

namespace app\common\model\Business;

use think\Model;

class Record extends Model
{
    // 模型对应的是哪张表
    protected $name = "business_record";

    // 指定一个自动设置的时间字段
    // 开启自动写入
    protected $autoWriteTimestamp = true;

    // 设置字段的名字
    protected $createTime = "createtime"; //插入的时候设置的字段名

    // 禁止 写入的时间字段
    protected $updateTime = false;

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

    // 联表查询用户
    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}