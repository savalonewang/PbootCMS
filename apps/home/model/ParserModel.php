<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年2月14日
 *  标签解析引擎模型
 */
namespace app\home\model;

use core\basic\Model;

class ParserModel extends Model
{

    // 存储分类及子编码
    protected $scodes = array();

    // 存储分类查询数据
    protected $sorts;

    // 存储栏目位置
    protected $position = array();

    // 站点配置信息
    public function getSite()
    {
        return parent::table('ay_site')->where("acode='" . session('lg') . "'")->find();
    }

    // 公司信息
    public function getCompany()
    {
        return parent::table('ay_company')->where("acode='" . session('lg') . "'")->find();
    }

    // 自定义标签
    public function getLabel()
    {
        return parent::table('ay_label')->decode()->column('value', 'name');
    }

    // 分类信息
    public function getSort($scode)
    {
        $field = array(
            'a.id',
            'a.pcode',
            'a.scode',
            'a.name',
            'a.subname',
            'b.type',
            'a.outlink',
            'a.listtpl',
            'a.contenttpl',
            'a.ico',
            'a.pic',
            'a.keywords',
            'a.description',
            'a.sorting'
        );
        $join = array(
            'ay_model b',
            'a.mcode=b.mcode',
            'LEFT'
        );
        return parent::table('ay_content_sort a')->field($field)
            ->where("a.acode='" . session('lg') . "'")
            ->where("a.scode='$scode'")
            ->join($join)
            ->find();
    }

    // 分类栏目列表
    public function getSorts()
    {
        $fields = array(
            'a.id',
            'a.pcode',
            'a.scode',
            'a.name',
            'a.subname',
            'b.type',
            'a.outlink',
            'a.listtpl',
            'a.contenttpl',
            'a.ico',
            'a.pic',
            'a.keywords',
            'a.description',
            'a.sorting'
        );
        $join = array(
            'ay_model b',
            'a.mcode=b.mcode',
            'LEFT'
        );
        $result = parent::table('ay_content_sort a')->where("a.acode='" . session('lg') . "'")
            ->where('a.status=1')
            ->join($join)
            ->order('a.pcode,a.sorting,a.id')
            ->column($fields, 'scode');
        
        foreach ($result as $key => $value) {
            if ($value['pcode']) {
                $result[$value['pcode']]['son'][] = $value; // 记录到关系树
            } else {
                $data['top'][] = $value; // 记录顶级菜单
            }
        }
        $data['tree'] = $result;
        return $data;
    }

    // 分类顶级编码
    public function getSortTopScode($scode)
    {
        if (! isset($this->sorts)) {
            $fields = array(
                'a.id',
                'a.pcode',
                'a.scode',
                'a.name',
                'a.outlink',
                'b.type'
            );
            $join = array(
                'ay_model b',
                'a.mcode=b.mcode',
                'LEFT'
            );
            $this->sorts = parent::table('ay_content_sort a')->where("a.acode='" . session('lg') . "'")
                ->join($join)
                ->column($fields, 'scode');
        }
        $result = $this->sorts;
        return $this->getTopParent($scode, $result);
    }

    // 获取位置
    public function getPosition($scode)
    {
        if (! isset($this->sorts)) {
            $fields = array(
                'a.id',
                'a.pcode',
                'a.scode',
                'a.name',
                'a.outlink',
                'b.type'
            );
            $join = array(
                'ay_model b',
                'a.mcode=b.mcode',
                'LEFT'
            );
            $this->sorts = parent::table('ay_content_sort a')->where("a.acode='" . session('lg') . "'")
                ->join($join)
                ->column($fields, 'scode');
        }
        $result = $this->sorts;
        $this->getTopParent($scode, $result);
        return array_reverse($this->position);
    }

    // 分类顶级编码及栏目树
    private function getTopParent($scode, $sorts)
    {
        if (! $scode) {
            return;
        }
        $this->position[] = $sorts[$scode];
        if ($sorts[$scode]['pcode']) {
            return $this->getTopParent($sorts[$scode]['pcode'], $sorts);
        } else {
            return $sorts[$scode]['scode'];
        }
    }

