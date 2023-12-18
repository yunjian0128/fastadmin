<?php

namespace app\ask\controller;

use think\Controller;

class Comment extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->PostModel = model('Post.Post');
        $this->PostCategoryModel = model('Post.Category');
        $this->PostCollectModel = model('Post.Collect');
        $this->CommentModel = model('Post.Comment');
    }

    public function index()
    {
        if ($this->request->isPost()) {
            // $page = $this->request->param('page', 1, 'intval');
            $postid = $this->request->param('postid', 0, 'trim');
            // $busid = $this->request->param('busid', 0, 'trim');
            $pid = $this->request->param('pid', 0, 'trim');
            // $limit = 10;
            // $offset = ($page - 1) * $limit;

            $post = $this->PostModel->find($postid);

            if (!$post) {
                $this->error('暂无帖子信息');
                exit;
            }

            $top = $this->CommentModel
                ->with(['business'])
                ->where(['postid' => $postid, 'pid' => $pid])
                ->order(['pid desc', 'id desc'])
                ->select();

            if (empty($top)) {
                $this->error('暂无评论');
                exit;
            }

            // 循环顶级
            foreach ($top as $item) {
                $item['children'] = $this->CommentModel->sublist($item['id']);

                // 去掉密码和密码盐
                unset($item['business']['password']);
                unset($item['business']['salt']);
                }
            }

            $this->success('返回评论列表', null, $top);
            exit;
        }
    

    // 添加评论
    public function add()
    {
        // 如果有Post请求
        if ($this->request->isPost()) {
            $postid = $this->request->param('postid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $pid = $this->request->param('pid', 0, 'trim');
            $content = $this->request->param('content', '', 'trim');

            $post = $this->PostModel->find($postid);

            if (!$post) {
                $this->error('暂无帖子信息');
                exit;
            }

            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('暂无客户信息');
                exit;
            }

            if (empty($content)) {
                $this->error('评论内容不能为空');
                exit;
            }

            $CommentData = [
                'postid' => $postid,
                'busid' => $busid,
                'pid' => $pid,
                'content' => $content,
                'status' => 0,
            ];

            // 插入数据
            $CommentStatus = $this->CommentModel->save($CommentData);

            if ($CommentStatus === FALSE) {
                $this->error($this->CommentModel->getError());
                exit;
            }

            $this->success('评论成功');
            exit;
        }
    }

    // 点赞
    public function like()
    {
        // 如果有Post请求
        if ($this->request->isPost()) {
            $commentid = $this->request->param('commentid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $postid = $this->request->param('postid', 0, 'trim');

            // 确认用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 确认帖子是否存在
            $post = $this->PostModel->find($postid);

            if (!$post) {
                $this->error('帖子不存在');
                exit;
            }

            // 确认评论是否存在
            $comment = $this->CommentModel->find($commentid);

            if (!$comment) {
                $this->error('评论不存在');
                exit;
            }

            $data = [
                'id' => $commentid,
            ];

            // 确认是否已经点赞
            $like_list = $comment['like_list'];

            $LikeFlag = in_array($busid, $like_list);

            if ($LikeFlag) {

                // 取消点赞
                $index = array_search($busid, $like_list);
                unset($like_list[$index]);
            } else {

                // 点赞
                $like_list[] = $busid;
            }

            if (empty($like_list)) {
                $data['like'] = NULL;
            } else {
                $data['like'] = implode(',', $like_list); // implode() 函数把数组元素组合为一个字符串。
            }

            // 更新评论
            $CommentStatus = $this->CommentModel->isUpdate(true)->save($data);

            if ($CommentStatus && $LikeFlag) {
                $this->success('取消点赞成功');
                exit;
            } elseif ($CommentStatus && !$LikeFlag) {
                $this->success('点赞成功');
                exit;
            } elseif ($CommentStatus === FALSE && $LikeFlag) {
                $this->error($this->CommentModel->getError());
                exit;
            } elseif ($CommentStatus === FALSE && !$LikeFlag) {
                $this->error($this->CommentModel->getError());
                exit;
            } else {
                $this->error('点赞失败');
                exit;
            }
        }
    }

    // 删除评论
    public function delete()
    {
        // 如果有Post请求
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            $postid = $this->request->param('postid', 0, 'trim');
            $post = $this->PostModel->find($postid);

            if (!$post) {
                $this->error('暂无帖子信息');
                exit;
            }

            $commentid = $this->request->param('commentid', 0, 'trim');
            $Comment = $this->CommentModel->find($commentid);

            if (!$Comment) {
                $this->error('暂无评论信息');
                exit;
            }

            $tree = $this->CommentModel->subtree($commentid);

            if (empty($tree)) {
                $this->error('暂无要删除的评论');
                exit;
            }

            // 删除数据
            $CommentStatus = $this->CommentModel->where(['id' => ['IN', $tree]])->delete();

            if ($CommentStatus === FALSE) {
                $this->error($this->CommentModel->getError());
                exit;
            }

            $this->success('删除评论成功');
        }
    }

}
?>