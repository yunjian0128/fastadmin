<?php

namespace addons\wechat\controller;

use addons\wechat\library\Config;
use addons\wechat\model\WechatAutoreply;
use addons\wechat\model\WechatCaptcha;
use addons\wechat\model\WechatContext;
use addons\wechat\model\WechatResponse;
use addons\wechat\model\WechatConfig;

use EasyWeChat\Factory;
use addons\wechat\library\Wechat as WechatService;
use addons\wechat\library\Config as ConfigService;
use think\Log;

/**
 * 微信接口
 */
class Index extends \think\addons\Controller {

    public $app = null;

    public function _initialize() {
        parent::_initialize();
        $this->app = Factory::officialAccount(Config::load());
    }

    /**
     * 
     */
    public function index() {
        $this->error("当前插件暂无前台页面");
    }

    /**
     * 微信API对接接口
     */
    public function api() {
        // 在接收微信服务器传递过来的信息
        $this->app->server->push(function ($message) {
            $wechatService = new WechatService;

            $matches = null;
            $openid = $message['FromUserName']; // 发送方帐号 
            $to_openid = $message['ToUserName']; // 接收方帐号（该公众号 ID）

            $unknownMessage = WechatConfig::getValue('default.unknown.message');
            $unknownMessage = $unknownMessage ? $unknownMessage : "";

            //判断事件的类型
            switch($message['MsgType']) {
                case 'event': // 事件消息
                    $event = $message['Event'];
                    $eventkey = $message['EventKey'] ? $message['EventKey'] : $message['Event'];

                    // 验证码消息
                    if(in_array($event, ['subscribe', 'SCAN']) && preg_match("/^captcha_([a-zA-Z0-9]+)_([0-9\.]+)/", $eventkey, $matches)) {
                        return WechatCaptcha::send($openid, $matches[1], $matches[2]);
                    }
                    switch($event) {
                        case 'subscribe': // 添加关注
                            $subscribeMessage = WechatConfig::getValue('default.subscribe.message');
                            $subscribeMessage = $subscribeMessage ?: "欢迎关注我们!";
                            return $subscribeMessage;
                        case 'unsubscribe': // 取消关注
                            return '';
                        case 'LOCATION': // 获取地理位置
                            return '';
                        case 'VIEW': // 跳转链接,eventkey为链接
                            return '';
                        case 'SCAN': // 扫码
                            return '';
                        default:
                            break;
                    }

                    $wechatResponse = WechatResponse::where(["eventkey" => $eventkey, 'status' => 'normal'])->find();
                    if($wechatResponse) {
                        $responseContent = (array)json_decode($wechatResponse['content'], true);
                        $wechatContext = WechatContext::where(['openid' => $openid])->order('id', 'desc')->find();
                        $data = ['eventkey' => $eventkey, 'command' => '', 'refreshtime' => time(), 'openid' => $openid];
                        if($wechatContext) {
                            $wechatContext->save($data);
                        } else {
                            $wechatContext = WechatContext::create($data, true);
                        }
                        $result = $wechatService->response($this, $openid, '', $responseContent, $wechatContext);
                        if($result) {
                            return $result;
                        }
                    }
                    return $unknownMessage;
                case 'text': // 文字消息
                case 'image': // 图片消息
                case 'voice': // 语音消息
                case 'video': // 视频消息
                case 'location': // 坐标消息
                case 'link': // 链接消息
                default: // 其它消息
                    // 自动回复处理
                    if($message['MsgType'] == 'text') {
                        $autoreply = null;

                        // 捕捉自动回复消息的列表
                        $autoreplyList = WechatAutoreply::where('status', 'normal')->cache(true)->order('weigh DESC,id DESC')->select();
                        foreach($autoreplyList as $index => $item) {

                            // 完全匹配和正则匹配
                            if($item['text'] == $message['Content'] || (in_array(mb_substr($item['text'], 0, 1), ['#', '~', '/']) && preg_match($item['text'], $message['Content'], $matches))) {
                                $autoreply = $item;
                                break;
                            }
                        }

                        if($autoreply) {
                            $wechatResponse = WechatResponse::where(["eventkey" => $autoreply['eventkey'], 'status' => 'normal'])->find();

                            if($wechatResponse) {
                                $responseContent = (array)json_decode($wechatResponse['content'], true);
                                $wechatContext = WechatContext::where(['openid' => $openid])->order('id', 'desc')->find();
                                $result = $wechatService->response($this, $openid, $message['Content'], $responseContent, $wechatContext, $matches);
                                if($result) {
                                    return $result;
                                }
                            }
                        }
                    }

                    // 写自己的回复消息
                    preg_match("/(.*)天气/imsU", $message['Content'], $weather);
                    if(!empty($weather)) {
                        // 获取城市信息
                        $city = isset($weather[1]) ? trim($weather[1]) : '';

                        if(empty($city)) {
                            return '您输入的城市信息有误，请重新输入';
                        }

                        // 调用别的控制器里面的方法
                        $InfoController = new \addons\wechat\controller\Info();
                        $result = $InfoController->weather($city);

                        return $result;
                    }

                    // 捕捉到发送的关键词 我的课程
                    if(trim($message['Content']) == "我的课程") {
                        // 调用别的控制器里面的方法
                        $InfoController = new \addons\wechat\controller\Info();
                        $success = $InfoController->subject($openid);

                        if($success['result']) {
                            return $success['data'];
                        } else {
                            return $success['msg'];
                        }
                    }

                    return $unknownMessage;
            }
            return ""; //SUCCESS
        });

        $response = $this->app->server->serve();

        // 将响应输出
        $response->send();
        return;
    }

