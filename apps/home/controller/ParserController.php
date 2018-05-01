<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年2月14日
 *  标签解析引擎控制器
 */
namespace app\home\controller;

use core\basic\Controller;
use app\home\model\ParserModel;

class ParserController extends Controller
{

    protected $model;

    public function __construct()
    {
        $this->model = new ParserModel();
    }

    public function _empty()
    {
        error('您访问的地址有误，请核对后重试！');
    }

    // 解析全局公共标签
    public function parserCommom($content)
    {
        $content = $this->parserSingleLabel($content); // 单标签解析
        $content = $this->parserSiteLabel($content); // 站点标签
        $content = $this->parserCompanyLabel($content); // 公司标签
        $content = $this->parserUserLabel($content); // 自定义标签
        $content = $this->parserNavLabel($content); // 分类列表
        $content = $this->parserSpecifySortLabel($content); // 指定分类
        $content = $this->parserSpecifyListLabel($content); // 指定列表
        $content = $this->parserSpecifyContentLabel($content); // 指定内容
        $content = $this->parserContentPicsLabel($content); // 内容多图
        $content = $this->parserSlideLabel($content); // 幻灯片
        $content = $this->parserLinkLabel($content); // 友情链接
        $content = $this->parserMessageLabel($content); // 留言板
        $content = $this->parserPageLabel($content); // CMS分页标签解析(需置后)
        $content = $this->parserIfLabel($content); // IF语句(需置后)
        return $content;
    }

    // 解析调节参数
    protected function parserParam($string)
    {
        if (! $string = trim($string))
            return array();
        $string = preg_replace('/\s+/', ' ', $string);
        $params = explode(' ', $string);
        $param = array();
        foreach ($params as $key => $value) {
            $temp = explode('=', $value);
            if (isset($temp[1])) {
                $param[$temp[0]] = $temp[1];
            } else {
                $param[$temp[0]] = '';
            }
        }
        return $param;
    }

    // 解析单标签
    public function parserSingleLabel($content)
    {
        $content = str_replace('{pboot:msgaction}', url('/home/Message/add'), $content); // 留言提交路径
        $content = str_replace('{pboot:checkcode}', CORE_DIR . '/code.php', $content); // 验证码路径
        $content = str_replace('{pboot:lgpath}', url('/home/Do/area'), $content); // 多语言切换前置路径,如{pboot:lgpath}?lg=cn
        $content = str_replace('{pboot:scaction}', url('/home/Search/index'), $content); // 搜索提交路径
        return $content;
    }