    // 分类子类集
    private function getSubScodes($scode)
    {
        if (! $scode) {
            return;
        }
        $this->scodes[] = $scode;
        $subs = parent::table('ay_content_sort')->where("pcode='$scode'")->column('scode');
        if ($subs) {
            foreach ($subs as $value) {
                $this->getSubScodes($value);
            }
        }
        return $this->scodes;
    }

    // 列表内容
    public function getList($scode, $num, $order, $field, $keyword)
    {
        $fields = array(
            'a.*',
            'b.name as sortname',
            'c.name as subsortname',
            'd.type',
            'e.*'
        );
        $join = array(
            array(
                'ay_content_sort b',
                'a.scode=b.scode',
                'LEFT'
            ),
            array(
                'ay_content_sort c',
                'a.subscode=c.scode',
                'LEFT'
            ),
            array(
                'ay_model d',
                'b.mcode=d.mcode',
                'LEFT'
            ),
            array(
                'ay_content_ext e',
                'a.id=e.contentid',
                'LEFT'
            )
        );
        
        // 获取所有子类分类编码
        $this->scodes = array(); // 先清空
        $scodes = $this->getSubScodes($scode);
        
        // 拼接条件
        $where1 = array(
            "a.scode in (" . implode(',', $scodes) . ")",
            "a.subscode='$scode'"
        );
        $where2 = array(
            "a.acode='" . session('lg') . "'",
            'a.status=1',
            'd.type=2'
        );
        
        return parent::table('ay_content a')->field($fields)
            ->where($where1, 'OR')
            ->where($where2)
            ->like('a.' . $field, $keyword)
            ->join($join)
            ->order($order)
            ->page(1, $num)
            ->decode()
            ->select();
    }

    // 指定列表内容
    public function getSpecifyList($scode, $num, $order, $field, $keyword)
    {
        $fields = array(
            'a.*',
            'b.name as sortname',
            'c.name as subsortname',
            'd.type',
            'e.*'
        );
        $join = array(
            array(
                'ay_content_sort b',
                'a.scode=b.scode',
                'LEFT'
            ),
            array(
                'ay_content_sort c',
                'a.subscode=c.scode',
                'LEFT'
            ),
            array(
                'ay_model d',
                'b.mcode=d.mcode',
                'LEFT'
            ),
            array(
                'ay_content_ext e',
                'a.id=e.contentid',
                'LEFT'
            )
        );
        $this->scodes = array(); // 先清空
        $scodes = $this->getSubScodes($scode);
        
        // 拼接条件
        $where1 = array(
            "a.scode in (" . implode(',', $scodes) . ")",
            "a.subscode='$scode'"
        );
        $where2 = array(
            "a.acode='" . session('lg') . "'",
            'a.status=1',
            'd.type=2'
        );
        
        return parent::table('ay_content a')->field($fields)
            ->where($where1, 'OR')
            ->where($where2)
            ->like('a.' . $field, $keyword)
            ->join($join)
            ->order($order)
            ->limit($num)
            ->decode()
            ->select();
    }

    // 内容详情
    public function getContent($id)
    {
        $field = array(
            'a.*',
            'b.name as sortname',
            'c.name as subsortname',
            'd.type',
            'e.*'
        );
        $join = array(
            array(
                'ay_content_sort b',
                'a.scode=b.scode',
                'LEFT'
            ),
            array(
                'ay_content_sort c',
                'a.subscode=c.scode',
                'LEFT'
            ),
            array(
                'ay_model d',
                'b.mcode=d.mcode',
                'LEFT'
            ),
            array(
                'ay_content_ext e',
                'a.id=e.contentid',
                'LEFT'
            )
        );
        $result = parent::table('ay_content a')->field($field)
            ->where("a.id='$id' OR a.filename='$id'")
            ->where("a.acode='" . session('lg') . "'")
            ->where('a.status=1')
            ->join($join)
            ->decode()
            ->find();
        if ($result) {
            $data2 = array(
                'visits' => '+=1'
            );
            parent::table('ay_content')->where("id={$result->id}")->update($data2);
        }
        return $result;
    }

