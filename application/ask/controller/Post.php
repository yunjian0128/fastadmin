<?php

namespace app\ask\controller;

use think\Controller;

class Post extends Controller {
    public function __construct() {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->PostModel = model('Post.Post');
        $this->PostCategoryModel = model('Post.Category');
        $this->PostCollectModel = model('Post.Collect');
        $this->CommentModel = model('Post.Comment');
        $this->FansModel = model('Fans');
    }

    public function index() {
        if($this->request->isPost()) {
            $page = $this->request->param('page', 1, 'trim');
            $cateid = $this->request->param('cateid', 0, 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');
            $limit = 10;

            $offset = ($page - 1) * $limit;

            $where = [];

            if($cateid) {
                $where['cateid'] = $cateid;
            }

            if($keywords) {
                $where['title'] = ['like', "%$keywords%"];
            }

            $list = $this->PostModel
                ->with(['category', 'business'])
                ->where($where)
                ->order('id', 'desc')
                ->limit($offset, $limit)
                ->select();

            if($list) {
                $this->success('返回帖子数据', null, $list);
                exit;
            } else {
                $this->error('暂无更多数据');
                exit;
            }
        }
    }

    // 获取帖子分类
    public function cate() {
        $list = $this->PostCategoryModel->order('weigh', 'asc')->select();

        if($list) {
            return $this->success('获取成功', null, $list);
        } else {
            return $this->error('暂无帖子分类');
        }
    }

    // 发布帖子
    public function add() {
        if($this->request->isPost()) {
            $title = $this->request->param('title', '', 'trim');
            $content = $this->request->param('content', '', 'trim');
            $point = $this->request->param('point', 0, 'trim');
            $cateid = $this->request->param('cateid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');

            // 确认用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 查看用户积分是否充足
            $UpdatePoint = bcsub($business['point'], $point);

            if($UpdatePoint < 0) {
                $this->error('积分不足，请先充值');
                exit;
            }

            // 帖子表、用户表、消费记录表
            $this->PostModel->startTrans();
            $this->BusinessModel->startTrans();

            // 插入帖子
            $PostData = [
                'title' => $title,
                'content' => $content,
                'point' => $point,
                'busid' => $busid,
                'cateid' => $cateid,
                'status' => '0',
            ];

            $PostStatus = $this->PostModel->save($PostData);

            if($PostStatus === FALSE) {
                $this->error($this->PostModel->getError());
                exit;
            }

            // 更新用户积分
            $BusinessData = [
                'id' => $busid,
                'point' => $UpdatePoint
            ];

            $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

            if($BusinessStatus === FALSE) {
                $this->PostModel->rollback();
                $this->error($this->BusinessModel->getError());
                exit;
            }

            if($PostStatus === FALSE || $BusinessStatus === FALSE) {
                $this->BusinessModel->rollback();
                $this->PostModel->rollback();
                $this->error('发帖失败');
                exit;
            } else {
                $this->PostModel->commit();
                $this->BusinessModel->commit();
                $this->success('发帖成功', '/pages/post/info', ['postid' => $this->PostModel->id]);
                exit;
            }
        }
    }

    // 帖子详情
    public function info() {

        // 如果有Post请求
        if($this->request->isPost()) {

            $postid = $this->request->param('postid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');

            $post = $this->PostModel->with(['category', 'business'])->find($postid);

            if(!$post) {
                $this->error('帖子不存在');
                exit;
            }

            $business = $this->BusinessModel->find($busid);

            if($business) {

                // 查询收藏的状态
                $collect = $this->PostCollectModel->where(['postid' => $postid, 'busid' => $busid])->find();

                if($collect) {

                    // 追加自定义数组元素
                    $post['collect'] = true;
                }

                $fansid = intval($busid);
                $busid = intval($post['busid']);

                // 查询是否关注
                $subscribe = $this->FansModel->where(['busid' => $busid, 'fansid' => $fansid])->find();

                if($subscribe) {

                    // 追加自定义数组元素
                    $post['subscribe'] = true;
                }
            }

            $this->success('返回帖子信息', null, $post);
            exit;
        }
    }

    // 收藏帖子
    public function collect() {
        // 如果有Post请求
        if($this->request->isPost()) {
            $postid = $this->request->param('postid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');

            // 确认用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 确认帖子是否存在
            $post = $this->PostModel->find($postid);

            if(!$post) {
                $this->error('帖子不存在');
                exit;
            }

            // 确认是否已经收藏
            $collect = $this->PostCollectModel->where(['postid' => $postid, 'busid' => $busid])->find();

            if($collect) {

                // 取消收藏
                $CollectStatus = $this->PostCollectModel->where(['id' => $collect['id']])->delete();

                if($CollectStatus === FALSE) {
                    $this->error($this->PostCollectModel->getError());
                    exit;
                } else {
                    $this->success('取消收藏成功');
                    exit;
                }
            } else {

                // 收藏帖子
                $CollectData = [
                    'postid' => $postid,
                    'busid' => $busid
                ];

                $CollectStatus = $this->PostCollectModel->save($CollectData);

                if($CollectStatus === FALSE) {
                    $this->error($this->PostCollectModel->getError());
                    exit;
                } else {
                    $this->success('收藏成功');
                    exit;
                }
            }
        }
    }

    // 编辑帖子 
    public function edit() {
        // 如果有Post请求
        if($this->request->isPost()) {
            $postid = $this->request->param('postid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $title = $this->request->param('title', '', 'trim');
            $content = $this->request->param('content', '', 'trim');
            $point = $this->request->param('point', 0, 'trim');
            $cateid = $this->request->param('cateid', 0, 'trim');

            // 确认用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 确认帖子是否存在
            $post = $this->PostModel->find($postid);

            if(!$post) {
                $this->error('帖子不存在');
                exit;
            }

            // 确认帖子是否属于该用户
            if($post['busid'] != $busid) {
                $this->error('帖子不属于该用户');
                exit;
            }

            // 帖子表、用户表、消费记录表
            $this->PostModel->startTrans();
            $this->BusinessModel->startTrans();

            // 查看用户积分是否充足
            $UpdatePoint = bcsub($business['point'], $point);

            if($UpdatePoint < 0) {
                $this->error('积分不足，请先充值');
                exit;
            }

            // 更新帖子
            $PostData = [
                'id' => $postid,
                'busid' => $busid,
                'title' => $title,
                'content' => $content,
                'cateid' => $cateid,
            ];

            if($point > 0) {
                $PostData['point'] = bcadd($post['point'], $point);
            }

            // 更新帖子
            $PostStatus = $this->PostModel->isUpdate(true)->save($PostData);

            if($PostStatus === FALSE) {
                $this->error($this->PostModel->getError());
                exit;
            }

            // 更新用户积分
            $BusinessData = [
                'id' => $busid,
                'point' => $UpdatePoint
            ];

            $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

            if($BusinessStatus === FALSE) {
                $this->PostModel->rollback();
                $this->error($this->BusinessModel->getError());
                exit;
            }

            if($PostStatus === FALSE || $BusinessStatus === FALSE) {
                $this->BusinessModel->rollback();
                $this->PostModel->rollback();
                $this->error('编辑帖子失败');
                exit;
            } else {
                $this->PostModel->commit();
                $this->BusinessModel->commit();
                $this->success('编辑帖子成功');
                exit;
            }
        }
    }

    // 删除帖子
    public function delete() {
        // 如果有Post请求
        if($this->request->isPost()) {
            $postid = $this->request->param('postid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $cateid = $this->request->param('cateid', 0, 'trim');

            // 确认用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            if($cateid == 3) {
                $comment = $this->CommentModel->find($postid);

                // 确认评论是否存在
                if(!$comment) {
                    $this->error('评论不存在');
                    exit;
                }

                // 确认评论是否属于该用户
                if($comment['busid'] != $busid) {
                    $this->error('评论不属于该用户');
                    exit;
                }

                // 确认评论是否已经采纳
                if($comment['status'] == 1) {
                    $this->error('已采纳的评论不能删除');
                    exit;
                }
            } else {

                // 确认帖子是否存在
                $post = $this->PostModel->find($postid);

                if(!$post) {
                    $this->error('帖子不存在');
                    exit;
                }

                // 确认帖子是否属于该用户
                if($post['busid'] != $busid && $cateid == 0) {
                    $this->error('帖子不属于该用户');
                    exit;
                }

                // 确认帖子是否已经解决
                if($post['status'] == 1 && $cateid == 0) {
                    $this->error('已解决的帖子不能删除');
                    exit;
                }
            }

            // 删除帖子
            if($cateid == 0) {

                // 帖子表、评论表开启事务
                $this->PostModel->startTrans();
                $this->CommentModel->startTrans();

                // 删除帖子
                $PostStatus = $this->PostModel->where(['id' => $postid])->delete();

                if($PostStatus === FALSE) {
                    $this->error($this->PostModel->getError());
                    exit;
                }

                // 删除评论
                $CommentStatus = $this->CommentModel->where(['postid' => $postid])->delete();

                if($CommentStatus === FALSE) {
                    $this->PostModel->rollback();
                    $this->error($this->CommentModel->getError());
                    exit;
                }

                if($PostStatus === FALSE || $CommentStatus === FALSE) {
                    $this->PostModel->rollback();
                    $this->CommentModel->rollback();
                    $this->error('删除帖子失败');
                    exit;
                } else {
                    $this->PostModel->commit();
                    $this->CommentModel->commit();
                    $this->success('删除帖子成功');
                    exit;
                }
            }

            // 取消收藏
            if($cateid == 2) {
                $CollectStatus = $this->PostCollectModel->where(['postid' => $postid, 'busid' => $busid])->delete();

                if($CollectStatus === FALSE) {
                    $this->error($this->PostCollectModel->getError());
                    exit;
                } else {
                    $this->success('取消收藏成功');
                    exit;
                }
            }

            // 删除评论
            if($cateid == 3) {
                $tree = $this->CommentModel->subtree($postid);

                if(empty($tree)) {
                    $this->error('暂无要删除的评论');
                    exit;
                }

                // 删除数据
                $CommentStatus = $this->CommentModel->where(['id' => ['IN', $tree]])->delete();

                if($CommentStatus === FALSE) {
                    $this->error($this->CommentModel->getError());
                    exit;
                }

                $this->success('删除评论成功');
            }
        }
    }

    // 采纳
    public function accept() {
        // 如果有Post请求
        if($this->request->isPost()) {
            $commentid = $this->request->param('commentid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $postid = $this->request->param('postid', 0, 'trim');

            // 确认用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 确认帖子是否存在
            $post = $this->PostModel->find($postid);

            if(!$post) {
                $this->error('帖子不存在');
                exit;
            }

            // 确认评论是否存在
            $comment = $this->CommentModel->find($commentid);

            if(!$comment) {
                $this->error('评论不存在');
                exit;
            }

            //采纳人的信息
            $acceptid = isset($comment['busid']) ? $comment['busid'] : 0;
            $accept = $this->BusinessModel->find($acceptid);

            if(!$accept) {
                $this->error('采纳人信息未知');
                exit;
            }

            // 确认评论的用户是否是帖子的发布者
            if($post['busid'] != $busid) {
                $this->error('你不是帖子的发布者，无法采纳');
                exit;
            }

            // 确认评论是否已经采纳
            if($comment['status'] == 1) {
                $this->error('该评论已经采纳');
                exit;
            }

            // 确认采纳的是不是自己的评论
            if($comment['busid'] == $busid) {
                $this->error('不能采纳自己的评论');
                exit;
            }

            // 帖子表、用户表、评论表
            // 开启事务
            $this->PostModel->startTrans();
            $this->BusinessModel->startTrans();
            $this->CommentModel->startTrans();

            // 更新帖子
            $PostData = [
                'id' => $postid,
                'status' => 1,
                'accept' => $acceptid,
            ];

            $PostStatus = $this->PostModel->isUpdate(true)->save($PostData);

            if($PostStatus === FALSE) {
                $this->error($this->PostModel->getError());
                exit;
            }

            // 更新用户积分
            $BusinessData = [
                'id' => $acceptid,
                'point' => bcadd($accept['point'], $post['point'])
            ];

            $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

            if($BusinessStatus === FALSE) {
                $this->PostModel->rollback();
                $this->error($this->BusinessModel->getError());
                exit;
            }

            // 更新评论
            $CommentData = [
                'id' => $commentid,
                'status' => 1,
            ];

            $CommentStatus = $this->CommentModel->isUpdate(true)->save($CommentData);

            if($CommentStatus === FALSE) {
                $this->BusinessModel->rollback();
                $this->PostModel->rollback();
                $this->error($this->CommentModel->getError());
                exit;
            }

            // 大判断
            if($PostStatus === FALSE || $BusinessStatus === FALSE || $CommentStatus === FALSE) {
                $this->CommentModel->rollback();
                $this->BusinessModel->rollback();
                $this->PostModel->rollback();
                $this->error('采纳失败');
                exit;
            } else {
                $this->CommentModel->commit();
                $this->BusinessModel->commit();
                $this->PostModel->commit();
                $this->success('采纳成功');
                exit;
            }
        }
    }

    // 关注用户
    public function subscribe() {
        // 如果有Post请求
        if($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $fansid = $this->request->param('fansid', 0, 'trim');

            // 确认用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('要关注的用户不存在');
                exit;
            }

            // 确认粉丝是否存在
            $fans = $this->BusinessModel->find($fansid);

            if(!$fans) {
                $this->error('用户不存在');
                exit;
            }

            if($busid == $fansid) {
                $this->error('自己不能关注自己');
                exit;
            }

            // 确认是否已经关注
            $subscribe = $this->FansModel->where(['busid' => $busid, 'fansid' => $fansid])->find();

            // 如果结果为空
            if(!$subscribe) {

                // 关注用户（插入数据）
                $FansData = [
                    'busid' => $busid,
                    'fansid' => $fansid
                ];

                $FansStatus = $this->FansModel->save($FansData);

                if($FansStatus === FALSE) {
                    $this->error($this->FansModel->getError());
                    exit;
                } else {
                    $this->success('关注成功');
                    exit;
                }
            }

            // 如果结果不为空 就是取消关注
            $FansStatus = $this->FansModel->where(['id' => $subscribe['id']])->delete();

            if($FansStatus === FALSE) {
                $this->error($this->FansModel->getError());
                exit;
            } else {
                $this->success('取消关注成功');
                exit;
            }
        }
    }
}
?>