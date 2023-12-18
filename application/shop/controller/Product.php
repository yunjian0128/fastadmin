<?php
namespace app\shop\controller;

use think\Controller;

class Product extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->ProductModel = model('Product.Product');
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $proid = $this->request->param('proid', 0, 'trim');

            $product = $this->ProductModel->find($proid);

            if ($product) {
                $this->success('返回商品数据', null, $product);
                exit;
            } else {
                $this->error('商品不存在');
                exit;
            }
        }
    }
}

?>