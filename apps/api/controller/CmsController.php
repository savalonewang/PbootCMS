<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年4月20日
 *  CMS通用接口控制器
 */
namespace app\api\controller;

use core\basic\Controller;
use app\api\model\CmsModel;

class CmsController extends Controller
{

    protected $model;

    public function __construct()
    {
        $this->model = new CmsModel();
    }

    // 站点基础信息
    public function site()
    {
        // 获取参数
        $acode = get('acode') ?: 'cn';
        
        // 读取数据
        if (! $name = get('name')) {
            $data = $this->model->getSiteAll($acode);
        } else {
            $data = $this->model->getSite($acode, $name);
        }
        
        // 输出数据
        json(1, $data);
    }

    // 公司信息
    public function company()
    {
        // 获取参数
        $acode = get('acode') ?: 'cn';
        
        // 读取数据
        if (! $name = get('name')) {
            $data = $this->model->getCompanyAll($acode);
        } else {
            $data = $this->model->getCompany($acode, $name);
        }
        
        // 输出数据
        json(1, $data);
    }

    // 自定义标签信息
    public function label()
    {
        // 获取全部或指定自定义标签
        if (! $name = get('name')) {
            $data = $this->model->getLabelAll();
        } else {
            $data = $this->model->getLabel($name);
        }
        
        // 输出数据
        json(1, $data);
    }

    // 获取菜单栏目树
    public function nav()
    {
        // 获取参数
        $acode = get('acode') ?: 'cn';
        
        // 获取栏目树
        if (! $scode = get('scode')) {
            $data = $this->model->getSorts($acode);
        } else { // 获取子类
            $data = $this->model->getSortsSon($acode, $scode);
        }
        // 输出数据
        json(1, $data);
    }

    // 当前栏目位置
    public function position()
    {
        // 获取参数
        $acode = get('acode') ?: 'cn';
        
        if (! ! $scode = get('scode')) {
            $data = $this->model->getPosition($acode, $scode);
            json(1, $data);
        } else {
            json(0, '必须传递当前分类scode参数');
        }
    }

    // 分类信息
    public function sort()
    {
        // 获取参数
        $acode = get('acode') ?: 'cn';
        
        if (! ! $scode = get('scode')) {
            $data = $this->model->getSort($acode, $scode);
            json(1, $data);
        } else {
            json(0, '必须传递分类scode参数');
        }
    }

    // 内容多图
    public function pics()
    {
        if (! ! $id = get('id')) {
            $acode = get('acode') ?: 'cn';
            if (! ! $pics = $this->model->getContentPics($acode, $id)) {
                $pics = explode(',', $pics);
            } else {
                $pics = array();
            }
            json(1, $pics);
        } else {
            json(0, '必须传递内容id参数');
        }
    }

    // 幻灯片
    public function slide()
    {
        if (! ! $gid = get('gid')) {
            $acode = get('acode') ?: 'cn';
            $num = get('num') ?: 5;
            $data = $this->model->getSlides($acode, $gid, $num);
            json(1, $data);
        } else {
            json(0, '必须传递幻灯片分组gid参数');
        }
    }

    // 友情链接
    public function link()
    {
        if (! ! $gid = get('gid')) {
            $acode = get('acode') ?: 'cn';
            $num = get('num') ?: 10;
            $data = $this->model->getLinks($acode, $gid, $num);
            json(1, $data);
        } else {
            json(0, '必须传递友情链接分组gid参数');
        }
    }

