<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2018年2月14日
 *  标签解析引擎模型
 */
namespace app\api\model;

use core\basic\Model;

class CmsModel extends Model
{

    // 存储分类及子编码
    protected $scodes = array();

    // 存储分类查询数据
    protected $sorts;

    // 存储栏目位置
    protected $position = array();

    // 单个站点配置信息
    public function getSite($acode, $name)
    {
        return parent::table('ay_site')->where("acode='" . $acode . "'")->value($name);
    }

    // 站点配置信息
    public function getSiteAll($acode)
    {
        return parent::table('ay_site')->where("acode='" . $acode . "'")->find();
    }

    // 单个公司信息
    public function getCompany($acode, $name)
    {
        return parent::table('ay_company')->where("acode='" . $acode . "'")->value($name);
    }

    // 公司信息
    public function getCompanyAll($acode)
    {
        return parent::table('ay_company')->where("acode='" . $acode . "'")->find();
    }

    // 自定义标签
    public function getLabel($name)
    {
        return parent::table('ay_label')->where("name='$name'")
            ->decode()
            ->value('value');
    }

    // 所有自定义标签
    public function getLabelAll()
    {
        return parent::table('ay_label')->decode()->column('value', 'name');
    }

    // 分类信息
    public function getSort($acode, $scode)
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
            ->where("a.acode='" . $acode . "'")
            ->where("a.scode='$scode'")
            ->join($join)
            ->find();
    }

    // 分类栏目列表
    public function getSorts($acode)
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
        $result = parent::table('ay_content_sort a')->field($fields)
            ->where("a.acode='" . $acode . "'")
            ->where('a.status=1')
            ->join($join)
            ->order('a.pcode,a.sorting,a.id')
            ->select();
        return get_tree($result, 0, 'scode', 'pcode');
    }

    // 分类顶级编码
    public function getSortTopScode($acode, $scode)
    {
        if (! isset($this->sorts)) {
            $fields = array(
                'a.id',
                'a.pcode',
                'a.scode',
                'a.name',
                'b.type'
            );
            $join = array(
                'ay_model b',
                'a.mcode=b.mcode',
                'LEFT'
            );
            $this->sorts = parent::table('ay_content_sort a')->where("a.acode='" . $acode . "'")
                ->join($join)
                ->column($fields, 'scode');
        }
        $result = $this->sorts;
        return $this->getTopParent($scode, $result);
    }

    // 获取位置
    public function getPosition($acode, $scode)
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
            $this->sorts = parent::table('ay_content_sort a')->where("a.acode='" . $acode . "'")
                ->join($join)
                ->column($fields, 'scode');
        }
        $result = $this->sorts;
        $this->position = array();
        $this->getTopParent($scode, $result);
        return array_reverse($this->position);
    }

    // 分类顶级编码及栏目树
    private function getTopParent($scode, $sorts)
    {
        if (! $scode || ! $sorts) {
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
    public function getList($acode, $scode, $num, $order, $field = '', $keyword = '')
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
            "a.scode in (" . implode_quot(',', $scodes) . ")",
            "a.subscode='$scode'"
        );
        $where2 = array(
            "a.acode='" . $acode . "'",
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

    // 指定列表内容，不带分页
    public function getSpecifyList($acode, $scode, $num, $order, $field = '', $keyword = '')
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
            "a.scode in (" . implode_quot(',', $scodes) . ")",
            "a.subscode='$scode'"
        );
        $where2 = array(
            "a.acode='" . $acode . "'",
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
    public function getContent($acode, $id)
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
            ->where("a.id='$id'")
            ->where("a.acode='" . $acode . "'")
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
    public function getContentPics($acode, $id)
    {
        $result = parent::table('ay_content')->where("id='$id'")
            ->where("acode='" . $acode . "'")
            ->where('status=1')
            ->value('pics');
        return $result;
    }

    // 获取搜索内容
    public function getSearch($acode, $field, $keyword, $where, $num, $order)
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
                "a.scode in (" . implode_quot(',', $scodes) . ")",
                "a.subscode='" . $where['scode'] . "'"
            );
            unset($where['scode']);
        }
        
        $where2 = array(
            "a.acode='" . $acode . "'",
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
    public function getSlides($acode, $gid, $num)
    {
        $result = parent::table('ay_slide')->where("gid='$gid'")
            ->where("acode='" . $acode . "'")
            ->limit($num)
            ->select();
        return $result;
    }

    // 友情链接
    public function getLinks($acode, $gid, $num)
    {
        $result = parent::table('ay_link')->where("gid='$gid'")
            ->where("acode='" . $acode . "'")
            ->limit($num)
            ->select();
        return $result;
    }

    // 获取留言
    public function getMessage($acode, $num)
    {
        return parent::table('ay_message')->where("status=1")
            ->where("acode='" . $acode . "'")
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