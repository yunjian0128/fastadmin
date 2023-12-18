<?php

namespace app\admin\controller\Hotel;

use app\common\controller\Backend;

class Room extends Backend
{
    // 当前模型
    protected $model = null;

    // 联表查询
    protected $relationSearch = true;

    // 当前无须登录方法
    protected $noNeedLogin = [];

    // 无需鉴权的方法,但需要登录
    protected $noNeedRight = [];

    // 构造函数
    public function __construct()
    {
        parent::__construct();

        // 将控制器和模型关联
        $this->model = model('common/Hotel/Room');
    }

    // 客户列表
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
                ->where($where)
                ->order($sort, $order)
                ->count();

            // 获取分页数据
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 返回json数据
            return json(['total' => $total, 'rows' => $list]);
        }

        // 将数据赋值给模板
        return $this->view->fetch();
    }

    // 添加房间
    public function add()
    {

        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否有ajax请求
        if ($this->request->isAjax()) {

            // 获取表单数据
            $params = $this->request->post('row/a');

            // 组装数据
            $data = [
                'name' => $params['name'],
                'thumb' => $params['thumb'],
                'price' => $params['price'],
                'content' => $params['content'],
                'total' => $params['total'],
                'flag' => $params['flag'],
            ];

            // 把data['flag']的所有逗号转化为英文逗号
            $data['flag'] = str_replace('，', ',', $data['flag']);

            // 把data['flag']的所有空格转化为英文逗号
            $data['flag'] = str_replace(' ', ',', $data['flag']);

            // 添加数据
            $result = $this->model->validate('common/Hotel/Room')->save($data);

            if ($result === false) {
                return json(['code' => 0, 'msg' => $this->model->getError()]);
            } else {
                return json(['code' => 1, 'msg' => '添加房间成功']);
            }
        }

        // 将数据赋值给模板
        return $this->view->fetch();
    }

    // 编辑房间
    public function edit($ids = NULL)
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断房间是否存在
        $row = $this->model->find($ids);

        // 房间不存在
        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 判断是否有ajax请求
        if ($this->request->isAjax()) {

            // 获取表单数据
            $params = $this->request->post('row/a');

            // 组装数据
            $data = [
                'id' => $ids,
                'name' => $params['name'],
                'thumb' => $params['thumb'],
                'price' => $params['price'],
                'content' => $params['content'],
                'total' => $params['total'],
                'flag' => $params['flag'],
            ];

            // 把data['flag']的所有逗号转化为英文逗号
            $data['flag'] = str_replace('，', ',', $data['flag']);

            // 编辑数据
            $result = $this->model->validate('common/Hotel/Room.edit')->isUpdate(true)->save($data);

            // 有新图片上传就删除旧图片
            if ($params['thumb'] != $row['thumb']) {

                // 字符串分割成数组
                $thumbs_row = explode(',', $row['thumb']);
                $thumbs_parmas = explode(',', $params['thumb']);

                // 交集
                $commonthumbs = array_intersect($thumbs_row, $thumbs_parmas);

                // 删除的图片 = 旧图片 - 交集
                $delete_thumbs = array_diff($thumbs_row, $commonthumbs);

                foreach ($delete_thumbs as $item) {

                    // 判断图片是否存在并删除
                    is_file("." . $item) && @unlink("." . $item);
                }
            }

            // 判断是否编辑成功
            if ($result === false) {
                return json(['code' => 0, 'msg' => $this->model->getError()]);
            } else {
                return json(['code' => 1, 'msg' => '编辑房间成功']);
            }
        }

        // 获取数据
        $row = $this->model->get($ids);

        // 将数据赋值给模板
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    // 删除房间
    public function del($ids = NULL)
    {
        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断房间是否存在
        $row = $this->model->find($ids);

        // 房间不存在
        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }

        // 判断是否有ajax请求
        if ($this->request->isAjax()) {

            // 删除数据
            $result = $this->model->destroy($ids);

            if ($result === false) {
                return json(['code' => 0, 'msg' => $this->model->getError()]);
            } else {
                return json(['code' => 1, 'msg' => '删除房间成功']);
            }
        }
    }
}

?>