<?php

namespace app\admin\controller\Subject;

// 引入后台基类控制器
use app\common\controller\Backend;

/**
 * 课程管理控制器
 * @package app\admin\controller\Subject
 */
class Subject extends Backend
{
    //当前模型
    protected $model = null;

    //设置多表查询
    protected $relationSearch = true;

    //当前无须登录方法
    protected $noNeedLogin = [];

    //无需鉴权的方法,但需要登录
    protected $noNeedRight = [];

    //构造函数
    public function __construct()
    {
        parent::__construct();

        //将控制器和模型关联
        $this->model = model('Subject.Subject');
        $this->CategoryModel = model('Subject.Category');
    }

    /**
     * 课程管理列表
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
                ->with(['category'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            // 获取分页数据
            $list = $this->model
                ->with(['category'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 遍历数据
            foreach ($list as $key => &$row) {
                $list[$key]['content'] = strip_tags($row['content']);
            }

            // 组装数据
            $result = array("total" => $total, "rows" => $list);

            // 返回数据
            return json($result);
        }
        return $this->view->fetch();
    }

    // 添加课程
    public function add()
    {
        //将请求当中所有的参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 接收表单传递的数据
        if ($this->request->isPost()) {

            // 接收row前缀的数据，并接收为数组类型
            $parmas = $this->request->param('row/a');

            // 组装数据
            $data = [
                'title' => $parmas['title'],
                'price' => $parmas['price'],
                'content' => $parmas['content'],
                'thumbs' => $parmas['thumbs'],
                'cateid' => $parmas['cateid'],
            ];

            // 插入数据
            $result = $this->model->validate('common/Subject/Subject')->save($data);

            if ($result === false) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('添加课程成功');
                exit;
            }
        }

        // 查询出所有的课程分类
        $catelist = $this->CategoryModel->column('id, name');

        // 将数据赋值给模板
        $this->view->assign('catelist', build_select('row[cateid]', $catelist, [], ['class' => 'selectpicker', 'required' => '']));

        // 渲染模板
        return $this->view->fetch();
    }

    // 编辑课程
    public function edit($ids = NULL)
    {
        // 根据id要判断数据是否存在
        $row = $this->model->find($ids);

        // 数据丢失，记录不存在
        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 将当前请求中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否有post过来请求
        if ($this->request->isPost()) {
            // 接收row前缀请求参数，并返回一个数组类型
            $params = $this->request->param('row/a');

            // 组装数据
            $data = [
                'id' => $ids,
                'title' => $params['title'],
                'content' => $params['content'],
                'price' => $params['price'],
                'cateid' => $params['cateid'],
                'thumbs' => $params['thumbs'],
            ];

            // 直接去更新
            $result = $this->model->validate('common/Subject/Subject')->isUpdate(true)->save($data);

            if ($result === FALSE) {
                $this->error($this->model->getError());
                exit;
            }

            // 判断是否有上传新图片 
            if ($data['thumbs'] != $row['thumbs']) {

                // 不相等就说明有换图片了，就删除掉旧图片
                is_file("." . $row['thumbs']) && @unlink("." . $row['thumbs']);
            }

            $this->success('编辑课程成功');
            exit;
        }

        // 先将课程分类查询出来
        $catelist = $this->CategoryModel->column('id,name');
        $this->assign('catelist', build_select('row[cateid]', $catelist, $row['cateid'], ['class' => 'selectpicker', 'required' => '']));

        // 有数据要赋值到模板中去
        $this->assign('row', $row);

        // 渲染模板
        return $this->view->fetch();
    }

    // 删除课程
    public function del($ids = NULL)
    {
        // 根据id查询出数据
        $row = $this->model->select($ids);

        // 判断数据是否存在
        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 软删除
        $result = $this->model->destroy($ids);

        if ($result === false) {
            $this->error('删除课程失败');
            exit;
        } else {
            $this->success('删除课程成功');
            exit;
        }
    }
}

?>