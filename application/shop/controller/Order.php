<?php
namespace app\shop\controller;

use think\Controller;

class Order extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->ProductModel = model('Product.Product');
        $this->CartModel = model('Product.Cart');
        $this->BusinessModel = model('Business.Business');
        $this->AddressModel = model('Business.Address');
        $this->OrderModel = model('Order.Order');
        $this->OrderProductModel = model('Order.Product');
        $this->RecordModel = model('Business.Record');
        $this->ExpressModel = model('Express');
        $this->BackModel = model('Depot.Back');
        $this->BackProductModel = model('Depot.BackProduct');

        $busid = $this->request->param('busid', 0, 'trim');

        $this->business = $this->BusinessModel->find($busid);

        if (!$this->business) {
            $this->error('用户不存在');
            exit;
        }
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $page = $this->request->param('page', 1, 'trim');
            $status = $this->request->param('status', 0, 'trim');
            $limit = 8;

            // 偏移量
            $offset = ($page - 1) * $limit;

            $where = ['busid' => $busid];

            if ($status != 0) {
                $where['status'] = $status;
            }

            $list = $this->OrderModel
                ->where($where)
                ->order('id', 'desc')
                ->limit($offset, $limit)
                ->select();

            // 遍历订单数据
            foreach ($list as $item) {
                $product = $this->OrderProductModel->with(['products'])->where(['orderid' => $item['id']])->find();
                $item['proname'] = isset($product['products']['name']) ? $product['products']['name'] : '未知商品';
                $item['thumb_text'] = isset($product['products']['thumb_text']) ? $product['products']['thumb_text'] : '';
            }

            if ($list) {
                $this->success('返回订单数据', null, $list);
                exit;
            } else {
                $this->error('暂无更多订单数据');
                exit;
            }
        }
    }

    // 下单
    public function add()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $cartids = $this->request->param('cartids', 0, 'trim');
            $addrid = $this->request->param('addrid', 0, 'trim');
            $remark = $this->request->param('remark', '', 'trim');
            $proid = $this->request->param('proid', 0, 'trim');
            $action = $this->request->param('action', '', 'trim');
            $count = $this->request->param('count', 0, 'trim');

            // 先判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 直接下单
            if ($action == 'buy') {

                // 收货地址是否存在
                $where = [
                    'busid' => $busid,
                    'id' => $addrid
                ];

                $address = $this->AddressModel->where($where)->find();

                if (!$address) {
                    $this->error('收货地址不存在');
                    exit;
                }

                $count = intval($count);

                // 判断商品是否存在
                $product = $this->ProductModel->find($proid);

                if (!$product) {
                    $this->error('商品不存在');
                    exit;
                }

                // 判断商品库存是否充足
                $stock = isset($product['stock']) ? $product['stock'] : 0;
                $stock = intval($stock);
                $stock = bcsub($stock, $count);

                if ($stock <= 0) {
                    $this->error('商品库存不足');
                    exit;
                }

                // 判断用户余额是否充足
                $price = isset($product['price']) ? $product['price'] : 0;
                $price = floatval($price);
                $total = bcmul($price, $count);
                $UpdateMoney = bcsub($business['money'], $total);

                if ($UpdateMoney < 0) {
                    $this->error('余额不足');
                    exit;
                }

                // 订单表 
                // 订单商品表 
                // 商品表
                // 用户余额表
                // 消费记录表

                //开启事务
                $this->OrderModel->startTrans();
                $this->OrderProductModel->startTrans();
                $this->ProductModel->startTrans();
                $this->BusinessModel->startTrans();
                $this->RecordModel->startTrans();

                // 订单表
                $OrderData = [
                    'code' => build_code("FA"),
                    'busid' => $busid,
                    'businessaddrid' => $addrid,
                    'amount' => $total,
                    'remark' => $remark,
                    'status' => '1',
                ];

                $OrderStatus = $this->OrderModel->validate('common/Order/Order')->save($OrderData);

                if ($OrderStatus === FALSE) {
                    $this->error($this->OrderModel->getError());
                    exit;
                }

                // 订单商品表
                $OrderProductData = [
                    'orderid' => $this->OrderModel->id,
                    'proid' => $proid,
                    'pronum' => $count,
                    'price' => $price,
                    'total' => $total,
                ];

                $OrderProductStatus = $this->OrderProductModel->validate('common/Order/Product')->save($OrderProductData);

                if ($OrderProductStatus === FALSE) {
                    $this->OrderModel->rollback();
                    $this->error($this->OrderProductModel->getError());
                    exit;
                }

                // 更新商品库存
                $ProductData = [
                    'id' => $proid,
                    'stock' => $stock
                ];

                $ProductStatus = $this->ProductModel->isUpdate(true)->save($ProductData);

                if ($ProductStatus === FALSE) {
                    $this->OrderProductModel->rollback();
                    $this->OrderModel->rollback();
                    $this->error($this->ProductModel->getError());
                    exit;
                }

                // 更新用户余额
                $BusinessData = [
                    'id' => $busid,
                    'money' => $UpdateMoney
                ];

                $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

                if ($BusinessStatus === FALSE) {
                    $this->ProductModel->rollback();
                    $this->OrderProductModel->rollback();
                    $this->OrderModel->rollback();
                    $this->error($this->BusinessModel->getError());
                    exit;
                }

                // 消费记录
                $RecordData = [
                    'total' => "-$total",
                    'content' => "购物商品花费余额为：￥$total 元",
                    'busid' => $busid
                ];

                $RecordStatus = $this->RecordModel->validate('common/Business/Record')->save($RecordData);

                if ($RecordStatus === FALSE) {
                    $this->BusinessModel->rollback();
                    $this->ProductModel->rollback();
                    $this->OrderProductModel->rollback();
                    $this->OrderModel->rollback();
                    $this->error($this->RecordModel->getError());
                    exit;
                }

                if ($OrderStatus === FALSE || $OrderProductStatus === FALSE || $ProductStatus === FALSE || $BusinessStatus === FALSE || $RecordStatus === FALSE) {
                    $this->BusinessModel->rollback();
                    $this->ProductModel->rollback();
                    $this->OrderProductModel->rollback();
                    $this->OrderModel->rollback();
                    $this->error('下单失败');
                    exit;
                } else {
                    $this->OrderModel->commit();
                    $this->OrderProductModel->commit();
                    $this->ProductModel->commit();
                    $this->BusinessModel->commit();
                    $this->RecordModel->commit();
                    $this->success('下单成功', '/order/index');
                    exit;
                }
            }

            // 判断是否有购物车记录
            $cart = $this->CartModel->with(['product'])->where(['cart.id' => ['in', $cartids]])->select();

            if (!$cart) {
                $this->error('购物车记录不存在');
                exit;
            }

            $where = [
                'busid' => $busid,
                'id' => $addrid
            ];

            $address = $this->AddressModel->where($where)->find();

            if (!$address) {
                $this->error('收货地址不存在');
                exit;
            }

            // 判断商品的库存是否充足
            foreach ($cart as $item) {
                // 商品库存
                $stock = isset($item['product']['stock']) ? $item['product']['stock'] : 0;
                $proname = isset($item['product']['name']) ? $item['product']['name'] : '';

                if ($item['nums'] > $stock) {
                    $this->error("$proname 商品库存不足");
                    exit;
                }
            }

            // 先判断余额是否充足
            $total = $this->CartModel->where(['id' => ['in', $cartids]])->sum('total');

            $UpdateMoney = bcsub($business['money'], $total);

            if ($UpdateMoney < 0) {
                $this->error('余额不足');
                exit;
            }

            // 订单表 
            // 订单商品表 
            // 商品表
            // 用户余额表
            // 消费记录表
            // 购物车表

            // 开启事务
            $this->OrderModel->startTrans();
            $this->OrderProductModel->startTrans();
            $this->ProductModel->startTrans();
            $this->BusinessModel->startTrans();
            $this->RecordModel->startTrans();
            $this->CartModel->startTrans();


            // 订单表
            $OrderData = [
                'code' => build_code("FA"),
                'busid' => $busid,
                'businessaddrid' => $addrid,
                'amount' => $total,
                'remark' => $remark,
                'status' => '1',
            ];

            $OrderStatus = $this->OrderModel->validate('common/Order/Order')->save($OrderData);

            if ($OrderStatus === FALSE) {
                $this->error($this->OrderModel->getError());
                exit;
            }

            // 订单商品表
            $OrderProductData = [];
            $ProductData = [];

            foreach ($cart as $item) {
                $OrderProductData[] = [
                    'orderid' => $this->OrderModel->id,
                    'proid' => $item['proid'],
                    'pronum' => $item['nums'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ];

                // 更新商品的库存
                $stock = isset($item['product']['stock']) ? $item['product']['stock'] : 0;

                // 更新后的库存
                $UpdateStock = bcsub($stock, $item['nums']);
                $UpdateStock = $UpdateStock <= 0 ? 0 : $UpdateStock;

                // 组装数据
                $ProductData[] = [
                    'id' => $item['proid'],
                    'stock' => $UpdateStock
                ];
            }

            $OrderProductStatus = $this->OrderProductModel->validate('common/Order/Product')->saveAll($OrderProductData);

            if ($OrderProductStatus === FALSE) {
                $this->OrderModel->rollback();
                $this->error($this->OrderProductModel->getError());
                exit;
            }

            // 更新商品库存
            $ProductStatus = $this->ProductModel->isUpdate(true)->saveAll($ProductData);

            if ($ProductStatus === FALSE) {
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error($this->ProductModel->getError());
                exit;
            }

            // 用户表更新余额
            $BusinessData = [
                'id' => $busid,
                'money' => $UpdateMoney
            ];

            $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

            if ($BusinessStatus === FALSE) {
                $this->ProductModel->rollback();
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error($this->BusinessModel->getError());
                exit;
            }

            // 消费记录
            $RecordData = [
                'total' => "-$total",
                'content' => "购物商品花费余额为：￥$total 元",
                'busid' => $busid
            ];

            $RecordStatus = $this->RecordModel->validate('common/Business/Record')->save($RecordData);

            if ($RecordStatus === FALSE) {
                $this->BusinessModel->rollback();
                $this->ProductModel->rollback();
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error($this->RecordModel->getError());
                exit;
            }

            // 购物车表执行删除语句
            $CartStatus = $this->CartModel->where(['id' => ['in', $cartids]])->delete();

            if ($CartStatus === FALSE) {
                $this->RecordModel->rollback();
                $this->BusinessModel->rollback();
                $this->ProductModel->rollback();
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error($this->CartModel->getError());
                exit;
            }

            if ($OrderStatus === FALSE || $OrderProductStatus === FALSE || $ProductStatus === FALSE || $BusinessStatus === FALSE || $RecordStatus === FALSE || $CartStatus === FALSE) {
                $this->CartModel->rollback();
                $this->RecordModel->rollback();
                $this->BusinessModel->rollback();
                $this->ProductModel->rollback();
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error('下单失败');
                exit;
            } else {

                $this->OrderModel->commit();
                $this->OrderProductModel->commit();
                $this->ProductModel->commit();
                $this->BusinessModel->commit();
                $this->RecordModel->commit();
                $this->CartModel->commit();
                $this->success('下单成功', '/order/index');
                exit;
            }
        }
    }

    // 订单详细信息
    public function info()
    {
        // 如果有Post请求
        if ($this->request->isPost()) {

            // 获得客户ID
            $busid = $this->request->param('busid', 0, 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 获得订单ID
            $orderid = $this->request->param('orderid', 0, 'trim');

            // 判断订单是否存在
            $order = $this->OrderModel->find($orderid);

            if (!$order) {
                $this->error('订单不存在');
                exit;
            }

            // 查询客户姓名
            $business = $this->BusinessModel->find($order['busid']);

            // 查询订单的联系人信息
            $address = $this->AddressModel->find($order['businessaddrid']);

            // 查询订单的商品信息
            $product = $this->OrderProductModel->with(['products'])->where(['orderid' => $order['id']])->find();

            // 查询物流公司
            $express = $this->ExpressModel->find($order['expressid']);

            // 组装数据
            $order['busname'] = isset($business['nickname']) ? $business['nickname'] : '未知用户';
            $order['address'] = isset($address['region_text']) ? $address['region_text'] . '-' . $address['address'] : '未知地址';
            $order['address_status'] = isset($address['status']) ? $address['status'] : 0;
            $order['mobile'] = isset($address['mobile']) ? $address['mobile'] : '未知手机号';
            $order['consignee'] = isset($address['consignee']) ? $address['consignee'] : '未知联系人';
            $order['proname'] = isset($product['products']['name']) ? $product['products']['name'] : '未知商品';
            $order['thumb_text'] = isset($product['products']['thumb_text']) ? $product['products']['thumb_text'] : '';
            $order['price'] = isset($product['price']) ? $product['price'] : 0;
            $order['pronum'] = isset($product['pronum']) ? $product['pronum'] : 0;
            $order['total'] = isset($product['total']) ? $product['total'] : 0;
            $order['expressname'] = isset($express['name']) ? $express['name'] : '未知物流公司';

            $this->success('返回订单数据', null, $order);
        }
    }

    // 取消订单
    public function cancel()
    {
        // 如果有Post请求
        if ($this->request->isPost()) {

            // 获得客户ID
            $busid = $this->request->param('busid', 0, 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 获得订单ID
            $orderid = $this->request->param('orderid', 0, 'trim');

            // 判断订单是否存在
            $order = $this->OrderModel->find($orderid);

            if (empty($order)) {
                $this->error('订单不存在');
                exit;
            }

            // 订单表 订单商品表 商品表 用户表 消费记录表
            // 开启事务
            $this->OrderModel->startTrans();
            $this->OrderProductModel->startTrans();
            $this->ProductModel->startTrans();
            $this->BusinessModel->startTrans();
            $this->RecordModel->startTrans();

            // 查询订单的商品信息
            $product = $this->OrderProductModel->with(['products'])->where(['orderid' => $orderid])->select();

            if (!$product) {
                $this->error('订单商品不存在');
                exit;
            }

            $ProductData = [];
            $OrderProductids = [];

            // 循环商品信息
            foreach ($product as $item) {
                $pronum = intval($item['pronum']);
                $stock = isset($item['products']['stock']) ? $item['products']['stock'] : 0;
                $stock = intval($stock);
                $OrderProductids[] = $item['id'];

                $ProductData[] = [
                    'id' => $item['proid'],
                    'stock' => bcadd($pronum, $stock)
                ];
            }

            // 更新用户余额
            $amount = $order['amount'];
            $money = $business['money'];
            $BusinessData = [
                'id' => $busid,
                'money' => bcadd($amount, $money)
            ];

            // 更新消费记录
            $RecordData = [
                'busid' => $busid,
                'content' => "取消购物订单：" . $order['code'] . "退款",
                'total' => $amount,
            ];

            $OrderStatus = $this->OrderModel->destroy($orderid, true);

            if ($OrderStatus === FALSE) {
                $this->error('删除订单失败');
                exit;
            }

            // 更新商品库存
            $ProductStatus = $this->ProductModel->isUpdate(true)->saveAll($ProductData);

            if ($ProductStatus === FALSE) {
                $this->OrderModel->rollback();
                $this->error('更新商品库存失败');
                exit;
            }

            // 更新用户余额
            $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

            if ($BusinessStatus === FALSE) {
                $this->ProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error('更新用户余额失败');
                exit;
            }

            // 更新消费记录
            $RecordStatus = $this->RecordModel->validate('common/Business/Record')->save($RecordData);

            if ($RecordStatus === FALSE) {
                $this->BusinessModel->rollback();
                $this->ProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error('追加消费记录失败');
                exit;
            }

            // 删除订单商品
            $OrderProductStatus = $this->OrderProductModel->destroy($OrderProductids);

            if ($OrderProductStatus === FALSE) {
                $this->RecordModel->rollback();
                $this->BusinessModel->rollback();
                $this->ProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error('删除订单商品失败');
                exit;
            }

            if ($OrderStatus === FALSE || $ProductStatus === FALSE || $BusinessStatus === FALSE || $RecordStatus === FALSE || $OrderProductStatus === FALSE) {
                $this->OrderProductModel->rollback();
                $this->RecordModel->rollback();
                $this->BusinessModel->rollback();
                $this->ProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error('取消订单失败');
                exit;
            } else {
                $this->OrderProductModel->commit();
                $this->RecordModel->commit();
                $this->BusinessModel->commit();
                $this->ProductModel->commit();
                $this->OrderModel->commit();
                $this->success('取消订单成功');
                exit;
            }
        }
    }

    // 确认收货
    public function confirm()
    {
        // 如果有Post请求
        if ($this->request->isPost()) {

            // 获得客户ID
            $busid = $this->request->param('busid', 0, 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 获得订单ID
            $orderid = $this->request->param('orderid', 0, 'trim');

            // 判断订单是否存在
            $order = $this->OrderModel->find($orderid);

            if (empty($order)) {
                $this->error('订单不存在');
                exit;
            }

            // 更新订单状态
            $OrderData = [
                'id' => $orderid,
                'status' => '3'
            ];

            $OrderStatus = $this->OrderModel->isUpdate(true)->save($OrderData);

            if ($OrderStatus === FALSE) {
                $this->error('更新订单状态失败');
                exit;
            }

            $this->success('确认收货成功');
        }
    }

    // 查看物流
    public function express()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $orderid = $this->request->param('orderid', 0, 'trim');

            //先判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            $order = $this->OrderModel->find($orderid);

            if (!$order) {
                $this->error('订单不存在');
                exit;
            }

            if ($order['status'] <= '1' || empty($order['expresscode'])) {
                $this->error('订单未发货');
                exit;
            }
            $order['expresscode'] = 'YT2509565537853';

            // 物流查询
            $express = query_express($order['expresscode']);

            // var_dump($express);
            // exit;

            if ($express) {
                $this->success('查询物流信息成功', null, $express);
                exit;
            } else {
                $this->error('物流信息查询失败');
                exit;
            }
        }
    }

    // 退货退款
    public function refund()
    {
        // 如果有Post请求
        if ($this->request->isPost()) {

            // 接收数据
            $busid = $this->request->param('busid', 0, 'trim');
            $orderid = $this->request->param('orderid', 0, 'trim');
            $reason = $this->request->param('reason', '', 'trim');
            $remark = $this->request->param('remark', '', 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 判断订单是否存在
            $order = $this->OrderModel->with('address')->find($orderid);

            if (!$order) {
                $this->error('订单不存在');
                exit;
            }

            // 判断订单是否属于该用户
            if ($order['busid'] != $busid) {
                $this->error('订单不属于该用户');
                exit;
            }

            // 判断订单状态是否允许退货退款
            if ($order['status'] != '2' && $order['status'] != '3' && $order['status'] != '4') {
                $this->error('订单状态不允许退货退款');
                exit;
            }

            // 订单表 增加一个退货退款状态 退货退款原因 退货退款备注
            // 订单商品表
            // 退货表
            // 退货商品表

            // 开启事务
            $this->OrderModel->startTrans();
            $this->BackModel->startTrans();
            $this->BackProductModel->startTrans();

            // 更新订单表
            $OrderData = [
                'id' => $orderid,
                'status' => '-2',
                'refundreason' => $reason,
            ];

            $OrderStatus = $this->OrderModel->isUpdate(true)->save($OrderData);

            if ($OrderStatus === FALSE) {
                $this->error('更新订单状态失败');
                exit;
            }

            // 组装数据
            // 先生成一个退货单号
            $code = build_code("BP");
            $data = [
                'code' => $code,
                'ordercode' => $order['code'],
                'busid' => $busid,
                'contact' => $order['address']['consignee'],
                'phone' => $order['address']['mobile'],
                'address' => $order['address']['address'],
                'province' => $order['address']['province'],
                'city' => $order['address']['city'],
                'district' => $order['address']['district'],
                'amount' => $order['amount'],
                'expressid' => $order['expressid'],
                'expresscode' => $order['expresscode'],
                'remark' => $remark,
                'status' => '0',
                'reason' => $reason,
                'adminid' => $order['adminid'],
            ];

            // 如果有图片上传
            if (isset($_FILES['thumbs']) && $_FILES['thumbs']['error'] == 0) {
                $success = build_upload('thumbs');

                // 如果上传失败，就提醒
                if (!$success['result']) {
                    $this->error($success['msg']);
                    exit;
                }

                // 如果上传成功
                $data['thumbs'] = $success['data'];
            }

            // 先存储上传的图片
            $thumbs = isset($data['thumbs']) ? $data['thumbs'] : '';

            // 退货表
            $BackStatus = $this->BackModel->validate('common/Depot/Back')->save($data);

            if ($BackStatus === FALSE) {

                // 回滚事务
                $this->OrderModel->rollback();
                $this->error($this->BackModel->getError());
                exit;
            }

            // 退货商品表
            $BackProductData = [];

            // 查询订单商品表
            $product = $this->OrderProductModel->with(['products'])->where(['orderid' => $orderid])->select();

            if (!$product) {
                $this->error('订单商品不存在');
                exit;
            }

            // 循环订单商品表
            foreach ($product as $item) {
                $BackProductData[] = [
                    'backid' => $this->BackModel->id,
                    'proid' => $item['proid'],
                    'nums' => $item['pronum'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ];
            }

            $BackProductStatus = $this->BackProductModel->validate('common/Depot/BackProduct')->saveAll($BackProductData);

            if ($BackProductStatus === FALSE) {
                $this->BackModel->rollback();
                $this->OrderModel->rollback();
                $this->error($this->BackProductModel->getError());
                exit;
            }

            if ($OrderStatus === FALSE || $BackStatus === FALSE || $BackProductStatus === FALSE) {

                // 回滚事务
                $this->BackProductModel->rollback();
                $this->BackModel->rollback();
                $this->OrderModel->rollback();

                // 删除上传的图片
                if (!empty($thumbs)) {
                    // 判断图片是否存在并删除
                    is_file("." . $item) && @unlink("." . $item);
                }

                $this->error('退货退款失败');
                exit;
            } else {
                $this->BackProductModel->commit();
                $this->BackModel->commit();
                $this->OrderModel->commit();
                $this->success('退货退款成功');
                exit;
            }
        }
    }

    // 评价
    public function comment()
    {
        // 如果有Post请求
        if ($this->request->isPost()) {

            // 接收数据
            $busid = $this->request->param('busid', 0, 'trim');
            $orderid = $this->request->param('orderid', 0, 'trim');
            $content = $this->request->param('content', '', 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 判断订单是否存在
            $order = $this->OrderModel->find($orderid);

            if (!$order) {
                $this->error('订单不存在');
                exit;
            }

            // 判断订单是否属于该用户
            if ($order['busid'] != $busid) {
                $this->error('订单不属于该用户');
                exit;
            }

            // 判断订单状态是否允许评价
            if ($order['status'] != '3') {
                $this->error('订单状态不允许评价');
                exit;
            }

            // 如果已经评论，就不允许再次评论
            if ($order['comment'] != '') {
                $this->error('订单已经评论，不允许再次评论');
                exit;
            }

            // 组装数据 
            $data = [
                'id' => $orderid,
                'comment' => $content,
            ];

            // 如果有图片上传
            if (isset($_FILES['thumbs']) && $_FILES['thumbs']['error'] == 0) {
                $success = build_upload('thumbs');

                // 如果上传失败，就提醒
                if (!$success['result']) {
                    $this->error($success['msg']);
                    exit;
                }

                // 如果上传成功
                $data['comment_thumbs'] = $success['data'];
            }

            // 先存储上传的图片
            $thumbs = isset($data['comment_thumbs']) ? $data['comment_thumbs'] : '';

            // 更新订单表
            $OrderStatus = $this->OrderModel->isUpdate(true)->save($data);

            if ($OrderStatus === FALSE) {

                // 删除上传的图片
                if (!empty($thumbs)) {
                    // 判断图片是否存在并删除
                    is_file("." . $thumbs) && @unlink("." . $thumbs);
                }

                $this->error($this->OrderModel->getError());
                exit;
            }

            $this->success('评价成功');
        }
    }
}