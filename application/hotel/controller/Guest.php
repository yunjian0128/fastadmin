<?php

namespace app\hotel\controller;

use think\Controller;

// 住客信息
class Guest extends Controller
{

    // 继承父类的初始化方法
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->model = model('Hotel.Guest');

        // 判断当前用户是否存在
        $this->busid = $this->request->param('busid', 0, 'trim');
        $this->business = $this->BusinessModel->find($this->busid);

        if (!$this->business) {
            $this->error('用户不存在');
            exit;
        }
    }

    // 住客信息列表
    public function index()
    {
        // 判断是否是post请求
        if ($this->request->isPost()) {
            $page = $this->request->param('page', 1, 'trim');
            $limit = 10;
            $start = ($page - 1) * $limit;

            $list = $this->model
                ->where(['busid' => $this->busid])
                ->order('id desc')
                ->limit($start, $limit)
                ->select();

            if ($list) {
                $this->success('住客信息', null, $list);
                exit;
            } else {
                $this->error('暂无住客信息');
                exit;
            }
        }
    }

    // 添加住客信息
    public function add()
    {
        // 判断是否是post请求
        if ($this->request->isPost()) {

            // 一次性接受全部数据
            $data = $this->request->param();

            // 插入数据
            $result = $this->model->allowField(true)->save($data); // allowField(true) 过滤非数据表字段

            if ($result) {
                $this->success('添加住客信息成功');
                exit;
            } else {
                $this->error($this->model->getError());
                exit;
            }
        }
    }

    // 住客信息详情
    public function info()
    {
        // 判断是否是post请求
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'trim');

            $guest = $this->model->find($id);

            if ($guest === FALSE) {
                $this->error('暂无住客信息');
                exit;
            } else {
                $this->success('住客信息', null, $guest);
                exit;
            }
        }
    }

    // 编辑住客信息
    public function edit()
    {
        // 判断是否是post请求
        if ($this->request->isPost()) {
            // 获取所有的参数
            $params = $this->request->param();

            // 先判断住客信息是否存在
            $guest = $this->model->find($params['id']);

            if (!$guest) {
                $this->error('住客信息不存在');
                exit;
            }

            $result = $this->model->allowField(true)->isUpdate(true)->save($params);

            if ($result === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('编辑住客信息成功');
                exit;
            }
        }
    }

    // 删除住客信息
    public function del()
    {

        // 判断是否是post请求
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'trim');

            $guest = $this->model->find($id);

            if ($guest === FALSE) {
                $this->error('暂无住客信息');
                exit;
            }

            $result = $this->model->where(['id' => $id])->delete();

            if ($result === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('删除住客信息成功');
                exit;
            }
        }
    }
}

?>