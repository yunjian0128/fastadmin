<?php

namespace app\hotel\controller;

use think\Controller;

class Order extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->CouponModel = model('Hotel.Coupon');
        $this->GuestModel = model('Hotel.Guest');
        $this->ReceiveModel = model('Hotel.CouponReceive');
        $this->OrderModel = model('Hotel.Order');
        $this->model = model('Hotel.Room');

        // 判断当前用户是否存在
        $this->busid = $this->request->param('busid', 0, 'trim');
        $this->business = $this->BusinessModel->find($this->busid);

        if (!$this->business) {
            $this->error('用户不存在');
            exit;
        }
    }

    // 列表
    public function index()
    {
        if ($this->request->isPost()) {
            $page = $this->request->param('page', '1', 'trim');
            $status = $this->request->param('status', '', 'trim');
            $limit = 10;
            $start = ($page - 1) * $limit;
            $where = ['busid' => $this->busid];

            if (!empty($status)) {
                $where['status'] = $status;
            }

            $list = $this->OrderModel
                ->with(['room'])
                ->where($where)
                ->limit($start, $limit)
                ->select();

            if ($list) {
                $this->success('返回列表', null, $list);
                exit;
            } else {
                $this->error('暂无数据');
                exit;
            }
        }
    }

    // 订单详情
    public function info()
    {
        if ($this->request->isPost()) {
            $orderid = $this->request->param('orderid', 0, 'trim');

            // 查询房间是否存在
            $order = $this->OrderModel->with(['room'])->find($orderid);

            // 查询住客信息
            $guestids = empty($order['guest']) ? "" : trim($order['guest']);
            $guest = $this->GuestModel->where(['id' => ['IN', $guestids]])->find();

            $data = [
                'order' => $order,
                'guest' => $guest
            ];

            if ($order) {
                $this->success('返回订单信息', null, $data);
                exit;
            } else {
                $this->error('暂无订单信息');
                exit;
            }
        }
    }

    // 订单数量
    public function count()
    {
        if ($this->request->isPost()) {
            $where = ['busid' => $this->busid];

            // 查询三种状态的订单数量
            $where['status'] = 1;
            $checkin = $this->OrderModel->where($where)->count();

            $where['status'] = -1;
            $refund = $this->OrderModel->where($where)->count();

            $where['status'] = 3;
            $comment = $this->OrderModel->where($where)->count();

            $data = [
                'checkin' => $checkin,
                'refund' => $refund,
                'comment' => $comment
            ];

            if ($data) {
                $this->success('返回订单数量', null, $data);
                exit;
            } else {
                $this->error('暂无订单信息');
                exit;
            }
        }
    }

    // 退款
    public function refund()
    {
        // 接收参数
        $orderid = $this->request->param('orderid', 0, 'trim');

        // 查询订单是否存在
        $order = $this->OrderModel->find($orderid);

        if (!$order) {
            $this->error('订单不存在');
            exit;
        }

        // 判断订单状态
        if ($order['status'] != 1) {
            $this->error('订单状态不允许退款');
            exit;
        }

        // 如果距离入住时间小于6小时，不允许退款
        $now = time();
        $starttime = $order['starttime'];

        if ($starttime - $now < 21600) {
            $this->error('距离入住时间小于6小时，不允许退款');
            exit;
        }

        // 修改订单状态
        $OrderStatus = $this->OrderModel->where(['id' => $orderid])->update(['status' => -1]);

        if ($OrderStatus === FALSE) {
            $this->error($this->OrderModel->getError());
            exit;
        }

        $this->success('取消订单申请成功，请耐心等待审核结果');
    }

    // 评价
    public function comment()
    {
        if ($this->request->isPost()) {
            $orderid = $this->request->param('orderid', 0, 'trim');
            $comment = $this->request->param('comment', '', 'trim');
            $rate = $this->request->param('rate', 5.0, 'trim');

            $order = $this->OrderModel->find($orderid);

            if (!$order) {
                $this->error('订单不存在');
                exit;
            }

            if ($order['status'] == '4') {
                $this->error('无须重复评价');
                exit;
            } else if ($order['status'] != '3') {
                $this->error('状态有误，暂时无法评价');
                exit;
            }

            // 更新语句
            $data = [
                'id' => $orderid,
                'status' => '4',
                'comment' => $comment,
                'rate' => $rate
            ];

            // 将rate转换为1位小数
            $data['rate'] = number_format(floatval($data['rate']), 1, '.', '');

            // 获取当前时间戳
            $now = time();
            $data['commenttime'] = $now;
            $result = $this->OrderModel->isUpdate(true)->save($data);

            if ($result === FALSE) {
                $this->error($this->OrderModel->getError());
                exit;
            } else {
                $this->success('评论成功');
                exit;
            }
        }
    }
}
