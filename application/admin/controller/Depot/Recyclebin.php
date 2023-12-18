<?php

namespace app\admin\controller\Depot;

use app\common\controller\Backend;

class Recyclebin extends Backend
{
    // 设置关联查询
    // protected $relationSearch = true;

    // 当前模型
    protected $model = null;

    // 当前无须登录方法
    protected $noNeedLogin = [];

    // 无需鉴权的方法,但需要登录
    protected $noNeedRight = [];

    // 构造函数
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 接收数据
        if ($this->request->isAjax()) {

            // 接收get参数
            $action = isset($_GET['action']) ? trim($_GET['action']) : '';

            // 判断是哪个表格
            if ($action == 'storage') {

                // 将控制器和模型关联
                $this->model = model('Depot.Storage');
            } else if ($action == 'back') {

                // 将控制器和模型关联
                $this->model = model('Depot.Back');
            } else {
                $this->error('参数错误');
                exit;
            }

            // 获取表格所提交的参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 获取数据总数
            $total = $this->model->onlyTrashed()->count();

            // 获取分页数据
            $list = $this->model
                ->onlyTrashed()
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 组装数据
            $result = array("total" => $total, "rows" => $list);

            // 返回数据
            return json($result);
        }
        return $this->view->fetch();
    }

    // 恢复
    public function restore($ids = NULL, $action = NULL)
    {
        // 判断是哪个表格
        if ($action == 'storage') {

            // 将控制器和模型关联
            $this->model = model('Depot.Storage');
        } else if ($action == 'back') {

            // 将控制器和模型关联
            $this->model = model('Depot.Back');
        } else {
            $this->error('参数错误');
            exit;
        }

        // 根据id判断数据是否存在
        $rows = $this->model->onlyTrashed()->whereIn('id', $ids)->select();

        if (!$rows) {
            $this->error(__('No Results were found'));
            exit;
        }

        $result = $this->model->onlyTrashed()->whereIn('id', $ids)->update(['deletetime' => NULL]);

        if ($result === FALSE) {
            $this->error('恢复数据失败');
            exit;
        } else {
            $this->success('恢复数据成功');
            exit;
        }
    }

    // 真实删除
    public function destroy($ids = NULL, $action = NULL)
    {
        // 判断是哪个表格
        if ($action == 'storage') {

            // 将控制器和模型关联
            $this->model = model('Depot.Storage');
            $this->StorageProductModel = model('Depot.StorageProduct');

            // 根据id判断数据是否存在
            $rows = $this->model->onlyTrashed()->where(['id' => ['in', $ids]])->select();

            if (!$rows) {
                $this->error(__('No Results were found'));
                exit;
            }

            // 开启事务
            $this->model->startTrans();
            $this->StorageProductModel->startTrans();

            // 删除入库单
            $StorageStatus = $this->model->onlyTrashed()->where(['id' => ['in', $ids]])->delete(true);

            // 如果失败
            if ($StorageStatus === FALSE) {
                $this->error($this->model->getError());
                exit;
            }

            // 删除入库商品
            $Proids = $this->StorageProductModel->whereIn('storageid', $ids)->column('id');

            $StorageProductStatus = $this->StorageProductModel->destroy($Proids);

            // 如果失败
            if ($StorageProductStatus === FALSE) {

                // 回滚事务
                $this->model->rollback();
                $this->error($this->StorageProductModel->getError());
                exit;
            }

            // 大判断
            if ($StorageStatus && $StorageProductStatus) {

                // 提交事务
                $this->model->commit();
                $this->StorageProductModel->commit();
                $this->success('删除数据成功');
                exit;
            } else {

                // 回滚事务
                $this->model->rollback();
                $this->StorageProductModel->rollback();
                $this->error('删除数据失败');
                exit;
            }
        } else if ($action == 'back') {

            // 将控制器和模型关联
            $this->model = model('Depot.Back');
            $this->BackProductModel = model('Depot.BackProduct');

            // 根据id判断数据是否存在
            $rows = $this->model->onlyTrashed()->whereIn('id', $ids)->select();

            if (!$rows) {
                $this->error(__('No Results were found'));
                exit;
            }

            // 记录要删除数据的thumbs路径
            $thumbs = [];
            foreach ($rows as $row) {
                $thumbs[] = $row['thumbs'];
            }

            // 开启事务
            $this->model->startTrans();
            $this->BackProductModel->startTrans();

            // 删除退货单
            $BackStatus = $this->model->onlyTrashed()->where(['id' => ['in', $ids]])->delete(true);

            // 如果失败
            if ($BackStatus === FALSE) {
                $this->error($this->model->getError());
                exit;
            }

            // 删除退货商品
            $Proids = $this->BackProductModel->whereIn('backid', $ids)->column('id');
            $BackProductStatus = $this->BackProductModel->destroy($Proids);

            // 如果失败
            if ($BackProductStatus === FALSE) {

                // 回滚事务
                $this->model->rollback();
                $this->error($this->BackProductModel->getError());
                exit;
            }

            // 大判断
            if ($BackStatus === FALSE && $BackProductStatus === FALSE) {

                // 回滚事务
                $this->model->rollback();
                $this->BackProductModel->rollback();
                $this->error('删除数据失败');
                exit;
            } else {

                // 提交事务
                $this->model->commit();
                $this->BackProductModel->commit();

                // 删除退货单图片
                foreach ($thumbs as $thumb) {
                    @is_file('.' . $thumb) && @unlink('.' . $thumb);
                }

                $this->success('删除数据成功');
                exit;
            }
        } else {
            $this->error('参数错误');
            exit;
        }
    }
}

?>