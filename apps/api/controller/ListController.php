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
        $acode = get('acode', 'var') ?: $this->config('lgs.0.acode');
        $scode = get('scode', 'var') ?: - 1;
        $num = get('num', 'int') ?: $this->config('pagesize');
        
        $order = get('order');
        if (! preg_match('/^[\w-,\s]+$/', $order)) {
            $order = 'istop DESC,isrecommend DESC,isheadline DESC,sorting ASC,date DESC,id DESC';
        } else {
            switch ($order) {
                case 'date':
                case 'istop':
                case 'sorting':
                    $order = 'istop DESC,isrecommend DESC,isheadline DESC,sorting ASC,date DESC,id DESC';
                    break;
                case 'isrecommend':
                    $order = 'isrecommend DESC,istop DESC,isheadline DESC,sorting ASC,date DESC,id DESC';
                    break;
                case 'isheadline':
                    $order = 'isheadline DESC,istop DESC,isrecommend DESC,sorting ASC,date DESC,id DESC';
                    break;
                case 'visits':
                case 'likes':
                case 'oppose':
                    $order = 'istop DESC,isrecommend DESC,isheadline DESC,' . $order . ' DESC,sorting ASC,date DESC,id DESC';
                    break;
                default:
                    $order = $order . ',sorting ASC,date DESC,id DESC';
            }
        }
        
        // 读取数据
        $data = $this->model->getList($acode, $scode, $num, $order);
        
        foreach ($data as $key => $value) {
            if ($value->outlink) {
                $data[$key]->link = $data->outlink;
            } else {
                $data[$key]->link = url('/api/list/index/scode/' . $data[$key]->id, false);
            }
            $data[$key]->likeslink = url('/home/Do/likes/id/' . $data[$key]->id, false);
            $data[$key]->opposelink = url('/home/Do/oppose/id/' . $data[$key]->id, false);
            $data[$key]->content = str_replace(STATIC_DIR . '/upload/', get_http_url() . STATIC_DIR . '/upload/', $data[$key]->content);
        }
        
        // 输出数据
        if (get('page') <= PAGECOUNT) {
            json(1, $data);
        } else {
            return json(0, '已经到底了！');
        }
    }
}