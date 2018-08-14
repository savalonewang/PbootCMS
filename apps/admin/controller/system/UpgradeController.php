<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年8月14日
 *  
 */
namespace app\admin\controller\system;

use core\basic\Controller;
use core\basic\Model;

class UpgradeController extends Controller
{

    // 服务器地址
    public $server = 'http://update.pbootcms.com';

    // 发布目录
    public $public = '/release';

    // 文件列表
    public $files = array();

    public function __construct()
    {
        // 检查下载目录、脚本目录、备份目录
        check_dir(RUN_PATH . '/upgrade', true);
        check_dir(DOC_PATH . STATIC_DIR . '/backup/upgrade', true);
    }

    public function index()
    {
        switch (get('action')) {
            case 'check':
                $upfile = $this->check();
                break;
            case 'local':
                $upfile = $this->local();
                break;
        }
        $this->assign('upfile', $upfile);
        $this->display('system/upgrade.html');
    }

    // 检查更新
    private function check()
    {
        set_time_limit(0);
        path_delete(RUN_PATH . '/upgrade');
        $files = $this->getServerList();
        if (! is_array($files)) {
            alert_back($files);
        } else {
            foreach ($files as $key => $value) {
                $file = ROOT_PATH . $value->path;
                if (md5_file($file) != $value->md5) {
                    if (file_exists($file)) {
                        $files[$key]->type = '<span style="color:Red">覆盖</span>';
                    } else {
                        $files[$key]->type = '新增';
                    }
                    $upfile[] = $files[$key];
                }
            }
        }
        return $upfile;
    }

    // 缓存文件
    private function local()
    {
        $files = $this->getLoaclList(RUN_PATH . '/upgrade');
        $files = json_decode(json_encode($files));
        foreach ($files as $key => $value) {
            $file = ROOT_PATH . $value->path;
            if (file_exists($file)) {
                $files[$key]->type = '<span style="color:Red">覆盖</span>';
            } else {
                $files[$key]->type = '新增';
            }
            $upfile[] = $files[$key];
        }
        return $upfile;
    }

    // 执行下载
    public function down()
    {
        if ($_POST) {
            if (! ! $list = post('list')) {
                foreach ($list as $value) {
                    $path = RUN_PATH . '/upgrade' . $value;
                    $types = '.gif|.jpeg|.png|.bmp|.jpg'; // 定义执行下载的类型
                    $ext = end(explode(".", basename($path))); // 扩展
                    if (strpos($types, $ext)) {
                        if (! $this->getServerDown($value, $path)) {
                            error("更新文件 $value 获取失败!");
                        }
                    } else {
                        $result = $this->getServerFile($value);
                    }
                    if ($result) {
                        check_dir(dirname($path), true);
                        if (! file_put_contents($path, $result)) {
                            error("更新文件 $value 写入本地失败!");
                        }
                    }
                }
                alert_location('所有更新文件下载成功！', url('/admin/Upgrade/index/action/local'));
            } else {
                alert_back('请选择要更新的文件！');
            }
        }
    }

    // 执行升级
    public function update()
    {
        if ($_POST) {
            if (! ! $list = post('list')) {
                $backdir = date('YmdHis');
                foreach ($list as $value) {
                    
                    $path = RUN_PATH . '/upgrade' . $value;
                    
                    // 升级文件
                    if (stripos($value, '/pack/') !== false) {
                        $des_path = ROOT_PATH . str_replace('/pack/', '/', $value);
                        $back_path = DOC_PATH . STATIC_DIR . '/backup/upgrade/' . $backdir . $des_path;
                        check_dir(dirname($des_path), true);
                        check_dir(dirname($back_path), true);
                        if (file_exists($des_path)) { // 执行备份
                            copy($des_path, $back_path);
                        }
                        if (! copy($path, $des_path)) {
                            $this->log("更新文件 " . basename($des_path) . " 升级失败!");
                            error("更新文件 $des_path 升级失败!");
                        }
                    }
                    
                    // 升级数据库
                    if (stripos($value, '/sql/') !== false) {
                        $des_path = ROOT_PATH . str_replace('/sql/', '/', $value);
                        switch ($this->config('database.type')) {
                            case 'mysqli':
                            case 'pdo_mysql':
                                if (preg_match('/^mysql[\w-]+\.sql$/i', basename($value))) {
                                    $sql = file_get_contents($path);
                                    if ($this->upsql($sql)) {
                                        copy($path, $des_path);
                                    }
                                }
                                break;
                            case 'sqlite':
                            case 'pdo_sqlite':
                                if (preg_match('/^sqlite[\w-]+\.sql$/i', basename($value))) {
                                    $sql = file_get_contents($path);
                                    if ($this->upsql($sql)) {
                                        copy($path, $des_path);
                                    }
                                }
                                break;
                        }
                    }
                }
                $this->log("系统更新成功!");
                path_delete(RUN_PATH . '/upgrade');
                alert_location('更新成功！', url('/admin/Upgrade/index'));
            } else {
                alert_back('请选择要更新的文件！');
            }
        }
    }

