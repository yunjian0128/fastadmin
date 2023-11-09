<?php

namespace app\common\controller;

use think\Controller;

/**
 * 前台公共控制器
 */
class Home extends Controller
{
    /*
     不需要登录的方法 如果有不需要验证登录的方法 就把方法名写进去
     如果所有的方法都不用登录 就给*
    **/
    public $NoLogin = [];


    public function __construct()
    {
        parent::__construct();

        //公共区域加载模型
        $this->BusinessModel = model('Business.Business');

        //登录的判断验证
        // var_dump($this->NoLogin);
        // exit;

        //获取当前访问的控制器方法名

        //返回当前的模块，控制器，方法
        // var_dump($this->request->module());
        // var_dump($this->request->controller());
        // var_dump($this->request->action());
        // echo $this->request->action();

        //当前访问控制器方法名
        $action = $this->request->action();

        //in_array 判断一个值在不在数组中如果在就返回true否则false
        if (!in_array($action, $this->NoLogin) && !in_array('*', $this->NoLogin)) {
            //如果在里面说明，不需要登录
            //如果不在就说明要登录
            $this->auth();
        }
    }

    /**
     * 验证是否登录
     * @param $redirect true 会跳转 false不会跳转
     * @return 返回用户信息
     */
    public function auth($redirect = true)
    {
        //获取出cookie
        $cookie = cookie('business');

        //取出id和mobile查询用户是否真实存在
        $id = isset($cookie['id']) ? trim($cookie['id']) : 0;
        $mobile = isset($cookie['mobile']) ? trim($cookie['mobile']) : '';

        //查询是否存在
        $where = ['id' => $id, 'mobile' => $mobile];
        $business = $this->BusinessModel->where($where)->find();

        if (!$business) {
            //没找到用户 cookie伪造的，所以要删除
            cookie('business', null);

            if ($redirect) {
                $this->error('非法访问，请重新登录', url('home/index/login'));
                exit;
            } else {
                return null;
            }
        }

        //如果要是找到了用户信息，那么我们要渲染数据
        if ($redirect) {
            $this->view->assign('business', $business);
        }

        return $business;
    }
}