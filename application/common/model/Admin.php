<?php

namespace app\common\model;

use think\Model;

// 引入软删除类
use traits\model\SoftDelete;

/**
 * 客户管理模型
 * @package app\common\model\
 */

class Admin extends Model
{
    // 设置当前模型对应的数据表名称
    protected $name = 'admin';

    // 使用软删除
    use SoftDelete;

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 设置更新的字段名
    protected $updateTime = false; // 不需要更新时间

    // 定义软删除字段名
    protected $deleteTime = 'deletetime';
}