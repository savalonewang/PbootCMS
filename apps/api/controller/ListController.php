<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年4月20日
 *  内容列表接口控制器
 */
namespace app\api\controller;

use core\basic\Controller;
use app\api\model\CmsModel;

class ListController extends Controller
{

    protected $model;

    public function __construct()
    {
        $this->model = new CmsModel();
    }

    public function index()
    {
        // 获取参数
        $acode = get('acode') ?: 'cn';
        $scode = get('scode') ?: - 1;
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
        
        // 读取数据
        $data = $this->model->getList($acode, $scode, $num, $order);
        
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
        if (get('page') <= PAGECOUNT) {
            json(1, $data);
        } else {
            return json(0, '已经到底了！');
        }
    }
}