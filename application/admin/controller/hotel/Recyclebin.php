<?php

namespace app\admin\controller\Hotel;

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

        // 判断是否有ajax请求
        if ($this->request->isAjax()) {

            // 接收get参数
            $action = isset($_GET['action']) ? trim($_GET['action']) : '';

            // 判断是哪个表格
            if ($action == 'room') {

                // 将控制器和模型关联
                $this->model = model('Hotel.Room');

                // 获取表格所提交的参数
                list($where, $sort, $order, $offset, $limit) = $this->buildparams(); // buildparams()方法在Backend控制器中

                // 获取数据总数
                $total = $this->model->onlyTrashed()->count();

                // 获取分页数据
                $list = $this->model
                    ->onlyTrashed()
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            } else if ($action == 'order') {

                // 将控制器和模型关联
                $this->model = model('Hotel.Order');

                // 获取表格所提交的参数
                list($where, $sort, $order, $offset, $limit) = $this->buildparams();

                // 获取数据总数
                $total = $this->model
                    ->onlyTrashed()
                    ->count();

                // 获取分页数据
                $list = $this->model
                    ->onlyTrashed()
                    ->with(['business', 'room'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            } else {
                $this->error('参数错误');
                exit;
            }

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
        if ($action == 'room') {

            // 将控制器和模型关联
            $this->model = model('Hotel.Room');
        } else if ($action == 'order') {

            // 将控制器和模型关联
            $this->model = model('Hotel.Order');
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
            $this->error($this->model->getError());
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
        if ($action == 'room') {

            // 将控制器和模型关联
            $this->model = model('Hotel.Room');

        } else if ($action == 'order') {

            // 将控制器和模型关联
            $this->model = model('Hotel.Order');
        } else {
            $this->error('参数错误');
            exit;
        }

        // 根据id判断数据是否存在
        $rows = $this->model->withTrashed()->whereIn('id', $ids)->select();

        if (!$rows) {
            $this->error(__('No Results were found'));
            exit;
        }

        if ($action == 'room') {
            $thumbslist = [];

            foreach ($rows as $item) {

                // 字符串分割成数组
                $thumb = explode(',', $item['thumb']);
                $thumbslist[] = $thumb;
            }
        }

        // 真实删除
        $result = $this->model->withTrashed()->whereIn('id', $ids)->delete(true);

        if ($result === FALSE) {
            $this->error($this->model->getError());
            exit;
        }

        if ($action == 'room') {

            // 删除图片
            foreach ($thumbslist as $value) {
                foreach ($value as $item) {

                    // 判断图片是否存在并删除
                    is_file("." . $item) && @unlink("." . $item);
                }
            }
        }

        // 删除成功
        $this->success('真实删除成功');
    }
}

?>