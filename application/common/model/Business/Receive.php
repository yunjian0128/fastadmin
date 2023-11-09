<?php

namespace app\common\model\Business;

use think\Model;

class Receive extends Model
{

    //模型对应的是哪张表
    protected $name = "business_receive";

    //指定一个自动设置的时间字段
    //开启自动写入
    protected $autoWriteTimestamp = true;

    //设置字段的名字
    protected $createTime = "applytime"; //插入的时候设置的字段名

    //禁止 写入的时间字段
    protected $updateTime = false;

    protected $append = [
        'status_text',
        //  申请状态
    ];

    public function getStatusTextAttr($value, $data)
    {
        $sexlist = ['apply' => '申请', 'allot' => '分配', 'recovery' => '回收', 'reject' => '拒绝'];
        return $sexlist[$data['status']];
    }


    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'applyid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
