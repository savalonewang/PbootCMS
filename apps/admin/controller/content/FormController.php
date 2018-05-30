<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年5月28日 
 *  自定义表单控制器
 */
namespace app\admin\controller\content;

use core\basic\Controller;
use app\admin\model\content\FormModel;

class FormController extends Controller
{

    private $model;

    public function __construct()
    {
        $this->model = new FormModel();
    }

    // 自定义表单列表
    public function index()
    {
        if ((! ! $fcode = get('fcode', 'var')) && $form = $this->model->getFormByCode($fcode)) {
            $this->assign('form', $form);
            if (get('action') == 'showdata') {
                $this->assign('showdata', true);
                $this->assign('fields', $this->model->getFormFieldByCode($fcode)); // 获取字段
                $table = $this->model->getFormTableByCode($fcode);
                $this->assign('formdata', $this->model->getFormData($table));
            }
            if (get('action') == 'showfield') {
                $this->assign('showfield', true);
                $this->assign('fields', $this->model->getFormFieldByCode($fcode));
            }
        } else {
            $this->assign('list', true);
            if (! ! ($field = get('field')) && ! ! ($keyword = get('keyword'))) {
                $result = $this->model->findForm($field, $keyword);
            } else {
                $result = $this->model->getList();
            }
            
            $this->assign('forms', $result);
        }
        $this->display('content/form.html');
    }

    // 自定义表单增加
    public function add()
    {
        if ($_POST) {
            if (get('action') == 'addform') {
                $fcode = get_auto_code($this->model->getLastCode());
                $form_name = post('form_name');
                $table_name = 'ay_' . post('table_name', 'var');
                
                if (! $form_name) {
                    alert_back('表单名称不能为空！');
                }
                
                if (! $table_name) {
                    alert_back('表单数据表不能为空！');
                }
                
                $data = array(
                    'fcode' => $fcode,
                    'form_name' => $form_name,
                    'table_name' => $table_name
                );
                
                if ($this->model->addForm($data)) {
                    if ($this->config('database.type') == 'sqlite' || $this->config('database.type') == 'pdo_sqlite') {
                        $this->model->amd("CREATE TABLE `$table_name` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL)");
                    } else {
                        $this->model->amd("CREATE TABLE `$table_name` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,PRIMARY KEY (`id`))ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");
                    }
                    $this->log('新增自定义表单成功！');
                    if (! ! $backurl = get('backurl')) {
                        success('新增成功！', $backurl);
                    } else {
                        success('新增成功！', url('/admin/Form/index'));
                    }
                } else {
                    $this->log('新增自定义表单失败！');
                    error('新增失败！', - 1);
                }
            } else {
                // 获取数据
                $fcode = post('fcode', 'var');
                $name = post('name', 'var');
                $type = post('type', 'int');
                if (! ! $value = post('value')) {
                    $value = str_replace("\r\n", ",", $value); // 替换回车
                    $value = str_replace("，", ",", $value); // 替换中文逗号分割符
                    $value = str_replace(" ", "", $value); // 替换空格
                }
                
                $description = post('description');
                $sorting = post('sorting') ?: 255;
                
                if (! $fcode) {
                    alert_back('表单编码不能为空！');
                }
                
                if (! $name) {
                    alert_back('字段名称不能为空！');
                }
                
                if (! $type) {
                    alert_back('字段类型不能为空！');
                }
                
                if (! $description) {
                    alert_back('字段描述不能为空！');
                }
                
                // 构建数据
                $data = array(
                    'fcode' => $fcode,
                    'name' => $name,
                    'type' => $type,
                    'value' => $value,
                    'description' => $description,
                    'sorting' => $sorting
                );
                
                // 获取表名称
                $table = $this->model->getFormTableByCode($fcode);
                
                // 字段类型及长度
                switch ($type) {
                    case '2':
                        $mysql = 'varchar(500)';
                        $sqlite = 'TEXT(500)';
                        break;
                    case '7':
                        $mysql = 'datetime';
                        $sqlite = 'TEXT';
                        break;
                    case '8':
                        $mysql = 'varchar(2000)';
                        $sqlite = 'TEXT(2000)';
                        break;
                    default:
                        $mysql = 'varchar(100)';
                        $sqlite = 'TEXT(100)';
                }
                
                // 字段不存在时创建
                if (! $this->model->isExistField($table, $name)) {
                    if ($this->config('database.type') == 'sqlite' || $this->config('database.type') == 'pdo_sqlite') {
                        $result = $this->model->amd("ALTER TABLE $table ADD COLUMN $name $sqlite NULL");
                    } else {
                        $result = $this->model->amd("ALTER TABLE $table ADD $name $mysql NULL COMMENT '$description'");
                    }
                } elseif ($this->model->checkFormField($fcode, $name)) {
                    alert_back('字段已经存在，不能重复添加！');
                }
                
                // 执行自定义表单记录添加
                if ($this->model->addFormField($data)) {
                    $this->log('新增表单字段成功！');
                    if (! ! $backurl = get('backurl')) {
                        success('新增成功！', $backurl);
                    } else {
                        success('新增成功！', url('/admin/Form/index/fcode/' . $fcode . '/action/showfield'));
                    }
                } else {
                    $this->log('新增表单字段失败！');
                    error('新增失败！', - 1);
                }
            }
        } else {
            $this->assign('add', true);
            $this->display('content/form.html');
        }
    }

