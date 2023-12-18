<?php

namespace app\common\model;

use think\Model;

/**
 * 签到模型
 */

class Fans extends Model
{
    // 表明
    protected $name = 'fans';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 关闭自动写入update_time字段
    protected $updateTime = false;

    // 追加属性
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

        return date("Y-m-d H:i", $createtime);
    }
}

?>