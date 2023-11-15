<?php

namespace app\ask\controller;

use think\Controller;

class Business extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');

        $this->PayModel = model('pay.Pay');
    }

    // 微信端授权登录
    public function login()
    {
        if ($this->request->isPost()) {
            $code = $this->request->param('code', '', 'trim');

            if (empty($code)) {
                $this->error('临时凭证获取失败');
                exit;
            }

            // 发送请求给微信端
            $wxauth = $this->code2Session($code);

            $openid = isset($wxauth['openid']) ? trim($wxauth['openid']) : '';

            if (empty($openid)) {
                $this->error('授权失败');
                exit;
            }

            // 根据openid查找是否存在用户
            $business = $this->BusinessModel->where(['openid' => $openid])->find();

            if ($business) {
                unset($business['salt']);
                unset($business['password']);
                // 授权成功
                $this->success('授权登录成功', null, $business);
                exit;
            } else {
                $this->success('授权成功，请绑定账号', "/pages/business/login", ['action' => 'bind', 'openid' => $openid]);
                exit;
            }
        }
    }

    // 微信端绑定
    public function bind()
    {
        if ($this->request->isPost()) {
            $openid = $this->request->param('openid', '', 'trim');
            $password = $this->request->param('password', '', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');

            $business = $this->BusinessModel->where(['mobile' => $mobile])->find();

            // 如果找得到就说明绑定过， 如果找不到就说明账号不存在，就注册插入
            if ($business) {
                // 更新语句
                if (!empty($business['openid'])) {
                    $this->error('该用户已绑定，无法重复绑定');
                    exit;
                }

                $data = [
                    'id' => $business['id'],
                    'openid' => $openid
                ];

                // 更新语句
                $result = $this->BusinessModel->isUpdate(true)->save($data);

                if ($result === FALSE) {
                    $this->error('绑定账号失败');
                    exit;
                } else {
                    $business['openid'] = $openid;
                    unset($business['salt']);
                    unset($business['password']);
                    $this->success('绑定账号成功', null, $business);
                    exit;
                }
            } else {
                // 数据插入
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
                    'openid' => $openid,
                    'mobile' => $mobile,
                    'nickname' => $mobile,
                    'password' => $password,
                    'salt' => $salt,
                    'gender' => '0', // 性别
                    'deal' => '0', // 成交状态
                    'money' => '0', // 余额
                    'auth' => '0', // 实名认证
                ];

                //查询出云课堂的渠道来源的ID信息 数据库查询
                $data['sourceid'] = model('common/Business/Source')->where(['name' => ['LIKE', "%问答社区%"]])->value('id');

                //执行插入 返回自增的条数
                $result = $this->BusinessModel->validate('common/Business/Business')->save($data);

                if ($result === FALSE) {
                    // 失败
                    $this->error($this->BusinessModel->getError());
                    exit;
                } else {
                    // 查询出当前插入的数据记录
                    $business = $this->BusinessModel->find($this->BusinessModel->id);

                    unset($business['salt']);
                    unset($business['password']);

                    // 注册
                    $this->success('注册成功', null, $business);
                    exit;
                }
            }
        }
    }

    // web注册登录方法
    public function web()
    {
        if ($this->request->isPost()) {
            $password = $this->request->param('password', '', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');

            $business = $this->BusinessModel->where(['mobile' => $mobile])->find();

            // 如果找得到就说明绑定过， 如果找不到就说明账号不存在，就注册插入
            if ($business) {
                // 验证密码是否正确
                $salt = $business['salt'];
                $password = md5($password . $salt);

                if ($password != $business['password']) {
                    $this->error('密码错误');
                    exit;
                } else {
                    unset($business['salt']);
                    unset($business['password']);
                    $this->success('登录成功', null, $business);
                    exit;
                }
            } else {
                // 数据插入
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
                    'auth' => '0', // 实名认证
                ];

                // 查询出云课堂的渠道来源的ID信息 数据库查询
                $data['sourceid'] = model('common/Business/Source')->where(['name' => ['LIKE', "%问答社区%"]])->value('id');

                // 执行插入 返回自增的条数
                $result = $this->BusinessModel->validate('common/Business/Business')->save($data);

                if ($result === FALSE) {
                    // 失败
                    $this->error($this->BusinessModel->getError());
                    exit;
                } else {
                    // 查询出当前插入的数据记录
                    $business = $this->BusinessModel->find($this->BusinessModel->id);

                    unset($business['salt']);
                    unset($business['password']);

                    // 注册
                    $this->success('注册成功', null, $business);
                    exit;
                }
            }
        }
    }

    // 修改数据的方法
    public function profile()
    {
        // 判断是否有Post过来数据
        if ($this->request->isPost()) {
            // 可以一次性接收到全部数据
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
                'id' => $business['id'], // 因为我们要执行更新语句
                'nickname' => $nickname,
                'mobile' => $mobile,
                'gender' => $gender,
            ];

            // 如果密码不为空 修改密码
            if (!empty($password)) {
                //重新生成一份密码盐
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

                //如果上传失败，就提醒
                if (!$success['result']) {
                    $this->error($success['msg']);
                    exit;
                }

                // 如果上传成功
                $data['avatar'] = $success['data'];
            }

            // 执行更新语句 数据验证 -> 需要用到验证器

            // 这是插入语句
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

            $business = $this->BusinessModel->find($id);

            unset($business['password']);
            unset($business['salt']);

            $this->success('更新资料成功', null, $business);
            exit;
        }
    }

    // 调用微信官方获取用户信息
    public function code2Session($code)
    {

        if ($code) {

            // 改成自己的小程序 appid
            $appid = "wx93999a9e6fc30184";

            // 改成自己的小程序 appSecret
            $appSecret = "62642542b106088268b9abad024d504a";

            // 微信官方提供的接口，获取唯一的opendid
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$appSecret&js_code=$code&grant_type=authorization_code";

            $result = $this->https_request($url);

            // 获取结果 将json转化为数组
            $resultArr = json_decode($result, true);

            return $resultArr;
        }
        return false;

    }

    // http请求 利用php curl扩展去发送get 或者 post请求 服务器上面一定要开启 php curl扩展
    // https://www.php.net/manual/zh/book.curl.php
    protected function https_request($url, $data = null)
    {
        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            if (!empty($data)) {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            // 发送会话，返回结果
            $output = curl_exec($curl);
            curl_close($curl);
            return $output;
        } else {
            return false;
        }
    }
}
