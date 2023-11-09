<?php

namespace app\common\model\Subject;

use think\Model;

// 引入软删除的模型
use traits\model\SoftDelete;

class Subject extends Model
{
    // 在模型内部引入软删除的trait
    use SoftDelete;

    // 设置当前模型对应的完整数据表名称
    protected $name = 'subject';

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
        'thumbs_text',
        'createtime_text',
        'likes_text'
    ];

    //课程图片获取器
    public function getThumbsTextAttr($value, $data)
    {
        $thumbs = isset($data['thumbs']) ? trim($data['thumbs']) : '';

        // 如果为空就给一个默认图片
        if (empty($thumbs) || !file_exists('.' . $thumbs)) {
            $thumbs = '/assets/home/images/video.jpg';
        }

        return $thumbs;
    }

    // 课程创建时间获取器
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = isset($data['createtime']) ? trim($data['createtime']) : '';

        if (empty($createtime)) {
            return '';
        }

        return date('Y-m-d H:i', $createtime);
    }

    // 课程点赞数获取器
    public function getLikesTextAttr($value, $data)
    {
        $likes = isset($data['likes']) ? trim($data['likes']) : '';

        // 如果为空就返回0
        if (empty($likes)) {
            return 0;
        }

        //将字符串变成数组
        $arr = explode(',', $likes);

        //统计数组的长度，就是点赞人的个数
        return count($arr);
    }

    // 定义一个关联查询的方法
    // 查询分类
    public function category()
    {
        // subject.cateid = category.id
        // $this->belongsTo(关联外键模型,外键字段,关联表的主键,废弃参数,链表方式);
        // setEagerlyType(0)  采用join的方式来做查询
        return $this->belongsTo('app\common\model\Subject\Category', 'cateid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}