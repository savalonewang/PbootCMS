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
                $config = array(
                    'sn',
                    'url_type',
                    'tpl_html_cache',
                    'tpl_html_cache_time'
                );
                if (in_array($key, $config)) {
                    if ($key == 'tpl_html_cache_time' && ! $value) {
                        $value = 900;
                    } else {
                        $value = post($key);
                    }
                    $this->modConfig($key, $value);
                } else {
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
            }
            $this->log('修改参数配置成功！');
            switch (post('submit')) {
                case 'api':
                    success('修改成功！', url('/admin/Config/index#tab=t2', false));
                default:
                    success('修改成功！', url('/admin/Config/index', false));
            }
        }
        $this->assign('basic', true);
        $configs = $this->model->getList();
        $configs['sn']['value'] = $this->config('sn');
        $configs['url_type']['value'] = $this->config('url_type');
        $configs['tpl_html_cache']['value'] = $this->config('tpl_html_cache');
        $configs['tpl_html_cache_time']['value'] = $this->config('tpl_html_cache_time');
        $this->assign('configs', $configs);
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
            success('修改成功！', url('/admin/Config/email'));
        }
        $this->assign('email', true);
        $this->assign('configs', $this->model->getList());
        $this->display('system/config.html');
    }

    // 修改配置文件
    private function modConfig($key, $value)
    {
        $config = file_get_contents(CONF_PATH . '/config.php');
        if (is_numeric($value)) {
            $config = preg_replace('/(\'' . $key . '\'([\s]+)?=>([\s]+)?)[\w\'\",]+,/', '${1}' . $value . ',', $config);
        } else {
            $config = preg_replace('/(\'' . $key . '\'([\s]+)?=>([\s]+)?)[\w\'\",]+,/', '${1}\'' . $value . '\',', $config);
        }
        return file_put_contents(CONF_PATH . '/config.php', $config);
    }
}