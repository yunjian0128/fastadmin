<?php

return [
    [
        'name' => 'app_id',
        'title' => 'app_id',
        'type' => 'string',
        'content' => [],
        'value' => 'wx8f07fb8d96dee4c9',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'secret',
        'title' => 'secret',
        'type' => 'string',
        'content' => [],
        'value' => 'd3554bfb2808112b9d3480e9c4bb1b8b',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'token',
        'title' => 'token',
        'type' => 'string',
        'content' => [],
        'value' => 'FASTADMIN',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'aes_key',
        'title' => 'aes_key',
        'type' => 'string',
        'content' => [],
        'value' => '5Wve3zBu49eg6aQFUPzpPCzlkBzK8yJ0I0sYTODuoHz',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'debug',
        'title' => '调试模式',
        'type' => 'radio',
        'content' => [
            '否',
            '是',
        ],
        'value' => '0',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'log_level',
        'title' => '日志记录等级',
        'type' => 'select',
        'content' => [
            'debug' => 'debug',
            'info' => 'info',
            'notice' => 'notice',
            'warning' => 'warning',
            'error' => 'error',
            'critical' => 'critical',
            'alert' => 'alert',
            'emergency' => 'emergency',
        ],
        'value' => 'info',
        'rule' => 'required',
        'msg' => '',
        'tip' => '生产环境日志记录等级',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'oauth_callback',
        'title' => '登录回调',
        'type' => 'string',
        'content' => [],
        'value' => 'https://fastadmin.yunjian0128.cn/addons/wechat/index/callback',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
];
