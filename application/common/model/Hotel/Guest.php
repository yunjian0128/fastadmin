<?php

namespace app\common\model\Hotel;

use think\Model;

/**
 * 客户管理模型
 * @package app\common\model\Hotel
 */

class Guest extends Model {
    // 设置当前模型对应的数据表名称
    protected $name = 'hotel_guest';

    // 附加属性
    protected $append = [
        'gender_text',
    ];

    // 性别获取器
    public function getGenderTextAttr($value, $data) {
        $gender = $data['gender'] ? $data['gender'] : 0;
        $list = ['0' => '女', '1' => '男'];

        return $list[$gender];
    }

    // 关联客户表
    public function business() {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

?>