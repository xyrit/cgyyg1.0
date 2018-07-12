<?php

namespace Home\Controller;

use Home\Common\UtilApi;

header("Access-Control-Allow-Origin:*");

/**
 * 个人中心
 * 参与明细- 进行中、即将揭晓、已揭晓商品
 * Author: joan
 */
class AttendController extends HomeController {
    /* 参与明细- 进行中、即将揭晓、已揭晓商品 */

    public function attendDetail() {
        $uid = floatval(I('post.uid'));
        $pageSize = I('post.pageSize'); //第几页，从0开始
        $pageIndex = I('post.pageIndex');
        $soso = intval(I('post.soso'));
        $state = intval(I("post.state"));
        if ($uid < 1) {//判断是否登陆
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token);
        }
        if ($soso == '5') {//时间段搜索
            A("ucenter")->sosoTimeCheck($soso);
        }
        $transForm = A("ucenter")->transForm($soso, $start_Time, $end_Time); //转换时间戳
        $timeStart = $transForm[0];
        $timeEnd = $transForm[1]; //dump($transForm);
        if ($uid > 0) {
            $pageSize = I('post.pageSize'); //每页数量
            $pageIndex = I('post.pageIndex'); //第几页，从0开始
            $total = D('DocumentProduct')->getAttendCount($uid, $pageIndex, $pageSize, $timeStart, $timeEnd, $state); //获取总记录echo $total;
            $pageCount = UtilApi::getPage($pageSize, $total); //获取分页
            $result = D('DocumentProduct')->attendNowList($uid, $pageIndex, $pageSize, $timeStart, $timeEnd, $state); //获取商品所有信息
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'startCode' => C('START_CODE'),
                'pageCount' => $pageCount,
                'list' => $result);
            echo json_encode($arr);
        } else {
            echo json_encode(array('code' => 504, 'info' => "用户名不存在"));
            exit;
        }
    }

    /*
     * 根据期号、用户id查询当前用户的参与时间、参与码集合
     */

    public function getAttendInfo() {
        $uid = floatval(I('post.uid')); //用户id
        $lottery_id = floatval(I('post.lottery_id')); //期号id
        if ($uid > 0 && $lottery_id > 0) {
            $result = D('DocumentProduct')->getAttendInfo($uid, $lottery_id); //获取用户的参与时间、参与码集合
            $list = $result["list"];//dump($list);
            $sum = $result["sum"];
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'sum' => $sum,
                'list' => $list);
            echo json_encode($arr);
        } else {
            echo json_encode(array('code' => 500, 'info' => "用户id或期号lottery_id不能为空"));
            exit;
        }
    }

}
