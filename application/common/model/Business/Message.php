<?php

namespace app\common\model\Business;

use think\Model;

// 软删除的模型
use traits\model\SoftDelete;

class Message extends Model
{
    // 继承软删除
    use SoftDelete;

    // 客户收货地址
    protected $name = 'message';

    // 指定一个自动设置的时间字段
    // 开启自动写入
    protected $autoWriteTimestamp = 'int';

    // 设置字段的名字
    protected $createTime = 'createtime'; //插入的时候设置的字段名

    // 禁止 写入的时间字段
    protected $updateTime = false;

    // 软删除的字段
    protected $deleteTime = 'deletetime';

    // 忽略数据表不存在的字段
    protected $field = true;

    // 追加属性
    protected $append = [
        'createtime_text'
    ];

    // 时间戳转换
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = empty($data['createtime']) ? '' : trim($data['createtime']);
        return date('Y-m-d H:i:s', $createtime);
    }

    // 查询发信人
    public function sender()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'sendid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 查询收信人
    public function receiver()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'receiveid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
