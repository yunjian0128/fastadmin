<?php

namespace app\common\model\Depot;

use think\Model;

class StorageProduct extends Model
{
    // 表名
    protected $name = 'depot_storage_product';

    // 忽略多余的字段
    protected $fields = true;
}
