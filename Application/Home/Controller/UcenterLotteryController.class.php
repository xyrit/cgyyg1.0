<?php

namespace Home\Controller;

use Home\Common\UtilApi;

header("Access-Control-Allow-Origin:*");

/**
 * 个人中心
 * 中奖纪录
 * Author: joan
 */
class UcenterLotteryController extends HomeController {
    /* 用户中心-中奖纪录 */

    public function lotteryRecord() {
        $uid = intval(I('post.uid'));
        $soso = intval(I('post.soso'));
        $state = intval(I("post.state"));
        if ($uid < 1) {
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token);
        }
        $pageSize = I('post.pageSize'); //第几页，从0开始
        $pageIndex = I('post.pageIndex');

        if ($soso == '5') {
            A("ucenter")->sosoTimeCheck($soso);
        }

        $transForm = A("ucenter")->transForm($soso, $start_Time, $end_Time);
        $timeStart = $transForm[0];
        $timeEnd = $transForm[1]; //dump($transForm);
        if ($uid > 0) {
            $total = D('DocumentProduct')->lotteryRecordCount($uid, $pageSize, $pageIndex, $timeStart, $timeEnd, $state); //echo $total;
            $pageCount = UtilApi::getPage($pageSize, $total);
            $list = D('DocumentProduct')->lotteryRecord($uid, $pageSize, $pageIndex, $timeStart, $timeEnd, $state);
            $arr = array('code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'startCode' => C('START_CODE'),
                'pageCount' => $pageCount,
                'list' => $list);
            echo json_encode($arr);
        } else {
            echo json_encode(array('code' => 504, 'info' => "用户名不存在"));
            exit;
        }
    }

    /*
     * 中奖纪录--确认收货
     * 
     */

    public function delivery() {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $id = floatval(I("post.id"));
        $pid = floatval(I("post.pid"));
        $lottery_id = floatval(I("post.lottery_id"));
        if (($lottery_id > 0) && ($pid > 0) && ($uid > 0) && ($id > 0)) {
            $data["uid"] = $uid;
            $data["pid"] = $pid;
            $data["win_id"] = $id;
            $data["lottery_id"] = $lottery_id;
            $data["type"] = 0;
            $data["apply_time"] = date("Y-m-d H:i:s", time()); //晒单时间
            $orerid = M("display_product")->data($data)->filter('strip_tags')->add(); //插入一条空的晒单记录
            $condition["status"] = '3'; //更改物流状态
            $state = M("win_prize")->where('id=' . $id)->save($condition); //更改签收的物流状态
            if ($orerid > 0) {
                UtilApi::getInfo(200, '成功');
                return;
            } else {
                UtilApi::getInfo(523, '添加失败');
                return;
            }
        } else {
            UtilApi::getInfo(500, '商品id、期号、中奖id都不能为空');
        }
    }

}
