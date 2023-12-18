<?php

namespace addons\wechat\controller;

use think\Controller;

//回复图文消息
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\NewsItem;


class Info extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->BusinessModel = model('common/Business/Business');
    }
    
    //城市天气查询
    function weather($city = '')
    {
        
        if(empty($city))
        {
            return '城市信息未知';
        }

        $url = "https://api.asilu.com/weather/?city=$city";
        $json = @file_get_contents($url);
        
        if(empty($json))
        {
            return "无返回结果";
        }
        
        //将json转换为php数组
        $result = json_decode($json, true);
    
        //获取当天的天气
        $data = isset($result['weather'][0]) ? $result['weather'][0] : [];
        
        if(empty($data))
        {
            return '';
        }
    
        $date = isset($data['date']) ? $data['date'] : '';
        $weather = isset($data['weather']) ? $data['weather'] : '';
        $temp = isset($data['temp']) ? $data['temp'] : '';
        $wind = isset($data['wind']) ? $data['wind'] : '';
    
        return "日期：$date 天气情况：$weather 温度：$temp 风向：$wind";
    }

    //查询课程
    function subject($openid = '')
    {
        $success = [
            'result' => false,
            'msg' => '',
            'data' => []
        ];

        if(empty($openid))
        {
            $success['result'] = false;
            $success['msg'] = '授权信息不存在';
            return $success;
        }

        // 根据openid查询这个人是否存在
        $business = $this->BusinessModel->where(['openid' => $openid])->find();

        if(!$business)
        {
            $success['result'] = false;
            $success['msg'] = '未找到授权用户';
            return $success;
        }

        $subid = model('common/Subject/Order')->where(['busid' => $business['id']])->column('subid');

        if(!$subid)
        {
            $success['result'] = false;
            $success['msg'] = '您暂未购买过任何课程';
            return $success;
        }

        $subject = model('common/Subject/Subject')->where(['id' => ['in', $subid]])->select();

        if(!$subject)
        {
            $success['result'] = false;
            $success['msg'] = '购买的课程不存在';
            return $success;
        }

        $piclist = [];

        //循环创建多条图文消息
        foreach($subject as $item)
        {
            $piclist[] = new NewsItem([
                'title' => $item['title'],
                'description' => strip_tags($item['content']),
                'url' => url('home/index/info', ['pid' => $item['id']], true, true),
                'image' => $item['thumbs_text'],
            ]);
        }

        $news = new News($piclist);

        $success['result'] = true;
        $success['msg'] = '查询课程成功';
        $success['data'] = $news;

        return $success;
    }    
}