    // 自定义表单删除
    public function del()
    {
        if (! $id = get('id', 'int')) {
            error('传递的参数值错误！', - 1);
        }
        
        // 删除表单
        if (get('action') == 'delform') {
            $table = $this->model->getFormTable($id);
            $fcode = $this->model->getFormCode($id);
            if ($this->model->delForm($id)) {
                $this->model->delFormFieldByCode($fcode); // 删除字段记录
                $this->model->amd("DROP TABLE IF EXISTS $table"); // 删除表
                $this->log('删除自定义表单' . $id . '成功！');
                success('删除成功！', - 1);
            } else {
                $this->log('删除自定义表单' . $id . '失败！');
                error('删除失败！', - 1);
            }
        } else {
            
            // 获取表单
            if (! $fcode = get('fcode', 'var')) {
                error('传递的参数值fcode错误！', - 1);
            }
            
            // 获取操作表
            $table = $this->model->getFormTableByCode($fcode);
            $name = $this->model->getFormFieldName($id);
            
            if ($this->model->delFormField($id)) {
                // mysql数据库执行字段删除，sqlite暂时不支持
                if (! ! $name) {
                    if ($this->config('database.type') == 'mysqli' || $this->config('database.type') == 'pdo_mysql') {
                        $result = $this->model->amd("ALTER TABLE $table DROP COLUMN $name");
                    }
                }
                $this->log('删除自定义表单' . $id . '成功！');
                success('删除成功！', - 1);
            } else {
                $this->log('删除自定义表单' . $id . '失败！');
                error('删除失败！', - 1);
            }
        }
    }

    // 自定义表单修改
    public function mod()
    {
        if (! $id = get('id', 'int')) {
            error('传递的参数值错误！', - 1);
        }
        
        // 单独修改状态
        if (($field = get('field', 'var')) && ! is_null($value = get('value', 'var'))) {
            if ($this->model->modForm($id, "$field='$value',update_user='" . session('username') . "'")) {
                location(- 1);
            } else {
                alert_back('修改失败！');
            }
        }
        
        // 修改操作
        if ($_POST) {
            
            // 修改表单
            if (get('action') == 'modform') {
                $form_name = post('form_name');
                
                if (! $form_name) {
                    alert_back('表单名称不能为空！');
                }
                $data = array(
                    'form_name' => $form_name
                );
                
                // 执行修改
                if ($this->model->modForm($id, $data)) {
                    $this->log('修改自定义表单' . $id . '成功！');
                    if (! ! $backurl = get('backurl')) {
                        success('修改成功！', $backurl);
                    } else {
                        success('修改成功！', url('/admin/Form/index'));
                    }
                } else {
                    location(- 1);
                }
            } else {
                
                // 获取数据
                $type = post('type');
                if (! ! $value = post('value')) {
                    $value = str_replace("\r\n", ",", $value); // 替换回车
                    $value = str_replace("，", ",", $value); // 替换中文逗号分割符
                    $value = str_replace(" ", "", $value); // 替换空格
                }
                
                $description = post('description');
                $sorting = post('sorting') ?: 255;
                
                if (! $description) {
                    alert_back('字段描述不能为空！');
                }
                
                // 构建数据
                $data = array(
                    'type' => $type,
                    'value' => $value,
                    'description' => $description,
                    'sorting' => $sorting
                );
                
                // 执行修改
                if ($this->model->modFormField($id, $data)) {
                    $this->log('修改表单字段' . $id . '成功！');
                    if (! ! $backurl = get('backurl')) {
                        success('修改成功！', $backurl);
                    } else {
                        success('修改成功！', url('/admin/Form/index'));
                    }
                } else {
                    location(- 1);
                }
            }
        } else {
            
            // 调取修改内容
            $this->assign('mod', true);
            
            if (get('action') == 'modform') {
                if (! $result = $this->model->getForm($id)) {
                    error('编辑的内容已经不存在！', - 1);
                }
                
                $this->assign('form', $result);
            } else {
                if (! $result = $this->model->getFormField($id)) {
                    error('编辑的内容已经不存在！', - 1);
                }
                $this->assign('field', $result);
            }
            $this->display('content/form.html');
        }
    }
}