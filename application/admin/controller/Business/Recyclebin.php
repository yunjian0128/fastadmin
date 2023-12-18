<?php

namespace app\admin\controller\Business;

use app\common\controller\Backend;

/**
 * 客户回收管理控制器
 * @package app\admin\controller\Business
 */

class Recyclebin extends Backend
{
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

        // 将控制器和模型关联
        $this->model = model('Business.Business');
    }

    // 客户回收列表
    public function index()
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否有ajax请求
        if ($this->request->isAjax()) {

            // 获取表格所提交的参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams(); // buildparams()方法在Backend控制器中

            // 表格需要两个参数，total和rows，total为数据总数，rows为分页数据
            // 获取数据总数
            $total = $this->model
                ->onlytrashed()
                ->where($where)
                ->with(['admin', 'source'])
                ->count();

            // 获取分页数据
            $list = $this->model
                ->onlyTrashed()
                ->where($where)
                ->with(['admin', 'source'])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 返回到前台
            return json(['total' => $total, 'rows' => $list]);
        }

        // 将数据赋值给模板
        return $this->view->fetch();
    }

    // 删除
    public function del($ids = null)
    {
        $id = empty($ids) ? [] : explode(',', $ids);

        // 用户图片列表
        $avatarList = [];

        // 判断是否有没有当前数据
        foreach ($id as $item) {
            // onlyTrashed()  仅查询软删除数据
            $res = $this->model->onlyTrashed()->find($item);
            if (!$res) {
                $this->error('未找当前用户');
            }

            // 获取选中的用户头像地址并且追加数组里
            array_push($avatarList, $res['avatar']);
        }

        // 过滤
        $avatarList = array_filter($avatarList);
        $result = $this->model->onlyTrashed()->destroy($id, true);

        if ($result === FALSE) {
            $this->error($this->model->getError());
            exit;
        } else {
    
            // 批量删除用户图片
            foreach ($avatarList as $val) {
                $src = substr($val, 1);
                @is_file($src) && @unlink($src);
            }

            $this->success('删除成功');
            exit;
        }
    }

    // 还原
    public function reduction($ids = null)
    {
        $id = empty($ids) ? [] : explode(',', $ids);

        // 判断是否有没有当前数据
        foreach ($id as $item) {
            $res = $this->model->onlyTrashed()->find($item);
            if (!$res) {
                $this->error('未找当前用户');
            }
        }

        $wheres = [
            'id' => ['in', $id]
        ];

        $result = $this->model->onlyTrashed()->where($wheres)->update(['deletetime' => null]);

        if ($result === FALSE) {
            $this->error($this->model->getError());
        } else {
            $this->success('还原成功');
        }
    }
}

?>