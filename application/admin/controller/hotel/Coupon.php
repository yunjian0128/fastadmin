<?php

namespace app\admin\controller\Hotel;

use app\common\controller\Backend;

class Coupon extends Backend
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
        $this->model = model('common/Hotel/Coupon');
    }

    // 优惠券列表
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

    // 添加优惠券
    public function add()
    {

        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断是否有ajax请求
        if ($this->request->isAjax()) {

            // 获取表单数据
            $params = $this->request->post('row/a');

            // 将接收到的开始时间和结束时间转换为时间戳
            $params['createtime'] = strtotime($params['createtime']);
            $params['endtime'] = strtotime($params['endtime']);

            // 如果已到结束时间，将优惠券状态改为已结束
            if ($params['endtime'] < time()) {
                $params['status'] = 0;
            } else {
                $params['status'] = 1;
            }

            // 组装数据
            $data = [
                'title' => $params['title'],
                'rate' => $params['rate'],
                'total' => $params['total'],
                'createtime' => $params['createtime'],
                'endtime' => $params['endtime'],
                'thumb' => $params['thumb'],
                'status' => $params['status'],
            ];

            // 将data['rate']类型转换为浮点数 保留两位小数
            // 将$data['rate']类型转换为浮点数并保留两位小数
            $data['rate'] = number_format(floatval($data['rate']), 2, '.', '');

            // 将data['total']类型转换为整型
            $data['total'] = intval($data['total']);

            // 添加数据
            $result = $this->model->validate('common/Hotel/Coupon.add')->save($data);

            if ($result === FALSE) {
                return json(['code' => 0, 'msg' => $this->model->getError()]);
            } else {
                return json(['code' => 1, 'msg' => '添加优惠券成功']);
            }
        }

        // 将数据赋值给模板
        return $this->view->fetch();
    }

    // 编辑优惠券
    public function edit($ids = NULL)
    {

        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断优惠券是否存在
        $row = $this->model->get($ids);

        if (!$row) {
            $this->error('优惠券不存在');
            exit;
        }

        // 判断是否有ajax请求
        if ($this->request->isAjax()) {

            // 获取表单数据
            $params = $this->request->post('row/a');

            // 将接收到的开始时间和结束时间转换为时间戳
            $params['createtime'] = strtotime($params['createtime']);
            $params['endtime'] = strtotime($params['endtime']);

            // 如果已到结束时间，将优惠券状态改为已结束
            if ($params['endtime'] < time()) {
                $params['status'] = 0;
            } else {
                $params['status'] = 1;
            }

            // 组装数据
            $data = [
                'title' => $params['title'],
                'rate' => $params['rate'],
                'total' => $params['total'],
                'createtime' => $params['createtime'],
                'endtime' => $params['endtime'],
                'thumb' => $params['thumb'],
                'status' => $params['status'],
            ];

            // 将data['rate']类型转换为浮点数并保留两位小数
            $data['rate'] = number_format(floatval($data['rate']), 2, '.', '');

            // 将data['total']类型转换为整型
            $data['total'] = intval($data['total']);

            // 编辑数据
            $result = $this->model->validate('common/Hotel/Coupon.edit')->save($data, ['id' => $ids]);

            if ($result === FALSE) {
                return json(['code' => 0, 'msg' => $this->model->getError()]);
            }

            // 如果有新图片上传，删除旧图片
            if ($params['thumb'] != $row['thumb']) {

                // 判断图片是否存在并删除
                is_file("." . $row['thumb']) && @unlink("." . $row['thumb']);
            }

            return json(['code' => 1, 'msg' => '编辑优惠券成功']);
        }

        // 将数据赋值给模板
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    // 删除优惠券
    public function del($ids = NULL)
    {

        // 将请求当中的所有参数去除html标签，去掉两边空白
        $this->request->filter(['strip_tags', 'trim']);

        // 判断优惠券是否存在
        $rows = $this->model->where('id', 'in', $ids)->select();

        if (!$rows) {
            $this->error('优惠券不存在');
            exit;
        }

        // 判断是否有ajax请求
        if ($this->request->isAjax()) {

            // 选择出所有要删除的数据的图片
            $thumblist = [];

            foreach ($rows as $item) {

                // 字符串分割成数组
                $thumb = explode(',', $item['thumb']);
                $thumblist[] = $thumb;
            }

            // 真实删除
            $result = $this->model->where('id', 'in', $ids)->delete();

            if ($result === FALSE) {
                return json(['code' => 0, 'msg' => '删除优惠券失败']);
            }

            // 删除图片
            foreach ($thumblist as $value) {
                foreach ($value as $item) {

                    // 判断图片是否存在并删除
                    is_file("." . $item) && @unlink("." . $item);
                }
            }

            return json(['code' => 1, 'msg' => '删除优惠券成功']);
        }
    }
}

?>
