<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年5月28日
 *  自定义表单模型类
 */
namespace app\admin\model\content;

use core\basic\Model;

class FormModel extends Model
{

    // 获取自定义表单列表
    public function getList()
    {
        return parent::table('ay_form')->page()->select();
    }

    // 查找自定义表单
    public function findForm($field, $keyword)
    {
        return parent::table('ay_form')->like($field, $keyword)
            ->page()
            ->select();
    }

    // 获取最后一个code
    public function getLastCode()
    {
        return parent::table('ay_form')->order('id DESC')->value('fcode');
    }

    // 获取自定义表单详情
    public function getForm($id)
    {
        return parent::table('ay_form')->where("id=$id")->find();
    }

    // 获取自定义表单表
    public function getFormTable($id)
    {
        return parent::table('ay_form')->where("id=$id")->value('table_name');
    }

    // 添加自定义表单
    public function addForm(array $data)
    {
        return parent::table('ay_form')->insert($data);
    }

    // 删除自定义表单
    public function delForm($id)
    {
        return parent::table('ay_form')->where("id=$id")->delete();
    }

    // 修改自定义表单
    public function modForm($id, $data)
    {
        return parent::table('ay_form')->where("id=$id")->update($data);
    }

    // 获取表单字段
    public function getFormField($fcode)
    {
        return parent::table('ay_form_field')->where("fcode='$fcode'")->select();
    }

    // 获取自定义表单名称
    public function getFormName($id)
    {
        return parent::table('ay_form')->where("id=$id")->value('name');
    }

    // 判断字段是否存在
    public function isExistField($field)
    {
        $fields = parent::tableFields('ay_content_ext');
        if (in_array($field, $fields)) {
            return true;
        } else {
            return false;
        }
    }
}