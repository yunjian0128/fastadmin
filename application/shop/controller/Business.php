<?php

namespace app\shop\controller;

use think\Controller;

// 引入FastAdmin自带的一个邮箱发送类
use app\common\library\Email;

/**
 * 用户接口
 */
class Business extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
    }


    /**
     * 用户注册
     *
     * @ApiTitle    (用户注册)
     * @ApiSummary  (用户注册)
     * @ApiMethod   (POST)
     * @ApiParams   (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams   (name="password", type="string", required=true, description="密码")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function register()
    {
        // 判断是否是Post请求
        if ($this->request->isPost()) {

            // 接收手机号和密码
            $mobile = $this->request->param('mobile', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            if (empty($password)) {
                $this->error('密码不能为空');
                exit;
            }

            // 生成一个密码盐
            $salt = randstr();

            // 加密密码
            $password = md5($password . $salt);

            // 组装数据
            $data = [
                'mobile' => $mobile,
                'nickname' => $mobile,
                'password' => $password,
                'salt' => $salt,
                'gender' => '0', // 性别
                'deal' => '0', // 成交状态
                'money' => '0', // 余额
                'auth' => '0', // 邮箱认证状态
            ];

            // 查询出云课堂的渠道来源的ID信息 数据库查询
            $data['sourceid'] = model('common/Business/Source')->where(['name' => ['LIKE', "%家居商城%"]])->value('id');

            // 执行插入 返回自增的条数
            $result = $this->BusinessModel->validate('common/Business/Business')->save($data);

            if ($result === FALSE) {

                // 失败
                $this->error($this->BusinessModel->getError());
                exit;
            } else {
                // 注册
                $this->success('注册成功', '/business/login');
                exit;
            }
        }
    }

    public function login()
    {
        // 判断是否是Post请求
        if ($this->request->isPost()) {

            // 获取数据
            $mobile = $this->request->param('mobile', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            // 根据手机号来查询数据存不存在
            $business = $this->BusinessModel->where(['mobile' => $mobile])->find();

            // 用户不存在
            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 验证密码
            $salt = $business['salt'];
            $password = md5($password . $salt);

            if ($password != $business['password']) {
                $this->error('密码不正确');
                exit;
            }

            unset($business['password']);
            unset($business['salt']);

            // 跳转会员中心
            $this->success('登录成功', '/business/index', $business);
            exit;
        }
    }

    public function check()
    {
        // 判断是否是Post请求
        if ($this->request->isPost()) {
            $id = $this->request->param('id', '0', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');

            // 查询
            $business = $this->BusinessModel->where(['id' => $id, 'mobile' => $mobile])->find();

            if ($business) {
                unset($business['password']);
                unset($business['salt']);

                $this->success("用户验证成功", null, $business);
                exit;
            } else {
                $this->error('用户不存在');
                exit;
            }
        }
    }

    public function profile()
    {
        // 判断是否有Post过来数据
        if ($this->request->isPost()) {

            // 接收数据
            $id = $this->request->param('id', 0, 'trim');
            $nickname = $this->request->param('nickname', '', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');
            $email = $this->request->param('email', '', 'trim');
            $gender = $this->request->param('gender', '0', 'trim');
            $code = $this->request->param('code', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($id);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 直接组装数据
            $data = [
                'id' => $business['id'],
                'nickname' => $nickname,
                'mobile' => $mobile,
                'gender' => $gender,
            ];

            // 如果密码不为空 修改密码
            if (!empty($password)) {

                // 重新生成一份密码盐
                $salt = randstr();
                $data['salt'] = $salt;
                $data['password'] = md5($password . $salt);
            }

            // 判断是否修改了邮箱 输入的邮箱 不等于 数据库存入的邮箱
            // 如果邮箱改变，需要重新认证
            if ($email != $business['email']) {
                $data['email'] = $email;
                $data['auth'] = '0';
            }

            // 判断是否有地区数据
            if (!empty($code)) {

                // 查询省市区的地区码出来
                $parent = model('Region')->where(['code' => $code])->value('parentpath');

                if (!empty($parent)) {
                    $arr = explode(',', $parent);
                    $data['province'] = isset($arr[0]) ? $arr[0] : null;
                    $data['city'] = isset($arr[1]) ? $arr[1] : null;
                    $data['district'] = isset($arr[2]) ? $arr[2] : null;
                }
            }

            // 判断是否有图片上传
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $success = build_upload('avatar');

                // 如果上传失败，就提醒
                if (!$success['result']) {
                    $this->error($success['msg']);
                    exit;
                }

                // 如果上传成功
                $data['avatar'] = $success['data'];
            }

            // 执行更新语句 数据验证 需要用到验证器
            // 更新语句 如果是更新语句，需要给data提供一个主键id的值 这就是更新语句 使用验证器的场景
            $result = $this->BusinessModel->validate('common/Business/Business.profile')->isUpdate(true)->save($data);

            if ($result === FALSE) {
                $this->error($this->BusinessModel->getError());
                exit;
            }

            // 判断是否有旧图片，如果有就删除
            if (isset($data['avatar'])) {
                is_file("." . $business['avatar']) && @unlink("." . $business['avatar']);
            }

            // 更新成功，要再次返回查询到的数据，用来覆盖cookie中的数据
            $business = $this->BusinessModel->find($id);

            // 删除密码和密码盐
            unset($business['password']);
            unset($business['salt']);

            $this->success('更新资料成功', null, $business);
            exit;
        }
    }

    // 邮箱认证
    public function email()
    {
        // 判断是否有Post过来数据
        if ($this->request->isPost()) {
            // 加载模型
            $EmsModel = model('common/Ems');

            // 接收用户id
            $id = $this->request->param('id', 0, 'trim');

            $business = $this->BusinessModel->find($id);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 获取用户信息
            $email = empty($business['email']) ? '' : trim($business['email']);

            if (empty($email)) {
                $this->error('邮箱地址为空');
                exit;
            }

            $action = $this->request->param('action', '', 'trim');

            // 发送验证码
            if ($action == "send") {

                // 生成一个验证码
                $code = randstr(5);

                // 开启事务
                $EmsModel->startTrans();

                // 删除掉之前旧的验证码
                $EmsModel->where(['email' => $email])->delete();

                // 把验证码插入到数据库表中
                $data = [
                    'event' => 'auth',
                    'email' => $email,
                    'code' => $code,
                    'times' => 0,
                ];

                // 插入数据
                $ems = $EmsModel->save($data);

                if ($ems === FALSE) {
                    $this->error('邮件插入失败');
                    exit;
                }

                // 邮件主题
                $name = config('site.name');
                $subject = "【{$name}】邮箱验证";

                // 组装文字信息
                $message = "<div>感谢您的使用，您的邮箱验证码为：<b>$code</b></div>";

                // 实例化邮箱验证类
                $PhpMailer = new Email;

                // 邮箱发送有规律，不可以发送关键词
                $result = $PhpMailer
                    ->to($email)
                    ->subject($subject)
                    ->message($message)
                    ->send();

                // 检测邮箱发送成功还是失败
                if ($result) {

                    // 发送验证码成功
                    // 将事务提交，提交的意思就是让刚刚插入的记录真实存在到数据表中
                    $EmsModel->commit();
                    $this->success('邮件发送成功，请注意查收');
                    exit;
                } else {

                    // 将刚才插入成功的验证码记录要撤销回滚
                    $EmsModel->rollback();
                    $this->error($PhpMailer->getError());
                    exit;
                }
            } else {
                $code = $this->request->param('code', '', 'trim');

                if (empty($code)) {
                    $this->error('验证码不能为空');
                    exit;
                }

                // 开启事务
                $EmsModel->startTrans();
                $this->BusinessModel->startTrans();

                // 查询这个验证码是否存在
                $where = ['email' => $email, 'code' => $code];
                $check = $EmsModel->where($where)->find();

                // 如果没找到记录
                if (!$check) {
                    $this->error('您输入的验证码有误，请重新输入');
                    exit;
                }

                // 1、更新用户表
                // 2、删除验证码记录

                // 组装数据
                $data = [
                    'id' => $business['id'],
                    'auth' => '1'
                ];

                // 执行
                $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($data);

                if ($BusinessStatus === FALSE) {
                    $this->error('用户邮箱认证状态修改失败');
                    exit;
                }

                // 删除验证码记录
                $EmsStatus = $EmsModel->where($where)->delete();

                if ($EmsStatus === FALSE) {

                    // 先要将用户表的更新进行回滚
                    $this->BusinessModel->rollback();
                    $this->error('验证码记录删除失败');
                    exit;
                }

                if ($BusinessStatus === FALSE || $EmsStatus === FALSE) {
                    $EmsModel->rollback();
                    $this->BusinessModel->rollback();
                    $this->error('验证失败');
                    exit;
                } else {

                    // 提交事务
                    $this->BusinessModel->commit();
                    $EmsModel->commit();
                    $this->success('邮箱验证成功');
                    exit;
                }
            }
        }
    }

    // 消费记录
    public function record()
    {
        // 判断是否有Post请求
        if ($this->request->isPost()) {

            // 接收用户id
            $id = $this->request->param('busid', 0, 'trim');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 10, 'intval');
            $offset = ($page - 1) * $limit;

            // 查询用户信息
            $business = $this->BusinessModel->find($id);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            $where = [
                'busid' => $id,
                'content' => ['LIKE', "%购物%"],
            ];

            // 查询消费记录
            $list = model('common/Business/Record')
                ->where($where)
                ->order('createtime DESC')
                ->limit($offset, $limit)
                ->select();

            // 返回消费数据
            if ($list) {
                $this->success('返回消费数据', null, $list);
                exit;
            } else {
                $this->error('暂无更多消费数据');
                exit;
            }
        }
    }

    // 充值
    public function pay()
    {
        if($this->request->isPost())
        {
            $busid = $this->request->param('busid', 0, 'trim');
            $money = $this->request->param('money', 1, 'trim');
            $type = $this->request->param('type', 'wx', 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business)
            {
                $this->error('用户不存在');
                exit;
            }

            if($money <= 0)
            {
                $this->error('充值金额不能小于0元');
                exit;
            }

            // 发送一个接口请求出去
            $host = config('site.cdnurl');
            $host = trim($host, '/');

            // 完整的请求接口地址
            $api = $host."/pay/index/create";

            // 订单支付完成后跳转的界面
            $reurl = "https://shop.yunjian0128.cn/#/business/pay";

            $callbackurl = $host."/shop/business/callback";

            // 携带一个自定义的参数过去 转换为json类型
            $third = json_encode(['busid' => $busid]);

            // 微信收款码
            $wxcode = config('site.wxcode');
            $wxcode = $host.$wxcode;

            // 支付宝收款码
            $zfbcode = config('site.zfbcode');
            $zfbcode = $host.$zfbcode;

            // 充值信息
            $PayData = [
                'name' => '余额充值',
                'third' => $third,
                'originalprice' => $money,

                //微信支付
                // 'paytype' => 0,
                // 'paypage' => 1,
                //支付宝支付
                // 'paytype' => 1,
                // 'paypage' => 2,

                'paypage' => 0,
                'wxcode' => $wxcode,
                'zfbcode' => $zfbcode,
                'reurl' => $reurl,
                'callbackurl' => $callbackurl,
            ];

            //要看是哪一种支付方式
            if($type == 'wx')
            {
                //微信
                $PayData['paytype'] = 0;
            }else
            {
                //支付宝
                $PayData['paytype'] = 1;
            }

            // 发起请求
            $result = httpRequest($api, $PayData);
            
            // 有错误
            if(isset($result['code']) && $result['code'] == 0)
            {
                $this->error($result['msg']);
                exit;
            }

            // 将json转换为php数组
            $result = json_decode($result, true);

            $this->success('生成付款码', null, $result['data']);
            exit;
        }
    }

    // 查询订单是否成功
    public function query()
    {
        if($this->request->isPost())
        {
            $busid = $this->request->param('busid', 0, 'trim');
            $payid = $this->request->param('payid', '', 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business)
            {
                $this->error('用户不存在');
                exit;
            }

            if(empty($payid))
            {
                $this->error('支付记录不存在');
                exit;
            }

            // 发送一个接口请求出去
            $host = config('site.cdnurl');
            $host = trim($host, '/');

            // 完整的请求接口地址
            $api = $host."/pay/index/status";

            // 发起请求
            $result = httpRequest($api, ['payid'=>$payid]);

            // 将json转换为php数组
            $result = json_decode($result, true);

            if(isset($result['code']) && $result['code'] == 0)
            {
                $this->error($result['msg']);
                exit;
            }else
            {
                $status = isset($result['data']['status']) ? $result['data']['status'] : 0;
                $this->success('查询充值状态', null, ['status' => $status]);
                exit;
            }
        }
    }

    // 充值回调
    public function callback()
    {
        // 判断是否有post请求过来
        if ($this->request->isPost()) 
        {
            // 获取到所有的数据
            $params = $this->request->param();

            // 充值的金额
            $price = isset($params['price']) ? $params['price'] : 0;
            $price = floatval($price);

            // 第三方参数(可多参数)
            $third = isset($params['third']) ? $params['third'] : '';

            // json字符串转换数组
            $third = json_decode($third, true);

            // 从数组获取充值的用户id
            $busid = isset($third['busid']) ? $third['busid'] : 0;

            // 支付方式
            $paytype = isset($params['paytype']) ? $params['paytype'] : 0;

            // 支付订单id
            $payid = isset($params['id']) ? $params['id'] : 0;

            $pay = $this->PayModel->find($payid);

            if(!$pay)
            {
                return json(['code' => 0, 'msg' => '支付订单不存在', 'data' => null]);
            }

            $payment = '';

            switch ($paytype) {
                case 0:
                    $payment = '微信支付';
                    break;
                case 1:
                    $payment = '支付宝支付';
                    break;
            }

            // 判断充值金额
            if ($price <= 0) {
                return json(['code' => 0, 'msg' => '充值金额为0', 'data' => null]);
            }

            // 加载模型
            $BusinessModel = model('Business.Business');
            $RecordModel = model('Business.Record');

            $business = $BusinessModel->find($busid);

            if (!$business) {
                return json(['code' => 0, 'msg' => '充值用户不存在', 'data' => null]);
            }

            // 开启事务
            $BusinessModel->startTrans();
            $RecordModel->startTrans();

            // 转成浮点类型
            $money = floatval($business['money']);

            // 余额 + 充值的金额
            $updateMoney = bcadd($money,$price,2);

            // 封装用户更新的数据
            $BusinessData = [
                'id' => $business['id'],
                'money' => $updateMoney
            ];

            // 自定义验证器
            $validate = [
                [
                    'money' => ['number','>=:0'],
                ],
                [
                    'money.number' => '余额必须是数字类型',
                    'money.>=' => '余额必须大于等于0元'
                ]
            ];

            $BusinessStatus = $BusinessModel->validate(...$validate)->isUpdate(true)->save($BusinessData);

            if($BusinessStatus === false)
            {
                return json(['code' => 0, 'msg' => $BusinessModel->getError(), 'data' => null]);
            }

            // 封装插入消费记录的数据
            $RecordData = [
                'total' => $price,
                'content' => "您使用{$payment}在大麦商城充值了 $price 元",
                'busid' => $business['id']
            ];

            // 插入
            $RecordStatus = $RecordModel->validate('common/Business/Record')->save($RecordData);

            if($RecordStatus === false)
            {
                $BusinessModel->rollback();
                return json(['code' => 0, 'msg' => $RecordModel->getError(), 'data' => null]);
            }

            if($BusinessStatus === false || $RecordStatus === false)
            {
                $BusinessModel->rollback();
                $RecordModel->rollback();
                return json(['code' => 0, 'msg' => '充值失败', 'data' => null]);
            }else{
                $BusinessModel->commit();
                $RecordModel->commit();

                // 订单号：\r\n
                // 金额:50元
                // 支付方式：
                // 时间，
                return json(['code' => 1, 'msg' => '充值成功', 'data' => null]);
            }
        }
    }

    // 充值记录
    public function payrecord()
    {
        // 判断是否有Post请求
        if ($this->request->isPost()) {

            // 接收用户id
            $id = $this->request->param('busid', 0, 'trim');
            $page = $this->request->param('page', 1, 'intval');
            $limit = $this->request->param('limit', 10, 'intval');
            $offset = ($page - 1) * $limit;

            // 查询用户信息
            $business = $this->BusinessModel->find($id);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            $where = [
                'busid' => $id,
                'content' => ['LIKE', "%大麦商城充值%"],
            ];

            // 查询消费记录
            $list = model('common/Business/Record')
                ->where($where)
                ->order('createtime DESC')
                ->limit($offset, $limit)
                ->select();

            // 返回消费数据
            if ($list) {
                $this->success('返回消费数据', null, $list);
                exit;
            } else {
                $this->error('暂无更多消费数据');
                exit;
            }
        }
    }
}

?>
