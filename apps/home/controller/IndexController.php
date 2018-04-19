<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年2月14日
 *  首页控制器
 */
namespace app\home\controller;

use core\basic\Controller;

class IndexController extends Controller
{

    protected $parser;

    public function __construct()
    {
        $this->parser = new ParserController();
    }

    // 首页
    public function index()
    {
        $content = parent::parser('index.html'); // 框架标签解析
        $content = $this->parser->parserPosition($content, 0); // CMS当前位置标签解析
        $content = $this->parser->parserSpecialPageSortLabel($content, 0, '首页', SITE_DIR); // 解析分类标签
        $content = $this->parser->parserCommom($content); // CMS公共标签解析
        $this->cache($content, true);
    }

    // 空拦截
    public function _empty()
    {
        error('您访问的地址有误，请核对后重试！');
    }
}