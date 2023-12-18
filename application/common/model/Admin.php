<?php

namespace app\common\model;

use think\Model;

// 引入系统配置表
use app\common\model\Config as ConfigModel;

/**
 * 客户管理模型
 * @package app\common\model\
 */

class Admin extends Model
{
    // 设置当前模型对应的数据表名称
    protected $name = 'admin';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 设置更新的字段名
    protected $updateTime = 'updatetime';

    // 定义软删除字段名
    protected $deleteTime = 'deletetime';

    protected $append = [
        'group_text',  // 角色的名称
        'avatar_text',
    ];

    /**
     * 重置用户密码
     * @author baiyouwen
     */
    public function resetPassword($uid, $NewPassword)
    {
        $passwd = $this->encryptPassword($NewPassword);
        $ret = $this->where(['id' => $uid])->update(['password' => $passwd]);
        return $ret;
    }

    // 密码加密
    protected function encryptPassword($password, $salt = '', $encrypt = 'md5')
    {
        return $encrypt($password . $salt);
    }

    //角色组的别名
    public function getGroupTextAttr($value, $data)
    {
        //权限分组表
        $AuthGroupAccessModel = model('AuthGroupAccess');

        //分组表
        $AuthGroupModel = model('AuthGroup');

        $gid = $AuthGroupAccessModel->where(['uid' => $data['id']])->value('group_id');

        if (!$gid) {
            return '暂无角色组';
        }

        //分组的名称
        $name = $AuthGroupModel->where(['id' => $gid])->value('name');

        if (!$name) {
            return '暂无角色组名称';
        }

        return $name;
    }

    /**
     * 获取个人头像信息  获取器
     * @param string $value
     * @param array  $data
     * @return string
     * get + AvatarCdn + Attr
     */
    public function getAvatarTextAttr($value, $data)
    {
        // 获取系统配置表里面的网站地址
        $url = ConfigModel::where('name', 'Url')->value('value');

        $url = $url ? $url : '';

        $avatar = str_replace($url,'',$data['avatar']);

        $avatar = empty($data['avatar']) ? $url . '/assets/img/avatar1.png' : $url . $avatar;
        return $avatar;
    }
}