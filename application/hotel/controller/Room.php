<?php

namespace app\hotel\controller;

use think\Controller;

class Room extends Controller
{
    // 开启联表查询
    // protected $relationSearch = true;

    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->CouponModel = model('Hotel.Coupon');
        $this->GuestModel = model('Hotel.Guest');
        $this->ReceiveModel = model('Hotel.CouponReceive');
        $this->OrderModel = model('Hotel.Order');
        $this->model = model('Hotel.Room');
        $this->CollectModel = model('Hotel.Collection');
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $page = $this->request->param('page', '1', 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');
            $limit = 10;
            $start = ($page - 1) * $limit;
            $where = [];

            if (!empty($keywords)) {
                $where['name'] = ['LIKE', "%$keywords%"];
            }

            $room = $this->model
                ->where($where)
                ->limit($start, $limit)
                ->select();

            // 如果传入了用户id，就查询一下收藏状态
            $busid = $this->request->param('busid', 0, 'trim');

            if ($room) {
                foreach ($room as $key => $value) {
                    $collect = $this->CollectModel->where(['busid' => $busid, 'roomid' => $value['id']])->find();
                    $room[$key]['collect'] = $collect ? true : false;
                }
            }

            if ($room) {
                $this->success('返回列表', null, $room);
                exit;
            } else {
                $this->error('暂无数据');
                exit;
            }
        }
    }

    public function info()
    {
        if ($this->request->isPost()) {
            $rid = $this->request->param('rid', 0, 'trim');

            // 查询房间是否存在
            $room = $this->model->find($rid);

            if (!$room) {
                $this->error('暂无房间信息');
                exit;
            }

            // 查询一下房间的订单
            $where = [
                'roomid' => $rid,
                'status' => ['IN', ['1', '2']],
            ];

            $count = $this->OrderModel->where($where)->count();

            // 在数据中插入一个自定义的属性，用来表示是否可以预订
            $room['state'] = bcsub($room['total'], $count) <= 0 ? false : true;

            $this->success('返回房间信息', null, $room);
            exit;
        }
    }

    public function guest()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $list = $this->GuestModel->where(['busid' => $busid])->select();

            if ($list) {
                $this->success('返回住客信息', null, $list);
                exit;
            } else {
                $this->error('暂无住客信息');
                exit;
            }
        }
    }

    public function coupon()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');

            $list = $this->ReceiveModel
                ->with(['coupon'])
                ->where(['busid' => $busid, 'coupon_receive.status' => '1'])
                ->select();

            if ($list) {
                $this->success('返回优惠券信息', null, $list);
                exit;
            } else {
                $this->error('暂无优惠券信息');
                exit;
            }
        }
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $roomid = $this->request->param('roomid', 0, 'trim');
            $couponid = $this->request->param('couponid', 0, 'trim');
            $starttime = $this->request->param('starttime', 0, 'trim');
            $endtime = $this->request->param('endtime', 0, 'trim');
            $guest = $this->request->param('guest', NULL, 'trim');
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            $room = $this->model->find($roomid);

            if (!$room) {
                $this->error('房间不存在');
                exit;
            }

            // 房间是否可以在预约
            $where = [
                'roomid' => $roomid,
                'status' => ['IN', '1,2']
            ];
            $count = $this->OrderModel->where($where)->count();
            $update = bcsub($room['total'], $count);

            if ($update <= 0) {
                $this->error('该房型已全部预约');
                exit;
            }

            $where = [
                'busid' => $busid,
                'coupon_receive.id' => $couponid
            ];

            $receive = $this->ReceiveModel->with(['coupon'])->where($where)->find();

            if ($receive) {
                if ($receive['status'] == '0') {
                    $this->error('该优惠券已失效');
                    exit;
                }
            }

            // 先计算天数 先计算出价格
            $day = intval(($endtime - $starttime) / 86400);
            $price = $origin_price = bcmul($day, $room['price']);

            if ($receive) {
                $rate = isset($receive['coupon']['rate']) ? $receive['coupon']['rate'] : 1;
                $rate = floatval($rate);
                $price = bcmul($origin_price, $rate);
            }

            $UpdateMoney = bcsub($business['money'], $price);

            if ($UpdateMoney < 0) {
                $this->error('余额不足，请先充值');
                exit;
            }

            // 订单表 用户表 优惠券
            $this->OrderModel->startTrans();
            $this->BusinessModel->startTrans();
            $this->ReceiveModel->startTrans();
            $OrderData = [
                'busid' => $busid,
                'roomid' => $roomid,
                'guest' => $guest,
                'origin_price' => $origin_price,
                'price' => $price,
                'starttime' => $starttime,
                'endtime' => $endtime,
                'status' => '1',
                'coupon_receive_id' => $couponid ? $couponid : NULL
            ];

            // 插入订单表
            $OrderStatus = $this->OrderModel->save($OrderData);

            if ($OrderStatus === FALSE) {
                $this->error('插入预约订单失败');
                exit;
            }

            // 更新
            $BusinessData = [
                'id' => $busid,
                'money' => $UpdateMoney
            ];

            $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

            if ($BusinessStatus === FALSE) {
                $this->OrderModel->rollback();
                $this->error('更新用户余额失败');
                exit;
            }

            // 判断是否有用到优惠券
            if ($OrderData['coupon_receive_id']) {
                $ReceiveData = [
                    'id' => $couponid,
                    'status' => '0'
                ];

                $ReceiveStatus = $this->ReceiveModel->isUpdate(true)->save($ReceiveData);

                if ($ReceiveStatus === FALSE) {
                    $this->BusinessModel->rollback();
                    $this->OrderModel->rollback();
                    $this->error('更新优惠券状态失败');
                    exit;
                }
            }

            if ($OrderStatus === FALSE || $BusinessStatus === FALSE) {
                $this->ReceiveModel->rollback();
                $this->BusinessModel->rollback();
                $this->OrderModel->rollback();
                $this->error('预约失败');
                exit;
            } else {
                $this->OrderModel->commit();
                $this->BusinessModel->commit();
                $this->ReceiveModel->commit();
                $this->success('预约成功');
                exit;
            }
        }
    }

    // 收藏
    public function collect()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');

            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 接收参数
            $roomid = $this->request->param('rid', 0, 'trim');

            // 先查询收藏状态
            $where = [
                'busid' => $busid,
                'roomid' => $roomid
            ];

            $collect = $this->CollectModel->where($where)->find();

            if ($collect) {
                // 取消收藏
                $CollectStatus = $this->CollectModel->where($where)->delete();

                if ($CollectStatus === FALSE) {
                    $this->error($this->CollectModel->getError());
                    exit;
                } else {
                    $this->success('取消收藏成功');
                    exit;
                }
            } else {
                $CollectData = [
                    'busid' => $busid,
                    'roomid' => $roomid
                ];

                $CollectStatus = $this->CollectModel->save($CollectData);

                if ($CollectStatus === FALSE) {
                    $this->error($this->CollectModel->getError());
                    exit;
                } else {
                    $this->success('收藏成功');
                    exit;
                }
            }
        }
    }

    // 收藏列表
    public function collectList()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');
            $page = $this->request->param('page', '1', 'trim');
            $limit = $this->request->param('limit', '10', 'trim');
            $start = ($page - 1) * $limit;
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            $where = [
                'busid' => $busid
            ];

            if (!empty($keywords)) {
                $where['name'] = ['LIKE', "%$keywords%"];
            }

            $list = $this->CollectModel
                ->with(['room'])
                ->where($where)
                ->limit($start, $limit)
                ->select();

            if ($list) {
                $this->success('返回收藏列表', null, $list);
                exit;
            } else {
                $this->error('暂无收藏');
                exit;
            }
        }
    }

    // 查询评论
    public function comment()
    {
        if ($this->request->isPost()) {

            // 接收参数
            $rid = $this->request->param('rid', 0, 'trim');

            // 判断当前房间是否存在
            $room = $this->model->find($rid);

            if (!$room) {
                $this->error('房间不存在');
                exit;
            }

            $where = [
                'roomid' => $rid,
                'status' => '4'
            ];

            $list = $this->OrderModel
                ->with(['business'])
                ->where($where)
                ->order('rate', 'DESC')
                ->limit(10)
                ->select();

            // 去除用户密码和密码盐
            if ($list) {
                foreach ($list as $key => $value) {
                    unset($list[$key]['business']['password']);
                    unset($list[$key]['business']['salt']);
                }
            }

            if ($list) {
                $this->success('返回评论列表', null, $list);
                exit;
            } else {
                $this->error('该房间暂无评论');
                exit;
            }
        }
    }
}
