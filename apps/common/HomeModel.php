<?php
namespace app\common;

use core\basic\Model;

class HomeModel extends Model
{

    // 获取区域列表
    public function getArea()
    {
        return parent::table('ay_area')->field('acode,name,is_default')
            ->order('is_default DESC')
            ->select();
    }

    // 获取主题
    public function getTheme()
    {
        return parent::table('ay_site')->where("acode='" . session('lg') . "'")->value('theme');
    }

    // 获取配置参数
    public function getConfig()
    {
        return parent::table('ay_config')->column('value', 'name');
    }
}