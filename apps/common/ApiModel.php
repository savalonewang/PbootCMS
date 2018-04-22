<?php
namespace app\common;

use core\basic\Model;

class ApiModel extends Model
{

    // 获取配置参数
    public function getConfig()
    {
        return parent::table('ay_config')->column('value', 'name');
    }
}