    // 搜索
    public function search()
    {
        if (! $_POST) {
            json(0, '请使用POST提交！');
        }
        
        $acode = get('acode') ?: 'cn';
        $num = get('num') ?: $this->config('pagesize');
        $order = get('order') ?: 'date';
        switch ($order) {
            case 'date':
            case 'istop':
            case 'isrecommend':
            case 'isheadline':
            case 'visits':
            case 'likes':
            case 'oppose':
                $order = $order . ' DESC';
                break;
            default:
                $order = $order . ' ASC';
        }
        $order .= ",sorting ASC,id DESC";
        
        // 获取参数
        $field = post('field') ?: 'title';
        $keyword = post('keyword');
        
        // 如果关键字为空，直接替换为空结果
        if (! $keyword) {
            json(0, '必须传递关键字keyword参数');
        }
        
        // 转义字符
        $where = escape_string($_POST);
        
        $cond = array(
            'd_source' => 'post',
            'd_regular' => '/^[^\s]+$/'
        );
        
        foreach ($_POST as $key => $value) {
            $where[$key] = filter($key, $cond);
            if ($_POST[$key] && ! $where[$key]) {
                json(0, '您的查询含有非法字符,已被系统拦截');
            }
        }
        
        // 去除特殊键值
        unset($where['keyword']);
        unset($where['field']);
        unset($where['page']);
        unset($where['appid']);
        unset($where['timestamp']);
        unset($where['signature']);
        
        // 读取数据
        $data = $this->model->getSearch($acode, $field, $keyword, $where, $num, $order);
        
        if ($data) {
            if ($data->outlink) {
                $data->link = $data->outlink;
            } else {
                $data->link = url('/api/list/index/scode/' . $data->id, false);
            }
            $data->likeslink = url('/home/Do/likes/id/' . $data->id, false);
            $data->opposelink = url('/home/Do/oppose/id/' . $data->id, false);
        }
        
        // 输出数据
        if (get('page') <= PAGECOUNT) {
            json(1, $data);
        } else {
            return json(0, '已经到底了！');
        }
    }

    // 留言记录
    public function msg()
    {
        // 获取参数
        $acode = get('acode') ?: 'cn';
        $num = get('num') ?: $this->config('pagesize');
        
        // 获取栏目数
        $data = $this->model->getMessage($acode, $num);
        
        if (get('page') <= PAGECOUNT) {
            json(1, $data);
        } else {
            return json(0, '已经到底了！');
        }
    }

    // 留言
    public function addmsg()
    {
        if ($_POST) {
            
            $acode = get('acode') ?: 'cn';
            $contacts = post('contacts');
            $mobile = post('mobile');
            $content = post('content');
            
            if (! $contacts) {
                json(0, '联系人不能为空!');
            }
            
            if (! $mobile) {
                json(0, '手机号码不能为空!');
            }
            
            if (! $content) {
                json(0, '留言内容不能为空!');
            }
            
            $data = array(
                'acode' => $acode,
                'contacts' => $contacts,
                'mobile' => $mobile,
                'content' => $content,
                'user_ip' => ip2long(get_user_ip()),
                'user_os' => get_user_os(),
                'user_bs' => get_user_bs(),
                'recontent' => '',
                'status' => 0,
                'create_user' => 'API',
                'update_user' => 'API'
            );
            
            if ($this->model->addMessage($data)) {
                $this->log('API提交留言成功！');
                if (session('config.message_send_mail') && session('config.message_send_to')) {
                    $mail_subject = "【PbootCMS】您有新的留言，请注意查收！";
                    $mail_body = "联系人：$contacts<br>手　机：$mobile<br>内　容：$content";
                    sendmail(session('config'), session('config.message_send_to'), $mail_subject, $mail_body);
                }
                json(1, '留言成功！');
            } else {
                $this->log('提交留言失败！');
                json(0, '留言失败！');
            }
        } else {
            json(0, '留言失败，请使用POST方式提交留言！');
        }
    }

    // 表单提交
    public function addform()
    {
        if ($_POST) {
            
            if (! $fcode = get('fcode', 'var')) {
                json(0, '传递的表单编码fcode有误！');
            }
            
            // 读取字段
            if (! $form = $this->model->getFormField($fcode)) {
                json(0, '接收表单不存在任何字段，请核对后重试！');
            }
            
            // 接收数据
            foreach ($form as $value) {
                $field_data = post($value->name);
                if ($value->required && ! $field_data) {
                    json(0, $value->description . '不能为空！');
                } else {
                    $data[$value->name] = post($value->name);
                }
            }
            
            // 设置创建时间
            if ($data) {
                $data['create_time'] = get_datetime();
            }
            
            // 写入数据
            if ($this->model->addForm($value->table_name, $data)) {
                $this->log('提交表单数据成功！');
                if (session('config.message_send_mail') && session('config.message_send_to')) {
                    $mail_subject = "【PbootCMS】您有新的表单数据，请注意查收！";
                    $mail_body = "您网站有新的表单数据提交，请登陆网站管理后台查看！";
                    sendmail(session('config'), session('config.message_send_to'), $mail_subject, $mail_body);
                }
                json(1, '表单提交成功！');
            } else {
                $this->log('提交表单数据失败！');
                json(0, '表单提交失败！');
            }
        } else {
            json(0, '提交失败，请使用POST方式提交！');
        }
    }

    // 空拦截
    public function _empty()
    {
        error('您调用的接口不存在，请核对后重试！');
    }
}