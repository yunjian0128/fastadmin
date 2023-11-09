<?php

namespace app\common\model\Business;

use think\Model;

// 引入软删除类
use traits\model\SoftDelete;

/**
 * 客户管理模型
 * @package app\common\model\Business
 */

class Business extends Model
{
    // 设置当前模型对应的数据表名称
    protected $name = 'business';

    // 使用软删除
    use SoftDelete;

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 设置更新的字段名
    protected $updateTime = false; // 不需要更新时间

    // 定义软删除字段名
    protected $deleteTime = 'deletetime';

    // 增加获取器字段选项
    protected $append = [
        'avatar_text',
        'region_text',
        'province_text',
        'city_text',
        'district_text',
        'createtime_text',
    ];

    // 获取器【地区】
    public function getRegionTextAttr($value, $data)
    {
        $province = model('Region')->where(['code' => $data['province']])->find();

        $city = model('Region')->where(['code' => $data['city']])->find();

        $district = model('Region')->where(['code' => $data['district']])->find();

        $output = [];

        if ($province) {
            $output[] = $province['name'];
        }

        if ($city) {
            $output[] = $city['name'];
        }

        if ($district) {
            $output[] = $district['name'];
        }

        //广东省-广州市-海珠区
        return implode('-', $output);
    }

    // 获取器【头像】
    public function getAvatarTextAttr($value, $data)
    {
        // 获取cdnurl
        $cdnurl = config('site.cdnurl') ? trim(config('site.cdnurl')) : '';
        $avatar = isset($data['avatar']) ? $data['avatar'] : '';

        // 如果头像为空，就返回默认头像
        if (empty($avatar) || !file_exists('.' . $avatar)) {
            $avatar = "/assets/img/avatar.png";
        }
        return $cdnurl . $avatar;
    }

    // 获取器【省份】
    public function getProvinceTextAttr($value, $data)
    {
        $region = empty($data['province']) ? '' : trim($data['province']);

        //查询中文字
        return model('Region')->where(['code' => $region])->value('name');
    }

    // 获取器【城市】
    public function getCityTextAttr($value, $data)
    {
        $region = empty($data['city']) ? '' : trim($data['city']);

        //查询中文字
        return model('Region')->where(['code' => $region])->value('name');
    }

    // 获取器【地区】
    public function getDistrictTextAttr($value, $data)
    {
        $region = empty($data['district']) ? '' : trim($data['district']);

        //查询中文字
        return model('Region')->where(['code' => $region])->value('name');
    }

    // 获取器【创建时间】
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = empty($data['createtime']) ? '' : trim($data['createtime']);

        //查询中文字
        return date('Y-m-d H:i:s', $createtime);
    }

    // 关联客户来源
    public function source()
    {
        return $this->belongsTo('app\common\model\Business\Source', 'sourceid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联客户所属管理员
    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'adminid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}