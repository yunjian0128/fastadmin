<?php

namespace app\common\model\Hotel;

use think\Model;

/**
 * 客户管理模型
 * @package app\common\model\Hotel
 */

class Collection extends Model
{
    // 设置当前模型对应的数据表名称
    protected $name = 'hotel_collection';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 关闭自动写入updatetime字段
    protected $updateTime = false;

    // 附加属性
    protected $append = [
        'createtime_text'
    ];

    // 时间获取器
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = $data['createtime'];
        if (empty($createtime)) {
            return '';
        }
        return date("Y-m-d H:i:s", $createtime);
    }

    // 关联客户表
    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联房间表
    public function room()
    {
        return $this->belongsTo('app\common\model\Hotel\Room', 'roomid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

?>