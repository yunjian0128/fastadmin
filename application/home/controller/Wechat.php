<?php

namespace app\home\controller;

use app\common\controller\Home;

//引入插件配置
use \addons\wechat\library\Config;

use EasyWeChat\Factory;
use \addons\wechat\library\Wechat as WechatService;
use \addons\wechat\library\Config as ConfigService;


class Wechat extends Home
{
    public $NoLogin = ['*'];

    public $app = null; // 公众号对象

    public function __construct()
    {
        parent::__construct();

        $this->app = Factory::officialAccount(Config::load());
    }

    //微信授权
    public function oauth()
    {
        //直接跳转
        return $this->app->oauth->redirect();
    }

    //模板消息
    public function template($data = [])
    {
        if(empty($data))
        {
            return false;
        }

        return $this->app->template_message->send($data);
    }
}