<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2016年11月6日
 *  应用控制基类  
 */
namespace core\basic;

use core\view\View;
use core\view\Paging;

class Controller
{

    // 显示模板
    final protected function display($file)
    {
        $view = View::getInstance();
        echo $view->parser($file);
    }

    // 解析模板
    final protected function parser($file)
    {
        $view = View::getInstance();
        return $view->parser($file);
    }

    // 缓存页面内容,默认直接显示内容，可传递第二参数false返回内容
    final protected function cache($content, $display = true)
    {
        $view = View::getInstance();
        $view->cache($content);
        if ($display) {
            echo $content;
        } else {
            return $content;
        }
    }

    // 设置视图主题
    final protected function setTheme($themeName)
    {
        $view = View::getInstance();
        $view->assign('theme', $themeName);
    }

    // 变量注入接口
    final protected function assign($var, $value)
    {
        $view = View::getInstance();
        $view->assign($var, $value);
    }

    // 变量获取接口
    final protected function getVar($var)
    {
        $view = View::getInstance();
        return $view->getVar($var);
    }

    // 手动生成分页信息,返回限制语句
    final protected function page($tatal, $morePageStr = false)
    {
        $page = Paging::getInstance();
        return $page->limit($tatal, $morePageStr);
    }

    // 获取配置信息
    final protected function config($item = null, $array = false)
    {
        return Config::get($item, $array);
    }

    // 缓存配置信息
    final protected function setConfig($itemName, array $data)
    {
        return Config::set($itemName, $data);
    }

    // 写入日志信息
    final protected function log($content, $level = "info")
    {
        Log::write($content, $level);
    }
}

