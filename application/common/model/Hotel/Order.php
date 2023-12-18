<?php

namespace app\common\model\Hotel;

use think\Model;
use traits\model\SoftDelete;

/**
 * 订单管理模型
 * @package app\common\model\Hotel
 */

class Order extends Model
{
    protected $name = 'hotel_order';

    // 开启软删除
    use SoftDelete;

    // 设置软删除字段
    protected $deleteTime = 'deletetime';

    // 附加属性
    protected $append = [
        'status_text',
        'starttime_text',
        'startday_text',
        'endtime_text',
        'endday_text',
        'order_day',
        'commenttime_text'
    ];

    // 订单状态列表
    public function getStatusList()
    {
        return [
            1 => __('已支付'),
            2 => __('已入住'),
            3 => __('已退房'),
            4 => __('已评价'),
            -1 => __('申请退款'),
            -2 => __('审核成功'),
            -3 => __('审核失败'),
        ];
    }

    // 订单状态获取器
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 下单时间获取器
    public function getStarttimeTextAttr($value, $data)
    {
        $starttime = $data['starttime'];
        if (empty($starttime)) {
            return '';
        }
        return date("Y-m-d H:i", $starttime);
    }

    // 退房时间获取器
    public function getEndtimeTextAttr($value, $data)
    {
        $endtime = $data['endtime'];
        if (empty($endtime)) {
            return '';
        }
        return date("Y-m-d H:i", $endtime);
    }

    // 总共入住几天
    public function getOrderDayAttr($value, $data)
    {
        $starttime = $data['starttime'] ? trim($data['starttime']) : 0;
        $endtime = $data['endtime'] ? trim($data['endtime']) : 0;
        $day = intval(($endtime - $starttime) / 86400);

        return $day;
    }

    public function getStartdayTextAttr($value, $data)
    {
        $starttime = $data['starttime'];

        if (empty($starttime)) {
            return '';
        }

        $key = date("w", $starttime);
        $week = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];

        return $week[$key];
    }

    public function getEnddayTextAttr($value, $data)
    {
        $endtime = $data['endtime'];

        if (empty($endtime)) {
            return '';
        }

        $key = date("w", $endtime);
        $week = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];

        return $week[$key];
    }

    // 评论时间获取器
    public function getCommenttimeTextAttr($value, $data)
    {
        $commenttime = $data['commenttime'];
        if (empty($commenttime)) {
            return '';
        }
        return date("Y-m-d H:i", $commenttime);
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

    // 关联优惠券领取表
    public function couponreceive()
    {
        return $this->belongsTo('app\common\model\Hotel\CouponReceive', 'coupon_receive_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

?>