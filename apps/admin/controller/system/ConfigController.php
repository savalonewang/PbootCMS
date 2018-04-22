<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年01月03日
 *  应用配置控制器
 */
namespace app\admin\controller\system;

use core\basic\Controller;
use app\admin\model\system\ConfigModel;

class ConfigController extends Controller
{

    private $model;

    public function __construct()
    {
        $this->model = new ConfigModel();
    }

    // 应用配置列表
    public function index()
    {
        // 修改参数配置
        if ($_POST) {
            foreach ($_POST as $key => $value) {
                if ($this->model->checkConfig("name='$key'")) {
                    $this->model->modValue($key, post($key));
                } else {
                    // 自动新增配置项
                    $data = array(
                        'name' => $key,
                        'value' => post($key),
                        'type' => 2,
                        'sorting' => 255,
                        'description' => ''
                    );
                    $this->model->addConfig($data);
                }
            }
            $this->log('修改参数配置成功！');
            success('修改成功！', url('admin/Config/index'));
        }
        $this->assign('basic', true);
        $this->assign('configs', $this->model->getList());
        $this->display('system/config.html');
    }

    // 邮件发送配置
    public function email()
    {
        // 修改参数配置
        if ($_POST) {
            foreach ($_POST as $key => $value) {
                $this->model->modValue($key, post($key));
            }
            $this->log('修改邮件发送配置成功！');
            success('修改成功！', url('admin/Config/email'));
        }
        $this->assign('email', true);
        $this->assign('configs', $this->model->getList());
        $this->display('system/config.html');
    }
}