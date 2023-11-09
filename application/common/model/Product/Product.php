<?php

namespace app\common\model\Product;

use think\Model;

// 引入软删除的模型
use traits\model\SoftDelete;

class Product extends Model
{
    // 在模型内部引入软删除的trait
    use SoftDelete;

    // 设置当前模型对应的完整数据表名称
    protected $name = 'product';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 禁止写入的字段
    protected $updateTime = false;

    // 定义软删除的字段
    protected $deleteTime = 'deletetime';

    //增加的获取器字段选项
    protected $append = [
        'flag_text',
        'status_text',
        'category_text',
        'unit_text',
        'thumb_text',
        'thumbs_text',
    ];

    // 获取标签列表
    public function getFlagList()
    {
        return ['1' => __('新品'), '2' => __('热销'), '3' => __('推荐')];
    }

    // 获取状态列表
    public function getStatusList()
    {
        return ['0' => __('下架'), '1' => __('上架')];
    }

    // 标签获取器
    public function getFlagTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['flag']) ? $data['flag'] : '');
        $list = $this->getFlagList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 状态获取器
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 分类获取器
    public function getCategoryTextAttr($value, $data)
    {
        $typeid = isset($data['typeid']) ? $data['typeid'] : 0;
        return model('Product.Category')->where(['id' => $typeid])->value('name');
    }

    // 单位获取器
    public function getUnitTextAttr($value, $data)
    {
        $unitid = isset($data['unitid']) ? $data['unitid'] : 0;
        return model('Product.Unit')->where(['id' => $unitid])->value('name');
    }

    // 商品图片获取器
    public function getThumbTextAttr($value, $data)
    {
        //获取到cdn的地址
        $cdnurl = config('site.cdnurl') ? config('site.cdnurl') : '';
        $cdnurl = trim($cdnurl, '/');

        // 多张图字符串结构
        $thumbs = isset($data['thumbs']) ? $data['thumbs'] : '';

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

    // 商品图片获取器
    public function getThumbsTextAttr($value, $data)
    {
        //获取到cdn的地址
        $cdnurl = config('site.cdnurl') ? config('site.cdnurl') : '';
        $cdnurl = trim($cdnurl, '/');

        // 多张图字符串结构
        $thumbs = isset($data['thumbs']) ? $data['thumbs'] : '';
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

    // 查询分类
    public function category()
    {
        // product.typeid = category.id
        // $this->belongsTo(关联外键模型,外键字段,关联表的主键,废弃参数,链表方式);
        // setEagerlyType(0)  采用join的方式来做查询
        return $this->belongsTo('app\common\model\Product\Category', 'typeid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 查询单位
    public function unit()
    {
        // product.unitid = unit.id
        // $this->belongsTo(关联外键模型,外键字段,关联表的主键,废弃参数,链表方式);
        // setEagerlyType(0)  采用join的方式来做查询
        return $this->belongsTo('app\common\model\Product\Unit', 'unitid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}