<?php

namespace Home\Model;

/**
 * 收货地址
 *
 * @author joan
 */
class AddressModel {
    /* 取出当前用户收货地址列表 */

    public function getlist($uid = 0, $addressid = 0) {
        $condition = ($addressid > 0) ? "id=" . $addressid : "uid='$uid' and status>0";
        $list = M("address")->field('id,realname,cellphone,province,city,area,address,status')->where($condition)->select();
        return $list;
    }

    /* 获得联动地址 */

    public function getArea($pid = 0) {
        $list = M("area")->field('id,area,pid')->where("pid=" . $pid)->select();
        return $list;
    }

    /* 默认地址修改 */

    public function addressSet($id = '', $data = '') {//dump($data);echo $id;
        $addressid = M("address")->where('id=' . $id)->save($data); //echo M("address")->_sql();exit;
        return $addressid;
    }

}
