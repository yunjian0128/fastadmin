<?php
namespace app\pay\controller;

use think\Controller;

class Index extends Controller
{
    // 管理员模型
    protected $AdminModel = null;

    // 支付模型
    protected $PayModel = null;

    public function __construct()
    {
        parent::__construct();

        $this->AdminModel = model('Admin');
        $this->PayModel = new \app\common\model\Pay\Pay;
    }

    // 登录
    public function login()
    {
        if ($this->request->isPost()) {
            $username = $this->request->param('username', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            // 查询管理员是否存在
            $admin = $this->AdminModel->where(['username' => $username])->find();

            if (!$admin) {
                return json(['code' => 0, 'msg' => '账号不存在', 'data' => null]);
            }

            // 匹配密码
            $password = md5(md5($password) . $admin['salt']);

            if ($password !== $admin['password']) {
                return json(['code' => 0, 'msg' => '密码错误', 'data' => null]);
            }

            if ($admin['status'] !== 'normal') {
                return json(['code' => 0, 'msg' => '账号已被禁用', 'data' => null]);
            }

            // 封装返回数据
            $data = [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'nickname' => $admin['nickname'],
                'avatar_cdn' => $admin['avatar_text']
            ];

            return json(['code' => 1, 'msg' => '登录成功', 'data' => $data]);
        }
    }

    // 当客户端监听到收款通知后发起请求到这里获取相关的数据
    public function check()
    {
        if ($this->request->isPost()) {
            $price = $this->request->param('price', 0, 'trim');
            $adminid = $this->request->param('adminid', 0, 'trim');
            $paytime = $this->request->param('paytime', '', 'trim');

            // 查询管理员是否存在
            $admin = $this->AdminModel->find($adminid);

            if (!$admin) {
                return json(['code' => 0, 'msg' => '账号不存在', 'data' => null]);
            }
            if ($admin['status'] !== 'normal') {
                return json(['code' => 0, 'msg' => '账号已被禁用', 'data' => null]);
            }

            $pay = $this->PayModel->where(['price' => $price, 'status' => 0])->find();

            if (!$pay) {
                return json(['code' => 0, 'msg' => '查询不到该订单', 'data' => null]);
            }

            $paytime = strtotime($paytime);

            $data = [
                'id' => $pay['id'],
                'status' => 1,
                'paytime' => $paytime
            ];

            $result = $this->PayModel->isUpdate(true)->save($data);

            if ($result === false) {
                return json(['code' => 0, 'msg' => '更新订单状态失败', 'data' => null]);
            }

            // 获取更新后的订单数据
            $UpdatePayData = $this->PayModel->find($pay['id']);

            return json(['code' => 1, 'msg' => '查询成功', 'data' => $UpdatePayData]);
        }
    }

    // 创建支付订单
    public function create()
    {
        if ($this->request->isPost()) {
            /* 
                新增订单 
                调用统一下单接口（支付）
                    传参
                        name
                        third => json {busid:15，order_code:xxxxx} 多个参数
                        paytype 0 =》 wx 1 =》 支付宝
                        originalprice
                        paypage => 0 => 默认收银台（html） 1 => 返回json数据
                        reurl
                        callbackurl
                        wxcode
                        zfbcode
                返回数据结构根据other判断
            */
            $params = $this->request->param();

            // 订单原价
            $money = $params['originalprice'] ?? 0;

            // 查询支付表最后一次未支付记录
            $pay = $this->PayModel->where(['status' => 0])->order('id DESC')->find();

            // 获取最后一次支付的递减值
            $subPrice = !empty($pay) ? bcadd(0.01, bcsub($pay['originalprice'], $pay['price'], 2), 2) : 0.01;

            // 封装数据
            $data = [
                'code' => build_code('Pay_'),
                'name' => $params['name'] ?? '',
                'third' => isset($params['third']) ? $params['third'] : '',
                'paytype' => $params['paytype'] ?? 0,
                'originalprice' => $money,
                'price' => bcsub($money, $subPrice, 2),
                'paypage' => $params['paypage'] ?? 0,
                'reurl' => $params['reurl'] ?? '',
                'callbackurl' => $params['callbackurl'] ?? '',
                'wxcode' => $params['wxcode'] ?? '',
                'zfbcode' => $params['zfbcode'] ?? '',
                'status' => 0
            ];

            $result = $this->PayModel->validate('common/Pay/Pay')->save($data);

            if ($result === false) {
                return json(['code' => 0, 'msg' => $this->PayModel->getError(), 'data' => null]);
            } else {
                $pay = $this->PayModel->find($this->PayModel->id);

                if (isset($data['paypage']) && $data['paypage'] == 0) {
                    return json(['code' => 1, 'msg' => '支付订单创建成功', 'data' => $pay]);
                } else {
                    return $this->fetch('page', ['pay' => $pay]);
                }
            }
        }
    }

    // 查询订单状态
    public function status()
    {
        if ($this->request->isPost()) {
            $payid = $this->request->param('payid', 0, 'trim');
            $pay = $this->PayModel->find($payid);

            if (!$pay) {
                return json(['code' => 0, 'msg' => '支付订单不存在', 'data' => $pay]);
            }

            switch ($pay['status']) {
                case 0:
                    return json(['code' => 0, 'msg' => '订单未支付', 'data' => $pay]);
                    break;

                case 1:
                    return json(['code' => 1, 'msg' => '订单已支付', 'data' => $pay]);
                    break;

                case 2:
                    return json(['code' => 0, 'msg' => '订单已关闭', 'data' => $pay]);
                    break;
            }
        }
    }
}

?>
