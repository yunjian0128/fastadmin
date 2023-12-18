<?php
namespace app\shop\controller;

use think\Controller;

class Index extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->ProductModel = model('Product.Product');
        $this->CategoryModel = model('Product.Category');
        $this->BusinessModel = model('Business.Business');
        $this->CartModel = model('Product.Cart');
    }

    // 首页数据
    public function index()
    {
        if ($this->request->isPost()) {

            // 轮播图 - flag = 3 推荐
            $top = $this->ProductModel->where(['flag' => '3'])->limit(5)->select();

            // 分类
            $type = $this->CategoryModel->limit(8)->select();

            // 新品首页
            $news = $this->ProductModel->where(['flag' => '1'])->limit(8)->select();
            $data = [
                'top' => $top,
                'type' => $type,
                'news' => $news,
            ];

            $this->success('首页数据', null, $data);
            exit;
        }
    }

    // 请求分类列表
    public function type()
    {
        if ($this->request->isPost()) {
            $list = $this->CategoryModel->select();

            if ($list) {
                $this->success('分类列表', null, $list);
                exit;
            } else {
                $this->error('暂无分类');
                exit;
            }
        }
    }

    // 商品数据列表
    public function list()
    {
        if ($this->request->isPost()) {
            $page = $this->request->param('page', 1, 'trim');
            $typeid = $this->request->param('typeid', 0, 'trim');
            $flag = $this->request->param('flag', '0', 'trim');
            $sort = $this->request->param('sort', 'createtime', 'trim');
            $by = $this->request->param('by', 'desc', 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');
            $limit = 8;

            // 偏移量
            $offset = ($page - 1) * $limit;

            // 查询分类名称
            $TypeName = $this->CategoryModel->where(['id' => $typeid])->value('name');
            $TypeName = empty($TypeName) ? '全部分类' : $TypeName;
            $where = [];

            // 关键词不为空
            if (!empty($keywords)) {
                $where['name'] = ['like', "%$keywords%"];
            }

            // 分类筛选
            if ($typeid) {
                $where['typeid'] = $typeid;
            }

            // 标签筛选
            if ($flag != "0") {
                $where['flag'] = $flag;
            }

            $list = $this->ProductModel
                ->where($where)
                ->order($sort, $by)
                ->limit($offset, $limit)
                ->select();

            $data = [
                'TypeName' => $TypeName,
                'list' => $list
            ];

            if ($list) {
                $this->success('返回商品数据', null, $data);
                exit;
            } else {
                $this->error('暂无更多商品数据');
                exit;
            }
        }
    }

    // 商品信息
    public function product()
    {
        if ($this->request->isPost()) {
            $proid = $this->request->param('proid', 0, 'trim');
            $product = $this->ProductModel->with(['category', 'unit'])->find($proid);

            if ($product) {
                $this->success('返回商品数据', null, $product);
                exit;
            } else {
                $this->error('无商品数据');
                exit;
            }
        }
    }

    // 获取购物车数据
    public function count()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');

            // 判断当前用户是否存在
            $bussniess = $this->BusinessModel->where(['id' => $busid])->find();

            if (!$bussniess) {
                $this->error('用户不存在');
                exit;
            }

            // 获取购物车内总商品数量
            $count = $this->CartModel->where(['busid' => $busid])->sum('nums');

            // 返回数据
            $this->success('购物车数量', null, $count);
            exit;
        }
    }
}

?>
