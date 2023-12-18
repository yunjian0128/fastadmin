<?php

namespace app\common\model\Post;

use think\Model;
use traits\model\SoftDelete;

class Comment extends Model
{
    use SoftDelete;

    //标志当前模型操作的是哪张表
    protected $name = "post_comment";

    //开启自动写入
    protected $autoWriteTimestamp = true;

    protected $createTime = 'createtime';

    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    protected $append = [
        'createtime_text',
        'like_count',
        'like_list',
        'comment_count',
        'status_text'
    ];

    public function getStatusList()
    {
        return ['0' => __('未采纳'), '1' => __('已采纳')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');

        $list = $this->getStatusList();

        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCommentCountAttr($value, $data)
    {
        $id = $data['id'];

        $count = $this->where(['pid' => $id])->count();

        return $count ? $count : 0;
    }

    public function getLikeListAttr($value, $data)
    {
        $like = $data['like'];

        if (empty($like)) {
            return [];
        }

        //把字符串转换数组
        return explode(',', $like);
    }

    //点赞数量
    public function getLikeCountAttr($value, $data)
    {
        $like = $data['like'];

        if (empty($like)) {
            return 0;
        }

        //把字符串转换数组
        $arr = explode(',', $like);

        //返回数组的长度
        return count($arr);
    }

    //时间戳
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = $data['createtime'];

        if (empty($createtime)) {
            return '';
        }

        return date("Y-m-d H:i", $createtime);
    }

    public function post()
    {
        return $this->belongsTo('app\common\model\Post\Post', 'postid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 递归查询子集的方法
    public function sublist($pid)
    {
        //查找子集
        $son = $this->with(['business'])
            ->where(['pid' => $pid])
            ->order(['pid asc', 'id asc'])
            ->select();

        if (empty($son)) {
            return [];
        }

        //循环递归
        foreach ($son as &$item) {
            $item['chidren'] = $this->sublist($item['id']);
        }

        return $son;
    }

    // 递归查询子集的ID的方法
    public function subtree($comid = FALSE, $ids = [])
    {
        if ($comid === FALSE) {
            return $ids;
        }

        // 将id转化为int类型
        $comid = intval($comid);

        // 将最外层的id放入数组中
        $ids[] = $comid;

        // 查询是否有子评论
        $son = $this->where(['pid' => $comid])->select();

        if ($son) {
            foreach ($son as $item) {
                // 递归，然后将目前所保留的id也带入进去
                $ids = $this->subtree($item['id'], $ids);
            }
        } else {
            return $ids;
        }

        return $ids;
    }
}
