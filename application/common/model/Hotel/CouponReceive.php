<?php

namespace app\common\model\Hotel;

use think\Model;

/**
 * 订单管理模型
 * @package app\common\model\Hotel
 */

class CouponReceive extends Model
{

    // 设置当前模型对应的数据表名称
    protected $name = 'hotel_coupon_receive';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 关闭自动写入updatetime字段
    protected $updateTime = false;

    // 附加属性
    protected $append = [
        'status_text',
        'createtime_text'
    ];

    // 订单状态列表
    public function getStatusList()
    {
        return [
            0 => __('不可使用'),
            1 => __('可使用'),
        ];
    }

    // 订单状态获取器
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 领取时间获取器
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = $data['createtime'];
        if (empty($createtime)) {
            return '';
        }
        return date("Y-m-d", $createtime);
    }


    // 关联客户表
    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联优惠券表
    public function coupon()
    {
        return $this->belongsTo('app\common\model\Hotel\Coupon', 'cid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

?>