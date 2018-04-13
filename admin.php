<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2016年11月5日
 *  管理后台入口文件
 */

// 设置输出编码
header('Content-Type:text/html; charset=utf-8');

// 入口文件地址绑定
define('URL_BLIND', 'admin');

// PHP版本检测
if (PHP_VERSION < '5.3') {
    exit('您服务器的PHP版本太低，本程序要求版本不小于5.3');
}

// 引用内核启动文件
require dirname(__FILE__) . '/core/start.php';
