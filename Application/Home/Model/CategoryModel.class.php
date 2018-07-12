<?php

namespace Home\Model;
use Think\Model;

/**
 * 首页商品分类模型
 * 更新时间 2015.12.23
 */
class CategoryModel extends Model {

    /* 
     *  商品分类垂直菜单调用
     */
    public function getCategory() {
        $field = 'id,name,pid,title,icon';
        $condition['display'] = 1;
        $condition['status'] = 1;
        $condition['ismenu'] = 1;
       // $condition['id'] = array('lt','165');
        $category = $this->field($field)->order('sort asc')->where($condition)->select();
        $list = $this->unlimitedForLevel($category);
        return $list;
    }
    
    /* 
     *  获取汽车模块分类
     */
    public function getCarCategory() {
        $field = 'id,name,pid,title,icon';
        $condition['display'] = 1;
        $condition['status'] = 1;
        $condition['ismenu'] = 1;
        $condition['id'] = array('gt','164');
        $category = $this->field($field)->order('sort asc')->where($condition)->select();
        $list = $this->unlimitedForLevel($category);
        return $list;
    }

    public function unlimitedForLevel($cate, $name = 'child', $pid = 0) {
        $arr = array();
        foreach ($cate as $key => $v) {
            //判断，如果$v['pid'] == $pid的则压入数组Child
            if ($v['pid'] == $pid) {
                //递归执行
                $v[$name] = self::unlimitedForLevel($cate, $name, $v['id']);
                $arr[] = $v;
            }
        }
        return $arr;
    }
}
