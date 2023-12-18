<?php
namespace app\home\controller;

// 引入底层控制器
use app\common\controller\Home;

// 控制器 Controller
class Index extends Home
{
    // 所有的方法都不需要登录
    public $NoLogin = ["*"];

    public function __construct()
    {
        // 继承底层的构造函数
        parent::__construct();

        // 公共区域中加载模型，那么下面所有的方法中都可以使用这个模型
        $this->BusinessModel = model('Business.Business');
        $this->SubjectModel = model('Subject.Subject');
        $this->ChapterModel = model('Subject.Chapter');
        $this->CommentModel = model('Subject.Comment');
        $this->OrderModel = model('Subject.Order');
    }

    public function index()
    {
        // 按照时间降序查询
        $top = $this->SubjectModel->order('createtime desc')->limit(5)->select();
        $list = $this->SubjectModel->order('id desc')->limit(8)->select();

        $data = [
            'top' => $top,
            'list' => $list
        ];

        // 赋值数据到模板中去
        $this->assign($data);

        // 渲染一个模板页面 V = View
        return $this->view->fetch();
    }

    public function register()
    {
        // 判断是否为post请求
        if ($this->request->isPost()) {
            // 接收手机号和密码
            // $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
            $mobile = $this->request->param('mobile', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            // 判断密码是否为空
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
                'gender' => '0',
                // 性别
                'deal' => '0',
                // 成交状态
                'money' => '0',
                // 余额
                'auth' => '0',
                // 实名认证
            ];

            // 查询出云课堂的渠道来源的ID信息 数据库查询
            $data['sourceid'] = model('common/Business/Source')->where(['name' => ['LIKE', "%云课堂%"]])->value('id');

            // 执行插入 返回自增的条数
            $result = $this->BusinessModel->validate('common/Business/Business')->save($data);

            if ($result === FALSE) {
                // 失败
                $this->error($this->BusinessModel->getError());
                exit;
            } else {
                // 注册
                $this->success('注册成功', url('home/index/login'));
                exit;
            }
        }
        // 访问地址：www.fastadmin.com/index.php/home/index/register
        // 渲染模板 application/home/view/Index/register.html
        return $this->view->fetch();
    }

