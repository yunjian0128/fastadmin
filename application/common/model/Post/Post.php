<?php

namespace app\common\model\Post;

use think\Model;
use traits\model\SoftDelete;

class Post extends Model
{
    // 表名
    protected $name = 'post';

    // 软删除
    use SoftDelete;

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    protected $updateTime = false;

    // 定义软删除字段名
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'createtime_text',
        'comment_text',
        'collect_text'
    ];

    public function getStatusList()
    {
        return ['0' => __('未解决'), '1' => __('已解决')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = empty($data['createtime']) ? '' : trim($data['createtime']);
        return date('Y-m-d H:i:s', $createtime);
    }

    public function getCommentTextAttr($value, $data)
    {
        $postid = empty($data['id']) ? '' : trim($data['id']);
        $count = model('Post.Comment')->where('postid', $postid)->group('busid')->count();
        return $count ? $count : 0;
    }

    public function getCollectTextAttr($value, $data)
    {
        $postid = empty($data['id']) ? '' : trim($data['id']);
        $count = model('Post.Collect')->where('postid', $postid)->count();
        return $count ? $count : 0;
    }

    // 关联分类
    public function category()
    {
        return $this->belongsTo('app\common\model\Post\Category', 'cateid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联用户
    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联回答 
    public function accept()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'accept', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联发帖人粉丝列表
    public function fans()
    {
        return $this->belongsTo('app\common\model\Fans', 'busid', 'busid', [], 'LEFT')->setEagerlyType(0);
    }
}
