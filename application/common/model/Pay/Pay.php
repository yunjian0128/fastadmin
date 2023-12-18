<?php

namespace app\common\model\pay;

use think\Model;

class Pay extends Model
{
    // 表名
    protected $name = 'pay';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'paytype_text',
        'status_text',
        'paytime_text'
    ];
    

    
    public function getPaytypeList()
    {
        return ['0' => __('微信支付'), '1' => __('支付宝支付')];
    }

    public function getStatusList()
    {
        return ['0' => __('待支付'), '1' => __('已支付'), '2' => __('已关闭')];
    }


    public function getPaytypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['paytype']) ? $data['paytype'] : '');
        $list = $this->getPaytypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPaytimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['paytime']) ? $data['paytime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPaytimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

}
