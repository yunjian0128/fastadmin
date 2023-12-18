<?php

namespace app\ask\controller;

use think\Controller;

// 引入FastAdmin自带的一个邮箱发送类
use app\common\library\Email;

class Business extends Controller {
    public function __construct() {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->PayModel = model('Pay.Pay');
        $this->FansModel = model('Fans');
        $this->PostModel = model('Post.Post');
        $this->CommentModel = model('Post.Comment');
        $this->CollectModel = model('Post.Collect');
        $this->MessageModel = model('Business.Message');
    }

    // 微信端授权登录
    public function login() {
        if($this->request->isPost()) {
            $code = $this->request->param('code', '', 'trim');

            if(empty($code)) {
                $this->error('临时凭证获取失败');
                exit;
            }

            // 发送请求给微信端
            $wxauth = $this->code2Session($code);

            $openid = isset($wxauth['openid']) ? trim($wxauth['openid']) : '';

            if(empty($openid)) {
                $this->error('授权失败');
                exit;
            }

            // 根据openid查找是否存在用户
            $business = $this->BusinessModel->where(['openid' => $openid])->find();

            if($business) {
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
    public function bind() {
        if($this->request->isPost()) {
            $openid = $this->request->param('openid', '', 'trim');
            $password = $this->request->param('password', '', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');

            $business = $this->BusinessModel->where(['mobile' => $mobile])->find();

            // 如果找得到就说明绑定过， 如果找不到就说明账号不存在，就注册插入
            if($business) {
                // 更新语句
                if(!empty($business['openid'])) {
                    $this->error('该用户已绑定，无法重复绑定');
                    exit;
                }

                $data = [
                    'id' => $business['id'],
                    'openid' => $openid
                ];

                // 更新语句
                $result = $this->BusinessModel->isUpdate(true)->save($data);

                if($result === FALSE) {
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
                if(empty($password)) {
                    $this->error('密码不能为空');
                    exit;
                }

                // 生成一个密码盐
                $salt = randstr();

                // 加密密码
                $password = md5($password.$salt);

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

                if($result === FALSE) {
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

    // 邮箱验证
    public function email() {

        // 加载模型
        $EmsModel = model('common/Ems');

        // 是否有Post请求过来
        if($this->request->Post()) {
            $action = $this->request->param('action', '', 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $code = $this->request->param('code', '', 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('当前用户不存在');
                exit;
            }

            if($action == "send") {
                // 获取用户信息
                $email = isset($business['email']) ? $business['email'] : '';

                if(empty($email)) {
                    $this->error('当前用户邮箱地址为空');
                    exit;
                }

                // 生成一个验证码
                $code = randstr(6);

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

                if($ems === FALSE) {
                    $this->error('邮件插入失败');
                    exit;
                }

                // 邮件主题
                $name = "知识问答社区";
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
                if($result) {
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
            }

            // 验证邮箱
            if($code) {
                $email = isset($business['email']) ? trim($business['email']) : '';

                if(empty($email)) {
                    $this->error('当前用户邮箱地址为空');
                    exit;
                }

                // 开启事务
                $EmsModel->startTrans();
                $this->BusinessModel->startTrans();

                // 查询这个验证码是否存在
                $where = ['email' => $email, 'code' => $code];
                $check = $EmsModel->where($where)->find();

                // 如果没找到记录
                if(!$check) {
                    $this->error('您输入的验证码有误，请重新输入');
                    exit;
                }

                // 1、更新用户表  2、删除验证码记录
                // 组装数据
                $data = [
                    'id' => $business['id'],
                    'auth' => '1'
                ];

                // 执行
                $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($data);

                if($BusinessStatus === FALSE) {
                    $this->error($this->BusinessModel->getError());
                    exit;
                }

                // 第二条 删除验证码记录
                $EmsStatus = $EmsModel->where($where)->delete();

                if($EmsStatus === FALSE) {
                    // 先要将用户表的更新进行回滚
                    $this->BusinessModel->rollback();
                    $this->error($EmsModel->getError());
                    exit;
                }

                if($BusinessStatus === FALSE || $EmsStatus === FALSE) {
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

    // 解绑微信端
    public function unbind() {
        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 判断是否存在关注关系
            if(empty($business['openid'])) {
                $this->error('该用户未绑定微信');
                exit;
            }

            // 组装数据
            $data = [
                'id' => $business['id'],
                'openid' => NULL
            ];

            // 更新语句
            $result = $this->BusinessModel->isUpdate(true)->save($data);

            if($result === FALSE) {
                $this->error('解绑失败');
                exit;
            }

            $this->success('解绑成功');
            exit;
        }
    }

    // web注册登录方法
    public function web() {
        if($this->request->isPost()) {
            $password = $this->request->param('password', '', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');

            $business = $this->BusinessModel->where(['mobile' => $mobile])->find();

            // 如果找得到就说明绑定过， 如果找不到就说明账号不存在，就注册插入
            if($business) {
                // 验证密码是否正确
                $salt = $business['salt'];
                $password = md5($password.$salt);

                if($password != $business['password']) {
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
                if(empty($password)) {
                    $this->error('密码不能为空');
                    exit;
                }

                // 生成一个密码盐
                $salt = randstr();

                // 加密密码
                $password = md5($password.$salt);

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

                if($result === FALSE) {
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
    public function profile() {
        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            // 可以一次性接收到全部数据
            $id = $this->request->param('id', 0, 'trim');
            $nickname = $this->request->param('nickname', '', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');
            $email = $this->request->param('email', '', 'trim');
            $gender = $this->request->param('gender', '0', 'trim');
            $code = $this->request->param('code', '', 'trim');
            $password = $this->request->param('password', '', 'trim');
            $motto = $this->request->param('motto', '', 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($id);

            if(!$business) {
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
            if(!empty($password)) {
                // 重新生成一份密码盐
                $salt = randstr();

                $data['salt'] = $salt;
                $data['password'] = md5($password.$salt);
            }

            // 如果个性签名不为空 修改个性签名
            if(!empty($motto)) {
                $data['motto'] = $motto;
            }

            // 判断是否修改了邮箱 输入的邮箱 不等于 数据库存入的邮箱
            // 如果邮箱改变，需要重新认证
            if($email != $business['email']) {
                $data['email'] = $email;
                $data['auth'] = '0';
            }

            // 判断是否有地区数据
            if(!empty($code)) {
                // 查询省市区的地区码出来
                $parent = model('Region')->where(['code' => $code])->value('parentpath');

                if(!empty($parent)) {
                    $arr = explode(',', $parent);
                    $data['province'] = isset($arr[0]) ? $arr[0] : null;
                    $data['city'] = isset($arr[1]) ? $arr[1] : null;
                    $data['district'] = isset($arr[2]) ? $arr[2] : null;
                }
            }

            // 判断是否有图片上传
            if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $success = build_upload('avatar');

                //如果上传失败，就提醒
                if(!$success['result']) {
                    $this->error($success['msg']);
                    exit;
                }

                // 如果上传成功
                $data['avatar'] = $success['data'];
            }

            // 执行更新语句 数据验证 -> 需要用到验证器
            // 更新语句 如果是更新语句，需要给data提供一个主键id的值 这就是更新语句 使用验证器的场景
            $result = $this->BusinessModel->validate('common/Business/Business.profile')->isUpdate(true)->save($data);

            if($result === FALSE) {
                $this->error($this->BusinessModel->getError());
                exit;
            }

            // 判断是否有旧图片，如果有就删除
            if(isset($data['avatar'])) {
                is_file(".".$business['avatar']) && @unlink(".".$business['avatar']);
            }

            $business = $this->BusinessModel->find($id);

            unset($business['password']);
            unset($business['salt']);

            $this->success('更新资料成功', null, $business);
            exit;
        }
    }

    // 客户详细信息
    public function info() {
        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 查询出用户的总帖子数
            $business['posts'] = $this->PostModel->where(['busid' => $busid])->count();

            // 查询出用户的解决帖子数
            $business['accepts'] = $this->PostModel->where(['accept' => $busid])->count();

            // 查询出用户的总评论数
            $business['comments'] = $this->CommentModel->where(['busid' => $busid])->count();

            // 查询出用户的总收藏数
            $business['collects'] = $this->CollectModel->where(['busid' => $busid])->count();

            // 查询出用户的总关注数
            $business['follows'] = $this->FansModel->where(['fansid' => $busid])->count();

            // 查询出用户的总粉丝数
            $business['fans'] = $this->FansModel->where(['busid' => $busid])->count();

            $this->success('用户信息', null, $business);

        }
    }

    // 调用微信官方获取用户信息
    public function code2Session($code) {

        if($code) {

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
    protected function https_request($url, $data = null) {
        if(function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            if(!empty($data)) {
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

    // 关注列表
    public function follow() {
        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $action = $this->request->param('action', '', 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');
            $page = $this->request->param('page', 1, 'intval');
            $limit = 10;
            $offset = ($page - 1) * $limit;

            // 搜索关键词不能为空
            if($action == 'search' && empty($keywords)) {
                $this->error('请输入关键词');
                exit;
            }

            if($action == 'search') {

                // 模糊查询
                $list = $this->BusinessModel
                    ->where(['nickname' => ['LIKE', "%$keywords%"]])
                    ->order('id', 'desc')
                    ->limit($offset, $limit)
                    ->select();

                if(!$list) {
                    $this->error('暂未搜索到相关用户');
                    exit;
                }

                $this->success('搜索成功', null, $list);
                exit;
            }

            if($action == 'follow') {
                // 判断用户是否存在
                $business = $this->BusinessModel->find($busid);

                if(!$business) {
                    $this->error('用户不存在');
                    exit;
                }

                // 查询出所有的关注列表
                $Subscribe = $this->FansModel
                    ->where(['fansid' => $busid])
                    ->order('id', 'desc')
                    ->limit($offset, $limit)
                    ->column('busid');

                // 查询列表的详细信息
                $list = $this->BusinessModel
                    ->where(['id' => ['IN', $Subscribe]])
                    ->select();

                // 去除密码和盐
                foreach($list as $key => $value) {
                    unset($list[$key]['salt']);
                    unset($list[$key]['password']);
                }

                // 最后添加一个关注时间
                foreach($list as $key => $value) {
                    $Fans = $this->FansModel->where(['busid' => $value['id'], 'fansid' => $busid])->find();
                    $list[$key]['followtime'] = $Fans['createtime_text'];
                }

                if(!$list) {
                    $this->error('暂无更多数据');
                    exit;
                }

                $this->success('关注列表', null, $list);
                exit;
            }

            if($action == 'fans') {
                // 判断用户是否存在
                $business = $this->BusinessModel->find($busid);

                if(!$business) {
                    $this->error('用户不存在');
                    exit;
                }

                // 查询出所有的粉丝列表
                $Fans = $this->FansModel
                    ->where(['busid' => $busid])
                    ->order('id', 'desc')
                    ->limit($offset, $limit)
                    ->column('fansid');

                // 查询列表的详细信息
                $list = $this->BusinessModel
                    ->where(['id' => ['IN', $Fans]])
                    ->select();

                // 去除密码和盐
                foreach($list as $key => $value) {
                    unset($list[$key]['salt']);
                    unset($list[$key]['password']);
                }

                // 最后添加一个关注时间
                foreach($list as $key => $value) {
                    $Fans = $this->FansModel->where(['busid' => $busid, 'fansid' => $value['id']])->find();
                    $list[$key]['followtime'] = $Fans['createtime_text'];
                }

                if(!$list) {
                    $this->error('暂无更多数据');
                    exit;
                }

                $this->success('粉丝列表', null, $list);
                exit;
            }
        }
    }

    // 取消关注
    public function nofollow() {
        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $fansid = $this->request->param('fansid', 0, 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 判断是否存在关注关系
            $fans = $this->FansModel->where(['busid' => $busid, 'fansid' => $fansid])->find();

            if(!$fans) {
                $this->error('关注关系不存在');
                exit;
            }

            // 删除关注关系
            $result = $this->FansModel->where(['busid' => $busid, 'fansid' => $fansid])->delete();

            if($result === FALSE) {
                $this->error($this->FansModel->getError());
                exit;
            }

            $this->success('取消关注成功');
            exit;
        }
    }

    // 客户帖子
    public function list() {
        // 联表查询

        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $cateid = $this->request->param('cateid', 0, 'trim');
            $page = $this->request->param('page', 1, 'intval');
            $limit = 10;
            $offset = ($page - 1) * $limit;

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 客户提问帖子
            if($cateid == 0) {

                // 查询出所有的帖子
                $list = $this->PostModel
                    ->with(['category', 'business'])
                    ->where(['busid' => $busid])
                    ->limit($offset, $limit)
                    ->order('id', 'desc')
                    ->select();
                if(!$list) {
                    $this->error('暂无更多数据');
                    exit;
                }

                $this->success('客户提问帖子', null, $list);
                exit;
            }

            // 客户解决帖子
            if($cateid == 1) {

                // 查询出所有的帖子
                $list = $this->PostModel
                    ->with(['category', 'business'])
                    ->where(['accept' => $busid])
                    ->limit($offset, $limit)
                    ->order('id', 'desc')
                    ->select();

                if(!$list) {
                    $this->error('暂无更多数据');
                    exit;
                }

                $this->success('客户解决帖子', null, $list);
                exit;
            }

            // 客户收藏帖子
            if($cateid == 2) {

                // 查询出所有的帖子
                $postid = $this->CollectModel
                    ->with(['post'])
                    ->where(['collect.busid' => $busid])
                    ->limit($offset, $limit)
                    ->order('id', 'desc')
                    ->column('post.id');

                // 查询帖子分类
                $list = $this->PostModel
                    ->with(['category', 'business'])
                    ->where(['post.id' => ['IN', $postid]])
                    ->order('id', 'desc')
                    ->select();
                if(!$list) {
                    $this->error('暂无更多数据');
                    exit;
                }

                $this->success('客户收藏帖子', null, $list);
                exit;
            }

            // 客户所有评论
            if($cateid == 3) {

                // 查询出该用户所有的评论
                $list = $this->CommentModel
                    ->with(['post'])
                    ->where(['comment.busid' => $busid])
                    ->limit($offset, $limit)
                    ->order('id', 'desc')
                    ->select();

                if(!$list) {
                    $this->error('暂无更多数据');
                    exit;
                }

                $this->success('客户所有评论', null, $list);
                exit;
            }
        }
    }

    // 客户是否关注另一个客户
    public function isfollow() {
        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $fansid = $this->request->param('fansid', 0, 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);
            $fans = $this->BusinessModel->find($fansid);

            if(!$business) {
                $this->error('查看的用户不存在');
                exit;
            }

            if(!$fans) {
                $this->error('当前用户不存在');
                exit;
            }

            // 判断是否存在关注关系
            $isfollow = $this->FansModel->where(['busid' => $busid, 'fansid' => $fansid])->find();

            if($isfollow) {
                $this->success('已关注', null, ['isfollow' => true]);
                exit;
            } else {
                $this->error('未关注', null, ['isfollow' => false]);
                exit;
            }
        }
    }

    // 关注客户
    public function followbus() {
        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $fansid = $this->request->param('fansid', 0, 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);
            $fans = $this->BusinessModel->find($fansid);

            if(!$business) {
                $this->error('查看的用户不存在');
                exit;
            }

            if(!$fans) {
                $this->error('当前用户不存在');
                exit;
            }

            // 判断是否存在关注关系
            $isfollow = $this->FansModel->where(['busid' => $busid, 'fansid' => $fansid])->find();

            if($isfollow) {

                // 删除关注关系
                $result = $this->FansModel->where(['busid' => $busid, 'fansid' => $fansid])->delete();

                if($result === FALSE) {
                    $this->error($this->FansModel->getError());
                    exit;
                } else {
                    $this->success('取消关注成功');
                    exit;
                }
            }

            // 组装数据
            $data = [
                'busid' => $busid,
                'fansid' => $fansid,
            ];

            // 执行插入
            $result = $this->FansModel->save($data);

            if($result === FALSE) {
                $this->error($this->FansModel->getError());
                exit;
            }

            $this->success('关注成功');
            exit;
        }
    }

    // 客户帖子
    public function post() {
        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $action = $this->request->param('action', '', 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');
            $page = $this->request->param('page', 1, 'intval');
            $limit = 10;
            $offset = ($page - 1) * $limit;

            // 搜索
            if($action == 'search') {

                // 关键词不能为空
                if(empty($keywords)) {
                    $this->error('请输入关键词');
                    exit;
                }

                // 模糊查询
                $list = $this->PostModel
                    ->with(['category', 'business'])
                    ->where(['title' => ['LIKE', "%$keywords%"]])
                    ->whereOr(['business.nickname' => ['LIKE', "%$keywords%"]])
                    ->order('id', 'desc')
                    ->limit($offset, $limit)
                    ->select();

                if(!$list) {
                    $this->error('暂未搜索到相关帖子');
                }

                $this->success('搜索成功', null, $list);
            }

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('当前用户不存在');
                exit;
            }

            // 客户提问帖子
            if($action == "post") {

                // 查询出所有的帖子
                $list = $this->PostModel
                    ->with(['category', 'business'])
                    ->where(['busid' => $busid])
                    ->limit($offset, $limit)
                    ->order('id', 'desc')
                    ->select();
                if(!$list) {
                    $this->error('暂无更多数据');
                    exit;
                }

                $this->success('客户提问帖子', null, $list);
                exit;
            }

            // 客户解决帖子
            if($action == "answer") {

                // 查询出所有的帖子
                $list = $this->PostModel
                    ->with(['category', 'business'])
                    ->where(['accept' => $busid])
                    ->limit($offset, $limit)
                    ->order('id', 'desc')
                    ->select();

                if(!$list) {
                    $this->error('暂无更多数据');
                    exit;
                }

                $this->success('客户解决帖子', null, $list);
                exit;
            }

            // 客户收藏帖子
            if($action == 'collect') {

                // 查询出所有的帖子
                $postid = $this->CollectModel
                    ->with(['post'])
                    ->where(['collect.busid' => $busid])
                    ->limit($offset, $limit)
                    ->order('id', 'desc')
                    ->column('post.id');

                // 查询帖子分类
                $list = $this->PostModel
                    ->with(['category', 'business'])
                    ->where(['post.id' => ['IN', $postid]])
                    ->order('id', 'desc')
                    ->select();
                if(!$list) {
                    $this->error('暂无更多数据');
                    exit;
                }

                $this->success('客户收藏帖子', null, $list);
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
            $reurl = "https://ask.yunjian0128.cn/#/pages/business/pay";

            $callbackurl = $host."/ask/business/callback";

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

            // 要看是哪一种支付方式
            if($type == 'wx')
            {
                // 微信
                $PayData['paytype'] = 0;
            }else
            {
                // 支付宝
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
                'content' => "您使用{$payment}在知识问答社区充值了 $price 元",
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
}
