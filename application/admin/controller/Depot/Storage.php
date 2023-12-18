<?php

namespace app\admin\controller\Depot;

use app\common\controller\Backend; // 引入公共控制器

/**
 * 入库管理
 *
 * @icon fa fa-circle-o
 */
class Storage extends Backend
{
    // 当前模型
    protected $model = null;

    // 设置多表查询
    protected $relationSearch = true;

    // 当前无须登录方法
    protected $noNeedLogin = [];

    // 无需鉴权的方法，但需要登录
    protected $noNeedRight = [];

    // 构造函数
    public function __construct()
    {
        parent::__construct();

        // 将控制器和模型关联
        $this->model = model('Depot.Storage');
        $this->SupplierModel = model('Depot.Supplier');
        $this->ProductModel = model('Product.Product');
        $this->StorageProductModel = model('Depot.StorageProduct');
        $this->BackModel = model('Depot.Back');


        // 获取所有的入库状态        
        $this->view->assign("statusList", $this->model->getStatusList());

        // 获取所有的供应商列表
        // $SupplierName = $this->SupplierModel->column(['id', 'name']);
        $SupplierList = $this->SupplierModel->select();

        // 获取入库类型列表
        $TypeList = $this->model->getTypeList();

        // 将列表赋值给模板
        $this->view->assign("typelist", $TypeList);
        $this->view->assign("supplierlist", $SupplierList);
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
            $total = $this->model->count();

            // 获取分页数据
            $list = $this->model
                ->with(['supplier', 'admin', 'reviewer'])
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

    // 添加
    public function add()
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否是Ajax请求
        if ($this->request->isPost()) {

            // 获取表单数据
            $params = $this->request->param('row/a');

            // 接收添加的商品数据，json字符串转成数组
            $productList = json_decode($params['product'], true);

            // 判断是否有商品数据
            if (!$productList) {
                $this->error('请添加商品');
                exit;
            }

            // 判断是否有空数据
            foreach ($productList as $value) {
                if (!$value['nums'] || !$value['price']) {
                    $this->error('请填写完整的商品信息');
                    exit;
                }
            }

            // 开启事务
            $this->model->startTrans();
            $this->StorageProductModel->startTrans();

            // 组装数据
            $StorageData = [
                'code' => build_code('SU'),
                'supplierid' => $params['supplierid'],
                'type' => $params['type'],
                'amount' => $params['total'],
                'remark' => $params['remark'],
                'status' => 0
            ];

            // 插入数据
            $StorageStatus = $this->model->validate('common/Depot/Storage')->save($StorageData);

            // 判断是否插入成功
            if ($StorageStatus === false) {
                $this->error($this->model->getError());
                exit;
            }

            // 获取入库单id
            $StorageId = $this->model->getLastInsID(); // 获取最后插入的id

            // 组装数据
            foreach ($productList as $value) {
                $ProductData[] = [
                    'storageid' => $StorageId,
                    'proid' => $value['id'],
                    'nums' => $value['nums'],
                    'price' => $value['price'],
                    'total' => $value['total']
                ];
            }

            $StorageProductStatus = $this->StorageProductModel->validate('common/Depot/StorageProduct')->saveAll($ProductData);

            // 判断是否插入成功
            if ($StorageProductStatus === false) {

                // 回滚事务
                $this->model->rollback();
                $this->error($this->StorageProductModel->getError());
                exit;
            }

            // 大判断
            if ($StorageStatus === false || $StorageProductStatus === false) {

                // 回滚事务
                $this->StorageProductModel->rollback();
                $this->model->rollback();
                $this->error('添加入库单失败');
                exit;
            } else {

                // 提交事务
                $this->model->commit();
                $this->StorageProductModel->commit();
                $this->success('添加入库单成功');
                exit;
            }
        }

        // 渲染模板
        return $this->view->fetch();
    }

