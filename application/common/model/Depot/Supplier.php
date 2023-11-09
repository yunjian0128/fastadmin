<?php

namespace app\common\model\Depot;

use think\Model;

class Supplier extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'depot_supplier';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 设置自动写入时间戳字段格式
    protected $createTime = 'createtime';

    // 关闭自动写入更新时间
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'address_text',
    ];

    // 联表查询
    // 省份
    public function province()
    {
        return $this->belongsTo('app\common\model\Region', 'province', 'code', [], 'LEFT')->setEagerlyType(0);
    }

    // 城市
    public function city()
    {
        return $this->belongsTo('app\common\model\Region', 'city', 'code', [], 'LEFT')->setEagerlyType(0);
    }

    // 区县
    public function district()
    {
        return $this->belongsTo('app\common\model\Region', 'district', 'code', [], 'LEFT')->setEagerlyType(0);
    }

    // 供应商地址

    // 详细地址
    public function getAddressTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['address']) ? $data['address'] : '');
        $province = $this->province()->value('name');
        $city = $this->city()->value('name');
        $district = $this->district()->value('name');
        return $province . $city . $district . $value;
    }
}
