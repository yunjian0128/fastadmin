<?php

namespace app\common\model\Business;

use think\Model;

// 软删除的模型
use traits\model\SoftDelete;

class Address extends Model
{
    // 继承软删除
    use SoftDelete;

    // 客户收货地址
    protected $name = 'business_address';

    // 指定一个自动设置的时间字段
    // 开启自动写入
    protected $autoWriteTimestamp = 'int';

    // 设置字段的名字
    protected $createTime = false; //插入的时候设置的字段名

    // 禁止 写入的时间字段
    protected $updateTime = false;

    // 软删除的字段
    protected $deleteTime = 'deletetime';

    // 忽略数据表不存在的字段
    protected $field = true;

    protected $append = [
        'region_text', //地区字符串,
    ];

    // 给模型定义一个关联查询
    public function provinces()
    {
        // belongsTo('关联模型名','外键名','关联表主键名',['模型别名定义'],'join类型');
        // 参数1：关联的模型
        // 参数2：用户表的外键的字段
        // 参数3：关联表的主键
        // 参数4：模型别名
        // 参数5：链接方式 left
        // setEagerlyType(1) IN查询
        // setEagerlyType(0) JOIN查询
        return $this->belongsTo('app\common\model\Region', 'province', 'code', [], 'LEFT')->setEagerlyType(0);
    }

    // 查询城市
    public function citys()
    {
        return $this->belongsTo('app\common\model\Region', 'city', 'code', [], 'LEFT')->setEagerlyType(0);
    }

    // 查询地区
    public function districts()
    {
        return $this->belongsTo('app\common\model\Region', 'district', 'code', [], 'LEFT')->setEagerlyType(0);
    }

    // 地区字符串
    public function getRegionTextAttr($value, $data)
    {
        $region = '';

        // 省
        $province = empty($data['province']) ? '' : $data['province'];
        if ($province) {
            $province_text = model('Region')::where('code', $province)->value('name');
            $region = $province_text;
        }

        // 市
        $city = empty($data['city']) ? '' : $data['city'];
        if ($city) {
            $city_text = model('Region')::where('code', $city)->value('name');
            $region .= '-' . $city_text;
        }

        // 区
        $district = empty($data['district']) ? '' : $data['district'];
        if ($district) {
            $district_text = model('Region')::where('code', $district)->value('name');
            $region .= '-' . $district_text;
        }

        //广东省-广州市-海珠区
        return $region;
    }
}
