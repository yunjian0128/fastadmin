<?php

namespace app\admin\controller\Pay;

use app\common\controller\Backend;

/**
 * 支付订单管理
 *
 * @icon fa fa-circle-o
 */
class Pay extends Backend {

    /**
     * Pay模型对象
     * @var \app\common\model\pay\Pay
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\common\model\Pay\Pay;
        $this->view->assign("paytypeList", $this->model->getPaytypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function index() {
        // 设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否有Ajax请求
        if($this->request->isAjax()) {
            if($this->request->request('keyField')) {
                return $this->selectpage();
            }

            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = ['total' => $list->total(), 'rows' => $list->items()];
            return json($result);
        }

        // 输出视图
        return $this->view->fetch();
    }

    public function del($ids = null) {
        $ids = $ids ?: $this->request->param('ids', 0, 'trim');
        $list = $this->model->select($ids);

        if(!$list) {
            $this->error('请选择需要删除订单');
        }

        $result = $this->model->destroy($ids);

        if($result === false) {
            $this->error('删除失败');
        } else {
            $this->success('删除成功');
        }

    }

    // 补单
    public function supplementary($ids = null) {
        $ids = $ids ?: $this->request->param('ids', 0, 'trim');
        $pay = $this->model->find($ids);

        if(!$pay) {
            $this->error('需要补单的订单不存在');
        }

        $data = $pay->toArray();
        $result = httpRequest($pay['callbackurl'], $data);

        if($result === false) {
            $this->error('补单异常');
        }

        $res = json_decode($result, true);
        $code = $res['code'] ?? 0;

        if($code === 0) {
            $this->error('补单失败');
        } else {

            $PayData = [
                'id' => $ids,
                'status' => 1,
            ];

            $result = $this->model->isUpdate(true)->save($PayData);

            if($result === false) {
                $this->error('补单失败');
            } else {
                $this->success('补单成功');
            }
        }
    }
}