    // 编辑
    public function edit($ids = NULL)
    {
        // 根据id判断数据是否存在
        $row = $this->model->find($ids);

        if (empty($row)) {
            $this->error(__('入库单不存在，请重新选择'));
            exit;
        }

        // 查询供应商信息
        $SupplierData = $this->SupplierModel->where(['id' => $row['supplierid']])->find();

        if ($SupplierData) {

            if (!$SupplierData) {
                $this->error('供应商信息不存在，请重新选择');
            }
        } else {

            // 查询退货订单信息
            $back = $this->BackModel->with(['business'])->where(['storageid' => $row['id']])->find();

            if (!$back) {
                $this->error('退货单不存在，请重新选择');
            }
        }

        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否是post请求
        if ($this->request->isPost()) {

            // 获取表单数据
            $params = $this->request->param('row/a');

            // 接收添加的商品数据，json字符串转成数组
            $productList = json_decode($params['product'], true);

            // 判断是否有商品数据
            if (!$productList) {
                $this->error('请添加商品');
                exit;
            }

            // 判断是否有空数据
            foreach ($productList as $value) {
                if (!$value['nums'] || !$value['price']) {
                    $this->error('请填写完整的商品信息');
                    exit;
                }
            }

            // 开启事务
            $this->model->startTrans();
            $this->StorageProductModel->startTrans();

            // 组装数据
            $StorageData = [
                'id' => $row['id'],
                'type' => $params['type'],
                'amount' => $params['total'],
                'remark' => $params['remark'],
                'status' => $row['status']
            ];

            if ($row['type'] == 1) {
                $StorageData['supplierid'] = $params['supplierid'];
            }

            // 如果等于true说明直销入库
            if ($SupplierData) {
                $StorageStatus = $this->model->validate('common/Depot/Storage.edit')->isUpdate(true)->save($StorageData);
            } else {
                $StorageStatus = $this->model->validate('common/Depot/Storage.back_edit')->isUpdate(true)->save($StorageData);
            }

            // 判断是否插入成功
            if ($StorageStatus === false) {
                $this->error($this->model->getError());
                exit;
            }

            // 存放封装好的商品数据
            $ProductData = [];

            // 封装一个在修改时新增的商品
            $NewProData = [];

            foreach ($productList as $item) {
                if (isset($item['proid'])) {
                    $ProductData[] = [
                        'id' => $item['id'],
                        'proid' => $item['proid'],
                        'nums' => $item['nums'],
                        'price' => $item['price'],
                        'total' => $item['total'],
                    ];
                } else {
                    $NewProData[] = [
                        'storageid' => $row['id'],
                        'proid' => $item['id'],
                        'nums' => $item['nums'],
                        'price' => $item['price'],
                        'total' => $item['total'],
                    ];
                }
            }

            // 获取需要删除的商品id
            $delproid = json_decode($params['delproid']);

            // 删除不需要的商品
            if (!empty($delproid)) {
                $DelStatus = $this->StorageProductModel->destroy($delproid);
                if ($DelStatus === FALSE) {
                    $this->model->rollback();
                    $this->error($this->StorageProductModel->getError());
                    exit;
                }
            }

            $NewProStatus = $this->StorageProductModel->validate('common/Depot/StorageProduct')->saveAll($NewProData);

            // 验证数据
            $ProductStatus = $this->StorageProductModel->validate('common/Depot/StorageProduct.edit')->isUpdate(true)->saveAll($ProductData);

            if ($ProductStatus === FALSE || $NewProStatus === FALSE) {
                $this->model->rollback();
                $this->error($this->StorageProductModel->getError());
                exit;
            }

            // 大判断
            if ($ProductStatus === FALSE || $StorageStatus === FALSE || $NewProStatus === FALSE) {
                $this->model->rollback();
                $this->StorageProductModel->rollback();
                $this->error(__('编辑入库单失败'));
                exit;
            } else {
                $this->model->commit();
                $this->StorageProductModel->commit();
                $this->success('编辑入库单成功');
                exit;
            }
        }

        // 查询入库商品信息
        $ProductList = $this->StorageProductModel->where(['storageid' => $row['id']])->select();

        if (!$ProductList) {
            $this->error('入库商品信息不存在，请重新选择');
        }

        // 根据proid查询商品信息
        foreach ($ProductList as $value) {
            $product = $this->ProductModel->where(['id' => $value['proid']])->find();

            if (!$product) {
                $this->error('商品信息不存在，请重新选择');
                continue;
            }

            $ProductData[] = [
                'product' => $product,
                'id' => $value['id'],
                'nums' => $value['nums'],
                'price' => $value['price'],
                'total' => $value['total'],
            ];
        }

        // 数据返回到js
        $this->assignconfig('ProductData', $ProductData);

        // 将数据返回模板
        $this->view->assign([
            'row' => $row,
            'supplierdata' => $SupplierData,
        ]);

        // 渲染模板
        return $this->view->fetch();
    }

