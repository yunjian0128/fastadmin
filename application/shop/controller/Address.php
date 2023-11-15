<?php

namespace app\shop\controller;

use think\Controller;

// 引入FastAdmin自带的一个邮箱发送类

/**
 * 用户收货地址接口
 */
class Address extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->AddressModel = model('Business.Address');

        $busid = $this->request->param('busid', '0', 'trim');

        // 查询
        $this->business = $this->BusinessModel->where(['id' => $busid])->find();

        if (!$this->business) {
            $this->error('用户不存在');
            exit;
        }
    }

    // 收货地址的列表查询
    public function index()
    {
        if ($this->request->isPost()) {

            // 找出当前用户的地址
            $address = $this->AddressModel->where(['busid' => $this->business['id']])->select();

            if ($address) {
                $this->success('查询收货地址', null, $address);
                exit;
            } else {
                $this->error('暂无地址');
                exit;
            }
        }
    }

    // 添加收货地址
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->param();
            $code = $this->request->param('code', '', 'trim');
            $status = $this->request->param('status', '0', 'trim');

            // 判断是否有地区数据
            if (!empty($code)) {

                // 查询省市区的地区码出来
                $parent = model('Region')->where(['code' => $code])->value('parentpath');

                if (!empty($parent)) {
                    $arr = explode(',', $parent);
                    $params['province'] = isset($arr[0]) ? $arr[0] : null;
                    $params['city'] = isset($arr[1]) ? $arr[1] : null;
                    $params['district'] = isset($arr[2]) ? $arr[2] : null;
                }
            }

            //开启事务
            $this->AddressModel->startTrans();

            //判断是否选择了默认收货地址
            if ($status == '1') {
                // 直接去更新覆盖，将已有的数据变成0
                $AddressStatus = $this->AddressModel->where(['busid' => $this->business['id']])->update(['status' => '0']);

                if ($AddressStatus === FALSE) {
                    $this->error('更新默认地址状态有误');
                    exit;
                }
            }

            //插入数据
            $result = $this->AddressModel->validate('common/Business/Address')->save($params);

            if ($result === FALSE) {
                $this->AddressModel->rollback();
                $this->error($this->AddressModel->getError());
                exit;
            } else {
                $this->AddressModel->commit();
                $this->success('添加成功');
                exit;
            }
        }
    }

    // 编辑收货地址
    public function edit()
    {
        if ($this->request->isPost()) {
            $params = $this->request->param();

            $id = $this->request->param('id', 0, 'trim');
            $code = $this->request->param('code', '', 'trim');
            $status = $this->request->param('status', '0', 'trim');

            $where = [
                'id' => $id,
                'busid' => $this->business['id']
            ];

            $address = $this->AddressModel->where($where)->find();

            if (!$address) {
                $this->error('收货地址不存在');
                exit;
            }

            //判断是否有地区数据
            if (!empty($code)) {
                //查询省市区的地区码出来
                $parent = model('Region')->where(['code' => $code])->value('parentpath');

                if (!empty($parent)) {
                    $arr = explode(',', $parent);
                    $params['province'] = isset($arr[0]) ? $arr[0] : null;
                    $params['city'] = isset($arr[1]) ? $arr[1] : null;
                    $params['district'] = isset($arr[2]) ? $arr[2] : null;
                }
            }

            //开启事务
            $this->AddressModel->startTrans();

            //判断是否选择了默认收货地址
            if ($status == '1') {
                //直接去更新覆盖，将已有的数据变成0
                $AddressStatus = $this->AddressModel->where(['busid' => $this->business['id']])->update(['status' => '0']);

                if ($AddressStatus === FALSE) {
                    $this->error('更新默认地址状态有误');
                    exit;
                }
            }

            //编辑数据
            $result = $this->AddressModel->validate('common/Business/Address')->isUpdate(true)->save($params);

            if ($result === FALSE) {
                $this->AddressModel->rollback();
                $this->error($this->AddressModel->getError());
                exit;
            } else {
                $this->AddressModel->commit();
                $this->success('更新成功');
                exit;
            }
        }
    }

    //删除收货地址
    public function del()
    {
        if ($this->request->isPost()) {
            $params = $this->request->param();

            $id = $this->request->param('id', 0, 'trim');

            $where = [
                'id' => $id,
                'busid' => $this->business['id']
            ];

            $address = $this->AddressModel->where($where)->find();

            if (!$address) {
                $this->error('收货地址不存在');
                exit;
            }

            $result = $this->AddressModel->destroy($id);

            if ($result === FALSE) {
                $this->error('删除失败');
                exit;
            } else {
                $this->success('更新成功');
                exit;
            }
        }
    }

    //查询收货地址
    public function info()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'trim');

            $where = [
                'id' => $id,
                'busid' => $this->business['id']
            ];

            $address = $this->AddressModel->where($where)->find();

            if ($address) {
                $this->success('返回收货地址', null, $address);
                exit;
            } else {
                $this->error('地址不存在');
                exit;
            }
        }
    }
}
