<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年3月5日
 *  留言控制器
 */
namespace app\home\controller;

use app\home\model\ParserModel;
use core\basic\Controller;

class MessageController extends Controller
{

    protected $model;

    public function __construct()
    {
        $this->model = new ParserModel();
    }

    // 留言新增
    public function add()
    {
        if ($_POST) {
            
            // 验证码验证
            $checkcode = post('checkcode');
            if ($this->config('message_check_code')) {
                if (! $checkcode) {
                    alert_back('验证码不能为空！');
                }
                
                if ($checkcode != session('checkcode')) {
                    alert_back('验证码错误！');
                }
            }
            
            // 读取字段
            if (! $form = $this->model->getFormField(1)) {
                alert_back('留言表单不存在任何字段，请核对后重试！');
            }
            
            // 接收数据
            $mail_body = '';
            foreach ($form as $value) {
                $field_data = post($value->name);
                if ($value->required && ! $field_data) {
                    alert_back($value->description . '不能为空！');
                } else {
                    $data[$value->name] = post($value->name);
                    $mail_body .= $value->name . '：' . post($value->name) . '<br>';
                }
            }
            
            // 设置创建时间
            if ($data) {
                $data['acode'] = session('lg');
                $data['user_ip'] = ip2long(get_user_ip());
                $data['user_os'] = get_user_os();
                $data['user_bs'] = get_user_bs();
                $data['acode'] = session('lg');
                $data['recontent'] = '';
                $data['status'] = 0;
                $data['create_user'] = 'guest';
                $data['update_user'] = 'guest';
            }
            
            if ($this->model->addMessage($data)) {
                $this->log('留言提交成功！');
                if ($this->config('message_send_mail') && $this->config('message_send_to')) {
                    $mail_subject = "【PbootCMS】您有新的表单数据，请注意查收！";
                    sendmail($this->config(), $this->config('message_send_to'), $mail_subject, $mail_body);
                }
                alert_location('提交成功！', '-1');
            } else {
                $this->log('留言提交失败！');
                alert_back('提交失败！');
            }
        } else {
            error('提交失败，请使用POST方式提交！');
        }
    }

    // 空拦截
    public function _empty()
    {
        error('您访问的地址有误，请核对后重试！');
    }
}

