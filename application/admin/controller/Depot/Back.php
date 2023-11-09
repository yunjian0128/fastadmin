<?php

namespace app\admin\controller\Depot;

use app\common\controller\Backend; // 引入公共控制器

/**
 * 入库管理
 *
 * @icon fa fa-circle-o
 */
class Back extends Backend
{
    // 当前模型
    protected $model = null;

    // 设置多表查询
    // protected $relationSearch = true;

    // 当前无须登录方法
    protected $noNeedLogin = [];

    // 无需鉴权的方法，但需要登录
    protected $noNeedRight = [];

    // 构造函数
    public function __construct()
    {
        parent::__construct();

        // 将控制器和模型关联
        $this->model = model('Depot.Back');
        $this->OrderModel = model('Product.Order');
        $this->AddressModel = model('Business.Address');
        $this->OrderProductModel = model('Product.OrderProduct');
        $this->BackProductModel = model('Depot.BackProduct');
        $this->BusinessModel = model('Business.Business');
        $this->StorageModel = model('Depot.Storage');
        $this->StorageProductModel = model('Depot.StorageProduct');


        // 获取所有的退货状态        
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
            $total = $this->model->count();

            // 获取分页数据
            $list = $this->model
                ->with(['business'])
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

    // 添加退货单
    public function add()
    {
        // 判断是否有Ajax请求
        if ($this->request->isPost()) {

            // 获取表单数据
            $params = $this->request->param("row/a");

            // 开启事务
            $this->model->startTrans();
            $this->BackProductModel->startTrans();

            // 查询订单
            $order = $this->OrderModel->where('code', $params['ordercode'])->find();

            if (!$order) {
                $this->error('订单不存在，请检查订单号是否正确');
                exit;
            }

            // 组装退货单数据
            $BackData = [
                'code' => build_code('BP'),
                'ordercode' => $params['ordercode'],
                'busid' => $order['busid'],
                'remark' => $params['remark'],
            ];

            // 查询订单商品
            $OrderProduct = $this->OrderProductModel->where('orderid', $order['id'])->select();

            if (!$OrderProduct) {
                $this->error('该订单没有商品，请检查订单号是否正确');
                exit;
            }

            $BackData['amount'] = $order['amount'];

            $BackData['status'] = 0;

            $BackData['adminid'] = $this->auth->id;

            // 查询地址
            $address = $this->AddressModel->where('id', $params['addrid'])->find();

            if (!$address) {
                $this->error('该选择联系人及收货地址');
                exit;
            }

            $BackData['contact'] = $address['consignee'];
            $BackData['phone'] = $address['mobile'];
            $BackData['address'] = $address['address'];
            $BackData['province'] = $address['province'];
            $BackData['city'] = $address['city'];
            $BackData['district'] = $address['district'];
            $BackStatus = $this->model->validate('common/Depot/Back')->save($BackData);

            if ($BackStatus === FALSE) {
                $this->error(__($this->model->getError()));
                exit;
            }

            // 组装退货商品数据
            $BackProductData = [];

            foreach ($OrderProduct as $item) {
                $BackProductData[] = [
                    'backid' => $this->model->id,
                    'proid' => $item['proid'],
                    'nums' => $item['pronum'],
                    'price' => $item['price'],
                    'total' => $item['total']
                ];
            }

            $BackProductStatus = $this->BackProductModel->validate('common/Depot/BackProduct')->saveAll($BackProductData);

            if ($BackProductStatus === FALSE) {

                // 回滚事务
                $this->model->rollback();
                $this->error(__($this->BackProductModel->getError()));
                exit;
            }

            if ($BackStatus && $BackProductStatus) {

                // 提交事务
                $this->model->commit();
                $this->BackProductModel->commit();
                $this->success('添加退货单成功');
                exit;
            } else {

                // 回滚事务
                $this->model->rollback();
                $this->BackProductModel->rollback();
                $this->error('添加退货单失败');
                exit;
            }
        }

        // 输出视图
        return $this->view->fetch();
    }

    // 编辑退货单
    public function edit($ids = null)
    {

        // 判断退货单是否存在
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }

        if ($this->request->isPost()) {
            $params = $this->request->param('row/a');

            // 开启事务
            $this->model->startTrans();
            $this->BackProductModel->startTrans();

            // 查询订单
            $order = model('Product.Order')->where(['code' => $params['ordercode']])->find();

            if (!$order) {
                $this->error(__('订单不存在'));
            }

            $OrderProduct = model('Product.OrderProduct')->where(['orderid' => $order['id']])->select();

            if (!$OrderProduct) {
                $this->error(__('订单商品不存在'));
            }

            // 封装退货单数据
            $BackData = [
                'id' => $ids,
                'ordercode' => $params['ordercode'],
                'busid' => $order['busid'],
                'remark' => $params['remark'],
                'amount' => $order['amount'],
                'status' => $row['status'],
                'adminid' => $this->auth->id
            ];

            // 查询地址
            $address = model('Business.Address')->where(['id' => $params['addrid']])->find();

            if (!$address) {
                $this->error(__('请选择联系人以及地址'));
            }

            $BackData['contact'] = $address['consignee'];
            $BackData['phone'] = $address['mobile'];
            $BackData['address'] = $address['address'];
            $BackData['province'] = $address['province'];
            $BackData['city'] = $address['city'];
            $BackData['district'] = $address['district'];

            // 更新数据库
            $BackStatus = $this->model->isUpdate(true)->save($BackData);

            if ($BackStatus === FALSE) {
                $this->error(__($this->model->getError()));
            }

            // 默认退货订单状态
            $BackProductStatus = TRUE;

            // 判断是否订单号有更新
            if ($params['ordercode'] != $row['ordercode']) {
                // 封装退货商品
                $BackProductData = [];

                foreach ($OrderProduct as $item) {
                    $BackProductData[] = [
                        'backid' => $this->model->id,
                        'proid' => $item['proid'],
                        'nums' => $item['pronum'],
                        'price' => $item['price'],
                        'total' => $item['total']
                    ];
                }

                $BackProductStatus = $this->BackProductModel->validate('common/Depot/BackProduct')->saveAll($BackProductData);
            }

            if ($BackProductStatus === FALSE) {
                $this->model->rollback();
                $this->error(__($this->BackProductModel->getError()));
            }

            if ($BackProductStatus === FALSE || $BackStatus === FALSE) {
                $this->model->rollback();
                $this->BackProductModel->rollback();
                $this->error("编辑退货单失败");
            } else {
                $this->model->commit();
                $this->BackProductModel->commit();
                $this->success('编辑退货单成功');
            }

        }

        // 查询地址数据
        $AddressWhere = [
            'consignee' => $row['contact'],
            'mobile' => $row['phone'],
            'address' => $row['address'],
            'province' => $row['province'],
            'city' => $row['city'],
            'district' => $row['district'],
            'busid' => $row['busid']
        ];

        $addrid = model('Business.Address')->where($AddressWhere)->value('id');

        $row['addrid'] = $addrid;

        $AddressData = $this->AddressModel->where('busid', $row['busid'])->select();

        // 查询退货单商品
        $BackProductList = $this->BackProductModel->with(['products'])->where(['backid' => $row['id']])->select();

        // 封装下拉的数据
        $AddressList = [];

        // 遍历数据
        foreach ($AddressData as $item) {
            $AddressList[$item['id']] = "联系人：{$item['consignee']} 联系方式：{$item['mobile']} 地址：{$item['region_text']}-{$item['address']}";
        }

        $this->assignconfig('back', ['BackProductList' => $BackProductList]);

        $data = [
            'row' => $row,
            'AddressList' => $AddressList,
        ];

        // 渲染模板
        return $this->view->fetch('', $data);
    }

