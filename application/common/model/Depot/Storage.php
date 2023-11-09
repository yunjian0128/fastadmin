<?php

namespace app\common\model\Depot;

use think\Model;
use traits\model\SoftDelete;

class Storage extends Model
{
    // 表名
    protected $name = 'depot_storage';

    use SoftDelete;

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 禁止写入的字段
    protected $updateTime = false;

    // 定义软删除的字段
    protected $deleteTime = 'deletetime';

    // 增加的获取器字段选项
    protected $append = [
        'status_text',
        'type_text',
        'createtime_text',
    ];

    // 订单状态数据
    public function getStatusList()
    {
        return [
            '0' => __('待审批'),
            '1' => __('审批失败'),
            '2' => __('待入库'),
            '3' => __('入库完成')
        ];
    }

    public function getTypeList()
    {
        return [
            '1' => __('直销入库'),
            '2' => __('退款入库'),
        ];
    }

    // 订单状态的获取器
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 订单类型的获取器
    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 时间的获取器
    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['createtime']) ? $data['createtime'] : '');
        return date('Y-m-d H:i:s', $value);
    }

    // 联表查询供应商
    public function supplier()
    {
        return $this->belongsTo('app\common\model\Depot\Supplier', 'supplierid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 联表查询入库人员
    public function admin()
    {
        return $this->belongsTo('app\common\model\Admin', 'adminid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 联表查询审核人员
    public function reviewer()
    {
        return $this->belongsTo('app\common\model\Admin', 'reviewerid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

}
