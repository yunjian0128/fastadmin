<?php

namespace app\admin\controller\Subject;

use app\common\controller\Backend;

/**
 * 课程分类管理控制器
 * @package app\admin\controller\Subject
 */
class Category extends Backend
{
    // 当前模型
    protected $model = null;

    // 当前无须登录方法
    protected $noNeedLogin = [];

    // 无需鉴权的方法,但需要登录
    protected $noNeedRight = [];

    // 构造函数
    public function __construct()
    {
        parent::__construct();

        // 将控制器和模型关联
        $this->model = model('Subject.Category');
    }

    /**
     * 课程分类管理列表
     * @return mixed
     */
    public function index()
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 接收数据
        if ($this->request->isAjax()) {
            // 获取表格所提交的参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams(); // buildparams()方法在Backend控制器中

            // 表格需要两个参数，total和rows，total为数据总数，rows为分页数据
            // 获取数据总数
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            // 获取分页数据
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 返回数据
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        // 渲染模板
        return $this->view->fetch();
    }

    /**
     * 添加课程分类
     * @return mixed
     */
    public function add()
    {
        // 将请求当中所有的参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否为POST请求
        if ($this->request->isPost()) {

            // 接收row前缀的数据，并接收为数组类型
            $parmas = $this->request->param('row/a');

            // 组装数据
            $data = [
                'name' => $parmas['name'],
                'weight' => $parmas['weight'],
            ];

            // 添加数据
            $result = $this->model->validate('common/Subject/Category')->save($data);

            // 添加失败
            if (!$result) {
                $this->error($this->model->getError());
                exit;
            }

            // 添加成功
            $this->success('添加课程分类成功');
            exit;
        }

        // 渲染模板
        return $this->view->fetch();
    }

    /**
     * 编辑课程分类
     * @return mixed
     */
    public function edit($ids = NULL)
    {
        // 判断分类是否存在
        $result = $this->model->find($ids);

        // 分类不存在
        if (!$result) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 将请求当中所有的参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否有post过来请求
        if ($this->request->isPost()) {

            // 接收row前缀的数据，并接收为数组类型
            $parmas = $this->request->param('row/a');

            // 组装数据
            $data = [

                // 更新条件
                'id' => $ids,
                'name' => $parmas['name'],
                'weight' => $parmas['weight'],
            ];

            // 更新数据
            $result = $this->model->validate('common/Subject/Category')->isUpdate(true)->save($data);

            // 更新失败
            if (!$result) {
                $this->error($this->model->getError());
                exit;
            }

            // 更新成功
            $this->success('编辑课程分类成功');
            exit;
        }

        // 将数据赋值给模板
        $this->view->assign('row', $result);

        // 渲染模板
        return $this->view->fetch();
    }

    /**
     * 删除课程分类
     * @return mixed
     */
    public function del($ids = NULL)
    {
        // 判断分类是否存在
        $result = $this->model->find($ids);

        // 分类不存在
        if (!$result) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 删除数据
        $result = $this->model->destroy($ids);

        // 删除失败
        if (!$result) {
            $this->error($this->model->getError());
            exit;
        }

        // 删除成功
        $this->success('删除课程分类成功');
        exit;
    }
}