    // 删除退货单
    public function del($ids = null)
    {
        // 判断退货单是否存在
        $rows = $this->model->select($ids);

        if (!$rows) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 软删除
        $status = $this->model->destroy($ids);

        if ($status) {
            $this->success('删除退货单成功');
        } else {
            $this->error('删除退货单失败');
        }
    }

    // 退货单详情
    public function info($ids = null)
    {
        // 判断退货单是否存在
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 查询退货单商品
        $BackProductList = $this->BackProductModel->with(['products'])->where(['backid' => $ids])->select();

        $data = [
            'row' => $row,
            'BackProductList' => $BackProductList
        ];

        // 渲染模板
        return $this->view->fetch('', $data);
    }

    // 通过审核
    public function pass()
    {
        if ($this->request->isAjax()) {
            $ids = $this->request->param('ids');
            $row = $this->model->find($ids);

            if (!$row) {
                $this->error(__('退货单不存在'));
            }

            // 封装更新的数据
            $data = [
                'id' => $ids,
                'status' => 1,
                'reviewerid' => $this->auth->id
            ];

            $result = $this->model->isUpdate(true)->save($data);

            if ($result === FALSE) {
                $this->error('审核通过失败');
            } else {
                $this->success('审核通过成功');
            }
        }
    }

    // 撤销审核
    public function cancel()
    {
        if ($this->request->isAjax()) {
            $ids = $this->request->param('ids');
            $row = $this->model->find($ids);

            if (!$row) {
                $this->error(__('退货单不存在'));
            }

            // 封装更新的数据
            $data = [
                'id' => $ids,
                'status' => 0,
                'reviewerid' => $this->auth->id
            ];

            $result = $this->model->isUpdate(true)->save($data);

            if ($result === FALSE) {
                $this->error('撤回审核失败');
            } else {
                $this->success('撤回审核成功');
            }
        }
    }

