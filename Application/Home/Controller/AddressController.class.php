<?php

namespace Home\Controller;

use Home\Common\UtilApi;

/**
 * 用户中心-个人地址
 * 包括列表、修改、删除、添加
 * Author: joan
 */
class AddressController extends HomeController {
    /* 个人中心-收货地址列表 */

    public function addressList() {
        if (IS_POST && !empty($_POST)) {
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token);
            $list = D("Address")->getlist($uid);
            $total = 3;
            $address_num = count($list);
            $remain = $total - $address_num;
            $arr = array('code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'address_num' => $address_num,
                'remain' => $remain,
                'address' => $list);
            echo json_encode($arr);
        } else {
            exit;
        }
    }

    /* 个人中心-收货地址添加 */

    public function addressAdd() {
        if (IS_POST && !empty($_POST)) {
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token);
            $cellphone = I('post.cellphone');
            $data['realname'] = I('post.realname');
            $data['province'] = I('post.province');
            $data['city'] = I('post.city');
            $data['area'] = I('post.area');
            $data['address'] = I('post.address');
            $data['cellphone'] = I('post.telephone');
            $data['verify'] = I('post.verify');
            $state = intval(I('post.state')); //是否默认地址
            $data['uid'] = $uid;
            $data['create_time'] = time();
            $data['status'] = ($state == '2') ? 2 : 1; //是否默认地址
            $data['take_address'] = $data['province'] . " " . $data['city'] . " " . $data['area'] . " " . $data['address']; //拼接地址
            $state = 2;
            $total = M("address")->field('count(id) as total')->where('uid=' . $uid)->select(); //查询当前用户地址数量
            $total = $total[0]["total"];
            if ($total > '3') {
                UtilApi::getInfo(500, '最多添加3个地址');
                exit;
            }
            //$type = intval(I('post.type')); //短信type
            $code = A("Verify")->checkVerify($cellphone, $data['verify'], 2, 4); //验证手机验证码
            if ($code > 0) {
                $addressid = M("address")->data($data)->filter('strip_tags')->add(); //插入库
                if ($addressid > 0) {
                    $this->defaultSet($addressid, $uid);
                }
            } else {
                echo json_encode(array('code' => 523, 'info' => '添加失败'));
            }
        }
    }

    /* 个人中心-收货地址修改 */

    public function addressSave() {
        if (IS_POST && !empty($_POST)) {
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token);
            $cellphone = I('post.cellphone');
            $data['realname'] = I('post.realname');
            $data['province'] = I('post.province');
            $data['city'] = I('post.city');
            $data['area'] = I('post.area');
            $data['address'] = I('post.address');
            $data['cellphone'] = I('post.telephone');
            $data['verify'] = I('post.verify');
            $state = intval(I('post.state'));
            $data['status'] = ($state == '2') ? 2 : 1;
            $data['take_address'] = $data['province'] . " " . $data['city'] . " " . $data['area'] . " " . $data['address'];
            $id = intval(I('post.id'));
            $type = intval(I('post.type')); //短信type
            $code = A("Verify")->checkVerify($cellphone, $data['verify'], 2, 5);
            if ($code > 0) {
                $addressid = M("address")->where('id=' . $id)->save($data);
                if ($addressid > 0) {
                    //echo json_encode(array('code' => 200, 'info' => '成功'));
                    $this->defaultSet($id, $uid);
                } else {
                    echo json_encode(array('code' => 524, 'info' => '修改失败,两次信息不能相同'));
                }
            }
        } else {
            echo json_encode(array('code' => 500, 'info' => '参数错误'));
        }
    }

    /* 个人中心-收货地址删除 */

    public function addressDel() {
        if (IS_POST && !empty($_POST)) {
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token);
            $id = floatval(I('post.id'));
            if ($id < '1') {
                UtilApi::getInfo(526, '参数不能为空');
                exit;
            }
            M("address")->where('id=' . $id)->delete();
            echo json_encode(array('code' => 200, 'info' => '成功', 'cart' => $del));
        } else {
            echo json_encode(array('code' => 200, 'info' => '成功', 'cart' => $del));
        }
    }

    /* 个人中心-设置默认收货地址 */

    public function addressSet() {
        if (IS_POST && !empty($_POST)) {
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token); //检查是否登录，是则返回uid，否则退出
            $id = floatval(I('post.id'));
            $this->defaultSet($id, $uid);
        } else {
            echo json_encode(array('code' => 526, 'info' => '参数不能为空'));
        }
    }

    /* 个人中心-得到联动地址（省、市、区） */

    public function getProvinces() {
        if (IS_POST && !empty($_POST)) {
            $pid = I('post.id'); //城市主键id
            $state = I('post.state');
            $ProvincesList = D("address")->getArea(0); //取出所有的省级
            if ($state == '1') {//省id对应的市
                $ProvincesList = D("address")->getArea($pid);
            } else if ($state == '2') {//市id对应的县区
                $ProvincesList = D("address")->getArea($pid);
            }
            echo json_encode(array('code' => 200, 'info' => '成功', 'city' => $ProvincesList));
        } else {
            echo json_encode(array('code' => 526, 'info' => '参数不能为空'));
        }
    }

    /* 默认地址设置方法 */

    public function defaultSet($id = '', $uid = '') {
        $data["status"] = 2; //默认地址状态
        $status_code = D("address")->addressSet($id, $data); //设置默认地址
        $data["status"] = 1; //普通地址状态
        $addressid = M("address")->where('(id <> ' . $id . ")  and (uid=" . $uid . ") and (status=2)")->save($data); //将旧的默认地址状态改为普通地址状态
        $old_addressid = M("address")->field('status')->where("uid=" . $uid . " and status=0")->find(); //查询是否存在隐藏默认地址
        $fixed_status = $old_addressid["status"];
        if ($fixed_status == '0') {//判断是否是隐藏默认地址，是则赋值。
            $default_addressid = M("address")->where("uid=" . $uid . " and status=2")->find(); //取出新设置默认地址的详细信息
            $condition["orderid"] = $default_addressid["orderid"];
            $condition["cellphone"] = $default_addressid["cellphone"];
            $condition["province"] = $default_addressid["province"];
            $condition["city"] = $default_addressid["city"];
            $condition["area"] = $default_addressid["area"];
            $condition["address"] = $default_addressid["address"];
            $condition["take_address"] = $default_addressid["take_address"];
            $condition["realname"] = $default_addressid["realname"];
            $condition["youbian"] = $default_addressid["youbian"];
            $condition["create_time"] = $default_addressid["create_time"];
            $addressid = M("address")->where("uid=" . $uid . " and status=0")->save($condition); //将取出的新设置默认地址的详细信息赋给隐藏的收货地址
        }
        if ($status_code > 0) {
            UtilApi::getInfo(200, '成功');
        } else {
            UtilApi::getInfo(524, '设置失败');
        }
    }

}