    public function info($ids = null)
    {
        // 根据id判断入库单是否存在
        $row = $this->model->with(['supplier', 'admin', 'reviewer'])->where(['storage.id' => $ids])->find();

        if (!$row) {
            $this->error('入库单不存在，请重新选择');
            exit;
        }

        // 查询入库商品信息
        $ProductList = $this->StorageProductModel->where(['storageid' => $row['id']])->select();

        if (!$ProductList) {
            $this->error('入库商品信息不存在，请重新选择');
            exit;
        }

        $productData = [];
        foreach ($ProductList as $item) {
            $product = model('Product.Product')->with(['category', 'unit'])->find($item['proid']);

            $productData[] = [
                'id' => $item['id'],
                'price' => $item['price'],
                'nums' => $item['nums'],
                'total' => $item['total'],
                'product' => $product
            ];
            ;

        }
        $data = [
            'row' => $row,
            'productData' => $productData
        ];
        return $this->view->fetch('', $data);
    }

    // 软删除
    public function del($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->where(['id' => ['in', $ids]])->select();

            if (empty($row)) {
                $this->error('请选择需要删除入库单');
            }

            $result = $this->model->destroy($ids);

            if ($result === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('删除入库单成功');
                exit;
            }
        }
    }

    // 通过审核
    public function pass($ids = null)
    {
        if ($this->request->isAjax()) {

            // 根据id查询入库单信息
            $row = $this->model->where(['id' => $ids])->find();

            if (!$row) {
                $this->error('入库单不存在，请重新选择');
                exit;
            }

            // 判断入库单状态是否为待审核
            if ($row['status'] != 0) {
                $this->error('入库单状态不正确');
                exit;
            }

            // 组装数据
            $data = [
                'id' => $ids,
                'status' => 2,
                'reviewerid' => $this->auth->id,
            ];

            // 修改入库单状态
            $status = $this->model->isUpdate(true)->save($data);

            // 判断是否修改成功
            if ($status === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('通过审核成功');
                exit;
            }
        }
    }

    // 撤销审核
    public function revoke($ids = null)
    {
        if ($this->request->isAjax()) {

            // 根据id查询入库单信息
            $row = $this->model->where(['id' => $ids])->find();

            if (!$row) {
                $this->error('入库单不存在，请重新选择');
                exit;
            }

            // 判断入库单状态是可以撤销审核
            if ($row['status'] == 0 || $row['status'] == 3) {
                $this->error('入库单状态不正确');
                exit;
            }

            // 组装数据
            $data = [
                'id' => $ids,
                'status' => 0,
                'reviewerid' => $this->auth->id,
            ];

            // 修改入库单状态
            $status = $this->model->isUpdate(true)->save($data);

            // 判断是否修改成功
            if ($status === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('撤销审核成功');
                exit;
            }
        }
    }

    // 拒绝审核
    public function refuse($ids = null)
    {
        if ($this->request->isAjax()) {

            // 根据id查询入库单信息
            $row = $this->model->where(['id' => $ids])->find();

            if (!$row) {
                $this->error('入库单不存在，请重新选择');
                exit;
            }

            // 判断入库单状态是否可以拒绝审核
            if ($row['status'] == 1 || $row['status'] == 3) {
                $this->error('入库单状态不正确');
                exit;
            }

            // 组装数据
            $data = [
                'id' => $ids,
                'status' => 1,
                'reviewerid' => $this->auth->id,
            ];

            // 修改入库单状态
            $status = $this->model->isUpdate(true)->save($data);

            // 判断是否修改成功
            if ($status === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('拒绝审核成功');
                exit;
            }

        }
    }

    // 确认入库
    public function storage($ids = null)
    {
        if ($this->request->isAjax()) {

            // 根据id查询入库单信息
            $row = $this->model->where(['id' => $ids])->find();

            if (!$row) {
                $this->error('入库单不存在，请重新选择');
                exit;
            }

            // 判断入库单状态是否可以确认入库
            if ($row['status'] != 2) {
                $this->error('入库单状态不正确');
                exit;
            }

            // 查询入库商品信息
            $ProductList = $this->StorageProductModel->where(['storageid' => $row['id']])->select();

            // 开启事务
            $this->model->startTrans();
            $this->ProductModel->startTrans();

            // 组装数据
            $data = [
                'id' => $ids,
                'status' => 3,
                'reviewerid' => $this->auth->id,
            ];

            // 修改入库单状态
            $StorageStatus = $this->model->isUpdate(true)->save($data);

            // 判断是否修改成功
            if ($StorageStatus === FALSE) {
                $this->error($this->model->getError());
                exit;
            }

            $ProductData = [];

            // 遍历入库商品信息
            foreach ($ProductList as $item) {
                $product = $this->ProductModel->where(['id' => $item['proid']])->find();
                $ProductData[] = [
                    'id' => $item['proid'],
                    'stock' => bcadd($product['stock'], $item['nums']), // 加法 bcadd 减法 bcsub 乘法 bcmul 除法 bcdiv
                ];
            }

            // 修改商品库存
            $ProductStatus = $this->ProductModel->isUpdate()->saveAll($ProductData);

            // 判断是否修改成功
            if ($ProductStatus === FALSE) {
                $this->model->rollback();
                $this->error($this->ProductModel->getError());
                exit;
            }

            // 大判断
            if ($StorageStatus === FALSE || $ProductStatus === FALSE) {
                $this->model->rollback();
                $this->ProductModel->rollback();
                $this->error('确认入库失败');
                exit;
            } else {
                $this->model->commit();
                $this->ProductModel->commit();
                $this->success('确认入库成功');
                exit;
            }
        }
    }

    // 商品信息
    public function product()
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否有Ajax请求
        if ($this->request->isAjax()) {
            // 获取表格所提交的参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 获取数据总数
            $total = $this->ProductModel->count();

            // 获取分页数据
            $list = $this->ProductModel
                ->with(['category', 'unit'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 返回数据
            $result = [
                "total" => $total,
                "rows" => $list
            ];
            return json($result);
        }

        // 输出视图
        return $this->view->fetch();

    }

    // 供应商信息
    public function supplier()
    {
        // 获取num
        $num = $this->request->param('num', 0, '');

        // 根据num查询出对应的供应商信息
        $SupplierData = $this->SupplierModel->where(['id' => $num])->find();

        // 组装数据
        $data = [
            'mobile' => $SupplierData['mobile'],
            'address' => $SupplierData['address_text'],
        ];

        // 返回数据
        if ($data) {
            $this->success('查询供应商信息成功', null, $data);
            exit;
        } else {
            $this->error('暂无信息');
            exit;
        }
    }
}

?>