    // 审核未通过
    public function fail()
    {
        $ids = $this->request->param('ids');
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error(__('退货单不存在'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->param('row/a');

            if (empty($params['reason'])) {
                $this->error('请填写作废理由');
            }

            // 封装更新的数据
            $data = [
                'id' => $ids,
                'status' => '-1',
                'reviewerid' => $this->auth->id,
                'reason' => $params['reason']
            ];

            $result = $this->model->isUpdate(true)->save($data);

            if ($result === FALSE) {
                $this->error('审核不通过失败');
            } else {
                $this->success('审核不通过成功');
            }

            // halt($params); // halt()方法用于打印数据
        }

        return $this->view->fetch();
    }

    // 确认收货
    public function receipt()
    {
        if ($this->request->isAjax()) {
            $ids = $this->request->param('ids');
            $row = $this->model->find($ids);

            if (!$row) {
                $this->error(__('退货单不存在'));
            }

            // 查询订单
            $order = $this->OrderModel->where(['code' => $row['ordercode']])->find();

            if (!$order) {
                $this->error('商品订单不存在');
            }

            $business = $this->BusinessModel->find($order['busid']);

            if (!$business) {
                $this->error('用户不存在');
            }

            // 开启事务
            $this->BusinessModel->startTrans();
            $this->model->startTrans();
            $this->OrderModel->startTrans();

            // 封装更新退货单数据
            $data = [
                'id' => $ids,
                'status' => 2
            ];

            $BackStatus = $this->model->isUpdate(true)->save($data);

            if ($BackStatus === FALSE) {
                $this->error('确认收货失败');
            }

            // 更新用户的余额
            $BusinessData = [
                'id' => $business['id'],
                'money' => bcadd($order['amount'], $business['money'], 2)
            ];

            $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

            if ($BusinessStatus === false) {
                $this->model->rollback();
                $this->error('更新用户余额失败');
            }

            // 更新订单的状态
            $OrderData = [
                'id' => $ids,
                'status' => -4,
            ];

            $OrderStatus = $this->OrderModel->isUpdate(true)->save($OrderData);

            if ($OrderStatus === false) {
                $this->model->rollback();
                $this->BusinessModel->rollback();
                $this->error('更新订单状态失败');
            }

            if ($BackStatus === false || $BusinessStatus === false || $OrderStatus === false) {
                $this->model->rollback();
                $this->BusinessModel->rollback();
                $this->OrderModel->rollback();
                $this->error('确认收货失败');
            } else {
                $this->model->commit();
                $this->BusinessModel->commit();
                $this->OrderModel->commit();
                $this->success('确认收货成功');
            }
        }
    }

    // 确认入库
    public function storage()
    {
        if ($this->request->isAjax()) {
            $ids = $this->request->param('ids');
            $row = $this->model->find($ids);

            if (!$row) {
                $this->error(__('退货单不存在'));
            }

            $BackProductList = $this->BackProductModel->where(['backid' => $ids])->select();

            if (!$BackProductList) {
                $this->error(__('退货商品不存在'));
            }

            $this->StorageModel->startTrans();
            $this->StorageProductModel->startTrans();
            $this->model->startTrans();

            // 封装入库数据
            $StorageData = [
                'code' => build_code('SU'), // 订单前缀可以自定义\
                'type' => 2,
                'amount' => $row['amount'],
                'status' => 0
            ];

            $StorageStatus = $this->StorageModel->validate('common/Depot/Storage.back')->save($StorageData);

            if ($StorageStatus === FALSE) {
                $this->error($this->StorageModel->getError());
                exit;
            }

            // 存放封装好的商品数据
            $ProductData = [];

            foreach ($BackProductList as $item) {
                $ProductData[] = [
                    'storageid' => $this->StorageModel->id,
                    'proid' => $item['proid'],
                    'nums' => $item['nums'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ];
            }

            // 验证数据
            $ProductStatus = $this->StorageProductModel->validate('common/Depot/StorageProduct')->saveAll($ProductData);

            if ($ProductStatus === FALSE) {
                $this->StorageModel->rollback();
                $this->error($this->StorageProductModel->getError());
            }

            // 更新退货单的数据
            $BackData = [
                'id' => $row['id'],
                'status' => 3,
                'stromanid' => $this->auth->id,
                'storageid' => $this->StorageModel->id
            ];

            $BackStatus = $this->model->isUpdate(true)->save($BackData);

            if ($BackStatus === FALSE) {
                $this->StorageModel->rollback();
                $this->StorageProductModel->rollback();
                $this->error($this->model->getError());
            }

            // 大判断
            if ($ProductStatus === FALSE || $StorageStatus === FALSE || $BackStatus === FALSE) {
                $this->StorageModel->rollback();
                $this->StorageProductModel->rollback();
                $this->model->rollback();
                $this->error(__('入库失败'));
            } else {
                $this->StorageModel->commit();
                $this->StorageProductModel->commit();
                $this->model->commit();
                $this->success('入库成功');
            }
        }
    }

    // 获得订单详情
    public function order()
    {

        // 判断是否有Ajax请求
        if ($this->request->isAjax()) {

            // 获取表单数据
            $params = $this->request->param();

            // 获得订单号
            $code = $params['ordercode'];

            // 获取订单详情
            $order = $this->OrderModel->where('code', $code)->find();

            if (empty($order)) {
                $this->error('订单不存在，请检查订单号是否正确');
                exit;
            }

            $busid = $order['busid'];
            $orderid = $order['id'];


            // 根据查到的用户id获取该订单的联系人信息
            $Consignee_address = $this->AddressModel->where('busid', $busid)->select();

            if (empty($Consignee_address)) {
                $this->error('该用户没有收货地址，请先添加收货地址');
                exit;
            }

            // 获取订单的商品信息
            $order_product = $this->OrderProductModel->with(['products'])->where('orderid', $orderid)->select();

            // 组装数据
            $data = [
                'order' => $order,
                'Consignee_address' => $Consignee_address,
                'order_product' => $order_product
            ];

            // 返回数据
            $this->success('获取订单数据成功', null, $data);
            exit;
        }
    }
}
