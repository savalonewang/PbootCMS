<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年4月20日
 *  基本接口控制器
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
            $data = $this->model->getAllLabel();
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
        
        // 获取栏目数
        $data = $this->model->getSorts($acode);
        
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
            $num = get('num') ?: 3;
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

    // 留言记录
    public function msg()
    {
        // 获取参数
        $acode = get('acode') ?: 'cn';
        $num = get('num') ?: $this->config('pagesize');
        
        // 获取栏目数
        $data = $this->model->getMessage($acode, $num);
        
        // 输出数据
        json(1, $data);
    }
}