<?php

namespace app\common\model\Hotel;

use think\Model;

// 软删除
use traits\model\SoftDelete;

/**
 * 房间管理模型
 * @package app\common\model\Hotel
 */

class Room extends Model
{
    protected $name = 'hotel_room';

    // 开启软删除
    use SoftDelete;

    // 定义软删除字段
    protected $deleteTime = 'deletetime';

    // 附加属性
    protected $append = [
        'thumb_text',
        'thumbs_text',
        'deletetime_text',
        'flag_text',
    ];

    // 房间单张缩略图获取器
    public function getThumbTextAttr($value, $data)
    {
        // 获取到cdn的地址
        $cdnurl = config('site.cdnurl') ? config('site.cdnurl') : '';
        $cdnurl = trim($cdnurl, '/');

        // 多张图字符串结构
        $thumbs = isset($data['thumb']) ? $data['thumb'] : '';

        if (empty($thumbs)) {
            $thumbs = "/assets/img/shop.jpg";
        } else {
            $thumbs = explode(',', $thumbs);
            if (!empty($thumbs)) {
                $pic = '';

                foreach ($thumbs as $item) {
                    if (is_file("." . $item)) {
                        $pic = $item;
                        break;
                    }
                }

                if (empty($pic)) {
                    $pic = "/assets/img/shop.jpg";
                }

                $thumbs = $pic;
            }
        }

        return $cdnurl . $thumbs;
    }

    // 房间多张缩略图获取器
    public function getThumbsTextAttr($value, $data)
    {
        //获取到cdn的地址
        $cdnurl = config('site.cdnurl') ? config('site.cdnurl') : '';
        $cdnurl = trim($cdnurl, '/');

        // 多张图字符串结构
        $thumbs = isset($data['thumb']) ? $data['thumb'] : '';
        $arr = explode(',', $thumbs);

        //返回结果
        $list = [];

        foreach ($arr as $item) {
            if (is_file("." . $item)) {
                $list[] = $cdnurl . $item;
            }
        }

        if (empty($list)) {
            $list[] = $cdnurl . "/assets/img/shop.jpg";
        }

        return $list;
    }

    // 删除时间获取器
    public function getDeleteTimeTextAttr($value, $data)
    {
        $deletetime = $data['deletetime'];
        if (empty($deletetime)) {
            return '';
        }
        return date("Y-m-d H:i", $deletetime);
    }

    // 房间标签获取器
    public function getFlagTextAttr($value, $data)
    {
        $flag = isset($data['flag']) ? trim($data['flag']) : '';
        $list = explode(',', $flag);
        return $list;
    }
}

?>