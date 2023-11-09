<?php
namespace app\shop\controller;

use think\Controller;

class Cart extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->ProductModel = model('Product.Product');
        $this->CategoryModel = model('Product.Category');
        $this->CartModel = model('Product.Cart');
        $this->BusinessModel = model('Business.Business');
        $this->AddressModel = model('Business.Address');
    }

    //购物车列表
    public function index()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $ids = $this->request->param('ids', 0, 'trim');

            //先判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            $where = ['busid' => $busid];

            if (!empty($ids)) {
                $where['cart.id'] = ['in', $ids];
            }

            $list = $this->CartModel->with(['product'])->where($where)->select();

            if ($list) {
                $this->success('返回购物车数据', null, $list);
                exit;
            } else {
                $this->error('购物车暂无数据');
                exit;
            }
        }
    }

    //添加购物车
    public function add()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $proid = $this->request->param('proid', 0, 'trim');

            //先判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            //先判断商品是否存在
            $product = $this->ProductModel->find($proid);

            if (!$product) {
                $this->error('商品不存在');
                exit;
            }

            //先去查购物车表中是否有当前这个商品
            $where = [
                'proid' => $proid,
                'busid' => $busid
            ];

            $cart = $this->CartModel->where($where)->find();

            if ($cart) {
                //执行更新语句

                //组装数据
                $nums = bcadd($cart['nums'], 1);
                $price = $product['price'];
                $total = bcmul($price, $nums);
                $data = [
                    'id' => $cart['id'],
                    'nums' => $nums,
                    'price' => $price,
                    'total' => $total
                ];

                //执行
                $result = $this->CartModel->validate('common/Product/Cart.edit')->isUpdate(true)->save($data);

                if ($result == FALSE) {
                    $this->error($this->CartModel->getError());
                    exit;
                } else {
                    $this->success('更新购物车数量成功');
                    exit;
                }


            } else {
                //插入语句
                // 组装数据
                $data = [
                    'busid' => $busid,
                    'proid' => $proid,
                    'nums' => 1,
                    'price' => $product['price'],
                    'total' => $product['price']
                ];

                //插入数据库
                $result = $this->CartModel->validate('common/Product/Cart')->save($data);

                if ($result === FALSE) {
                    $this->error($this->CartModel->getError());
                    exit;
                } else {
                    $this->success('添加购物车成功');
                    exit;
                }
            }
        }
    }

    // 编辑购物车
    public function edit()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $cartid = $this->request->param('cartid', 0, 'trim');
            $nums = $this->request->param('nums', 0, 'trim');

            //先判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            //先判断购物车记录是否存在
            $cart = $this->CartModel->find($cartid);

            if (!$cart) {
                $this->error('购物车记录不存在');
                exit;
            }

            $product = $this->ProductModel->find($cart['proid']);

            if (!$product) {
                $this->error('商品不存在');
                exit;
            }

            if ($nums <= 0) {
                $this->error('购物车数量有误');
                exit;
            }

            //更新购物车
            $price = $product['price'];
            $total = bcmul($price, $nums);
            $data = [
                'id' => $cart['id'],
                'nums' => $nums,
                'price' => $price,
                'total' => $total
            ];

            //执行
            $result = $this->CartModel->validate('common/Product/Cart.edit')->isUpdate(true)->save($data);

            if ($result == FALSE) {
                $this->error($this->CartModel->getError());
                exit;
            } else {
                $this->success('更新购物车数量成功');
                exit;
            }
        }
    }

    // 删除购物车
    public function del()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $cartid = $this->request->param('cartid', 0, 'trim');

            //先判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            //先判断购物车记录是否存在
            $cart = $this->CartModel->find($cartid);

            if (!$cart) {
                $this->error('购物车记录不存在');
                exit;
            }

            //执行
            $result = $this->CartModel->where(['id' => $cartid])->delete();

            if ($result == FALSE) {
                $this->error($this->CartModel->getError());
                exit;
            } else {
                $this->success('删除购物车成功');
                exit;
            }
        }
    }

    //返回地址
    public function address()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');

            //先判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            //查询当前用户的收货地址
            $address = $this->AddressModel->where(['busid' => $busid, 'status' => '1'])->find();

            if (!$address) {
                $address = $this->AddressModel->where(['busid' => $busid])->find();
            }

            if ($address) {
                $this->success('返回地址数据', null, $address);
                exit;
            } else {
                $this->error('暂无收货地址');
                exit;
            }
        }
    }
}
