<?php

namespace app\admin\controller\Hotel;

// 引入公共控制器
use app\common\controller\Backend;

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
     * @var \app\common\model\Hotel\Order
     */

    // 当前模型
    protected $model = NULL;

    public function __construct()
    {
        // 继承父类构造函数
        parent::__construct();

        // 将控制器和模型关联
        $this->model = model('Hotel.Order');
        $this->BusinessModel = model('Business.Business');

        // 根据订单状态分类订单
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function index()
    {
        // 设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否有Ajax请求
        if ($this->request->isAjax()) {

            // 获取表格所提交的参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 获取数据总数
            $total = $this->model->count();

            // 获取分页数据
            $list = $this->model
                ->with(['business', 'room'])
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

    public function info($ids = NULL)
    {
        // 关联查询
        $row = $this->model->with(['business', 'room', 'couponreceive'])->find($ids);

        if (!$row) {
            $this->error('订单不存在');
            exit;
        }

        // 查询优惠券
        if ($row['couponreceive']) {
            $coupon = model('Hotel.Coupon')->find($row['couponreceive']['cid']);
        } else {
            $coupon = null;
        }

        // 将查到的优惠券信息插入到订单信息中
        $row['coupon'] = $coupon;

        $this->assign([
            'row' => $row
        ]);

        return $this->fetch();
    }

    // 软删除
    public function del($ids = NULL)
    {
        $ids = $ids ?: $this->request->params('ids', '', 'trim');

        $row = $this->model->where('id', 'in', $ids)->select();

        if (!$row) {
            $this->error('请选择需要删除的订单');
        }

        $result = $this->model->destroy($ids);

        if ($result === false) {
            $this->error($this->model->getError());
        } else {
            $this->success('删除成功');
        }
    }

    // 已入住
    public function checkin($ids = NULL)
    {
        // 接收参数
        $ids = $ids ?: $this->request->param('ids', 0, 'trim');

        // 查询订单是否存在
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error('订单不存在');
        }

        // 判断订单状态
        if ($row['status'] != "1") {
            $this->error('订单状态不允许入住');
        }

        // 封装数据
        $data = [
            'id' => $ids,
            'status' => "2"
        ];

        // 更新订单状态
        $result = $this->model->isUpdate(true)->save($data);

        if ($result === false) {
            $this->error($this->model->getError());
        } else {
            $this->success('入住成功');
        }
    }

    // 已退房
    public function checkout($ids = NULL)
    {
        // 接收参数
        $ids = $ids ?: $this->request->param('ids', 0, 'trim');

        // 查询订单是否存在
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error('订单不存在');
        }

        // 判断订单状态
        if ($row['status'] != "2") {
            $this->error('订单状态不允许退房');
        }

        // 封装数据
        $data = [
            'id' => $ids,
            'status' => "3"
        ];

        // 更新订单状态
        $result = $this->model->isUpdate(true)->save($data);

        if ($result === false) {
            $this->error($this->model->getError());
        } else {
            $this->success('退房成功');
        }
    }

    // 允许退款
    public function allow($ids = NULL)
    {
        // 接收参数
        $ids = $ids ?: $this->request->param('ids', 0, 'trim');

        // 查询订单是否存在
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error('订单不存在');
        }

        // 判断订单状态
        if ($row['status'] != -1) {
            $this->error('订单状态不允许退款');
        }

        // 开启事务
        $this->model->startTrans();
        $this->BusinessModel->startTrans();

        // 封装数据
        $data = [
            'id' => $ids,
            'status' => -2
        ];

        // 更新订单状态
        $result = $this->model->isUpdate(true)->save($data);

        if ($result === false) {
            $this->error('更新订单状态失败');
        }

        // 查询用户信息
        $business = $this->BusinessModel->find($row['busid']);

        if (!$business) {
            $this->error('用户不存在');
        }

        // 更新用户余额
        $BusinessData = [
            'id' => $business['id'],
            'money' => bcadd($row['price'], $business['money'], 2)
        ];

        $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

        if ($BusinessStatus === false) {

            // 回滚事务
            $this->model->rollback();
            $this->error('更新用户余额失败');
        }

        if ($result === false || $BusinessStatus === false) {

            // 回滚事务
            $this->model->rollback();
            $this->BusinessModel->rollback();
            $this->error('允许退款失败');
        } else {

            // 提交事务
            $this->model->commit();
            $this->BusinessModel->commit();
            $this->success('允许退款成功');
        }
    }

    // 拒绝退款
    public function refuse($ids = NULL)
    {
        // 接收参数
        $ids = $ids ?: $this->request->param('ids', 0, 'trim');

        // 查询订单是否存在
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error('订单不存在');
        }

        // 判断订单状态
        if ($row['status'] != -1) {
            $this->error('订单状态不允许退款');
        }

        // 封装数据
        $data = [
            'id' => $ids,
            'status' => -3
        ];

        // 更新订单状态
        $result = $this->model->isUpdate(true)->save($data);

        if ($result === false) {
            $this->error($this->model->getError());
        } else {
            $this->success('拒绝退款成功');
        }
    }
}

?>