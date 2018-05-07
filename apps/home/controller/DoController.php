<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年3月8日
 *  
 */
namespace app\home\controller;

use core\basic\Controller;
use app\home\model\DoModel;

class DoController extends Controller
{

    private $model;

    public function __construct()
    {
        $this->model = new DoModel();
    }

    // 多语言切换
    public function area()
    {
        $lg = post('lg') ?: get('lg');
        if ($lg) {
            $lgs = session('lgs');
            foreach ($lgs as $value) {
                if ($value->acode == $lg) {
                    session('lg', $lg);
                }
            }
            location(url('home/index/index'));
        }
    }

    // 点赞
    public function likes()
    {
        if (($id = get('id', 'int')) && ! cookie('likes_' . $id)) {
            $this->model->addLikes($id);
            cookie('likes_' . $id, true, 31536000);
        }
        location('-1');
    }

    // 反对
    public function oppose()
    {
        if (($id = get('id', 'int')) && ! cookie('oppose_' . $id)) {
            $this->model->addOppose($id);
            cookie('oppose_' . $id, true, 31536000);
        }
        location('-1');
    }
}



