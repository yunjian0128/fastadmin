<?php

namespace app\common\model\Depot;

use think\Model;

use traits\model\SoftDelete;

class Back extends Model
{
    // 表名
    protected $name = 'depot_back';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 软删除
    use SoftDelete;

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'province_text',
        'city_text',
        'district_text',
        'region_text',
        'express_text',
        'admin_text',
        'reviewer_text',
        'stroman_text',
        'thumbs_cdn',
    ];

    public function getStatusList()
    {
        return ['0' => __('未审核'), '1' => __('已审核，未收货'), '2' => __('已收货，未入库'), '3' => __('已入库'), '-1' => __('审核不通过')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getProvinceTextAttr($value, $data)
    {
        $province = $data['province'];

        if (empty($province)) {
            return '';
        }

        return model('Region')->where(['code' => $province])->value('name');
    }

    public function getCityTextAttr($value, $data)
    {
        $city = $data['city'];

        if (empty($city)) {
            return '';
        }

        return model('Region')->where(['code' => $city])->value('name');
    }

    public function getDistrictTextAttr($value, $data)
    {
        $district = $data['district'];

        if (empty($district)) {
            return '';
        }

        return model('Region')->where(['code' => $district])->value('name');
    }

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

    public function getExpressTextAttr($value, $data)
    {
        $expressid = $data['expressid'];

        if (empty($expressid)) {
            return '';
        }

        return model('Express')->where(['id' => $expressid])->value('name');
    }

    public function getAdminTextAttr($value, $data)
    {
        $adminid = $data['adminid'];

        if (empty($adminid)) {
            return '';
        }

        return model('Admin')->where(['id' => $adminid])->value('nickname');
    }

    public function getReviewerTextAttr($value, $data)
    {
        $reviewerid = $data['reviewerid'];

        if (empty($reviewerid)) {
            return '';
        }

        return model('Admin')->where(['id' => $reviewerid])->value('nickname');
    }

    public function getStromanTextAttr($value, $data)
    {
        $stromanid = $data['stromanid'];

        if (empty($stromanid)) {
            return '';
        }

        return model('Admin')->where(['id' => $stromanid])->value('nickname');
    }

    public function getThumbsCdnAttr($value, $data)
    {
        $cdn = config('site.url');

        // $thumbs = empty($data['thumbs']) ? [] : explode(',', $data['thumbs']);
        $thumbs = empty($data['thumbs']) ? [] : trim($data['thumbs']);

        if (empty($thumbs)) {
            return [];
        }

        //字符串替换
        // uploads/20211015/1.jpg, http://www.ask.com/uploads/20211015/1.jpg,
        // uploads/20211015/2.jpg,
        // uploads/20211015/3.jpg

        $thumbs = str_replace("/uploads/", $cdn . "/uploads/", $thumbs);

        //在转换为数组
        $list = explode(',', $thumbs);

        return $list;
    }

    // 关联查询客户
    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT');
    }

    // 
}