    public function login()
    {
        // 判断是否为post请求
        if ($this->request->isPost()) {
            // 接收手机号和密码
            // $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
            $mobile = $this->request->param('mobile', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            // 判断手机号是否在数据库中
            $result = $this->BusinessModel->where(['mobile' => $mobile])->find();

            if (!$result) {
                $this->error('用户不存在');
                exit;
            }

            // 密码校验
            $salt = $result['salt'];
            $password = md5($password . $salt);

            // 判断密码是否正确
            if ($password != $result['password']) {
                $this->error('密码错误');
                exit;
            }

            // 保存cookie信息
            $cookie = [
                'id' => $result['id'],
                'mobile' => $result['mobile']
            ];

            // 存放cookie 关闭浏览器之后自动销毁
            cookie('business', $cookie);

            // 跳转页面
            $this->success('登录成功', url('home/business/index'));
            exit;

        }
        // 访问地址：www.fastadmin.com/index.php/home/index/register
        // 渲染模板 application/home/view/Index/register.html
        return $this->view->fetch();
    }

    // 退出登录
    public function logout()
    {
        // 清除cookie
        cookie('business', null);
        $this->success('退出成功', url('home/index/index'));
        exit;
    }

    // 搜索
    public function search()
    {
        // 先判断是否有ajax请求过来
        if ($this->request->isAjax()) {
            // 接收参数
            $page = $this->request->param('page', 1, 'trim');
            $limit = $this->request->param('limit', 10, 'trim');
            $search = $this->request->param('search', '', 'trim');

            $where = [];

            if (!empty($search)) {
                // 模糊查询
                $where['title'] = ['LIKE', "%$search%"];
            }

            // 查询数据总数
            $count = $this->SubjectModel->where($where)->count();

            // 偏移量
            $start = ($page - 1) * $limit;

            $list = $this->SubjectModel
                ->with(['category'])
                ->where($where)
                ->order('createtime desc')
                ->limit($start, $limit)
                ->select();

            $data = [
                'count' => $count,
                'list' => $list
            ];

            if (empty($list)) {
                $this->error('暂无更多数据');
                exit;
            } else {
                $this->success('成功返回数据', null, $data);
                exit;
            }
        }
        return $this->view->fetch();
    }

    // 课程详情
    public function info()
    {
        $subid = $this->request->param('subid', '0', 'trim');

        // 先判断课程是否存在
        $subject = $this->SubjectModel->find($subid);

        if (!$subject) {
            $this->error('课程不存在', url('home/index/search'));
            exit;
        }

        // 查询章节
        $chapter = $this->ChapterModel->where(['subid' => $subid])->select();

        // 查询评论
        $comment = $this->CommentModel->with(['business'])->where(['subid' => $subid])->limit(8)->select();

        // 判断是否登录
        $business = $this->auth(false);

        if ($business) {
            $busid = $business['id'];
            $likes = $subject['likes'];

            // 将点赞人的id转换成数组
            if (!empty($likes)) {
                $likes = explode(',', $likes);
            } else {
                $likes = [];
            }

            // 判断当前用户是否点赞
            if (in_array($busid, $likes)) {
                $subject['like_status'] = true;
            } else {
                $subject['like_status'] = false;
            }
        } else {
            // 没有登录
            $subject['like_status'] = false;
        }

        // 传出数据
        $this->assign([
            'subject' => $subject,
            'chapter' => $chapter,
            'comment' => $comment,
        ]);

        return $this->view->fetch();
    }

    // 点赞
    public function like()
    {
        if ($this->request->isAjax()) {
            // 接收参数
            $subid = $this->request->param('subid', 0, 'trim');
            $status = $this->request->param('status', '', 'trim');

            if (!in_array($status, ['add', 'remove'])) {
                $this->error('点赞状态有问题');
                exit;
            }

            // 判断是否有登录
            $business = $this->auth(false);

            if (!$business) {
                $this->error('请登录');
                exit;
            }

            // 判断课程是否存在
            $subject = $this->SubjectModel->where(['id' => $subid])->find();

            if (!$subject) {
                $this->error('课程不存在');
                exit;
            }

            $likes = empty($subject['likes']) ? [] : explode(',', $subject['likes']);

            // 如果在 就说明点过赞 要取消点赞
            if (in_array($business['id'], $likes)) {
                $pos = array_search($business['id'], $likes);

                // 删除变量元素
                unset($likes[$pos]);
            } else {
                $likes[] = $business['id'];
            }

            // 会怕存在遗漏 去重 保证唯一性
            $likes = array_unique($likes);
            $likes = implode(',', $likes);

            // 组装数据更新
            $data = [
                'id' => $subid,
                'likes' => $likes
            ];

            $result = $this->SubjectModel->isUpdate(true)->save($data);

            if ($result === FALSE) {
                $msg = $status == "add" ? "点赞失败" : "取消点赞失败";
                $this->error($msg);
                exit;
            } else {
                $msg = $status == "add" ? "点赞成功" : "取消点赞成功";
                $this->success($msg);
                exit;
            }
        }

        return $this->view->fetch();
    }

    // 评论
    public function more()
    {
        // 获取课程id
        $subid = $this->request->param("subid", 0, "trim");

        // 将课程id传回模板
        $this->assign("subid", $subid);

        // 判断是否是ajax请求
        if ($this->request->isAjax()) {
            $subid = $this->request->param("subid", 0, "trim");
            $limit = $this->request->param('limit', 10, 'trim');
            $page = $this->request->param('page', 1, 'trim');

            $where = [
                'subid' => $subid
            ];

            // 查询评论总数
            $count = $this->CommentModel->where($where)->count();

            // 偏移量
            $start = ($page - 1) * $limit;

            // 查询评论
            $list = $this->CommentModel
                ->with(['business'])
                ->where($where)
                ->order('createtime desc')
                ->limit($start, $limit)
                ->select();

            $data = [
                'count' => $count,
                'list' => $list
            ];

            if (empty($list)) {
                $this->error('暂无更多数据');
                exit;
            } else {
                $this->success('成功返回数据', null, $data);
                exit;
            }
        }
        return $this->view->fetch();
    }

    // 播放视频
    public function play()
    {
        // 判断是否是ajax请求
        if ($this->request->isAjax()) {
            $subid = $this->request->param('subid', 0, 'trim');
            $cid = $this->request->param('cid', 0, 'trim');

            // 判断课程是否存在
            $subject = $this->SubjectModel->find($subid);

            if (!$subject) {
                $this->error('课程不存在');
                exit;
            }

            // 判断章节是否存在
            $chapter = $this->ChapterModel->find($cid);

            // 章节不存在
            if (!$chapter) {
                // 根据课程找出这个课程的第一个章节
                $chapter = $this->ChapterModel->where(['subid' => $subid])->order('id asc')->find();

                if (!$chapter) {
                    $this->error('该课程暂无章节');
                    exit;
                }
            }

            // 判断是否登录
            $business = $this->auth(false);

            if (!$business) {
                $this->error('请登录');
                exit;
            }

            // 判断是否购买
            $where = [
                'subid' => $subid,
                'busid' => $business['id']
            ];

            $order = $this->OrderModel->where($where)->find();

            // 没有购买
            if (!$order) {
                $this->success('请先购买课程', null, ['status' => 'buy']);
                exit;
            } else {
                // 已经购买
                $this->success('可以播放', null, $chapter);
                exit;
            }
        }
    }

    // 购买课程
    public function buy()
    {
        // 判断是否是ajax请求
        if ($this->request->isAjax()) {
            $subid = $this->request->param('subid', 0, 'trim');

            // 判断课程是否存在
            $subject = $this->SubjectModel->find($subid);

            if (!$subject) {
                $this->error('课程不存在');
                exit;
            }

            // 判断是否登录
            $business = $this->auth(false);
            if (!$business) {
                $this->error('请先登录');
                exit;
            }

            // 查询是否购买
            $where = [
                'subid' => $subid,
                'busid' => $business['id']
            ];

            $order = $this->OrderModel->where($where)->find();

            if ($order) {
                $this->error('您已经购买过了');
                exit;
            }

            // subject_order 课程订单 插入
            // business 用户余额 更新
            // business_record 消费记录 插入

            // 加载模型
            $OrderModel = model('Subject.Order');
            $BusinessModel = model('Business.Business');
            $RecordModel = model('Business.Record');

            // 启动事务
            $OrderModel->startTrans();
            $BusinessModel->startTrans();
            $RecordModel->startTrans();

            // 判断用户余额是否足够
            $moeny = empty($business['money']) ? 0 : $business['money'];
            $price = empty($subject['price']) ? -1 : $subject['price'];


            if ($price < 0) {
                $this->error('课程价格有误，无法购买');
                exit;
            }

            // 两个高精度的浮点数相减
            $UpdateMoney = bcsub($moeny, $price, 2);

            if ($UpdateMoney < 0) {
                $this->error('您的余额不足，请先充值');
                exit;
            }

            // 生成课程订单号
            $code = build_code('SUB');

            // 组装数据
            $OrderData = [
                'subid' => $subid,
                'busid' => $business['id'],
                'code' => $code,
                'total' => $price,
            ];

            // 插入订单
            $OrderStatus = $OrderModel->validate('common/Subject/Order')->save($OrderData);

            if ($OrderStatus === FALSE) {
                $this->error($OrderModel->getError());
                exit;
            }

            // 更新用户余额
            $BusinessData = [
                'id' => $business['id'],
                'money' => $UpdateMoney
            ];

            // 验证器
            $validate = [
                [
                    'money' => 'require|number|>=:0',
                ],
                [
                    'money.require' => '余额未知',
                    'money.number' => '余额必须是数字',
                    'money.>=' => '余额必须是大于或等于0元',
                ]
            ];

            $BusinessStatus = $BusinessModel->validate(...$validate)->isUpdate(true)->save($BusinessData);

            if ($BusinessStatus === FALSE) {
                $this->error($BusinessModel->getError());
                exit;
            }

            $title = $subject['title'];
            $content = "您购买了课程【{$title}】:花费了：￥ $price 元";

            // 插入消费记录
            $RecordData = [
                'total' => $price,
                'busid' => $business['id'],
                'content' => $content,
            ];

            $RecordStatus = $RecordModel->validate('common/Business/Record')->save($RecordData);

            if ($RecordStatus === FALSE) {

                // 回滚事务
                $BusinessModel->rollback();
                $OrderModel->rollback();
                $this->error($RecordModel->getError());
                exit;
            }

            // 总的判断
            if ($OrderStatus === FALSE || $BusinessStatus === FALSE || $RecordStatus === FALSE) {
                $RecordModel->rollback();
                $BusinessModel->rollback();
                $OrderModel->rollback();
                $this->error('购买课程失败');
                exit;
            } else {

                // 提交事务
                $OrderModel->commit();
                $BusinessModel->commit();
                $RecordModel->commit();
                $this->success('购买课程成功');
                exit;
            }
        }
    }
}

?>