    /**
     * 登录回调
     */
    public function callback() {

        // 获取到的是授权后的用户信息
        $user = $this->app->oauth->user();

        if(!$user) {
            $this->error('授权失败', url('home/index/login'));
            exit;
        }

        // 获取openid 授权id
        $openid = $user->getId();

        // $json = '{"id":"ode_g6iKvQBXAFMYFlXsnZWRJRSw","name":"\u0424\u0430\u041d \u0421\u0456\u043d\u044c\u0441\u0456\u043d\u044c","nickname":"\u0424\u0430\u041d \u0421\u0456\u043d\u044c\u0441\u0456\u043d\u044c","avatar":"https:\/\/thirdwx.qlogo.cn\/mmopen\/vi_32\/DYAIOgq83eo4V9WjY78oDaMiaeeTF2wmSIxgu0ToaW6j0lRgso58MBNmcVKDzPa7PhYialQIicVJSZuHRMRmFhPcQ\/132","email":null,"original":{"openid":"ode_g6iKvQBXAFMYFlXsnZWRJRSw","nickname":"\u0424\u0430\u041d \u0421\u0456\u043d\u044c\u0441\u0456\u043d\u044c","sex":0,"language":"","city":"","province":"","country":"","headimgurl":"https:\/\/thirdwx.qlogo.cn\/mmopen\/vi_32\/DYAIOgq83eo4V9WjY78oDaMiaeeTF2wmSIxgu0ToaW6j0lRgso58MBNmcVKDzPa7PhYialQIicVJSZuHRMRmFhPcQ\/132","privilege":[]},"token":"74_iKNRooRjl1mUEmtXvftxa7k40c7D9kCmqfp5QBgPiu9o36nfyn0HXy-qjKQp-QB4X30eUTwI76MTUexNml2W1-04PkcYpAWq_otrxFq-vmg","access_token":"74_iKNRooRjl1mUEmtXvftxa7k40c7D9kCmqfp5QBgPiu9o36nfyn0HXy-qjKQp-QB4X30eUTwI76MTUexNml2W1-04PkcYpAWq_otrxFq-vmg","refresh_token":"74_XVULI1XsRVaK0YvGSP4pH5Br3lDhsapQEtGFQFZgRjmA5A6_Mc6WqllSeQV9hvpBguM0PI57STCpBd2KhIkAJwMdshzV7pdPoKUwriPXg60","provider":"WeChat"}';

        // $user = json_decode($json, true);
        // $openid = $user['id'];

        $BusinessModel = model('common/Business/Business');

        // 根据openid查询这个人是否存在
        $business = $BusinessModel->where(['openid' => $openid])->find();

        if($business) {
            // 保存cookie信息
            $cookie = [
                'id' => $business['id'],
                'mobile' => $business['mobile'],
            ];

            // 存放cookie 关闭浏览器之后自动销毁
            cookie('business', $cookie);

            // 跳转会员中心
            $this->success('授权登录成功', url('home/business/index'));
            exit;
        } else {
            // 将openid保存了
            session('openid', $openid);
            $this->success('授权成功', url('home/index/login'));
            exit;
        }
    }

    /**
     * 支付回调
     */
    public function notify() {
        Log::record(file_get_contents('php://input'), "notify");
        $response = $this->app->handlePaidNotify(function ($message, $fail) {
            // 你的逻辑
            return true;
            // 或者错误消息
            $fail('Order not exists.');
        });

        $response->send();
        return;
    }
}
