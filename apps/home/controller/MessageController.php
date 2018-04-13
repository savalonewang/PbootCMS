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

    protected $parser;

    protected $model;

    public function __construct()
    {
        $this->parser = new ParserController();
        $this->model = new ParserModel();
    }

    // 留言新增
    public function add()
    {
        if ($_POST) {
            
            $contacts = post('contacts');
            $mobile = post('mobile');
            $content = post('content');
            $checkcode = post('checkcode');
            
            if (! $contacts) {
                alert_back('联系人不能为空!');
            }
            
            if (! $mobile) {
                alert_back('号码不能为空！');
            }
            
            if (! $content) {
                alert_back('内容不能为空！');
            }
            
            if (session('config.message_check_code')) {
                if (! $checkcode) {
                    alert_back('验证码不能为空！');
                }
                
                if ($checkcode != session('checkcode')) {
                    alert_back('验证码错误！');
                }
            }
            
            $data = array(
                'acode' => session('acode'),
                'contacts' => $contacts,
                'mobile' => $mobile,
                'content' => $content,
                'user_ip' => ip2long(get_user_ip()),
                'user_os' => get_user_os(),
                'user_bs' => get_user_bs(),
                'recontent' => '',
                'status' => 0,
                'create_user' => session('username'),
                'update_user' => session('username')
            );
            
            if ($this->model->addMessage($data)) {
                $this->log('提交留言成功！');
                if (session('config.message_send_mail') && session('config.message_send_to')) {
                    $mail_subject = "【PbootCMS】您有新的留言，请注意查收！";
                    $mail_body = "联系人：$contacts<br>手　机：$mobile<br>内　容：$content";
                    sendmail(session('config'), session('config.message_send_to'), $mail_subject, $mail_body);
                }
                alert_location('留言成功！', '-1');
            } else {
                $this->log('提交留言失败！');
                alert_back('留言失败！');
            }
        } else {
            error('留言失败，请使用POST方式提交留言！');
        }
    }

    // 空拦截
    public function _empty()
    {
        error('您访问的地址有误，请核对后重试！');
    }
}

