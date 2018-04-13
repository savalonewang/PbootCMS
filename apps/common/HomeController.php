<?php
namespace app\common;

use core\basic\Controller;

class HomeController extends Controller
{

    public function __construct()
    {
        $model = new HomeModel();
        
        // 获取默认区域
        if (! isset($_SESSION['lg'])) {
            $area = $model->getArea();
            if (isset($area[0])) {
                session('lg', $area[0]->acode);
                session('lgs', $area);
            } else {
                session('lg', 'cn');
                session('lgs', array(
                    'cn'
                ));
            }
        }
        
        // 站点基础信息
        if (session('config') != $model->getConfig()) {
            session('config', $model->getConfig());
        }
        
        // 手机自适应主题
        $theme = $model->getTheme(); // 获取系统设置的主题
        if (session('config.open_wap') && is_mobile()) {
            $this->setTheme($theme . '/wap'); // 移动端主题
        } else {
            $this->setTheme($theme);
        }
    }
}