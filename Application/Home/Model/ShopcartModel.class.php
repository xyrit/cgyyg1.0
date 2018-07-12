<?php

namespace Home\Model;

use Think\Model;
use Think\Page;

/**
 * 登录用户的购物车类
 */
class ShopcartModel extends Model {
    /*
      查询当前登陆用户购物车信息
     */

    public function getCart($uid = '') {
        $prefix = C('DB_PREFIX');
        $sql = "select sc.id ,sc.lottery_id,sc.attend_count,d.price, " .
                " d.title ,p.path,sc.goodid,lp.attend_count as buy_count,lp.need_count" .
                " from " . $prefix . "shopcart as sc  " .
                " left join " . $prefix . "lottery_product as lp on sc.lottery_id = lp.lottery_id " .
                " left join " . $prefix . "document as d on lp.pid = d.id" .
                " left join " . $prefix . "picture as p on d.cover_id = p.id" .
                " where sc.uid=" . $uid . " order by sc.id  ";
        //echo $sql;
        $list = $this->query($sql); //dump($list);
        return $list;
    }

    /*
      查询该商品购物车信息
     */

    public function getlist($id = '', $uid = '') {
        //$list = D("shopcart")->field('id,goodid,lottery_id,attend_count')->where("goodid='$id'and uid='$uid'")->select();
        $prefix = C('DB_PREFIX');
        $sql = "select sc.id ,sc.lottery_id,sc.attend_count,d.price, " .
                " d.title ,d.cover_id,p.path,sc.goodid,lp.attend_count as buy_count,lp.need_count" .
                " from " . $prefix . "shopcart as sc  " .
                " left join " . $prefix . "lottery_product as lp on sc.goodid = lp.pid" .
                " left join " . $prefix . "document as d on lp.pid = d.id" .
                " left join " . $prefix . "picture as p on d.cover_id = p.id" .
                " where (sc.goodid=" . $id . " and sc.uid=" . $uid . ") order by sc.id  ";
        //echo $sql;
        $list = $this->query($sql);
        //dump(00);
        return $list;
    }

    /*
      查询订单支付列表
     */

    public function getPaylist($id = '', $uid = '') {
        //$list = D("shopcart")->field('id,goodid,lottery_id,attend_count')->where("goodid='$id'and uid='$uid'")->select();
        $prefix = C('DB_PREFIX');
        $sql = "select sc.id,d.title,p.path,sc.lottery_id,sc.attend_count,sc.goodid as pid,lp.need_count,lp.attend_count as buy_count" .
                " from " . $prefix . "shopcart as sc  left join  " . $prefix . "lottery_product as lp on sc.lottery_id=lp.lottery_id " .
                " left join  " . $prefix . "document as d on lp.pid = d.id  " .
                " left join " . $prefix . "picture as p on d.cover_id= p.id" .
                " where ((sc.uid=" . $uid . ") and (sc.id in (" . $id . "))  ) order by sc.id  ";
        //echo $sql;
        $list = $this->query($sql); //dump($list);
        return $list;
    }

    /*
      购物车数量修改
     */

    public function cartSave($id = '', $uid = '', $data = '') {
        D("shopcart")->where("id='$id'and uid='$uid'")->save($data);
        //echo D("shopcart")->getLastSql();
    }

    /*
      所选商品剩余数量
     */

    public function remainCount($id = '') {
        $prefix = C('DB_PREFIX');
        $sql = "select lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit  from " . $prefix . "shopcart as sc " .
                " left join " . $prefix . "lottery_product as lp on sc.lottery_id = lp.lottery_id " .
                " where sc.id=" . $id . " order by sc.id  "; //echo $sql;exit;
        $list = $this->query($sql); //dump($list);
        return $list;
    }

}
