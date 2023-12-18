<?php

namespace app\common\model\Hotel;

use think\Model;

/**
 * 订单管理模型
 * @package app\common\model\Hotel
 */

class Coupon extends Model {
    protected $name = 'hotel_coupon';

    // 附加属性
    protected $append = [
        'status_text',
        'thumb_text',
        'createtime_text',
        'endtime_text',
        'createtime_info',
        'endtime_info',
    ];

    // 订单状态列表
    public function getStatusList() {
        return [
            0 => __('活动结束'),
            1 => __('正在活动中'),
        ];
    }

    // 优惠券状态获取器
    public function getStatusTextAttr($value, $data) {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();

        return isset($list[$value]) ? $list[$value] : '';
    }

    // 优惠券图片获取器
    public function getThumbTextAttr($value, $data) {

        //获取到cdn的地址
        $cdnurl = config('site.cdnurl') ? config('site.cdnurl') : '';
        $cdnurl = trim($cdnurl, '/');
        $thumb = isset($data['thumb']) ? $data['thumb'] : '';

        //如果为空就给一个默认图片地址
        if(empty($thumb) || !@is_file(".".$thumb)) {
            $thumb = "/assets/img/coupon.jpg";
        }

        return $cdnurl.$thumb;
    }

    // 活动开始时间获取器
    public function getCreatetimeTextAttr($value, $data) {
        $createtime = $data['createtime'];

        if(empty($createtime)) {
            return '';
        }
        return date("Y-m-d", $createtime);
    }

    // 活动结束时间获取器
    public function getEndtimeTextAttr($value, $data) {
        $endtime = $data['endtime'];

        if(empty($endtime)) {
            return '';
        }
        return date("Y-m-d", $endtime);
    }

    // 活动开始时间获取器（详细）
    public function getCreatetimeInfoAttr($value, $data) {
        $createtime = $data['createtime'];

        if(empty($createtime)) {
            return '';
        }
        return date("Y-m-d H:i:s", $createtime);
    }

    // 活动结束时间获取器（详细）
    public function getEndtimeInfoAttr($value, $data) {
        $endtime = $data['endtime'];

        if(empty($endtime)) {
            return '';
        }
        return date("Y-m-d H:i:s", $endtime);
    }
}

?>