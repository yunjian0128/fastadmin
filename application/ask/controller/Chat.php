<?php

namespace app\ask\controller;

use think\Controller;

class Chat extends Controller {
    public function __construct() {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->MessageModel = model('Business.Message');
    }

    // 查询私信列表
    public function list() {

        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $sendid = $this->request->param('sendid', 0, 'trim');
            $receiveid = $this->request->param('receiveid', 0, 'trim');
            $page = $this->request->param('page', 1, 'intval');
            $limit = 10;
            $offset = ($page - 1) * $limit;

            // 判断用户是否存在
            $business = $this->BusinessModel->find($sendid);

            if(!$business) {
                $this->error('当前用户不存在');
                exit;
            }

            // 判断用户是否存在
            $receive = $this->BusinessModel->find($receiveid);

            if(!$receive) {
                $this->error('查看的用户不存在');
                exit;
            }

            // 查询出所有的私信列表
            $list = $this->MessageModel
                ->with(['sender', 'receiver'])
                ->where("(sendid = ? AND receiveid = ?) OR (sendid = ? AND receiveid = ?)", [$sendid, $receiveid, $receiveid, $sendid])
                ->limit($offset, $limit)
                ->order('createtime', 'desc')
                ->select();

            // 如果delid==busid，代表当前用户删除了该私信
            if($list) {
                foreach($list as $key => $value) {
                    if($value['delid'] == $sendid) {
                        unset($list[$key]);
                    }

                    // 去除密码和盐
                    unset($list[$key]['sender']['password']);
                    unset($list[$key]['sender']['salt']);
                    unset($list[$key]['receiver']['password']);
                    unset($list[$key]['receiver']['salt']);
                }
            }

            if(!$list) {
                $this->error('暂无更多数据');
                exit;
            }

            // 请求过列表后，将所有的私信都设置为已读
            $ids = $this->MessageModel
                ->where(['sendid' => $receiveid, 'receiveid' => $sendid])
                ->column('id');

            // 组装数据
            $data = [
                'id' => ['in', $ids],
                'status' => 1,
            ];

            // 执行更新
            $result = $this->MessageModel
                ->isUpdate(true)
                ->save($data);

            if($result === FALSE) {
                $this->error($this->MessageModel->getError());
                exit;
            }

            $this->success('私信列表', null, $list);
            exit;
        }
    }

    // 私信
    public function send() {
        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $sendid = $this->request->param('sendid', 0, 'trim');
            $receiveid = $this->request->param('receiveid', 0, 'trim');
            $content = $this->request->param('content', '', 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($sendid);
            $receive = $this->BusinessModel->find($receiveid);

            if(!$business) {
                $this->error('当前用户不存在');
                exit;
            }

            if(!$receive) {
                $this->error('查看的用户不存在');
                exit;
            }

            // 组装数据
            $data = [
                'sendid' => $sendid,
                'receiveid' => $receiveid,
                'content' => $content,
                'status' => 0,
            ];

            // 执行插入
            $result = $this->MessageModel->save($data);

            if($result === FALSE) {
                $this->error($this->MessageModel->getError());
                exit;
            }

            $this->success('发送成功');
            exit;
        }
    }

    // 消息列表
    public function message() {

        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 查询出所有的私信列表
            $Messages = $this->MessageModel
                ->where("(sendid = ?)  OR (receiveid = ?)",
                    [$busid, $busid])
                ->order('createtime', 'desc')
                ->select();

            // 如果delid==busid，代表当前用户删除了该私信
            if($Messages) {
                foreach($Messages as $key => $value) {
                    if($value['delid'] == $busid) {
                        unset($Messages[$key]);
                    }
                }
            }

            $busids = [];

            foreach($Messages as $value) {
                if($value['sendid'] == $busid) {
                    $busids[] = $value['receiveid'];
                } else {
                    $busids[] = $value['sendid'];
                }
            }

            $busids = array_unique($busids);
            $busids = array_values($busids);

            $list = [];

            // 未读消息数
            $unread = 0;

            foreach($busids as $key => $value) {
                $list[$key] = $this->BusinessModel->find($value);
                $last_message = $this->MessageModel
                    ->where("(sendid = ? AND receiveid = ?) 
                        OR (sendid = ? AND receiveid = ?)",
                        [$busid, $value, $value, $busid])
                    ->order('createtime', 'desc')
                    ->select();
                $list[$key]['last_message'] = $last_message[0];

                foreach($last_message as $v) {
                    if($v['status'] == 0 && $v['receiveid'] == $busid) {
                        $unread++;
                    }
                }

                $list[$key]['unread'] = $unread;
            }

            // 如果keywords不为空，代表是搜索
            if($keywords) {
                foreach($list as $key => $value) {
                    if(strpos($value['nickname'], $keywords) === FALSE
                        && strpos($value['last_message']['content'], $keywords) === FALSE) {
                        unset($list[$key]);
                    }
                }
            }

            $list = array_values($list);

            // 去除密码和盐
            foreach($list as $key => $value) {
                unset($list[$key]['password']);
                unset($list[$key]['salt']);
            }

            if(!$list) {
                $this->error('暂无更多数据');
                exit;
            }

            $this->success('私信列表', null, $list);
        }
    }

    // 阅读私信
    public function read() {

        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $messageid = $this->request->param('messageid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('当前用户不存在');
                exit;
            }

            $message = $this->MessageModel->find($messageid);

            if(!$message) {
                $this->error('当前私信不存在');
                exit;
            }

            if($message['sendid'] != $busid && $message['receiveid'] != $busid) {
                $this->error('该私信不属于当前用户');
                exit;
            }

            // 判断是否已读
            if($message['status'] == 1) {
                $this->error('该私信已读');
                exit;
            } else {

                // 组装数据
                $data = [
                    'id' => $messageid,
                    'status' => 1,
                ];

                // 执行更新
                $result = $this->MessageModel
                    ->isUpdate(true)
                    ->save($data);

                if($result === FALSE) {
                    $this->error($this->MessageModel->getError());
                    exit;
                }

                $this->success('私信已读成功');
                exit;
            }
        }
    }

    // 删除私信
    public function delete() {

        // 判断是否有Post过来数据
        if($this->request->isPost()) {
            $messageid = $this->request->param('messageid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');

            // 转化为整数
            $busid = intval($busid);

            // 判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if(!$business) {
                $this->error('当前用户不存在');
                exit;
            }

            $message = $this->MessageModel->find($messageid);

            if(!$message) {
                $this->error('当前私信不存在');
                exit;
            }

            if($message['sendid'] != $busid && $message['receiveid'] != $busid) {
                $this->error('该私信不属于当前用户');
                exit;
            }

            // 选择出所有符合条件的私信
            $messages = $this->MessageModel
                ->where("(sendid = ? AND receiveid = ?) 
                    OR (sendid = ? AND receiveid = ?)",
                    [$message['sendid'], $message['receiveid'], $message['receiveid'], $message['sendid']])
                ->select();

            $messages = collection($messages)->toArray();

            // 选择出所有符合条件的私信id
            $messageids = array_column($messages, 'id');

            if($messages[0]['delid'] == NULL) {
                $delid = $busid;

                // 组装数据
                $data = [
                    'id' => ['in', $messageids],
                    'delid' => $delid,
                ];

                // 执行更新
                $result = $this->MessageModel
                    ->isUpdate(true)
                    ->save($data);

                if($result === FALSE) {
                    $this->error($this->MessageModel->getError());
                    exit;
                }

                $this->success('私信删除成功');
                exit;
            } else {

                // 不为空代表双方都删除了
                $result = $this->MessageModel->destroy($messageids);

                if($result === FALSE) {
                    $this->error($this->MessageModel->getError());
                    exit;
                }

                $this->success('私信删除成功');
                exit;
            }
        }
    }
}

?>