<?php

namespace app\admin\controller\Business;

use app\common\controller\Backend;

/**
 * 客户公海管理控制器
 * @package app\admin\controller\Business
 */

class Highsea extends Backend
{
    // 当前模型
    protected $model = null;

    // 联表查询
    protected $relationSearch = true;

    // 当前无须登录方法
    protected $noNeedLogin = [];

    // 无需鉴权的方法,但需要登录
    protected $noNeedRight = [];

    // 构造函数
    public function __construct()
    {
        parent::__construct();

        // 将控制器和模型关联
        $this->model = model('Business.Business');
        $this->AdminModel = model('Admin');
        $this->ReceiveModel = model('Business.Receive');
    }

    /**
     * 客户公海管理列表
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
                ->with(['source'])
                ->where('adminid', 'null')
                ->where($where)
                ->order($sort, $order)
                ->count();

            // 获取分页数据
            $list = $this->model
                ->with(['source'])
                ->where('adminid', 'null')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 返回数据
            return json(['total' => $total, 'rows' => $list]);
        }

        // 渲染视图
        return $this->view->fetch();
    }

    // 客户公海领取管理
    public function apply($ids = NULL)
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 接收数据 
        $ids = !empty($ids) ? explode(',', $ids) : []; // 将字符串转换为数组
        $row = $this->model->all($ids); // 查询出所有的数据

        // 判断查询出的数据是否为空
        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }

        $recoverylist = [];
        $businesslist = [];

        // 开启事务
        $this->ReceiveModel->startTrans();
        $this->model->startTrans();

        // 遍历数据
        foreach ($row as $value) {

            // 判断是否已经被领取
            if ($value['adminid'] != NULL) {
                $this->error('客户已被领取');
                exit;
            }

            // 组装数据
            $recoverylist[] = [
                'busid' => $value['id'],
                'applyid' => $this->auth->id,
                'status' => 'apply',
            ];
            $businesslist[] = [
                'id' => $value['id'],
                'adminid' => $this->auth->id,
            ];
        }

        // 插入领取数据
        $result = $this->ReceiveModel->saveAll($recoverylist);
        if (!$result) {

            // 回滚事务
            $this->ReceiveModel->rollback();
            $this->error($this->ReceiveModel->getError());
            exit;
        }

        // 更新客户数据
        $result = $this->model->isUpdate(true)->saveAll($businesslist);

        if ($result === false) {

            // 回滚事务
            $this->model->rollback();
            $this->ReceiveModel->rollback();
            $this->error($this->model->getError());
            exit;
        } else {

            // 提交事务
            $this->ReceiveModel->commit();
            $this->model->commit();
            $this->success('领取客户成功');
            exit;
        }

        // 渲染视图
        return $this->view->fetch();

    }

    // 客户公海分配管理
    public function recovery($ids = NULL)
    {
        //将请求当中所有的参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 接收数据
        $ids = !empty($ids) ? explode(',', $ids) : []; // 将字符串转换为数组
        $row = $this->model->all($ids); // 查询出所有的数据

        // 判断查询出的数据是否为空
        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 接收表单传递的数据
        if ($this->request->isPost()) {

            // 接收row前缀的数据，并接收为数组类型
            $parmas = $this->request->param('row/a');
            $recoverylist = [];
            $businesslist = [];

            // 遍历数据
            foreach ($row as $value) {

                // 判断是否已经被领取
                if ($value['adminid'] != NULL) {
                    $this->error('客户已被领取');
                    exit;
                }

                $recoverylist[] = [
                    'busid' => $value['id'],
                    'applyid' => $parmas['adminid'],
                    'status' => 'allot',
                ];

                $businesslist[] = [
                    'id' => $value['id'],
                    'adminid' => $parmas['adminid'],
                ];
            }

            // 开启事务
            $this->model->startTrans();
            $this->ReceiveModel->startTrans();

            // 插入领取数据
            $result = $this->ReceiveModel->saveAll($recoverylist);

            if ($result === false) {

                // 回滚事务
                $this->ReceiveModel->rollback();
                $this->error($this->ReceiveModel->getError());
                exit;
            }
            // exit;

            // 更新客户数据
            $result = $this->model->isUpdate(true)->saveAll($businesslist);

            if ($result === false) {

                // 回滚事务
                $this->model->rollback();
                $this->ReceiveModel->rollback();
                $this->error($this->model->getError());
                exit;
            } else {

                // 提交事务
                $this->ReceiveModel->commit();
                $this->model->commit();
                $this->success('分配客户成功');
                exit;
            }
        }

        // 查询出所有的管理员
        $adminlist = $this->AdminModel->column('id, username');

        // 将数据赋值给模板
        $this->view->assign([
            'adminlist' => $adminlist,
            'row' => $row
        ]);

        // 渲染模板
        return $this->view->fetch();
    }

    // 客户公海删除管理
    public function del($ids = NULL)
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 接收数据
        $ids = !empty($ids) ? explode(',', $ids) : []; // 将字符串转换为数组
        $row = $this->model->all($ids); // 查询出所有的数据

        // 判断查询出的数据是否为空
        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 删除数据
        $result = $this->model->destroy($ids);

        // 判断删除是否成功
        if ($result) {
            $this->success('删除客户成功');
            exit;
        } else {
            $this->error($this->model->getError());
            exit;
        }
    }
}



?>