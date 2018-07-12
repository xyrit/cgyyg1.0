<?php

namespace Wap\Controller;

header("Access-Control-Allow-Origin:*");

use Home\Common\UtilApi;

//import("Home.Common.checkLogin");

/* * ***购物车控制器**
 *  joan
 */

class ShopcartController extends HomeController {
    /*
      添加商品
      param int $id 商品主键
      string $mobile 手机号(sessionid)
      int $attend_count 参与人次
     */

    public function addItem() {
        $attend_count = floatval(I('post.attend_count'));
        $id = floatval(I('post.id'));
        $marketprice = I('post.marketprice');
        $lottery_id = floatval(I('post.lottery_id'));
        $sort = I('post.sort');
        $sort = $id;
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $this->checkProduct($lottery_id, $id);
        if ($id < 1 or $attend_count < 1 or $lottery_id < 1) {
            echo json_encode(array('code' => 500, 'info' => 'id不能为空'));
            exit;
        }
        if ($uid > '0') {//已登录
            $table = D("shopcart");
            $data['goodid'] = $id;
            $data['uid'] = $uid;
            $data['marketprice'] = $marketprice;
            $data['sort'] = $sort;
            $data['lottery_id'] = $lottery_id;
            $dataattend_count = M("shopcart")->where("goodid='$id'and uid='$uid' ")->getField("attend_count");
            if ($dataattend_count) {
                $data['attend_count'] = $dataattend_count + $attend_count;
                $table->where("goodid='$id'and uid='$uid'")->save($data);
            } else {
                $data['create_time'] = NOW_TIME;
                $data['attend_count'] = $attend_count;
                $table->add($data);
            }
            $data['attend_count'] = M("shopcart")->where("goodid='$id'and uid='$uid'")->getField("attend_count");
            unset($data['sort']); //删除多余元素
            echo json_encode(array('code' => 200, 'info' => '成功', 'cart' => $data));
        } else {//未登录
            $data['lottery_id'] = $lottery_id;
            $itemid = $this->getItem($sort);
            $data['attend_count'] = $attend_count;
            $data['sum'] = $this->getNum();
            echo json_encode(array('code' => 200, 'info' => '成功'));
        }
    }

    /*
      修改购物车中的商品数量
      int $id 商品主键
      int $attend_count 某商品修改后的数量
     * 
     */

    public function changeNum() {
        $content = I('post.content');
        $array = str_replace("&quot;", "\"", $content);
        $array = json_decode($array, true);
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        if (!empty($_POST)) {
            $arrs = $array['list']; //dump($arrs);exit;
            $level = count($arrs); //选中的商品种类,数组长度
            $i = 0;
            foreach ($arrs as $k => $v) {
                $id = $arrs[$k]['id']; //购物车id
                $title = $arrs[$k]['title']; //商品名称
                $path = $arrs[$k]['path']; //图片路径
                $attend_count = $arrs[$k]['attend_count']; //修改的参与人次
                if (floatval($id) < 1) {
                    UtilApi::getInfo(500, '购物车id不能为空');
                    return;
                }
                if (floatval($attend_count) < 1) {
                    UtilApi::getInfo(500, '参与人次不能为空');
                    return;
                }
                $data['attend_count'] = $attend_count;
                $count = D("Home/shopcart")->remainCount($id); //获取该购物车id的购买人次限制信息
                $remainCount = $count[0]['need_count'] - $count[0]['attend_count']; //当前商品剩余人次
                $attend_limit = $count[0]['attend_limit']; //当前商品最低参与人次
                $max_attend_limit = $count[0]['max_attend_limit']; //当前商品最大参与人次

                if ($attend_count > $remainCount) {
                    echo json_encode(array('code' => 519, 'info' => $arrs[$k]['title'] . '超过剩余人次'));
                    exit;
                } elseif ($attend_count < 1) {
                    echo json_encode(array('code' => 520, 'info' => '参与人次不能小于1人次'));
                    exit;
                }
                D("Home/shopcart")->cartSave($id, $uid, $data); //保存更改的购物车信息
                $payfor = D("Home/shopcart")->getPaylist($id, $uid); //返回修改后的商品信息
                $attend_counts += $attend_count;
                $list[$i]['remain_count'] = $remainCount; //剩余人次
                $list[$i]['title'] = $title; //商品名称
                $list[$i]['path'] = $path; //商品图片路径

                $payfor[$i]['remain_count'] = $remainCount;
                $payfor[$i]['title'] = $title;
                $payfor[$i]['path'] = $path;
                //$list[$i]['list1'] = $payfor; //确认后的商品支付信息
                $list[$i] = $payfor[0]; //确认后的商品支付信息
                $i++;
            }
            $sum = $attend_counts;
            $cart['list'] = $list;
            $cart['sum'] = $sum; //总金额
            $cart['level'] = $level; //商品类别数目
            echo json_encode(array('code' => 200, 'info' => '成功', 'host' => C('HOST')));
        } else {
            UtilApi::getInfo(500, 'content不能为空');
            return;
        }
    }

