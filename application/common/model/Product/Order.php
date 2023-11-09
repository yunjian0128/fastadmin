<?php

namespace app\common\model\Product;

use think\Model;
use traits\model\SoftDelete;

class Order extends Model
{

    use SoftDelete;

    // 表名
    protected $name = 'order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 禁止写入的字段
    protected $updateTime = false;

    // 定义软删除的字段
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text'
    ];

    // 订单状态数据
    public function getStatusList()
    {
        return [
            '0' => __('未支付'),
            '1' => __('已支付'),
            '2' => __('已发货'),
            '3' => __('已收货'),
            '4' => __('已完成'),
            '-1' => __('仅退款'),
            '-2' => __('退款退货'),
            '-3' => __('售后中'),
            '-4' => __('退货成功'),
            '-5' => __('退货失败')
        ];
    }

    // 订单状态的获取器
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 关联物流
    public function express()
    {
        return $this->belongsTo('app\common\model\Express', 'expressid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联用户
    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联用户收货地址
    public function address()
    {
        return $this->belongsTo('app\common\model\Business\Address', 'businessaddrid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 销售员
    public function sale()
    {
        return $this->belongsTo('app\common\model\Admin', 'adminid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 审核员
    public function review()
    {
        return $this->belongsTo('app\common\model\Admin', 'checkmanid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 发货员
    public function dispatched()
    {
        return $this->belongsTo('app\common\model\Admin', 'shipmanid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联查询订单商品
    public function orderProduct()
    {
        return $this->belongsTo('app\common\model\Product\Order_Product', 'orderid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
