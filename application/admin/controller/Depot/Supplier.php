<?php

namespace app\admin\controller\Depot;

use app\common\controller\Backend;

class Supplier extends Backend
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
        $this->model = model('Depot.Supplier');
        $this->RegionModel = model('Region');
    }

    /**
     * 供应商管理列表
     * @return mixed
     */
    public function index()
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 接收数据
        if ($this->request->isAjax()) {

            // 获取表格所提交的参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams(); // buildparams()方法在Backend控制器中

            // 表格需要两个参数，total和rows，total为数据总数，rows为分页数据
            // 获取数据总数
            $total = $this->model->count();

            // 获取分页数据
            $list = $this->model
                ->with(['province', 'city', 'district'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 返回数据
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        // 渲染视图
        return $this->view->fetch();
    }

    /**
     * 添加供应商
     * @return mixed
     */
    public function add()
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 接收数据
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            // 组装数据
            $data = [
                'name' => $params['name'],
                'address' => $params['address'],
                'mobile' => $params['mobile'],
            ];

            if (!empty($params['code'])) {

                // 根据code查询出parentpath
                $parentpath = $this->RegionModel->where(['code' => $params['code']])->value('parentpath');

                if (!$parentpath) {
                    $this->error('所选的地区不存在，请重新选择');
                    exit;
                }

                $path = explode(',', $parentpath);

                // 组装数据
                $data['province'] = $path[0] ?? '';
                $data['city'] = $path[1] ?? '';
                $data['district'] = $path[2] ?? '';
            }

            // 插入数据
            $result = $this->model->validate('common/Depot/Supplier')->save($data);

            if ($result === false) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('添加供应商成功');
                exit;
            }
        }

        // 渲染视图
        return $this->view->fetch();
    }

    /**
     * 编辑供应商
     * @return mixed
     */
    public function edit($ids = null)
    {
        // 根据id判断数据是否存在
        $row = $this->model->find($ids);

        if (empty($row)) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 接收数据
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            // 组装数据
            $data = [
                'id' => $ids,
                'name' => $params['name'],
                'address' => $params['address'],
                'mobile' => $params['mobile'],
            ];

            if (!empty($params['code'])) {

                // 根据code查询出parentpath
                $parentpath = $this->RegionModel->where(['code' => $params['code']])->value('parentpath');

                if (!$parentpath) {
                    $this->error('所选的地区不存在，请重新选择');
                    exit;
                }

                $path = explode(',', $parentpath);

                // 组装数据
                $data['province'] = $path[0] ?? '';
                $data['city'] = $path[1] ?? '';
                $data['district'] = $path[2] ?? '';
            }

            // 插入数据
            $result = $this->model->validate('common/Depot/Supplier')->isUpdate(true)->save($data);

            if ($result === false) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('编辑供应商成功');
                exit;
            }

        }

        // 地区数据回显
        $row['regionCode'] = $row['district'] ?: ($row['city'] ?: $row['province']);
        
        // 将数据赋值给模板
        $this->view->assign('row', $row);

        // 渲染模板
        return $this->view->fetch();
    }

    /**
     * 删除供应商
     * @return mixed
     */
    public function del($ids = null)
    {
        // 根据id判断数据是否存在
        $row = $this->model->find($ids);

        if (empty($row)) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 删除数据
        $result = $this->model->destroy($ids);

        if ($result === false) {
            $this->error($this->model->getError());
            exit;
        } else {
            $this->success('删除供应商成功');
            exit;
        }
    }
}

?>
