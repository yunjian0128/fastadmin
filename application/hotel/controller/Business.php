<?php

namespace app\hotel\controller;

use think\Controller;

// 引入FastAdmin自带的一个邮箱发送类
use app\common\library\Email;

class Business extends Controller {

    // 构造方法
    public function __construct() {
        parent::__construct();

        // 公共区域加载模型
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

    // 注册
    public function register() {
        if($this->request->isPost()) {
            $password = $this->request->param('password', '', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');

            // 手机号码不能为空
            if(empty($mobile)) {
                $this->error('请输入手机号');
                exit;
            }

            // 密码不能为空
            if(empty($password)) {
                $this->error('请输入密码');
                exit;
            }

            // 查询用户是否存在
            $business = $this->BusinessModel->where(['mobile' => $mobile])->find();

            // 如果找得到就说明号码已经注册了，提示用户可直接登录
            if($business) {
                $this->error('该号码已注册，可直接登录');
                exit;
            }

            // 找不到就需要插入数据
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
                'auth' => '0', // 邮箱认证状态
            ];

            // 记录客户来源
            $data['sourceid'] = model('common/Business/Source')->where(['name' => ['LIKE', "%酒店预订%"]])->value('id');

            // 执行插入
            $result = $this->BusinessModel->validate('common/Business/Business')->save($data);

            if($result === FALSE) {
                $this->error($this->BusinessModel->getError());
                exit;
            } else {
                $this->success('注册成功', '/business/login');
                exit;
            }
        }
    }

    // 登录
    public function login() {

        // 判断是否是Post请求
        if($this->request->isPost()) {
            // 获取数据
            $mobile = $this->request->param('mobile', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            // 根据手机号来查询数据存不存在
            $business = $this->BusinessModel->where(['mobile' => $mobile])->find();

            // 用户不存在
            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 验证密码
            $salt = $business['salt'];
            $password = md5($password.$salt);

            if($password != $business['password']) {
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

    // 验证用户是否属于合法登录
    public function check() {
        if($this->request->isPost()) {
            $id = $this->request->param('id', '0', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');

            // 查询
            $business = $this->BusinessModel->where(['id' => $id, 'mobile' => $mobile])->find();

            if($business) {
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

    // 编辑
    public function profile() {

        // 判断是否是post提交
        if($this->request->isPost()) {
            // 接收数据
            $busid = $this->request->param('busid', 0, 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            $nickname = $this->request->param('nickname', '', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');
            $email = $this->request->param('email', '', 'trim');
            $gender = $this->request->param('gender', '0', 'trim');
            $code = $this->request->param('code', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            // 组装数据
            $data = [
                'id' => $busid,
                'nickname' => $nickname,
                'mobile' => $mobile,
                'gender' => $gender,
            ];

            // 如果密码不为空，就修改密码，生成密码盐，生成新密码
            if(!empty($password)) {

                // 生成密码盐
                $salt = randstr();
                $data['salt'] = $salt;

                // 生成新密码
                $data['password'] = md5($password.$salt);
            }

            // 判断是否修改了邮箱 输入的邮箱 不等于 数据库存入的邮箱
            // 如果邮箱改变，需要重新认证
            if($email != $business['email']) {
                $data['email'] = $email;
                $data['auth'] = '0';
            }

            // 判断是否有地区数据
            if(!empty($code)) {
                $parent = model('Region')->where(['code' => $code])->value('parentpath');

                if(!empty($parent)) {
                    $parent = explode(',', $parent);
                    $data['province'] = isset($parent[0]) ? $parent[0] : '';
                    $data['city'] = isset($parent[1]) ? $parent[1] : '';
                    $data['district'] = isset($parent[2]) ? $parent[2] : '';
                }
            }

            // 判断是否有图片上传
            if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $success = build_upload('avatar');

                // 如果上传失败，就提醒
                if(!$success['result']) {
                    $this->error($success['msg']);
                    exit;
                }

                // 如果上传成功
                $data['avatar'] = $success['data'];
            }

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

            // 更新成功，要再次返回查询到的数据，用来覆盖cookie中的数据
            $data = $this->BusinessModel->find($busid);

            // 去除密码和密码盐
            unset($data['password']);
            unset($data['salt']);

            $this->success('更新资料成功', null, $data);
            exit;
        }
    }

    // 邮箱验证
    public function email() {
        // 加载模型
        $EmsModel = model('common/Ems');

        // 是否有ajax请求过来
        if($this->request->isAjax()) {
            $action = $this->request->param('action', '', 'trim');

            if($action == "send") {
                // 获取用户信息
                $email = isset($this->view->business['email']) ? $this->view->business['email'] : '';

                if(empty($email)) {
                    $this->error('邮箱地址为空');
                    exit;
                }

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

                if($ems === FALSE) {
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
        }

        // 是否有post请求过来
        if($this->request->isPost()) {
            $code = $this->request->param('code', '', 'trim');

            if(empty($code)) {
                $this->error('验证码不能为空');
                exit;
            }

            $email = isset($this->view->business['email']) ? trim($this->view->business['email']) : '';

            if(empty($email)) {
                $this->error('邮箱不能为空');
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
                'id' => $this->view->business['id'],
                'auth' => '1'
            ];

            // 执行
            $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($data);

            if($BusinessStatus === FALSE) {
                $this->error('用户邮箱认证状态修改失败');
                exit;
            }

            // 第二条 删除验证码记录
            $EmsStatus = $EmsModel->where($where)->delete();

            if($EmsStatus === FALSE) {
                // 先要将用户表的更新进行回滚
                $this->BusinessModel->rollback();
                $this->error('验证码记录删除失败');
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
                $this->success('邮箱验证成功', url('home/business/index'));
                exit;
            }
        }
        return $this->view->fetch();
    }

    // 用户订单
    public function order() {

        // 判断是否是ajax请求
        if($this->request->isAjax()) {
            $busid = $this->request->param('busid', '', 'trim');
            $limit = $this->request->param('limit', 10, 'trim');
            $page = $this->request->param('page', 1, 'trim');

            $where = ['busid' => $busid];

            // 查询用户订单数量
            $count = $this->OrderModel->where($where)->count();

            // 偏移量
            $start = ($page - 1) * $limit;

            // 查询用户订单
            $list = $this->OrderModel
                ->with('subject')
                ->where($where)
                ->order('createtime desc')
                ->limit($start, $limit)
                ->select();

            // 组装数据
            $data = [
                'count' => $count,
                'list' => $list
            ];

            if(empty($list)) {
                $this->error('暂无更多数据');
                exit;
            } else {
                $this->success('成功返回数据', null, $data);
                exit;
            }
        }
        return $this->view->fetch();
    }

    // 用户消费
    public function record() {
        // 获取用户id
        $busid = $this->view->business['id'];

        // 返回用户id
        $this->assign('busid', $busid);

        // 判断是否是ajax请求
        if($this->request->isAjax()) {
            $busid = $this->request->param('busid', '', 'trim');
            $limit = $this->request->param('limit', 10, 'trim');
            $page = $this->request->param('page', 1, 'trim');

            $where = ['busid' => $busid];

            // 查询用户订单数量
            $count = $this->RecordModel->where($where)->count();

            // 偏移量
            $start = ($page - 1) * $limit;

            // 查询用户订单
            $list = $this->RecordModel
                ->where($where)
                ->order('createtime desc')
                ->limit($start, $limit)
                ->select();

            // 组装数据
            $data = [
                'count' => $count,
                'list' => $list
            ];

            if(empty($list)) {
                $this->error('暂无更多数据');
                exit;
            } else {
                $this->success('成功返回数据', null, $data);
                exit;
            }
        }
        return $this->view->fetch();
    }

    // 用户充值
    public function recharge() {
        // 获取用户id
        $busid = $this->view->business['id'];

        // 查询用户余额
        $business = $this->BusinessModel->where(['id' => $busid])->find();
        $money = $business->money;

        // 传出数据
        $this->assign('money', $money);
        $this->assign('busid', $busid);

        // 判断是否是ajax请求
        if($this->request->isPost()) {
            $recharge = $this->request->param('money', 0, 'trim');

            // 如果recharge为0
            if($recharge <= 0) {
                $this->error('充值金额不能小于0');
                exit;
            }

            if($money <= 0) {
                $this->error('充值失败');
                exit;
            }

            $money += $recharge;

            // 开启事务
            $this->BusinessModel->startTrans();
            $RechargeStatus = $this->BusinessModel->isUpdate(true)->save([
                'id' => $busid,
                'money' => $money
            ]);

            if($RechargeStatus) {
                $this->BusinessModel->commit();
                $this->success('充值成功');
                exit;
            } else {
                // 回滚事务
                $this->BusinessModel->rollback();
                $this->error('充值失败');
                exit;
            }
        }
        return $this->view->fetch();
    }

    // 联系我们
    public function contact() {
        // 获取当前IP
        $data = GetClientIP();

        // 传出数据
        $this->assign('data', $data);

        return $this->view->fetch();
    }

    // 用户评价
    public function comment() {
        // 获取用户id和课程id
        $busid = $this->view->business['id'];
        $subid = $this->request->param('subid', 0, 'trim');

        $where = [
            'busid' => $busid,
            'subid' => $subid
        ];

        // 查询用户评价
        $comments = $this->CommentModel->where($where)->find();
        $content = isset($comments->content) ? $comments->content : '';

        // 查询课程信息
        $subject = $this->SubjectModel->where(['id' => $subid])->find();

        // 传出数据
        $this->assign([
            'content' => $content,
            'subject' => $subject
        ]);

        // 判断是否是post请求
        if($this->request->isPost()) {
            $content = $this->request->param('content', '', 'trim');

            // 如果内容为空
            if(empty($content)) {
                $this->error('评论内容不能为空');
                exit;
            }

            // 组装数据
            $data = [
                'busid' => $busid,
                'subid' => $subid,
                'content' => $content
            ];

            // 开启事务
            $this->CommentModel->startTrans();

            // 如果评价存在就更新评论
            if(!empty($comments)) {

                // 更新数据
                $result = $this->CommentModel->isUpdate(true)->save([
                    'content' => $content
                ], [
                    'busid' => $busid,
                    'subid' => $subid
                ]);

                // 如果更新失败
                if($result === FALSE) {
                    $this->CommentModel->rollback();
                    $this->error('更新评论失败');
                    exit;
                }

                // 提交事务
                $this->CommentModel->commit();
                $this->success('更新评论成功', url('home/index/more', ['subid' => $subid]));
                exit;
            }

            // 插入数据
            $result = $this->CommentModel->save($data);

            // 如果插入失败
            if($result === FALSE) {

                // 回滚事务
                $this->CommentModel->rollback();
                $this->error('评论失败');
                exit;
            }

            // 提交事务
            $this->CommentModel->commit();
            $this->success('评论成功', url('home/index/more', ['subid' => $subid]));
            exit;
        }
        return $this->view->fetch();
    }
}

?>