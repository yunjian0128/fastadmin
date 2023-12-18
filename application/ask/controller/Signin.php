<?php
namespace app\ask\controller;

use think\Controller;

class Signin extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->model = model('Signin');
        $this->BusinessModel = model('Business.Business');

    }

    // 签到天数
    public function index()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $date = $this->request->param('date', date("Y-m"), 'trim');

            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 开始时间和结束时间
            $start = date("Y-m-01", strtotime($date));
            $end = date("Y-m-t", strtotime($date));

            $list = $this->model
                ->where(['busid' => $busid])
                ->whereTime('createtime', 'between', [$start, $end])
                ->order('createtime', 'asc')
                ->select();

            if (!$list) {
                $this->error('本月暂无签到记录');
                exit;
            }

            $this->success('签到记录', null, $list);
            exit;
        }
    }

    // 签到
    public function add()
    {
        // 判断是否是post请求
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');

            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 查询今天是否签到
            $start = strtotime(date("Y-m-d") . "00:00:00");
            $end = strtotime(date("Y-m-d") . "23:59:59");

            $check = $this->model
                ->where(['busid' => $busid])
                ->whereTime('createtime', 'between', [$start, $end])
                ->find();

            if ($check) {
                $this->error('今天已经签到过了');
                exit;
            }

            // 签到 开启事务
            $this->model->startTrans();
            $this->BusinessModel->startTrans();

            // 插入签到表
            $SigninStatus = $this->model->save(['busid' => $busid]);

            if ($SigninStatus === FALSE) {
                $this->error($this->model->getError());
                exit;
            }

            $point = $business['point'];
            $point = intval($point) >= 0 ? intval($point) : 0;
            $point = $point + 1;
            $BusData = [
                'id' => $busid,
                'point' => $point
            ];

            // 更新用户积分
            $BusStatus = $this->BusinessModel->isUpdate(true)->save($BusData);

            if ($BusStatus === FALSE) {
                $this->model->rollback();
                $this->error($this->BusinessModel->getError());
                exit;
            }

            // 大判断
            if ($SigninStatus && $BusStatus) {
                $this->model->commit();
                $this->BusinessModel->commit();
                $this->success('签到成功');
                exit;
            } else {
                $this->BusinessModel->rollback();
                $this->model->rollback();
                $this->error('签到失败');
                exit;
            }
        }
    }
}

?>