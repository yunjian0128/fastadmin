<?php

namespace app\admin\controller\Subject;

use app\common\controller\Backend;

class Recyclebin extends Backend
{
    // 设置关联查询
    protected $relationSearch = true;

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
            if ($action == 'subject') {

                // 将控制器和模型关联
                $this->model = model('Subject.Subject');

                // 获取表格所提交的参数
                list($where, $sort, $order, $offset, $limit) = $this->buildparams(); // buildparams()方法在Backend控制器中

                // 获取数据总数
                $total = $this->model
                    ->onlyTrashed()
                    ->with(['category'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

                // 获取分页数据
                $list = $this->model
                    ->onlyTrashed()
                    ->with(['category'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            } else if ($action == 'order') {

                // 将控制器和模型关联
                $this->model = model('Subject.Order');

                // 获取表格所提交的参数
                list($where, $sort, $order, $offset, $limit) = $this->buildparams();

                // 获取数据总数
                $total = $this->model
                    ->onlyTrashed()
                    ->with(['subject', 'business'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

                // 获取分页数据
                $list = $this->model
                    ->onlyTrashed()
                    ->with(['subject', 'business'])
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
        if ($action == 'subject') {

            // 将控制器和模型关联
            $this->model = model('Subject.Subject');
        } else if ($action == 'order') {

            // 将控制器和模型关联
            $this->model = model('Subject.Order');
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
        if ($action == 'subject') {

            // 将控制器和模型关联
            $this->model = model('Subject.Subject');
            $this->ChapterModel = model('Subject.Chapter');

            // 根据id判断数据是否存在
            $rows = $this->model->onlyTrashed()->whereIn('id', $ids)->select();

            if (!$rows) {
                $this->error(__('No Results were found'));
                exit;
            }

            // 获取选中的用户图片地址并且追加数组里
            $thumbsList = $this->model->onlyTrashed()->whereIn('id', $ids)->column('thumbs');

            // 记录该课程的所有章节id
            $chapterIds = $this->ChapterModel->whereIn('subid', $ids)->column('id');

            // 根据章节id获取章节的所有图片地址
            $chapterUrlsList = $this->ChapterModel->whereIn('id', $chapterIds)->column('url');

            // 开启事务
            $this->model->startTrans();
            $this->ChapterModel->startTrans();

            // 真实删除
            $SubjectStatus = $this->model->onlyTrashed()->whereIn('id', $ids)->delete(true);

            if ($SubjectStatus === FALSE) {
                $this->error($this->model->getError());
                exit;
            }

            $ChapterStatus = $this->ChapterModel->destroy($chapterIds);

            // 如果失败
            if ($ChapterStatus === FALSE) {

                // 回滚事务
                $this->model->rollback();
                $this->error($this->ChapterModel->getError());
                exit;
            }

            // 大判断
            if ($SubjectStatus === FALSE || $ChapterStatus === FALSE) {

                // 回滚事务
                $this->ChapterModel->rollback();
                $this->model->rollback();
                $this->error('真实删除失败');
                exit;
            } else {

                // 批量删除用户图片
                if (!empty($thumbsList)) {
                    foreach ($thumbsList as $val) {
                        $src = substr($val, 1);
                        @is_file($src) && @unlink($src);
                    }
                }

                // 批量删除章节视频
                if (!empty($chapterUrlsList)) {
                    foreach ($chapterUrlsList as $val) {
                        $src = substr($val, 1);
                        @is_file($src) && @unlink($src);
                    }
                }
                $this->model->commit();
                $this->ChapterModel->commit();
                $this->success('真实删除数据成功');
                exit;
            }
        } else if ($action == 'order') {

            // 将控制器和模型关联
            $this->model = model('Subject.Order');

            // 根据id判断数据是否存在
            $rows = $this->model->onlyTrashed()->whereIn('id', $ids)->select();

            if (!$rows) {
                $this->error(__('No Results were found'));
                exit;
            }

            // 真实删除
            $result = $this->model->onlyTrashed()->whereIn('id', $ids)->delete(true);

            if ($result === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('真实删除数据成功');
                exit;
            }
        } else {
            $this->error('参数错误');
            exit;
        }
    }
}

?>