    // 单篇详情
    public function getAbout($scode)
    {
        $field = array(
            'a.*',
            'b.name as sortname',
            'c.name as subsortname',
            'd.type',
            'e.*'
        );
        $join = array(
            array(
                'ay_content_sort b',
                'a.scode=b.scode',
                'LEFT'
            ),
            array(
                'ay_content_sort c',
                'a.subscode=c.scode',
                'LEFT'
            ),
            array(
                'ay_model d',
                'b.mcode=d.mcode',
                'LEFT'
            ),
            array(
                'ay_content_ext e',
                'a.id=e.contentid',
                'LEFT'
            )
        );
        $result = parent::table('ay_content a')->field($field)
            ->where("a.scode='$scode'")
            ->where("a.acode='" . session('lg') . "'")
            ->where('a.status=1')
            ->join($join)
            ->decode()
            ->find();
        if ($result) {
            $data2 = array(
                'visits' => '+=1'
            );
            parent::table('ay_content')->where("id={$result->id}")->update($data2);
        }
        return $result;
    }

    // 指定内容多图
    public function getContentPics($id)
    {
        $result = parent::table('ay_content')->where("id='$id'")
            ->where("acode='" . session('lg') . "'")
            ->where('status=1')
            ->value('pics');
        return $result;
    }

    // 指定内容多选调用
    public function getContentCheckbox($id, $field)
    {
        $result = parent::table('ay_content_ext')->where("contentid='$id'")->value($field);
        return $result;
    }

    // 上一篇内容
    public function getContentPre($scode, $id)
    {
        $this->scodes = array();
        $scodes = $this->getSubScodes($scode);
        $result = parent::table('ay_content')->field('id,title')
            ->where("id<$id")
            ->in('scode', $scodes)
            ->where("acode='" . session('lg') . "'")
            ->where('status=1')
            ->order('id DESC')
            ->find();
        return $result;
    }

    // 下一篇内容
    public function getContentNext($scode, $id)
    {
        $this->scodes = array();
        $scodes = $this->getSubScodes($scode);
        $result = parent::table('ay_content')->field('id,title')
            ->where("id>$id")
            ->in('scode', $scodes)
            ->where("acode='" . session('lg') . "'")
            ->where('status=1')
            ->order('id ASC')
            ->find();
        return $result;
    }

    // 获取搜索内容
    public function getSearch($field, $keyword, $where, $num, $order)
    {
        // 此处不使用join，避免字段查询错误问题
        $fields = array(
            'a.*',
            'b.name as sortname',
            'c.name as subsortname',
            'd.type',
            'e.*'
        );
        $join = array(
            array(
                'ay_content_sort b',
                'a.scode=b.scode',
                'LEFT'
            ),
            array(
                'ay_content_sort c',
                'a.subscode=c.scode',
                'LEFT'
            ),
            array(
                'ay_model d',
                'b.mcode=d.mcode',
                'LEFT'
            ),
            array(
                'ay_content_ext e',
                'a.id=e.contentid',
                'LEFT'
            )
        );
        
        // 如果有限定分类，则获取子类集
        $this->scodes = array();
        
        // 拼接条件
        $where1 = '';
        if (isset($where['scode'])) {
            $scodes = $this->getSubScodes($where['scode']);
            $where1 = array(
                "a.scode in (" . implode(',', $scodes) . ")",
                "a.subscode='" . $where['scode'] . "'"
            );
            unset($where['scode']);
        }
        
        $where2 = array(
            "a.acode='" . session('lg') . "'",
            'a.status=1'
        );
        
        return parent::table('ay_content a')->field($fields)
            ->where($where1, 'OR')
            ->where($where)
            ->like($field, $keyword)
            ->join($join)
            ->where($where2)
            ->order($order)
            ->page(1, $num)
            ->decode()
            ->select();
    }

    // 幻灯片
    public function getSlides($gid, $num)
    {
        $result = parent::table('ay_slide')->where("gid='$gid'")
            ->where("acode='" . session('lg') . "'")
            ->limit($num)
            ->select();
        return $result;
    }

    // 友情链接
    public function getLinks($gid, $num)
    {
        $result = parent::table('ay_link')->where("gid='$gid'")
            ->where("acode='" . session('lg') . "'")
            ->limit($num)
            ->select();
        return $result;
    }

    // 获取留言
    public function getMessage($num)
    {
        return parent::table('ay_message')->where("status=1")
            ->where("acode='" . session('lg') . "'")
            ->order('id DESC')
            ->decode(false)
            ->page(1, $num)
            ->select();
    }

    // 新增留言
    public function addMessage($data)
    {
        return parent::table('ay_message')->autoTime()->insert($data);
    }
}