    // 升级数据库
    private function upsql($sql)
    {
        $sql = explode(';', $sql);
        $model = new Model();
        foreach ($sql as $value) {
            $value = trim($value);
            $model->amd($value);
        }
        return true;
    }

    // 获取列表
    private function getServerList()
    {
        $url = $this->server . '/index.php/upgrate/getlist/version/' . APP_VERSION . '.' . RELEASE_TIME;
        if (! ! $rs = get_url($url)) {
            $rs = json_decode($rs);
            if ($rs->code) {
                return $rs->data;
            } else {
                alert_back($rs->data);
            }
        } else {
            error('获取更新列表时发生服务器错误，请稍后再试！');
        }
    }

    // 获取文件
    private function getServerFile($path)
    {
        $url = $this->server . '/index.php/upgrate/getFile';
        $data['path'] = $path;
        if (! ! $rs = get_url($url, $data)) {
            $rs = json_decode($rs);
            if ($rs->code) {
                return $rs->data;
            } else {
                alert_back($rs->data);
            }
        } else {
            error('获取更新文件时发生服务器错误，请稍后再试！');
        }
    }

    // 获取非文本文件
    private function getServerDown($source, $des)
    {
        $url = $this->server . $this->public . $source;
        if (! ! $sfile = fopen($url, "rb")) {
            if (! ! $cfile = fopen($des, "wb")) {
                while (! feof($sfile)) {
                    $fwrite = fwrite($cfile, fread($sfile, 1024 * 8), 1024 * 8);
                    if ($fwrite === false) {
                        return false;
                    }
                }
                return true;
            }
        }
        if ($sfile) {
            fclose($sfile);
        }
        if ($cfile) {
            fclose($cfile);
        }
        return false;
    }

    // 获取文件列表
    private function getLoaclList($path)
    {
        $files = scandir($path);
        foreach ($files as $value) {
            if ($value != '.' && $value != '..') {
                if (is_dir($path . '/' . $value)) {
                    $this->getLoaclList($path . '/' . $value);
                } else {
                    $file = $path . '/' . $value;
                    if (! mb_check_encoding($file, 'utf-8')) {
                        $out_path = mb_convert_encoding($file, 'UTF-8', 'GBK');
                    } else {
                        $out_path = $file;
                    }
                    
                    if (stripos($out_path, '/pack/') !== false) {
                        $out_path = str_replace(RUN_PATH . '/upgrade/pack/', '/', $out_path);
                        $file_dir = 'pack';
                    }
                    
                    if (stripos($out_path, '/script/') !== false) {
                        $out_path = str_replace(RUN_PATH . '/upgrade/script/', '/', $out_path);
                        $file_dir = 'script';
                    }
                    
                    if (stripos($out_path, '/sql/') !== false) {
                        $out_path = str_replace(RUN_PATH . '/upgrade/sql/', '/', $out_path);
                        $file_dir = 'sql';
                    }
                    
                    $this->files[] = array(
                        'path' => $out_path,
                        'dir' => $file_dir,
                        'md5' => md5_file($file),
                        'ctime' => filectime($file)
                    );
                }
            }
        }
        return $this->files;
    }
}