    // 解析站点标签
    public function parserSiteLabel($content)
    {
        $pattern = '/\{pboot:site([\w]+)(\s+[^}]+)?\}/';
        if (preg_match_all($pattern, $content, $matches)) {
            $data = $this->model->getSite();
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                $params = $this->parserParam($matches[2][$i]);
                switch ($matches[1][$i]) {
                    case 'index':
                        $content = str_replace($matches[0][$i], url('/', false), $content);
                        break;
                    case 'path':
                        $content = str_replace($matches[0][$i], SITE_DIR, $content);
                        break;
                    case 'logo':
                        if (isset($data->logo)) {
                            $content = str_replace($matches[0][$i], SITE_DIR . $data->logo, $content);
                        } else {
                            $content = str_replace($matches[0][$i], '', $content);
                        }
                        break;
                    case 'tplpath':
                        $content = str_replace($matches[0][$i], APP_THEME_DIR, $content);
                        break;
                    case 'language':
                        $content = str_replace($matches[0][$i], session('lg'), $content);
                        break;
                    case 'statistical':
                        if (isset($data->statistical)) {
                            $content = str_replace($matches[0][$i], decode_string($data->statistical), $content);
                        } else {
                            $content = str_replace($matches[0][$i], '', $content);
                        }
                    case 'copyright':
                        if (isset($data->copyright)) {
                            $content = str_replace($matches[0][$i], decode_string($data->copyright), $content);
                        } else {
                            $content = str_replace($matches[0][$i], '', $content);
                        }
                    default:
                        if (isset($data->{$matches[1][$i]})) {
                            $content = str_replace($matches[0][$i], $this->adjustLabelData($params, $data->{$matches[1][$i]}), $content);
                        } else {
                            $content = str_replace($matches[0][$i], '', $content);
                        }
                }
            }
        }
        return $content;
    }

    // 解析公司标签
    public function parserCompanyLabel($content)
    {
        $pattern = '/\{pboot:company([\w]+)(\s+[^}]+)?\}/';
        if (preg_match_all($pattern, $content, $matches)) {
            $data = $this->model->getCompany();
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                if (! $data) { // 无数据时直接替换为空
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                $params = $this->parserParam($matches[2][$i]);
                switch ($matches[1][$i]) {
                    default:
                        if (isset($data->{$matches[1][$i]})) {
                            $content = str_replace($matches[0][$i], $this->adjustLabelData($params, $data->{$matches[1][$i]}), $content);
                        }
                }
            }
        }
        return $content;
    }

    // 解析自定义标签
    public function parserUserLabel($content)
    {
        $pattern = '/\{label:([\w]+)(\s+[^}]+)?\}/';
        if (preg_match_all($pattern, $content, $matches)) {
            $data = $this->model->getLabel();
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                if (! $data) { // 无数据时直接替换为空
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                $params = $this->parserParam($matches[2][$i]);
                switch ($matches[1][$i]) {
                    default:
                        if (isset($data[$matches[1][$i]])) {
                            $content = str_replace($matches[0][$i], $this->adjustLabelData($params, $data[$matches[1][$i]]), $content);
                        }
                }
            }
        }
        return $content;
    }

    // 解析栏目列表标签
    public function parserNavLabel($content)
    {
        $pattern = '/\{pboot:nav(\s+[^}]+)?\}([\s\S]*?)\{\/pboot:nav\}/';
        $pattern2 = '/\[nav:([\w]+)(\s+[^]]+)?\]/';
        $pattern3 = '/pboot:([0-9])+nav/';
        if (preg_match_all($pattern, $content, $matches)) {
            $data = $this->model->getSorts();
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                
                // 无数据时直接替换整体标签为空
                if (! $data['tree']) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                
                // 获取调节参数
                $params = $this->parserParam($matches[1][$i]);
                $parent = 0;
                $num = 0;
                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'parent':
                            $parent = $value;
                            break;
                        case 'num':
                            $num = $value;
                            break;
                    }
                }
                
                if ($parent) { // 非顶级栏目起始
                    if (isset($data['tree'][$parent]['son'])) {
                        $out_data = $data['tree'][$parent]['son'];
                    } else {
                        $out_data = array();
                    }
                } else { // 顶级栏目起始
                    $out_data = $data['top'];
                }
                
                // 读取指定数量
                if ($num) {
                    $out_data = array_slice($out_data, 0, $num);
                }
                
                // 匹配到内部标签
                if (preg_match_all($pattern2, $matches[2][$i], $matches2)) {
                    $count2 = count($matches2[0]); // 循环内的内容标签数量
                } else {
                    $count2 = 0;
                }
                
                $out_html = '';
                $key = 1;
                foreach ($out_data as $value) { // 按查询的数据条数循环
                    $one_html = $matches[2][$i];
                    if ($count2) {
                        for ($j = 0; $j < $count2; $j ++) { // 循环替换数据
                            $params = $this->parserParam($matches2[2][$j]);
                            switch ($matches2[1][$j]) {
                                case 'i':
                                    $one_html = str_replace($matches2[0][$j], $key, $one_html);
                                    break;
                                case 'link':
                                    if ($value['outlink']) {
                                        $one_html = str_replace($matches2[0][$j], $value['outlink'], $one_html);
                                    } elseif ($value['type'] == 1) {
                                        $one_html = str_replace($matches2[0][$j], url('/home/about/index/scode/' . $value['scode']), $one_html);
                                    } else {
                                        $one_html = str_replace($matches2[0][$j], url('/home/list/index/scode/' . $value['scode']), $one_html);
                                    }
                                    break;
                                case 'soncount':
                                    if (isset($data['tree'][$value['scode']]['son'])) {
                                        $one_html = str_replace($matches2[0][$j], count($data['tree'][$value['scode']]['son']), $one_html);
                                    } else {
                                        $one_html = str_replace($matches2[0][$j], 0, $one_html);
                                    }
                                    break;
                                case 'ico':
                                    $one_html = str_replace($matches2[0][$j], SITE_DIR . $value['ico'], $one_html);
                                    break;
                                case 'pic':
                                    $one_html = str_replace($matches2[0][$j], SITE_DIR . $value['pic'], $one_html);
                                    break;
                                default:
                                    if (isset($value[$matches2[1][$j]])) {
                                        $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value[$matches2[1][$j]]), $one_html);
                                    }
                            }
                        }
                    }
                    $key ++;
                    $out_html .= $one_html;
                }
                
                // 无限极嵌套解析
                if (preg_match($pattern3, $out_html, $matches3)) {
                    $out_html = str_replace('pboot:' . $matches3[1] . 'nav', 'pboot:nav', $out_html);
                    $out_html = str_replace('[' . $matches3[1] . 'nav:', '[nav:', $out_html);
                    $out_html = $this->parserNavLabel($out_html);
                }
                
                // 执行内容替换
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 解析当前位置
    public function parserPosition($content, $scode)
    {
        $pattern = '/\{pboot:position(\s+[^}]+)?\}/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            $separator = '>>';
            for ($i = 0; $i < $count; $i ++) {
                $params = $this->parserParam($matches[1][$i]);
                
                // 分离参数
                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'separator':
                            $separator = $value;
                            break;
                    }
                }
                
                $data = $this->model->getPosition($scode);
                $out_html = '<a href="' . SITE_DIR . '/">首页</a>';
                foreach ($data as $key => $value) {
                    if ($value['outlink']) {
                        $out_html .= $separator . '<a href="' . $value['outlink'] . '">' . $value['name'] . '</a>';
                    } elseif ($value['type'] == 1) {
                        $out_html .= $separator . '<a href="' . url('/home/about/index/scode/' . $value['scode']) . '">' . $value['name'] . '</a>';
                    } elseif ($value['type'] == 2) {
                        $out_html .= $separator . '<a href="' . url('/home/list/index/scode/' . $value['scode']) . '">' . $value['name'] . '</a>';
                    }
                }
                // 执行内容替换
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 解析当前分类标签
    public function parserSortLabel($content, $sort)
    {
        $pattern = '/\{sort:([\w]+)(\s+[^}]+)?\}/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                $params = $this->parserParam($matches[2][$i]);
                switch ($matches[1][$i]) {
                    case 'link':
                        if ($sort->outlink) {
                            $content = str_replace($matches[0][$i], $sort->outlink, $content);
                        } elseif ($sort->type == 1) {
                            $content = str_replace($matches[0][$i], url('/home/about/index/scode/' . $sort->scode), $content);
                        } else {
                            $content = str_replace($matches[0][$i], url('/home/list/index/scode/' . $sort->scode), $content);
                        }
                        break;
                    case 'tcode': // 顶级栏目ID
                        $content = str_replace($matches[0][$i], $this->model->getSortTopScode($sort->scode), $content);
                        break;
                    case 'ico':
                        $content = str_replace($matches[0][$i], SITE_DIR . $sort->ico, $content);
                        break;
                    case 'pic':
                        $content = str_replace($matches[0][$i], SITE_DIR . $sort->pic, $content);
                        break;
                    default:
                        if (isset($sort->{$matches[1][$i]})) {
                            $content = str_replace($matches[0][$i], $this->adjustLabelData($params, $sort->{$matches[1][$i]}), $content);
                        } else {
                            $content = str_replace($matches[0][$i], '', $content);
                        }
                }
            }
        }
        return $content;
    }

    // 解析非栏目页分类标签
    public function parserSpecialPageSortLabel($content, $id, $page, $link)
    {
        $pattern = '/\{sort:([\w]+)(\s+[^}]+)?\}/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                $params = $this->parserParam($matches[2][$i]);
                switch ($matches[1][$i]) {
                    case 'tcode': // 顶级栏目ID
                        $content = str_replace($matches[0][$i], $id, $content);
                        break;
                    case 'pcode': // 父栏目ID
                        $content = str_replace($matches[0][$i], $id, $content);
                        break;
                    case 'scode': // 当前栏目ID
                        $content = str_replace($matches[0][$i], $id, $content);
                        break;
                    case 'link':
                        $content = str_replace($matches[0][$i], $link, $content);
                        break;
                    case 'name': // 当前分类名称
                        $content = str_replace($matches[0][$i], $page, $content);
                        break;
                    default:
                        $content = str_replace($matches[0][$i], '', $content);
                }
            }
        }
        return $content;
    }

    // 解析指定分类标签
    public function parserSpecifySortLabel($content)
    {
        $pattern = '/\{pboot:sort(\s+[^}]+)?\}([\s\S]*?)\{\/pboot:sort\}/';
        $pattern2 = '/\[sort:([\w]+)(\s+[^]]+)?\]/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                // 获取调节参数
                $params = $this->parserParam($matches[1][$i]);
                $scode = - 1;
                
                // 跳过未指定scode的列表
                if (! array_key_exists('scode', $params)) {
                    continue;
                }
                
                // 分离分类编码
                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'scode':
                            $scode = $value;
                            break;
                    }
                }
                
                // 避免传递分类为0读取全部数据
                if (! $scode) {
                    $scode = - 1;
                }
                
                // 读取数据
                $data = $this->model->getSort($scode);
                
                // 无数据直接跳过
                if (! $data) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                
                // 匹配到内部标签
                if (preg_match_all($pattern2, $matches[2][$i], $matches2)) {
                    $count2 = count($matches2[0]); // 循环内的内容标签数量
                } else {
                    $count2 = 0;
                }
                
                $out_html = $matches[2][$i];
                for ($j = 0; $j < $count2; $j ++) { // 循环替换数据
                    $params = $this->parserParam($matches2[2][$j]);
                    switch ($matches2[1][$j]) {
                        case 'link':
                            if ($data->outlink) {
                                $out_html = str_replace($matches2[0][$j], $data->outlink, $out_html);
                            } elseif ($data->type == 1) {
                                $out_html = str_replace($matches2[0][$j], url('/home/about/index/scode/' . $data->scode), $out_html);
                            } else {
                                $out_html = str_replace($matches2[0][$j], url('/home/list/index/scode/' . $data->scode), $out_html);
                            }
                            break;
                        case 'ico':
                            $out_html = str_replace($matches2[0][$j], SITE_DIR . $data->ico, $out_html);
                            break;
                        case 'pic':
                            $out_html = str_replace($matches2[0][$j], SITE_DIR . $data->pic, $out_html);
                            break;
                        default:
                            if (isset($data->{$matches2[1][$j]})) {
                                $out_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $data->{$matches2[1][$j]}), $out_html);
                            }
                    }
                }
                // 执行替换
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 解析当前分类列表标签
    public function parserListLabel($content, $scode)
    {
        $pattern = '/\{pboot:list(\s+[^}]+)?\}([\s\S]*?)\{\/pboot:list\}/';
        $pattern2 = '/\[list:([\w]+)(\s+[^]]+)?\]/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                // 获取调节参数
                $params = $this->parserParam($matches[1][$i]);
                $num = $this->config('pagesize');
                $order = 'date DESC';
                $filter = '';
                
                // 跳过带scode的指定列表
                if (array_key_exists('scode', $params)) {
                    continue;
                }
                
                // 分离参数
                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'num':
                            $num = $value;
                            break;
                        case 'order':
                            switch ($value) {
                                case 'date':
                                case 'istop':
                                case 'isrecommend':
                                case 'isheadline':
                                case 'visits':
                                case 'likes':
                                case 'oppose':
                                    $order = $value . ' DESC';
                                    break;
                                default:
                                    $order = $value . ' ASC';
                            }
                            $order .= ",sorting ASC,id DESC";
                            break;
                        case 'filter':
                            $filter = $value;
                            break;
                    }
                }
                
                // 内容过滤筛选
                $filter_field = '';
                $filter_keyword = '';
                if ($filter) {
                    $filter = explode('|', $filter);
                    if (count($filter) == 2) {
                        $filter_field = $filter[0];
                        $filter_keyword = $filter[1];
                    }
                }
                
                // 读取数据
                if (! isset($data)) { // 避免同页面多次调用无分类参数列表出现分页错误，多次调用取相同数据
                    $data = $this->model->getList($scode, $num, $order, $filter_field, $filter_keyword);
                }
                
                // 无数据直接替换
                if (! $data) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                
                // 匹配到内部标签
                if (preg_match_all($pattern2, $matches[2][$i], $matches2)) {
                    $count2 = count($matches2[0]); // 循环内的内容标签数量
                } else {
                    $count2 = 0;
                }
                
                $out_html = '';
                $key = 1;
                foreach ($data as $value) { // 按查询数据条数循环
                    $one_html = $matches[2][$i];
                    for ($j = 0; $j < $count2; $j ++) { // 循环替换数据
                        $params = $this->parserParam($matches2[2][$j]);
                        switch ($matches2[1][$j]) {
                            case 'i':
                                $one_html = str_replace($matches2[0][$j], $key, $one_html);
                                break;
                            case 'link':
                                if ($value->outlink) {
                                    $one_html = str_replace($matches2[0][$j], $value->outlink, $one_html);
                                } elseif ($value->filename) {
                                    $one_html = str_replace($matches2[0][$j], url('/home/content/index/id/' . $value->filename), $one_html);
                                } else {
                                    $one_html = str_replace($matches2[0][$j], url('/home/content/index/id/' . $value->id), $one_html);
                                }
                                break;
                            case 'sortlink':
                                $one_html = str_replace($matches2[0][$j], url('/home/list/index/scode/' . $value->scode), $one_html);
                                break;
                            case 'subsortlink':
                                $one_html = str_replace($matches2[0][$j], url('/home/list/index/scode/' . $value->subscode), $one_html);
                                break;
                            case 'sortname':
                                if ($value->sortname) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->sortname), $one_html);
                                } else {
                                    $one_html = str_replace($matches2[0][$j], '', $one_html);
                                }
                                break;
                            case 'subsortname':
                                if ($value->subsortname) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->subsortname), $one_html);
                                } else {
                                    $one_html = str_replace($matches2[0][$j], '', $one_html);
                                }
                                break;
                            case 'ico':
                                $one_html = str_replace($matches2[0][$j], SITE_DIR . $value->ico, $one_html);
                                break;
                            case 'enclosure':
                                $one_html = str_replace($matches2[0][$j], SITE_DIR . $value->enclosure, $one_html);
                                break;
                            case 'likeslink':
                                $one_html = str_replace($matches2[0][$j], url('/home/Do/likes/id/' . $value->id), $one_html);
                                break;
                            case 'opposelink':
                                $one_html = str_replace($matches2[0][$j], url('/home/Do/oppose/id/' . $value->id), $one_html);
                                break;
                            default:
                                if (isset($value->{$matches2[1][$j]})) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->{$matches2[1][$j]}), $one_html);
                                } elseif (strpos($matches2[1][$j], 'ext_') === 0) {
                                    $one_html = str_replace($matches2[0][$j], '', $one_html);
                                }
                        }
                    }
                    $key ++;
                    $out_html .= $one_html;
                }
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 解析指定分类列表标签
    public function parserSpecifyListLabel($content)
    {
        $pattern = '/\{pboot:list(\s+[^}]+)?\}([\s\S]*?)\{\/pboot:list\}/';
        $pattern2 = '/\[list:([\w]+)(\s+[^]]+)?\]/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                // 获取调节参数
                $params = $this->parserParam($matches[1][$i]);
                $num = 10;
                $order = 'date DESC';
                $scode = - 1;
                $filter = '';
                $page = 0; // 默认不执行分页
                           
                // 跳过未指定scode的列表
                if (! array_key_exists('scode', $params)) {
                    continue;
                }
                
                // 分离参数
                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'scode':
                            $scode = $value;
                            break;
                        case 'num':
                            $num = $value;
                            break;
                        case 'order':
                            switch ($value) {
                                case 'date':
                                case 'istop':
                                case 'isrecommend':
                                case 'isheadline':
                                case 'visits':
                                case 'likes':
                                case 'oppose':
                                    $order = $value . ' DESC';
                                    break;
                                default:
                                    $order = $value . ' ASC';
                            }
                            $order .= ",sorting ASC,id DESC";
                            break;
                        case 'filter':
                            $filter = $value;
                            break;
                        case 'page':
                            $page = $value;
                    }
                }
                
                // 避免传递分类为0读取全部数据
                if (! $scode) {
                    $scode = - 1;
                }
                
                // 内容过滤筛选
                $filter_field = '';
                $filter_keyword = '';
                if ($filter) {
                    $filter = explode('|', $filter);
                    if (count($filter) == 2) {
                        $filter_field = $filter[0];
                        $filter_keyword = $filter[1];
                    }
                }
                
                // 读取数据
                if ($page) {
                    $data = $this->model->getList($scode, $num, $order, $filter_field, $filter_keyword);
                } else {
                    $data = $this->model->getSpecifyList($scode, $num, $order, $filter_field, $filter_keyword);
                }
                
                // 无数据直接替换为空
                if (! $data) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                
                // 匹配到内部标签
                if (preg_match_all($pattern2, $matches[2][$i], $matches2)) {
                    $count2 = count($matches2[0]); // 循环内的内容标签数量
                } else {
                    $count2 = 0;
                }
                
                $out_html = '';
                $key = 1;
                foreach ($data as $value) { // 按查询数据条数循环
                    $one_html = $matches[2][$i];
                    for ($j = 0; $j < $count2; $j ++) { // 循环替换数据
                        $params = $this->parserParam($matches2[2][$j]);
                        switch ($matches2[1][$j]) {
                            case 'i':
                                $one_html = str_replace($matches2[0][$j], $key, $one_html);
                                break;
                            case 'link':
                                if ($value->outlink) {
                                    $one_html = str_replace($matches2[0][$j], $value->outlink, $one_html);
                                } elseif ($value->filename) {
                                    $one_html = str_replace($matches2[0][$j], url('/home/content/index/id/' . $value->filename), $one_html);
                                } else {
                                    $one_html = str_replace($matches2[0][$j], url('/home/content/index/id/' . $value->id), $one_html);
                                }
                                break;
                            case 'sortlink':
                                $one_html = str_replace($matches2[0][$j], url('/home/list/index/scode/' . $value->scode), $one_html);
                                break;
                            case 'subsortlink':
                                $one_html = str_replace($matches2[0][$j], url('/home/list/index/scode/' . $value->subscode), $one_html);
                                break;
                            case 'sortname':
                                if ($value->sortname) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->sortname), $one_html);
                                } else {
                                    $one_html = str_replace($matches2[0][$j], '', $one_html);
                                }
                                break;
                            case 'subsortname':
                                if ($value->subsortname) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->subsortname), $one_html);
                                } else {
                                    $one_html = str_replace($matches2[0][$j], '', $one_html);
                                }
                                break;
                            case 'ico':
                                $one_html = str_replace($matches2[0][$j], SITE_DIR . $value->ico, $one_html);
                                break;
                            case 'enclosure':
                                $one_html = str_replace($matches2[0][$j], SITE_DIR . $value->enclosure, $one_html);
                                break;
                            case 'likeslink':
                                $one_html = str_replace($matches2[0][$j], url('/home/Do/likes/id/' . $value->id), $one_html);
                                break;
                            case 'opposelink':
                                $one_html = str_replace($matches2[0][$j], url('/home/Do/oppose/id/' . $value->id), $one_html);
                                break;
                            default:
                                if (isset($value->{$matches2[1][$j]})) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->{$matches2[1][$j]}), $one_html);
                                } elseif (strpos($matches2[1][$j], 'ext_') === 0) {
                                    $one_html = str_replace($matches2[0][$j], '', $one_html);
                                }
                        }
                    }
                    $key ++;
                    $out_html .= $one_html;
                }
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 解析当前内容标签
    public function parserContentLabel($content, $sort, $data)
    {
        $pattern = '/\{content:([\w]+)(\s+[^}]+)?\}/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                // 无数据直接替换并跳过
                if (! $data) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                $params = $this->parserParam($matches[2][$i]);
                switch ($matches[1][$i]) {
                    case 'link':
                        if ($data->outlink) {
                            $content = str_replace($matches[0][$i], $data->outlink, $content);
                        } elseif ($data->filename) {
                            $content = str_replace($matches[0][$i], url('/home/content/index/id/' . $data->filename), $content);
                        } else {
                            $content = str_replace($matches[0][$i], url('/home/content/index/id/' . $data->id), $content);
                        }
                        break;
                    case 'sortlink':
                        $content = str_replace($matches[0][$i], url('/home/list/index/scode/' . $data->scode), $content);
                        break;
                    case 'subsortlink':
                        $content = str_replace($matches[0][$i], url('/home/list/index/scode/' . $data->subscode), $content);
                        break;
                    case 'sortname':
                        if ($data->sortname) {
                            $content = str_replace($matches[0][$i], $this->adjustLabelData($params, $data->sortname), $content);
                        } else {
                            $content = str_replace($matches[0][$i], '', $content);
                        }
                        break;
                    case 'subsortname':
                        if ($data->subsortname) {
                            $content = str_replace($matches[0][$i], $this->adjustLabelData($params, $data->subsortname), $content);
                        } else {
                            $content = str_replace($matches[0][$i], '', $content);
                        }
                        break;
                    case 'ico':
                        $content = str_replace($matches[0][$i], SITE_DIR . $data->ico, $content);
                        break;
                    case 'enclosure':
                        $content = str_replace($matches[0][$i], SITE_DIR . $data->enclosure, $content);
                        break;
                    case 'likeslink':
                        $content = str_replace($matches[0][$i], url('/home/Do/likes/id/' . $data->id), $content);
                        break;
                    case 'opposelink':
                        $content = str_replace($matches[0][$i], url('/home/Do/oppose/id/' . $data->id), $content);
                        break;
                    case 'precontent':
                        if ($data->type != 2) // 非列表内容页不解析
                            break;
                        if (! ! $pre = $this->model->getContentPre($sort->scode, $data->id)) {
                            $content = str_replace($matches[0][$i], '<a href="' . url('/home/content/index/id/' . $pre->id) . '">' . $pre->title . '</a>', $content);
                        } else {
                            $content = str_replace($matches[0][$i], '没有了！', $content);
                        }
                        break;
                    case 'nextcontent':
                        if ($data->type != 2) // 非列表内容页不解析
                            break;
                        if (! ! $next = $this->model->getContentNext($sort->scode, $data->id)) {
                            $content = str_replace($matches[0][$i], '<a href="' . url('/home/content/index/id/' . $next->id) . '">' . $next->title . '</a>', $content);
                        } else {
                            $content = str_replace($matches[0][$i], '没有了！', $content);
                        }
                        break;
                    default:
                        if (isset($data->{$matches[1][$i]})) {
                            $content = str_replace($matches[0][$i], $this->adjustLabelData($params, $data->{$matches[1][$i]}), $content);
                        } elseif (strpos($matches[1][$i], 'ext_') === 0) {
                            $content = str_replace($matches[0][$i], '', $content);
                        }
                }
            }
        }
        return $content;
    }

    // 解析指定内容标签
    public function parserSpecifyContentLabel($content)
    {
        $pattern = '/\{pboot:content(\s+[^}]+)?\}([\s\S]*?)\{\/pboot:content\}/';
        $pattern2 = '/\[content:([\w]+)(\s+[^]]+)?\]/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                // 获取调节参数
                $params = $this->parserParam($matches[1][$i]);
                $id = - 1;
                
                // 跳过未指定id的列表
                if (! array_key_exists('id', $params)) {
                    continue;
                }
                
                // 分离参数
                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'id':
                            $id = $value;
                            break;
                    }
                }
                
                // 读取数据
                if (! $data = $this->model->getContent($id)) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                
                // 匹配到内部标签
                if (preg_match_all($pattern2, $matches[2][$i], $matches2)) {
                    $count2 = count($matches2[0]); // 循环内的内容标签数量
                } else {
                    $count2 = 0;
                }
                
                $out_html = $matches[2][$i];
                for ($j = 0; $j < $count2; $j ++) { // 循环替换数据
                    $params = $this->parserParam($matches2[2][$j]);
                    switch ($matches2[1][$j]) {
                        case 'link':
                            if ($data->outlink) {
                                $out_html = str_replace($matches2[0][$j], $data->outlink, $out_html);
                            } elseif ($data->filename) {
                                $out_html = str_replace($matches2[0][$j], url('/home/content/index/id/' . $data->filename), $out_html);
                            } else {
                                $out_html = str_replace($matches2[0][$j], url('/home/content/index/id/' . $data->id), $out_html);
                            }
                            break;
                        case 'sortlink':
                            $out_html = str_replace($matches2[0][$j], url('/home/list/index/scode/' . $data->scode), $out_html);
                            break;
                        case 'subsortlink':
                            $out_html = str_replace($matches2[0][$j], url('/home/list/index/scode/' . $data->subscode), $out_html);
                            break;
                        case 'sortname':
                            if ($data->sortname) {
                                $out_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $data->sortname), $out_html);
                            } else {
                                $out_html = str_replace($matches2[0][$j], '', $out_html);
                            }
                            break;
                        case 'subsortname':
                            if ($data->subsortname) {
                                $out_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $data->subsortname), $out_html);
                            } else {
                                $out_html = str_replace($matches2[0][$j], '', $out_html);
                            }
                            break;
                        case 'ico':
                            $out_html = str_replace($matches2[0][$j], SITE_DIR . $data->ico, $out_html);
                            break;
                        case 'enclosure':
                            $out_html = str_replace($matches2[0][$j], SITE_DIR . $data->enclosure, $out_html);
                            break;
                        case 'likeslink':
                            $out_html = str_replace($matches2[0][$j], url('/home/Do/likes/id/' . $data->id), $out_html);
                            break;
                        case 'opposelink':
                            $out_html = str_replace($matches2[0][$j], url('/home/Do/oppose/id/' . $data->id), $out_html);
                            break;
                        default:
                            if (isset($data->{$matches2[1][$j]})) {
                                $out_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $data->{$matches2[1][$j]}), $out_html);
                            } elseif (strpos($matches2[1][$j], 'ext_') === 0) {
                                $out_html = str_replace($matches[0][$i], '', $out_html);
                            }
                    }
                }
                // 执行替换
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 解析指定内容多图
    public function parserContentPicsLabel($content)
    {
        $pattern = '/\{pboot:pics(\s+[^}]+)?\}([\s\S]*?)\{\/pboot:pics\}/';
        $pattern2 = '/\[pics:([\w]+)(\s+[^]]+)?\]/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                // 获取调节参数
                $params = $this->parserParam($matches[1][$i]);
                $id = - 1;
                
                // 跳过未指定id的列表
                if (! array_key_exists('id', $params)) {
                    continue;
                }
                
                // 分离参数
                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'id':
                            $id = $value;
                            break;
                        case 'num':
                            $num = $value;
                            break;
                    }
                }
                
                // 读取内容多图
                if (! ! $pics = $this->model->getContentPics($id)) {
                    $pics = explode(',', $pics);
                } else {
                    $pics = array();
                }
                
                // 无图直接替换为空并跳过
                if (! $pics) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                
                // 匹配到内部标签
                if (preg_match_all($pattern2, $matches[2][$i], $matches2)) {
                    $count2 = count($matches2[0]); // 循环内的内容标签数量
                } else {
                    $count2 = 0;
                }
                
                $out_html = '';
                $key1 = 0;
                $key2 = 1;
                foreach ($pics as $value) { // 按查询图片条数循环
                    $one_html = $matches[2][$i];
                    for ($j = 0; $j < $count2; $j ++) { // 循环替换数据
                        switch ($matches2[1][$j]) {
                            case 'n':
                                $one_html = str_replace($matches2[0][$j], $key1, $one_html);
                                break;
                            case 'i':
                                $one_html = str_replace($matches2[0][$j], $key2, $one_html);
                                break;
                            case 'src':
                                $one_html = str_replace($matches2[0][$j], SITE_DIR . $value, $one_html);
                                break;
                        }
                    }
                    $key1 ++;
                    $key2 ++;
                    $out_html .= $one_html;
                    if (isset($num) && $key2 > $num) {
                        break;
                    }
                }
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 解析幻灯片标签
    public function parserSlideLabel($content)
    {
        $pattern = '/\{pboot:slide(\s+[^}]+)?\}([\s\S]*?)\{\/pboot:slide\}/';
        $pattern2 = '/\[slide:([\w]+)(\s+[^]]+)?\]/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                // 获取调节参数
                $params = $this->parserParam($matches[1][$i]);
                $gid = 1;
                $num = 3;
                
                // 跳过未指定gid的列表
                if (! array_key_exists('gid', $params)) {
                    continue;
                }
                
                // 分离参数
                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'gid':
                            $gid = $value;
                            break;
                        case 'num':
                            $num = $value;
                            break;
                    }
                }
                
                // 读取数据
                if (! $data = $this->model->getSlides($gid, $num)) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                
                // 匹配到内部标签
                if (preg_match_all($pattern2, $matches[2][$i], $matches2)) {
                    $count2 = count($matches2[0]); // 循环内的内容标签数量
                } else {
                    $count2 = 0;
                }
                
                $out_html = '';
                $key1 = 0;
                $key2 = 1;
                foreach ($data as $value) { // 按查询数据条数循环
                    $one_html = $matches[2][$i];
                    for ($j = 0; $j < $count2; $j ++) { // 循环替换数据
                        switch ($matches2[1][$j]) {
                            case 'n':
                                $one_html = str_replace($matches2[0][$j], $key1, $one_html);
                                break;
                            case 'i':
                                $one_html = str_replace($matches2[0][$j], $key2, $one_html);
                                break;
                            case 'src':
                                $one_html = str_replace($matches2[0][$j], SITE_DIR . $value->pic, $one_html);
                                break;
                            default:
                                if (isset($value->{$matches2[1][$j]})) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->{$matches2[1][$j]}), $one_html);
                                }
                        }
                    }
                    $key1 ++;
                    $key2 ++;
                    $out_html .= $one_html;
                }
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 解析友情链接标签
    public function parserLinkLabel($content)
    {
        $pattern = '/\{pboot:link(\s+[^}]+)?\}([\s\S]*?)\{\/pboot:link\}/';
        $pattern2 = '/\[link:([\w]+)(\s+[^]]+)?\]/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                // 获取调节参数
                $params = $this->parserParam($matches[1][$i]);
                $gid = 1;
                $num = 10;
                
                // 跳过未指定gid的列表
                if (! array_key_exists('gid', $params)) {
                    continue;
                }
                
                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'gid':
                            $gid = $value;
                            break;
                        case 'num':
                            $num = $value;
                            break;
                    }
                }
                
                // 读取数据
                if (! $data = $this->model->getLinks($gid, $num)) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                
                // 匹配到内部标签
                if (preg_match_all($pattern2, $matches[2][$i], $matches2)) {
                    $count2 = count($matches2[0]); // 循环内的内容标签数量
                } else {
                    $count2 = 0;
                }
                
                $out_html = '';
                $key = 1;
                foreach ($data as $value) { // 按查询数据条数循环
                    $one_html = $matches[2][$i];
                    for ($j = 0; $j < $count2; $j ++) { // 循环替换数据
                        switch ($matches2[1][$j]) {
                            case 'i':
                                $one_html = str_replace($matches2[0][$j], $key, $one_html);
                                break;
                            case 'logo':
                                $one_html = str_replace($matches2[0][$j], SITE_DIR . $value->logo, $one_html);
                                break;
                            default:
                                if (isset($value->{$matches2[1][$j]})) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->{$matches2[1][$j]}), $one_html);
                                }
                        }
                    }
                    $key ++;
                    $out_html .= $one_html;
                }
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 解析留言板标签
    public function parserMessageLabel($content)
    {
        $pattern = '/\{pboot:message(\s+[^}]+)?\}([\s\S]*?)\{\/pboot:message\}/';
        $pattern2 = '/\[message:([\w]+)(\s+[^]]+)?\]/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                // 获取调节参数
                $params = $this->parserParam($matches[1][$i]);
                $num = $this->config('pagesize');
                
                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'num':
                            $num = $value;
                            break;
                    }
                }
                
                // 读取数据
                if (! $data = $this->model->getMessage($num)) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                
                // 匹配到内部标签
                if (preg_match_all($pattern2, $matches[2][$i], $matches2)) {
                    $count2 = count($matches2[0]); // 循环内的内容标签数量
                } else {
                    $count2 = 0;
                }
                
                $out_html = '';
                $key = 1;
                foreach ($data as $value) { // 按查询数据条数循环
                    $one_html = $matches[2][$i];
                    for ($j = 0; $j < $count2; $j ++) { // 循环替换数据
                        $params = $this->parserParam($matches2[2][$j]);
                        switch ($matches2[1][$j]) {
                            case 'i':
                                $one_html = str_replace($matches2[0][$j], $key, $one_html);
                                break;
                            case 'ip':
                                $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, long2ip($value->user_ip)), $one_html);
                                break;
                            case 'os':
                                $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->user_os), $one_html);
                                break;
                            case 'bs':
                                $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->user_bs), $one_html);
                                break;
                            case 'askdate':
                                $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->create_time), $one_html);
                                break;
                            case 'replydate':
                                $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->update_time), $one_html);
                                break;
                            default:
                                if (isset($value->{$matches2[1][$j]})) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->{$matches2[1][$j]}), $one_html);
                                }
                        }
                    }
                    $key ++;
                    $out_html .= $one_html;
                }
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 解析内容搜索结果标签
    public function parserSearchLabel($content)
    {
        $pattern = '/\{pboot:search(\s+[^}]+)?\}([\s\S]*?)\{\/pboot:search\}/';
        $pattern2 = '/\[search:([\w]+)(\s+[^]]+)?\]/';
        
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            $field = get('field') ?: 'title';
            $keyword = get('keyword');
            
            for ($i = 0; $i < $count; $i ++) {
                
                // 如果关键字为空，直接替换为空结果
                if (! $keyword) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                
                // 获取调节参数
                $params = $this->parserParam($matches[1][$i]);
                $num = $this->config('pagesize');
                $order = 'date desc';
                
                foreach ($params as $key => $value) {
                    switch ($key) {
                        case 'num':
                            $num = $value;
                            break;
                        case 'order':
                            switch ($value) {
                                case 'date':
                                case 'istop':
                                case 'isrecommend':
                                case 'isheadline':
                                case 'visits':
                                case 'likes':
                                case 'oppose':
                                    $order = $value . ' DESC';
                                    break;
                                default:
                                    $order = $value . ' ASC';
                            }
                            $order .= ",sorting ASC,id DESC";
                            break;
                    }
                }
                
                // 转义字符
                $where = escape_string($_GET);
                
                // 去除特殊键值
                unset($where['keyword']);
                unset($where['field']);
                unset($where['page']);
                
                // 读取数据
                if (! $data = $this->model->getSearch($field, $keyword, $where, $num, $order)) {
                    $content = str_replace($matches[0][$i], '', $content);
                    continue;
                }
                
                // 匹配到内部标签
                if (preg_match_all($pattern2, $matches[2][$i], $matches2)) {
                    $count2 = count($matches2[0]); // 循环内的内容标签数量
                } else {
                    $count2 = 0;
                }
                
                $out_html = '';
                $key = 1;
                foreach ($data as $value) { // 按查询数据条数循环
                    $one_html = $matches[2][$i];
                    for ($j = 0; $j < $count2; $j ++) { // 循环替换数据
                        $params = $this->parserParam($matches2[2][$j]);
                        switch ($matches2[1][$j]) {
                            case 'i':
                                $one_html = str_replace($matches2[0][$j], $key, $one_html);
                                break;
                            case 'link':
                                if ($value->outlink) {
                                    $one_html = str_replace($matches2[0][$j], $value->outlink, $one_html);
                                } elseif ($value->filename) {
                                    $one_html = str_replace($matches2[0][$j], url('/home/content/index/id/' . $value->filename), $one_html);
                                } else {
                                    $one_html = str_replace($matches2[0][$j], url('/home/content/index/id/' . $value->id), $one_html);
                                }
                                break;
                            case 'sortlink':
                                $one_html = str_replace($matches2[0][$j], url('/home/list/index/scode/' . $value->scode), $one_html);
                                break;
                            case 'subsortlink':
                                $one_html = str_replace($matches2[0][$j], url('/home/list/index/scode/' . $value->subscode), $one_html);
                                break;
                            case 'sortname':
                                if ($value->sortname) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->sortname), $one_html);
                                } else {
                                    $one_html = str_replace($matches2[0][$j], '', $one_html);
                                }
                                break;
                            case 'subsortname':
                                if ($value->subsortname) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->subsortname), $one_html);
                                } else {
                                    $one_html = str_replace($matches2[0][$j], '', $one_html);
                                }
                                break;
                            case 'ico':
                                $one_html = str_replace($matches2[0][$j], SITE_DIR . $value->ico, $one_html);
                                break;
                            case 'enclosure':
                                $one_html = str_replace($matches2[0][$j], SITE_DIR . $value->enclosure, $one_html);
                                break;
                            case 'likeslink':
                                $one_html = str_replace($matches2[0][$j], url('/home/Do/likes/id/' . $value->id), $one_html);
                                break;
                            case 'opposelink':
                                $one_html = str_replace($matches2[0][$j], url('/home/Do/oppose/id/' . $value->id), $one_html);
                                break;
                            default:
                                if (isset($value->{$matches2[1][$j]})) {
                                    $one_html = str_replace($matches2[0][$j], $this->adjustLabelData($params, $value->{$matches2[1][$j]}), $one_html);
                                } elseif (strpos($matches2[1][$j], 'ext_') === 0) {
                                    $one_html = str_replace($matches2[0][$j], '', $one_html);
                                }
                        }
                    }
                    $key ++;
                    $out_html .= $one_html;
                }
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 解析列表分页标签
    public function parserPageLabel($content)
    {
        $pattern = '/\{page:([\w]+)\}/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                switch ($matches[1][$i]) {
                    case 'bar':
                        $content = str_replace($matches[0][$i], $this->getVar('pagebar'), $content);
                        break;
                    case 'status':
                        $content = str_replace($matches[0][$i], $this->getVar('pagestatus'), $content);
                        break;
                    case 'current':
                        $content = str_replace($matches[0][$i], $this->getVar('pagecurrent'), $content);
                        break;
                    case 'index':
                        $content = str_replace($matches[0][$i], $this->getVar('pageindex'), $content);
                        break;
                    case 'pre':
                        $content = str_replace($matches[0][$i], $this->getVar('pagepre'), $content);
                        break;
                    case 'next':
                        $content = str_replace($matches[0][$i], $this->getVar('pagenext'), $content);
                        break;
                    case 'last':
                        $content = str_replace($matches[0][$i], $this->getVar('pagelast'), $content);
                        break;
                    case 'numbar':
                        $content = str_replace($matches[0][$i], $this->getVar('pagenumbar'), $content);
                        break;
                    case 'selectbar':
                        $content = str_replace($matches[0][$i], $this->getVar('pageselectbar'), $content);
                        break;
                }
            }
        }
        return $content;
    }

    // 解析IF条件标签
    public function parserIfLabel($content)
    {
        $pattern = '/\{pboot:if\(([^}]+)\)\}([\s\S]*?)\{\/pboot:if\}/';
        $pattern2 = '/pboot:([0-9])+if/';
        if (preg_match_all($pattern, $content, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i ++) {
                $flag = '';
                $out_html = '';
                // 对于无参数函数不执行解析工作
                if (preg_match('/[\w]+\(\)/', $matches[1][$i])) {
                    continue;
                }
                eval('if(' . $matches[1][$i] . '){$flag="if";}else{$flag="else";}');
                if (preg_match('/([\s\S]*)?\{else\}([\s\S]*)?/', $matches[2][$i], $matches2)) { // 判断是否存在else
                    switch ($flag) {
                        case 'if': // 条件为真
                            if (isset($matches2[1])) {
                                $out_html = $matches2[1];
                            }
                            break;
                        case 'else': // 条件为假
                            if (isset($matches2[2])) {
                                $out_html = $matches2[2];
                            }
                            break;
                    }
                } elseif ($flag == 'if') {
                    $out_html = $matches[2][$i];
                }
                
                // 无限极嵌套解析
                if (preg_match($pattern2, $out_html, $matches3)) {
                    $out_html = str_replace('pboot:' . $matches3[1] . 'if', 'pboot:if', $out_html);
                    $out_html = str_replace('{' . $matches3[1] . 'else}', '{else}', $out_html);
                    $out_html = $this->parserIfLabel($out_html);
                }
                
                // 执行替换
                $content = str_replace($matches[0][$i], $out_html, $content);
            }
        }
        return $content;
    }

    // 调整标签数据
    public function adjustLabelData($params, $data)
    {
        if (! $params || ! $data)
            return $data;
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'style': // 时间样式
                    if ($params['style'] && $date = strtotime($data)) {
                        $data = date($params['style'], $date);
                    }
                    break;
                case 'len': // 长度截取
                    if ($params['len'] && is_string($data)) {
                        $data = mb_substr($data, 0, $params['len'], 'utf-8');
                    }
                    break;
                case 'drophtml': // 去除html标签
                    if ($params['drophtml']) {
                        $data = strip_tags($data);
                    }
                    break;
                case 'dropblank': // 清理特殊空白
                    if ($params['dropblank']) {
                        $data = clear_html_blank($data);
                    }
                    break;
                case 'decode': // 解码或转义字符
                    if ($params['decode']) {
                        $data = decode_string($data);
                    } else {
                        $data = escape_string($data);
                    }
                    break;
                case 'substr': // 截取字符串
                    if ($params['substr'] && is_string($data)) {
                        $arr = explode(',', $params['substr']);
                        if (count($arr) == 2 && $arr[1]) {
                            $data = mb_substr($data, $arr[0] - 1, $arr[1], 'utf-8');
                        } else {
                            $data = mb_substr($data, $arr[0] - 1);
                        }
                    }
                    break;
            }
        }
        return $data;
    }
}