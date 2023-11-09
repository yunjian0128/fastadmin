<?php

namespace app\common\model\Product;

use think\Model;

class Category extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'product_type';

    //增加的获取器字段选项
    protected $append = [
        'thumb_text',
    ];

    // 图片获取器
    public function getThumbTextAttr($value, $data)
    {
        //获取到cdn的地址
        $cdnurl = config('site.cdnurl') ? config('site.cdnurl') : '';
        $cdnurl = trim($cdnurl, '/');

        // 多张图字符串结构
        $thumb = isset($data['thumb']) ? $data['thumb'] : '';

        if (empty($thumb)) {
            $thumb = "/assets/img/shop.jpg";
        } else {
            $thumb = $cdnurl . $thumb;
        }

        return $thumb;
    }
}
