<?php

namespace app\admin\controller\Business;

use app\common\controller\Backend;

/**
 * 客户私海管理控制器
 * @package app\admin\controller\Business
 */

class Privatesea extends Backend
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
        $this->SourceModel = model('Business.Source');
        $this->RegionModel = model('Region');

        // 查询出所有客户来源
        $Sourcelist = $this->SourceModel->column('id,name');

        // 将数据赋值给模板
        $this->view->assign('Sourcelist', $Sourcelist);
    }

    // 客户私海列表
    public function index()
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否有ajax请求
        if ($this->request->isAjax()) {

            // 获取表格所提交的参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams(); // buildparams()方法在Backend控制器中
            $where = ['adminid' => $this->auth->id];


            // 表格需要两个参数，total和rows，total为数据总数，rows为分页数据
            // 获取数据总数
            $total = $this->model
                ->with(['source', 'admin'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            // 获取客户私海列表
            $list = $this->model
                ->with(['source', 'admin'])
                ->where($where)
                ->order('id', 'desc')
                ->limit($offset, $limit)
                ->select();

            // 组装数据
            $data = [
                'total' => $total,
                'rows' => $list,
            ];

            // 返回数据
            return json($data);
        }

        // 渲染视图
        return $this->view->fetch();
    }

    // 客户私海添加
    public function add()
    {
        // 获取表单提交的数据
        if ($this->request->isPost()) {

            // 接收row前缀的数据，并接收为数组类型
            $row = $this->request->post('row/a');
            $salt = randstr();
            $password = md5($row['mobile'] . $salt);

            // 组装数据
            $data = [
                'nickname' => $row['nickname'],
                'mobile' => $row['mobile'],
                'gender' => $row['gender'],
                'sourceid' => $row['sourceid'],
                'email' => $row['email'],
                'deal' => $row['deal'],
                'adminid' => $this->auth->id,
                'password' => $password,
                'salt' => $salt,
                'auth' => $row['auth'],
                'money' => $row['money']
            ];

            // 选择地区
            if (!empty($row['code'])) {

                $parentpath = $this->RegionModel->where('code', $row['code'])->value('parentpath');

                if (!$parentpath) {
                    $this->error('所选的地区不存在，请重新选择');
                    exit;
                }

                $path = explode(',', $parentpath);
                $province = $path[0] ?? null;
                $city = $path[1] ?? null;
                $district = $path[2] ?? null;
                $data['province'] = $province;
                $data['city'] = $city;
                $data['district'] = $district;
            }

            // 开启事务
            $this->model->startTrans();
            $this->ReceiveModel->startTrans();

            $result = $this->model->validate("common/Business/Business.register")->save($data);

            if ($result === FALSE) {
                $this->error($this->model->getError());
                exit;
            }

            // 封装领取数据
            $Receivelist = [
                'applyid' => $this->auth->id,
                'status' => 'allot',
                'busid' => $this->model->id
            ];

            // 插入领取表
            $result = $this->ReceiveModel->validate('common/Business/Receive')->save($Receivelist);

            if ($result === FALSE) {
                $this->model->rollback();
                $this->error($this->ReceiveModel->getError());
                exit;
            }

            // 提交事务
            $this->model->commit();
            $this->ReceiveModel->commit();
            $this->success('添加客户成功');
            exit;
        }

        // 渲染页面
        return $this->view->fetch();
    }

    // 客户私海编辑
    public function edit($ids = null)
    {
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error(__('未找到当前用户'));
        }

        if ($this->request->isPost()) {

            $params = $this->request->param("row/a");

            $data = [
                'id' => $ids,
                'nickname' => $params['nickname'],
                'mobile' => $params['mobile'],
                'email' => $params['email'],
                'gender' => $params['gender'],
                'sourceid' => $params['sourceid'],
                'deal' => $params['deal'],
                'auth' => $params['auth'],
                'money' => $params['money']
            ];

            if ($params['email'] != $row['email']) {
                $data['auth'] = 0;
            }

            // 修改密码
            if (!empty($params['password'])) {
                $salt = randstr();
                $data['salt'] = $salt;
                $data['password'] = md5($params['password'] . $salt);
            }

            // 修改省市区
            if (!empty($params['code'])) {

                $parentpath = $this->RegionModel->where('code', $params['code'])->value('parentpath');

                if (!$parentpath) {
                    $this->error('所选地区不存在，请重新输入');
                }

                $path = explode(',', $parentpath);

                $province = $path[0] ?? null;
                $city = $path[1] ?? null;
                $district = $path[2] ?? null;
                $data['province'] = $province;
                $data['city'] = $city;
                $data['district'] = $district;
            }

            $result = $this->model->validate("common/Business/Business.profile")->isUpdate(true)->save($data);

            if ($result === false) {
                $this->error($this->model->getError());
            }

            $this->success("修改客户成功");
        }

        // 处理地区数据回显
        $row['regionCode'] = $row['district'] ?: ($row['city'] ?: $row['province']);

        $this->assign('row', $row);

        // 渲染页面
        return $this->view->fetch();
    }

    // 客户私海删除
    public function del($ids = null)
    {
        $ids = $this->request->param('ids', 0, 'trim');

        $row = $this->model->find($ids);

        if (!$row) {
            $this->error(__('未找到当前用户'));
        }

        // 删除客户数据
        $result = $this->model->destroy($ids);

        if ($result === FALSE) {
            $this->error($this->model->getError());
            exit;
        }

        $this->success('删除客户成功');
        exit;
    }

    // 客户回收
    public function recovery($ids = NULL)
    {
        $ids = !empty($ids) ? explode(',', $ids) : [];
        $row = $this->model->column('id');

        foreach ($ids as $item) {
            if (!in_array($item, $row)) {
                $this->error(__('没有找到该用户'));
                exit;
            }
        }
        $ReceiveData = [];
        $businessData = [];
        foreach ($ids as $value) {

            $ReceiveData[] = [
                'applyid' => $this->auth->id,
                'status' => 'recovery',
                'busid' => $value
            ];

            $businessData[] = [
                'id' => $value,
                'adminid' => null
            ];
        }

        $this->model->startTrans();
        $this->ReceiveModel->startTrans();

        // 更新客户表
        $BusinessStatus = $this->model->saveAll($businessData);

        if ($BusinessStatus === FALSE) {
            $this->error($this->model->getError());
        }

        // 插入领取表
        $ReceiveStatus = $this->ReceiveModel->saveAll($ReceiveData);

        if ($ReceiveStatus === FALSE) {
            $this->model->rollback();
            $this->error($this->ReceiveModel->getError());
        }

        if ($ReceiveStatus === FALSE || $BusinessStatus === FALSE) {
            $this->model->rollback();
            $this->ReceiveModel->rollback();
            $this->error('回收失败');
            exit;
        } else {
            $this->model->commit();
            $this->ReceiveModel->commit();
            $this->success('回收成功');
            exit;
        }
    }
}

?>