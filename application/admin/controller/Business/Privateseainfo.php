<?php

namespace app\admin\controller\business;

use app\common\controller\Backend;

/**
 * 
 */
class Privateseainfo extends Backend
{
    // 关联查询
    protected $relationSearch = true;

    /**
     * 当前控制器下的一个模型属性
     */
    protected $model = null;

    // 客户模型
    protected $BusinessModel = null;

    // 领取记录模型
    protected $ReceiveModel = null;

    // 初始化
    public function _initialize()
    {
        parent::_initialize();

        // 全局用户模型
        $this->BusinessModel = model('Business.Business');

        // 回访表
        $this->model = model('Business.Visit');
        $this->ReceiveModel = model('Business.Receive');
    }

    // 客户详情
    public function index()
    {
        $ids = $this->request->param('ids', 0, 'trim');
        $row = $this->BusinessModel->find($ids);

        if (!$row) {
            $this->error('客户不存在，请重新选择');
        }

        $this->assign([
            'row' => $row
        ]);

        return $this->fetch();
    }

    // 回访列表
    public function visit($ids = null)
    {
        // 设置过滤方法
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->with(['admin', 'business'])
                ->where($where)
                ->where('busid', $ids)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->count(); 

            $list = $this->model
                ->with(['admin', 'business'])
                ->where($where)
                ->where('busid', $ids)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select(); 

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
    }

    // 添加回访记录
    public function add($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            $params['busid'] = $ids;
            $params['adminid'] = $this->auth->id;

            if ($params) {
                $result = $this->model->validate("Common/Business/Visit")->save($params);
                if ($result === false) {
                    $this->error($this->model->getError());
                    exit;
                }
                $this->success("添加内容成功");
                exit;
            }
        }

        return $this->view->fetch();
    }

    // 编辑回访记录
    public function edit($ids = null)
    {
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error(__('未找到当前回访记录'));
            exit;
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            if ($params) {
                $params['id'] = $ids;
                $params['busid'] = $row['busid'];
                $params['adminid'] = $row['adminid'];
                $result = $this->model->validate("Common/Business/Visit")->isUpdate(true)->save($params);

                if ($result === false) {
                    $this->error($this->model->getError());
                    exit;
                }

                $this->success("编辑内容成功");
                exit;
            }
        }

        $this->assign("row", $row);

        return $this->view->fetch();
    }

    // 删除回访记录
    public function del($ids = null)
    {
        $ids = !empty($ids) ? explode(',', $ids) : [];

        $row = $this->model->column('id');

        foreach ($ids as $item) {
            if (!in_array($item, $row)) {
                $this->error(__('没有找到该回访记录'));
                exit;
            }
        }

        $result = $this->model->destroy($ids);

        if ($result === false) {
            $this->error($this->model->getError());
            exit;
        }

        $this->success("删除成功");
        exit;
    }

    // 申请列表
    public function receive($ids = null)
    {
        // 设置过滤方法
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->ReceiveModel
                ->with(['admin', 'business'])
                ->where($where)
                ->where('busid', $ids)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->count(); //查询总数

            $list = $this->ReceiveModel
                ->with(['admin', 'business'])
                ->where($where)
                ->where('busid', $ids)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select(); //查询数据

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }
}

?>
