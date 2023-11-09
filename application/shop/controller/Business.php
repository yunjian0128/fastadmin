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
        if ($this->request->isPost()) {
            //接收手机号和密码
            // $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
            $mobile = $this->request->param('mobile', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            if (empty($password)) {
                $this->error('密码不能为空');
                exit;
            }

            //生成一个密码盐
            $salt = randstr();

            //加密密码
            $password = md5($password . $salt);

            //组装数据
            $data = [
                'mobile' => $mobile,
                'nickname' => $mobile,
                'password' => $password,
                'salt' => $salt,
                'gender' => '0', //性别
                'deal' => '0', //成交状态
                'money' => '0', //余额
                'auth' => '0', //实名认证
            ];

            //查询出云课堂的渠道来源的ID信息 数据库查询
            $data['sourceid'] = model('common/Business/Source')->where(['name' => ['LIKE', "%家居商城%"]])->value('id');

            //执行插入 返回自增的条数
            $result = $this->BusinessModel->validate('common/Business/Business')->save($data);

            if ($result === FALSE) {
                //失败
                $this->error($this->BusinessModel->getError());
                exit;
            } else {
                //注册
                $this->success('注册成功', '/business/login');
                exit;
            }
        }
    }

    public function login()
    {
        //判断是否是Post请求
        if ($this->request->isPost()) {
            //获取数据
            $mobile = $this->request->param('mobile', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            //根据手机号来查询数据存不存在
            $business = $this->BusinessModel->where(['mobile' => $mobile])->find();

            // 用户不存在
            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            //数据打印
            // var_dump($business->toArray());
            // exit;

            //验证密码
            $salt = $business['salt'];
            $password = md5($password . $salt);

            if ($password != $business['password']) {
                $this->error('密码不正确');
                exit;
            }

            unset($business['password']);
            unset($business['salt']);

            //跳转会员中心
            $this->success('登录成功', '/business/index', $business);
            exit;
        }
    }

    public function check()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', '0', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');

            //查询
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
            // $result = $this->BusinessModel->validate('common/Business/Business')->save($data);

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

            $this->success('更新资料成功');
            exit;
        }
    }

    // 邮箱认证
    public function email()
    {
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

                // 第二条 删除验证码记录
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
}
