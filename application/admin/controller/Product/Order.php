<?php

namespace app\admin\controller\Product;

use app\common\controller\Backend; // 引入公共控制器

/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{
    // 开启关联查询
    protected $relationSearch = true;

    /**
     * Order模型对象
     * @var \app\common\model\Product\Order
     */
    protected $model = null;

    // 订单商品模型
    protected $OrderProductModel = null;

    public function __construct()
    {
        // 继承父类构造函数
        parent::__construct();

        // 将控制器和模型关联
        $this->model = model('Product.Order');
        $this->OrderProductModel = model('Product.OrderProduct');

        // 根据订单状态分类订单
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否有Ajax请求
        if ($this->request->isAjax()) {

            // 获取表格所提交的参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 获取数据总数
            $total = $this->model
                ->with(['express', 'business'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            // 获取分页数据
            $list = $this->model
                ->with(['express', 'business'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 返回数据
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        // 输出视图
        return $this->view->fetch();
    }

    public function info($ids = null)
    {
        // 关联查询
        $row = $this->model->with(['business', 'express', 'address' => ['provinces', 'citys', 'districts'], 'sale', 'review', 'dispatched'])->find($ids);

        if (!$row) {
            $this->error('订单不存在');
            exit;
        }

        $OrderProductData = $this->OrderProductModel->with(['products'])->where(['orderid' => $ids])->select();

        $this->assign([
            'row' => $row,
            'OrderProductData' => $OrderProductData
        ]);

        return $this->fetch();
    }

    // 发货
    public function deliver($ids = null)
    {
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error('订单不存在');
        }


        // 处理提交表单
        if ($this->request->isPost()) {
            $params = $this->request->param('row/a');

            // 封装数据
            $data = [
                'id' => $ids,
                'expressid' => $params['expressid'],
                'expresscode' => $params['expresscode'],
                'shipmanid' => $this->auth->id,
                'status' => 2
            ];

            // 定义验证器
            $validate = [
                [
                    'expressid' => 'require',
                    'expresscode' => 'require|unique:order'
                ],
                [
                    'expressid.require' => '配送物流未知',
                    'expresscode.unique' => '配送物流单号已存在，请重新输入',
                    'expresscode.require' => '请输入配送物流单号'
                ]
            ];

            $result = $this->model->validate(...$validate)->isUpdate(true)->save($data);

            if ($result === false) {
                $this->error($this->model->getError());
            } else {
                $this->success('发货成功');
            }
        }


        // 查询物流公司的数据
        $ExpData = model('Express')->column('id,name');

        $this->assign([
            'ExpData' => $ExpData,
            'row' => $row
        ]);

        return $this->fetch();
    }

    // 软删除
    public function del($ids = null)
    {
        $ids = $ids ?: $this->request->params('ids', '', 'trim');

        $row = $this->model->where('id', 'in', $ids)->select();

        if (!$row) {
            $this->error('请选择需要删除的订单');
        }

        $result = $this->model->destroy($ids);

        if ($result === false) {
            $this->error('删除失败');
        } else {
            $this->success('删除成功');
        }
    }

    public function refund($ids = null)
    {
        $ids = $ids ?: $this->request->param('ids', 0, 'trim');

        $row = $this->model->find($ids);

        if (!$row) {
            $this->error('订单不存在');
        }

        if ($this->request->isPost()) {
            $params = $this->request->param('row/a');

            if (empty($params['examinereason']) && $params['refund'] == 0) {
                $this->error('请填写不同意退货的原因');
            }

            // 同意仅退款
            if ($params['refund'] === '1' && $row['status'] === '-1') {
                $BusinessModel = model('Business.Business');

                $business = $BusinessModel->find($row['busid']);

                if (!$business) {
                    $this->error('用户不存在');
                }

                // 开启事务
                $BusinessModel->startTrans();
                $this->model->startTrans();

                // 更新用户余额
                $BusinessData = [
                    'id' => $business['id'],
                    'money' => bcadd($row['amount'], $business['money'], 2)
                ];

                $BusinessStatus = $BusinessModel->isUpdate(true)->save($BusinessData);

                if ($BusinessStatus === false) {
                    $this->error('更新用户余额失败');
                }

                // 更新订单的状态
                $OrderData = [
                    'id' => $ids,
                    'status' => -4,
                ];

                $OrderStatus = $this->model->isUpdate(true)->save($OrderData);

                if ($OrderStatus === false) {
                    $BusinessModel->rollback();
                    $this->error('更新订单状态失败');
                }

                if ($BusinessStatus === false || $OrderStatus === false) {
                    $BusinessModel->rollback();
                    $this->model->rollback();
                    $this->error('同意退款失败');
                } else {
                    $BusinessModel->commit();
                    $this->model->commit();
                    $this->success('同意退款成功');
                }
            }

            // 不同意退货
            if ($params['refund'] === '0') {
                // 封装数据
                $data = [
                    'id' => $ids,
                    'status' => -5,
                    'examinereason' => $params['examinereason']
                ];

                $result = $this->model->isUpdate(true)->save($data);

                if ($result === false) {
                    $this->error();
                } else {
                    $this->success();
                }
            }

            // 同意退款退货
            if ($params['refund'] === '1' && $row['status'] === '-2') {
                // 封装数据
                $data = [
                    'id' => $ids,
                    'status' => -3
                ];

                $result = $this->model->isUpdate(true)->save($data);

                if ($result === false) {
                    $this->error();
                } else {
                    $this->success();
                }
            }
        }

        $this->assign([
            'row' => $row
        ]);

        return $this->fetch();
    }
}
