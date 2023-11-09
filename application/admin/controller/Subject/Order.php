<?php

namespace app\admin\controller\Subject;

use app\common\controller\Backend;

/**
 * 订单管理控制器
 * @package app\admin\controller\Subject
 */

class Order extends Backend
{
    // 当前模型
    protected $model = null;

    // 设置多表查询
    protected $relationSearch = true;

    // 当前无须登录方法
    protected $noNeedLogin = [];

    // 无需鉴权的方法，但需要登录
    protected $noNeedRight = [];

    // 构造函数
    public function __construct()
    {
        parent::__construct();

        // 将控制器和模型关联
        $this->model = model('Subject.Order');
    }

    /**
     * 订单管理列表
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
                ->with(['subject', 'business'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            // 获取分页数据
            $list = $this->model
                ->with(['subject', 'business'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 组装数据
            $result = array("total" => $total, "rows" => $list);

            // 返回json数据
            return json($result);
        }

        // 渲染模板
        return $this->view->fetch();
    }

    /**
     * 订单管理删除
     * @return mixed
     */
    public function del($ids = NULL)
    {
        // 判断订单是否存在
        $result = $this->model->find($ids);

        // 如果订单不存在
        if (!$result) {
            $this->error(__('No Results were found'));
        }

        // 删除订单
        if ($result->destroy($ids)) {
            $this->success("删除成功");
        } else {
            $this->error(__('Delete failed'));
        }


    }
}