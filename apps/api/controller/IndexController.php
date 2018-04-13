<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2017年11月29日
 *  开放接口控制器
 */
namespace app\api\controller;

use core\basic\Controller;

class IndexController extends Controller
{

    private $model;

    public function __construct()
    {
        $this->model = model('admin.Index');
    }

    // 未知接口检测
    public function _empty()
    {
        json(0, '不存在您调用的接口');
    }

    // 用户登陆接口
    public function login()
    {
        if (! $_POST) {
            json(0, '请使用POST方式调用接口！');
        }
        
        // 就收数据
        $username = post('username', 'var', true);
        $password = post('password');
        if (! $username) {
            json(0, '用户名不能为空！');
        }
        if (! $password) {
            json(0, '密码不能为空！');
        }
        
        // 执行用户登录
        $where = array(
            'username' => $username,
            'password' => encrypt_string($password)
        );
        
        if (! ! $login = $this->model->login($where)) {
            $data = array(
                'id' => $login->id,
                'ucode' => $login->ucode,
                'username' => $login->username,
                'realname' => $login->realname,
                'sid' => encrypt_string($_SERVER['HTTP_USER_AGENT'] . $username . $password)
            );
            json(1, $data);
        } else {
            json(0, '用户名或密码错误！');
        }
    }
}