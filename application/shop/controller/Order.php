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
            $page = $this->request->param('page', 1, 'trim');
            $status = $this->request->param('status', 0, 'trim');
            $limit = 8;

            //偏移量
            $offset = ($page - 1) * $limit;

            $where = ['busid' => $this->business['id']];

            if ($status != 0) {
                $where['status'] = $status;
            }

            $list = $this->OrderModel
                ->where($where)
                ->order('id', 'desc')
                ->limit($offset, $limit)
                ->select();

            if ($list) {
                $this->success('返回订单数据', null, $list);
                exit;
            } else {
                $this->error('暂无更多订单数据');
                exit;
            }
        }
    }


    //下单
    public function add()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $cartids = $this->request->param('cartids', 0, 'trim');
            $addrid = $this->request->param('addrid', 0, 'trim');
            $remark = $this->request->param('remark', '', 'trim');

            //先判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            //判断是否有购物车记录
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

            //判断商品的库存是否充足
            foreach ($cart as $item) {
                // 商品库存
                $stock = isset($item['product']['stock']) ? $item['product']['stock'] : 0;
                $proname = isset($item['product']['name']) ? $item['product']['name'] : '';

                if ($item['nums'] > $stock) {
                    $this->error("$proname 商品库存不足");
                    exit;
                }
            }

            //先判断余额是否充足
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

            //开启事务
            $this->OrderModel->startTrans();
            $this->OrderProductModel->startTrans();
            $this->ProductModel->startTrans();
            $this->BusinessModel->startTrans();
            $this->RecordModel->startTrans();
            $this->CartModel->startTrans();


            //订单表
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

            //订单商品表
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

                //更新商品的库存
                $stock = isset($item['product']['stock']) ? $item['product']['stock'] : 0;

                //更新后的库存
                $UpdateStock = bcsub($stock, $item['nums']);
                $UpdateStock = $UpdateStock <= 0 ? 0 : $UpdateStock;

                //组装数据
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

            //更新商品库存
            $ProductStatus = $this->ProductModel->isUpdate(true)->saveAll($ProductData);

            if ($ProductStatus === FALSE) {
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error($this->ProductModel->getError());
                exit;
            }

            //用户表更新余额
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

            //消费记录
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

            //购物车表执行删除语句
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
}