    /* 查询购物车-列表 */

    public function index() {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        if ($uid > '0') {//已登录
            $result = D("Home/shopcart")->getCart($uid);
            $total = 0; //总金额
            foreach ($result as $k => $val) {
                $total += $result[$k][attend_count];
                $remain_count = $result[$k]['need_count'] - $result[$k]['buy_count'];
                $result[$k][remain_count] = $remain_count;
                unset($result[$k]['buy_count']);
            }
            $data['info'] = $result;
            $data['total'] = $total;
            $data['count'] = count($result);
           // $data['HotList'] = D("Home/LotteryProduct")->getHotList(4); //热门推荐         
            echo json_encode(array('code' => 200, 'info' => '成功', 'host' => C('HOST'), 'cart' => $data));
        } else {
            $uid = "";
            echo json_encode(array('code' => 200, 'info' => '成功', 'host' => C('HOST'), 'cart' => $data));
        }
    }

    /* 订单支付列表 */

    public function payFor() {
        $arr = array();
        $ids = I('post.id');
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        if (empty($ids)) {
            echo json_encode(array('code' => 500, 'info' => 'id不能为空'));
            exit;
        }
        // $arr = explode(',', $ids);
        if ($uid > '0') {//已登录
            //$uid = D("Member")->getUid($sessionid);
            $cart = D("Home/shopcart");
            $result = $cart->getPaylist($ids, $uid); //dump($result);exit;
            $total = 0; //总金额
            foreach ($result as $k => $val) {
                $total += $result[$k][attend_count];
                $remain_count = $result[$k]['need_count'] - $result[$k]['buy_count'];
                $result[$k][remain_count] = $remain_count;
            }
            if (!empty($result)) {//echo 000;
                $account = M("Member")->where("uid='$uid'")->getField("account");
            }
            //$account = M("Member")->where("uid='$uid'")->getField("account");
            $data['info'] = $result;
            $data['total'] = $total;
            $data['account'] = $account;
            $data['ebank'] = $total - $account;
            echo json_encode(array('code' => 200, 'info' => '成功', 'host' => C('HOST'), 'cart' => $data));
        }
    }

    /* 批量删除 */

    public function delItem($ids = '') {
        $arr = array();
        $ids = I('post.id');
        if (empty($ids)) {
            echo json_encode(array('code' => 500, 'info' => 'id不能为空'));
            exit;
        }
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $arr = explode(',', $ids);
        foreach ($arr as $k => $ide) {
            $del = M("shopcart")->where('id=' . $arr[$k] . ' and uid=' . $uid)->delete();
            if ($del > 0) {
                echo json_encode(array('code' => 200, 'info' => '成功', 'cart' => $del));
            } else {
                echo json_encode(array('code' => 500, 'info' => '成功', 'cart' => '删除失败'));
            }
        }
    }

    /*
     * 查询购物车总数量
     * 
     */

    public function listNum() {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $count = M('shopcart')->field('id')->where('uid=' . $uid)->count();
        echo json_encode(array('code' => 200, 'info' => '成功', 'total' => $count));
    }

    /*
     * 检查添加到购物车商品的期号、商品id是否存在
     * 
     */

    public function checkProduct($lottery_id = '', $pid = '') {
        $id = M("lottery_product")->field("id")->where('lottery_id=' . $lottery_id . ' and pid=' . $pid)->find();
        if ($id > 0) {
            return true;
        } else {
            UtilApi::getInfo(500, '商品的期号、商品id不存在');
            exit;
        }
    }

}
