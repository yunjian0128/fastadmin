<?php

namespace app\hotel\controller;

use think\Controller;

class Coupon extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->model = model('Hotel.Coupon');
        $this->ReceiveModel = model('Hotel.CouponReceive');
    }

    // 优惠券列表
    public function index()
    {
        $list = $this->model->where(['status' => '1'])->order('createtime', 'desc')->limit(5)->select();

        if ($list) {
            $this->success('', null, $list);
            exit;
        } else {
            $this->error('无优惠活动');
            exit;
        }
    }

    // 优惠券详情
    public function info()
    {
        if ($this->request->isPost()) {
            $cid = $this->request->param('cid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');

            $coupon = $this->model->find($cid);

            if ($coupon === FALSE) {
                $this->error('暂无优惠券信息');
                exit;
            }

            $business = $this->BusinessModel->find($busid);

            if ($business) {
                // 查看当前用户是否有领取过优惠券
                $check = $this->ReceiveModel->where(['cid' => $cid, 'busid' => $busid])->find();

                // 领取状态
                $coupon['receive'] = $check ? true : false;
            } else {
                // 可领取状态true 不能领取false
                $coupon['receive'] = false;
            }

            // 查找领取的用户列表
            $receive = $this->ReceiveModel->with(['business'])->where(['cid' => $cid])->select();

            $data = [
                'coupon' => $coupon,
                'receive' => $receive
            ];

            $this->success('返回优惠券信息', null, $data);
            exit;
        }
    }

    // 领取优惠券
    public function receive()
    {
        if ($this->request->isPost()) {
            $cid = $this->request->param('cid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');

            $coupon = $this->model->find($cid);

            if ($coupon === FALSE) {
                $this->error('暂无优惠券信息');
                exit;
            }

            if ($coupon['status'] == '0') {
                $this->error('活动已结束');
                exit;
            }

            if ($coupon['total'] <= '0') {
                $this->error('优惠券剩余0张，无法领取');
                exit;
            }

            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 查看当前用户是否有领取过优惠券
            $check = $this->ReceiveModel->where(['cid' => $cid, 'busid' => $busid])->find();

            if ($check) {
                $this->error('已领取过优惠券，无法重复领取');
                exit;
            }


            // 开启事务
            $this->model->startTrans();
            $this->ReceiveModel->startTrans();

            // 插入领取记录
            $ReceiveData = [
                'cid' => $cid,
                'busid' => $busid,
                'status' => '1'
            ];

            $ReceiveStatus = $this->ReceiveModel->save($ReceiveData);

            if ($ReceiveStatus === FALSE) {
                $this->error($this->ReceiveModel->getError());
                exit;
            }

            $total = bcsub($coupon['total'], 1);
            $total = $total <= 0 ? 0 : $total;

            // 优惠券数量
            $CouponData = [
                'id' => $cid,
                'total' => $total
            ];

            $CouponStatus = $this->model->isUpdate(true)->save($CouponData);

            if ($CouponStatus === FALSE) {
                $this->ReceiveModel->rollback();
                $this->error($this->model->getError());
                exit;
            }

            if ($ReceiveStatus === FALSE || $CouponStatus === FALSE) {
                $this->model->rollback();
                $this->ReceiveModel->rollback();
                $this->error('领取优惠券失败');
                exit;
            } else {
                $this->ReceiveModel->commit();
                $this->model->commit();
                $this->success('领取优惠券成功');
                exit;
            }
        }
    }

    // 用户已经领取的优惠券
    public function business()
    {
        // 判断是否是post请求
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $page = $this->request->param('page', 1, 'intval');
            $status = $this->request->param('status', '', 'trim');
            $limit = $this->request->param('limit', 10, 'intval');
            $start = ($page - 1) * $limit;

            // var_dump($this->request->param());
            // exit;

            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            $where = ['busid' => $busid];

            if (!empty($status)) {
                $where['coupon_receive.status'] = $status;
            }

            $list = $this->ReceiveModel
                ->with(['coupon'])
                ->where($where)
                ->limit($start, $limit)
                ->select();

            if ($list) {
                $this->success('已经领取的优惠券', null, $list);
                exit;
            } else {
                $this->error('暂无优惠券');
                exit;
            }
        }
    }

    // 优惠券列表
    public function list()
    {
        // 判断是否是post请求
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 10, 'intval');
            $start = ($page - 1) * $limit;

            $list = $this->model
                ->where(['status' => '1'])
                ->limit($start, $limit)
                ->select();

            if ($list) {
                $this->success('优惠券列表', null, $list);
                exit;
            } else {
                $this->error('暂无优惠券');
                exit;
            }
        }
    }
}

?>