<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年4月20日
 *  搜索接口控制器
 */
namespace app\api\controller;

use core\basic\Controller;
use app\api\model\CmsModel;

class SearchController extends Controller
{

    protected $model;

    public function __construct()
    {
        $this->model = new CmsModel();
    }

    public function index()
    {
        if (! $_POST) {
            json(0, '请使用POST提交！');
        }
        
        $acode = get('acode') ?: 'cn';
        $num = get('num') ?: $this->config('pagesize');
        $order = get('order') ?: 'date';
        switch ($order) {
            case 'date':
            case 'istop':
            case 'isrecommend':
            case 'isheadline':
            case 'visits':
            case 'likes':
            case 'oppose':
                $order = $order . ' DESC';
                break;
            default:
                $order = $order . ' ASC';
        }
        $order .= ",sorting ASC,id DESC";
        
        // 获取参数
        $field = post('field') ?: 'title';
        $keyword = post('keyword');
        
        // 如果关键字为空，直接替换为空结果
        if (! $keyword) {
            json(0, '必须传递关键字keyword参数');
        }
        
        // 转义字符
        $where = escape_string($_POST);
        
        $cond = array(
            'd_source' => 'get',
            'd_regular' => '/^[^\s]+$/'
        );
        
        foreach ($_POST as $key => $value) {
            $where[$key] = filter($key, $cond);
            if ($_POST[$key] && ! $where[$key]) {
                json(0, '您的查询含有非法字符,已被系统拦截');
            }
        }
        
        // 去除特殊键值
        unset($where['keyword']);
        unset($where['field']);
        unset($where['page']);
        
        // 读取数据
        $data = $this->model->getSearch($acode, $field, $keyword, $where, $num, $order);
        
        if ($data) {
            if ($data->outlink) {
                $data->link = $data->outlink;
            } else {
                $data->link = url('/api/list/index/scode/' . $data->id, false);
            }
            $data->likeslink = url('/home/Do/likes/id/' . $data->id, false);
            $data->opposelink = url('/home/Do/oppose/id/' . $data->id, false);
        }
        
        // 输出数据
        json(1, $data);
    }
}