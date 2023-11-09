<?php

namespace app\admin\controller\Subject;

use app\common\controller\Backend;

class Info extends Backend
{
    // 设置关联查询
    protected $relationSearch = true;

    // 当前模型
    protected $model = null;

    // 当前无须登录方法
    protected $noNeedLogin = [];

    // 无需鉴权的方法，但需要登录
    protected $noNeedRight = [];

    // 构造函数
    public function __construct()
    {
        parent::__construct();

        // 将控制器和模型关联
        $this->model = model('Subject.Subject');
        $this->SubjectModel = model('Subject.Subject');
        $this->OrderModel = model('Subject.Order');
        $this->CommentModel = model('Subject.Comment');
        $this->ChapterModel = model('Subject.Chapter');
    }

    public function index($ids = NULL)
    {
        // 判断课程是否存在
        $subject = $this->model->find($ids);

        if (!$subject) {
            $this->error('课程不存在');
            exit;
        }
        return $this->view->fetch();
    }

    public function order($ids = NULL)
    {
        // 修改当前模型
        $this->model = $this->OrderModel;

        // 判断课程是否存在
        $subject = $this->SubjectModel->find($ids);

        if (!$subject) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 查询课程订单
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {

            // 获取表格所提交的参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where = ['subid' => $ids];

            // 表格需要两个参数，total和rows，total为数据总数，rows为分页数据
            // 获取数据总数
            $total = $this->model
                ->with(['business'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            // 获取分页数据
            $list = $this->model
                ->with(['business'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 组装数据
            $result = ['total' => $total, 'rows' => $list];

            // 返回数据
            return json($result);
        }
    }

    public function comment($ids = NULL)
    {
        // 修改当前模型
        $this->model = $this->CommentModel;

        // 判断课程是否存在
        $subject = $this->SubjectModel->find($ids);

        if (!$subject) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 查询课程评论
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {

            // 获取表格所提交的参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where = ['subid' => $ids];
            // var_dump($sort);
            // exit;

            // 表格需要两个参数，total和rows，total为数据总数，rows为分页数据
            // 获取数据总数
            $total = $this->model
                ->with(['business'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            // 获取分页数据
            $list = $this->model
                ->with(['business'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 打印上一条sql语句
            // echo $this->model->getLastSql();
            // exit;

            // 组装数据
            $result = ['total' => $total, 'rows' => $list];

            // 返回数据
            return json($result);
        }
    }

    // 课程章节
    public function chapter($ids = NULL)
    {
        // 修改当前模型
        $this->model = $this->ChapterModel;

        // 关闭关联查询
        $this->relationSearch = false;

        // 判断课程是否存在
        $subject = $this->SubjectModel->find($ids);
        // var_dump($subject);
        // exit;

        if (!$subject) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 查询课程章节
        $this->request->filter(['strip_tags', 'trim']);

        // 获取表格所提交的参数
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        $where = ['subid' => $ids];

        // 表格需要两个参数，total和rows，total为数据总数，rows为分页数据
        // 获取数据总数
        $total = $this->model
            ->where($where)
            ->order($sort, $order)
            ->count();
        // var_dump($total);
        // exit;

        // 获取分页数据
        $list = $this->model
            ->where($where)
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->select();

        // 打印上一条sql语句
        // echo $this->model->getLastSql();
        // exit;

        // var_dump(collection($list)->toArray());
        // exit;

        // 组装数据
        $result = ['total' => $total, 'rows' => $list];

        // 返回数据
        return json($result);
    }

    // 课程章节添加
    public function chapter_add($ids = NULL)
    {
        // 修改当前模型
        $this->model = $this->ChapterModel;

        // 根据id要判断课程是否存在
        $subject = $this->SubjectModel->find($ids);

        if (!$subject) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 将当前请求中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 接收表单传递的数据
        if ($this->request->isPost()) {

            // 接收row前缀的数据，并接收为数组类型
            $params = $this->request->param('row/a');

            // 组装数据
            $data = [
                'subid' => $ids,
                'title' => $params['title'],
                'url' => $params['video'],
            ];

            // 插入数据
            $result = $this->model->validate('common/Subject/Chapter')->save($data);

            // 判断是否插入成功
            if ($result === false) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('添加章节成功');
                exit;
            }
        }

        // 渲染模板
        return $this->view->fetch();
    }

    // 课程章节编辑
    public function chapter_edit($ids = NULL)
    {
        // 修改当前模型
        $this->model = $this->ChapterModel;

        // 根据id要判断章节是否存在
        $chapter = $this->model->find($ids);

        if (!$chapter) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 将当前请求中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 接收表单传递的数据
        if ($this->request->isPost()) {

            // 接收row前缀的数据，并接收为数组类型
            $params = $this->request->param('row/a');

            // 组装数据
            $data = [
                'id' => $ids,
                'title' => $params['title'],
                'url' => $params['video'],
            ];

            // 更新数据
            $result = $this->model->validate('common/Subject/Chapter')->isUpdate(true)->save($data);

            // 更新失败
            if ($result === false) {
                $this->error($this->model->getError());
                exit;
            }
            // 判断是否有新视频上传
            if ($data['url'] != $chapter['url']) {

                // 删除旧视频
                is_file('.' . $chapter['url']) && unlink('.' . $chapter['url']);
            }

            $this->success('编辑章节成功');
            exit;
        }

        // 将数据赋值给模板
        $this->view->assign('row', $chapter);

        // 渲染模板
        return $this->view->fetch();
    }

    // 课程章节删除
    public function chapter_del($ids = NULL)
    {
        // 修改当前模型
        $this->model = $this->ChapterModel;

        // 根据id要判断章节是否存在
        $chapter = $this->model->find($ids);

        if (!$chapter) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 删除数据
        $result = $this->model->destroy($ids);

        // 判断是否删除成功
        if ($result === false) {
            $this->error($this->model->getError());
            exit;
        }

        // 删除视频
        is_file('.' . $chapter['url']) && unlink('.' . $chapter['url']);

        $this->success('删除章节成功');
        exit;
    }
}