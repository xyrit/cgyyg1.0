<?php

namespace Wap\Controller;

use Home\Common\UtilApi;

header("Access-Control-Allow-Origin:*");

/**
 * 个人中心
 * 我关注的、我看过的
 * Author: Bruce
 */
class UcenterFocusController extends HomeController {
    /*
     * 关注商品
     */

    public function focusProduct() {
        $user_token = I('post.user_token');
        $pid = I('post.pid');
        $lotteryId = I('post.lotteryId');
        if (empty($user_token)) {
            UtilApi::getInfo(500, 'user_token不能为空');
            return;
        }
        if (empty($pid)) {
            UtilApi::getInfo(500, '商品id不能为空');
            return;
        }
        if (empty($lotteryId)) {
            UtilApi::getInfo(500, '期号id不能为空');
            return;
        }
        $this->is_login($user_token); //判断是否登录
        $uid = floatval(S($user_token));
        $flag = D("Home/documentProduct")->focusProduct($uid, $pid, $lotteryId);
        if ($flag == -1) {
            UtilApi::getInfo(500, '已经关注过该商品');
        } else {
            if ($flag == 1) {
                UtilApi::getInfo(200, '成功');
            } else {
                UtilApi::getInfo(500, '关注失败');
            }
        }
    }

    /*
     * 关注商品列表
     */

    public function focusList() {
        $user_token = I('post.user_token');
        $pageIndex = I('post.pageIndex');
        $pageSize = I('post.pageSize');
        if (empty($user_token)) {
            UtilApi::getInfo(500, 'user_token不能为空');
            return;
        }
        if (isset($pageSize)) {
            if ($pageSize == 0) {
                UtilApi::getInfo(500, 'pageSize必须大于0');
                return;
            }
        } else {
            UtilApi::getInfo(500, 'pageSize不能为空');
            return;
        }
        if (isset($pageIndex) == false) {
            UtilApi::getInfo(500, 'pageIndex不能为空');
            return;
        }
        $this->is_login($user_token); //判断是否登录
        $uid = floatval(S($user_token));
        $total = D('Home/documentProduct')->focusCount($uid);
        $list = D('Home/documentProduct')->focusList($uid, $pageIndex, $pageSize);
        $pageCount = floor($total / $pageSize);
        if ($total % $pageSize > 0) {
            $pageCount = $pageCount + 1;
        }
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pageCount' => $pageCount,
            'list' => $list
        );
        echo json_encode($arr);
    }

    /*
     * 清空失效商品或删除某个失效商品
     */

    public function delFocus() {
        $user_token = I('post.user_token');
        $lotteryId = I('post.lotteryId');
        if (empty($user_token)) {
            UtilApi::getInfo(500, 'user_token不能为空');
            return;
        }
        if (empty($lotteryId)) {
            UtilApi::getInfo(500, '期号id不能为空');
            return;
        }
        $this->is_login($user_token); //判断是否登录
        $uid = floatval(S($user_token));
        $flag = M('collect')->where(array('uid' => $uid, 'lottery_id' => array('in', $lotteryId)))->delete();
        if ($flag) {
            UtilApi::getInfo(200, '成功');
        } else {
            UtilApi::getInfo(500, '没有失效的商品');
        }
    }

    /*
     * 记录浏览记录商品
     */

    public function readProduct() {
        $user_token = I('post.user_token');
        $pid = I('post.pid');
        $lotteryId = I('post.lotteryId');
        if (empty($user_token)) {
            UtilApi::getInfo(500, 'user_token不能为空');
            return;
        }
        if (empty($pid)) {
            UtilApi::getInfo(500, '商品id不能为空');
            return;
        }
        if (empty($lotteryId)) {
            UtilApi::getInfo(500, '期号id不能为空');
            return;
        }
        $this->is_login($user_token); //判断是否登录
        $uid = floatval(S($user_token));
        $flag = D("Home/documentProduct")->readProduct($uid, $pid, $lotteryId);
        if ($flag == -1) {
            UtilApi::getInfo(500, '已经记录过该商品');
        } else {
            if ($flag == 1) {
                UtilApi::getInfo(200, '成功');
            } else {
                UtilApi::getInfo(500, '记录失败');
            }
        }
    }

    /*
     * 商品浏览记录列表
     */

    public function readList() {
        $user_token = I('post.user_token');
        $pageIndex = I('post.pageIndex');
        $pageSize = I('post.pageSize');
        if (empty($user_token)) {
            UtilApi::getInfo(500, 'user_token不能为空');
            return;
        }
        if (isset($pageSize)) {
            if ($pageSize == 0) {
                UtilApi::getInfo(500, 'pageSize必须大于0');
                return;
            }
        } else {
            UtilApi::getInfo(500, 'pageSize不能为空');
            return;
        }
        if (isset($pageIndex) == false) {
            UtilApi::getInfo(500, 'pageIndex不能为空');
            return;
        }
        $this->is_login($user_token); //判断是否登录
        $uid = floatval(S($user_token));
        $total = D('Home/documentProduct')->readCount($uid);
        $list = D('Home/documentProduct')->readList($uid, $pageIndex, $pageSize);
        $pageCount = floor($total / $pageSize);
        if ($total % $pageSize > 0) {
            $pageCount = $pageCount + 1;
        }
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pageCount' => $pageCount,
            'list' => $list
        );
        echo json_encode($arr);
    }

    /*
     * 清空失效商品或删除某个失效商品
     */

    public function delRead() {
        $user_token = I('post.user_token');
        $lotteryId = I('post.lotteryId');
        if (empty($user_token)) {
            UtilApi::getInfo(500, 'user_token不能为空');
            return;
        }
        if (empty($lotteryId)) {
            UtilApi::getInfo(500, '期号id不能为空');
            return;
        }
        $this->is_login($user_token); //判断是否登录
        $uid = floatval(S($user_token));
        $flag = M('read')->where(array('uid' => $uid, 'lottery_id' => array('in', $lotteryId)))->delete();
        if ($flag) {
            UtilApi::getInfo(200, '成功');
        } else {
            UtilApi::getInfo(500, '没有失效的商品');
        }
    }

}
