<?php

namespace Home\Controller;

use Home\Common\UtilApi;

header("Access-Control-Allow-Origin:*");

/**
 * 用户中心-晒单模块
 * 晒单列表、添加晒单、晒单详情、评论
 * Author: joan
 */
class DisplayController extends HomeController {
    /* 用户中心-晒单记录 */

    public function orderRecord($uid = '') {
        $uid = I('post.uid');
        $pageSize = I('post.pageSize'); //第几页，从0开始
        $pageIndex = I('post.pageIndex');
        $soso = intval(I('post.soso'));
        $state = intval(I("post.state"));
        $user_token = I("post.user_token");
        if (empty($uid) && empty($user_token)) {
            UtilApi::getInfo(500, 'uid或user_token不能为空');
            return;
        }
        if ($uid < 1) {//查看自己的参与记录
            $uid = $this->is_login($user_token);
        }
        if ($soso == '5') {
            A("ucenter")->sosoTimeCheck($soso);
        }
        $transForm = A("ucenter")->transForm($soso, $start_Time, $end_Time);
        $timeStart = $transForm[0];
        $timeEnd = $transForm[1]; //dump($transForm);
        if ($uid > 0) {
            $pageSize = I('post.pageSize'); //第几页，从0开始
            $pageIndex = I('post.pageIndex'); //每页数量
            $total = D('DocumentProduct')->orderRecordCount($uid, $pageIndex, $pageSize, $timeStart, $timeEnd, $state); //dump($total);
            $pageCount = UtilApi::getPage($pageSize, $total);
            $list = D('DocumentProduct')->orderRecord($uid, $pageIndex, $pageSize, $timeStart, $timeEnd, $state);
            $picarr = array();
            foreach ($list as $k => $v) {
                if (!empty($list[$k]["path"])) {
                    $picarr[$k] = explode(',', $list[$k]["path"]);
                    $list[$k]["path"] = $picarr[$k];
                }
            }
            $arr = array('code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'pic_host' => C('PICTURE'),
                'startCode' => C('START_CODE'),
                'pageCount' => $pageCount,
                'list' => $list);
            echo json_encode($arr);
        } else {
            echo json_encode(array('code' => 504, 'info' => "用户名不存在"));
            exit;
        }
    }

    /* 个人中心-添加晒单 */

    public function orderAdd() {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $id = floatval(I("post.id"));
        $title = I("post.title");
        $description = I("post.description"); //dump($_POST);exit;
        if ($id < 1) {
            UtilApi::getInfo(500, 'id不能为空');
            exit;
        }
        if (empty($title)) {
            UtilApi::getInfo(500, '标题不能为空');
            exit;
        }
        if (empty($description)) {
            UtilApi::getInfo(500, '内容不能为空');
            exit;
        }
        $picture = M("display_product")->field('pics')->where('id=' . $id)->find();
        $pics = $picture["pics"];
        if (empty($pics)) {
            UtilApi::getInfo(500, '图片不能为空,请先上传图片');
            return;
        }
        $data["title"] = $title;//晒单标题
        $data["description"] = $description;//晒单描述
        $data["apply_time"] = date("Y-m-d H:i:s", time());//晒单时间
        $data["score"] = 100;//晒单积分
        $data["type"] = 1; //未审核
        $data["status"] = 3; //审核中
        $orderid = M("display_product")->where('id=' . $id)->save($data); //插入数据库
        if ($orderid) {
            echo json_encode(array('code' => 200, 'info' => '成功', 'host' => C('HOST'), 'path' => $data));
        } else {
            UtilApi::getInfo(523, '添加失败');
        }
    }

    /* 晒单详情--添加评论 */

    public function commentAdd() {
        if (!empty($_POST["uid"]) && !empty($_POST["content"]) && !empty($_POST["dpid"])) {
            $data["content"] = I('post.content');
            $data["create_time"] = time();
            $data["uid"] = floatval(I('post.uid'));
            $data["dpid"] = floatval(I('post.dpid'));
            if ($data["uid"] < 1 or $data["dpid"]) {
                UtilApi::getInfo(500, 'id不能为空');
            }
            $contentid = M("comment")->data($data)->add();
            if ($contentid > 0) {
                echo json_encode(array('code' => 200, 'info' => '成功'));
            } else {
                echo json_encode(array('code' => 523, 'info' => '添加失败'));
            }
        } else {
            echo json_encode(array('code' => 521, 'info' => '评论内容不能为空'));
        }
    }

    /*
     * 查看他人晒单--晒单详情
     * 
     */

    public function orderInfo() {
        $id = floatval(I('post.dpid'));
        $order_info = D('DocumentProduct')->orderInfo($id);
        $pics = $order_info["pics"];
        $path = explode(',', $pics);
        echo json_encode(array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'order_info' => $order_info));
    }

    /*
     * 查看他人晒单--评论列表
     * 
     */

    public function commentInfo() {
        $id = floatval(I('post.dpid'));
        $pageSize = floatval(I('post.pageSize')); //每页数量
        $pageIndex = floatval(I('post.pageIndex')); //第几页，从0开始
        $total = M("comment")->where('did=' . $id)->count(); //echo $total;
        $pageCount = UtilApi::getPage($pageSize, $total);
        $comment = D('DocumentProduct')->commentInfo($id, $pageIndex, $pageSize);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'startCode' => C('START_CODE'),
            'pageCount' => $pageCount,
            'list' => $comment);
        echo json_encode($arr);
    }

    /*
     * 晒单信息修改
     */

    public function orderUp() {
        $id = floatval(I('post.dpid')); //晒单id
        $order_info = M("display_product")->field('title,description,pics as path')->where('id=' . $id)->find(); //取出晒单信息
        $pics = $order_info["path"];
        if (!empty($pics)) {
            $picarr = array();
            $picarr = explode(',', $pics); //将图片字符串装入数组
            $order_info["path"] = $picarr;
        }
        echo json_encode(array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'order_info' => $order_info));
    }

    /* 删除晒单图片 */

    public function pictureDel() {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $id = floatval(I('post.dpid')); //晒单id
        $pic = I('post.pic'); //图片字符串
        $order_info = M("display_product")->field('pics')->where('id=' . $id)->find(); //取出晒单信息
        $pics = $order_info["pics"];
        if (!empty($id) && !empty($pic)) {
            if (preg_match("/,/i", $pics)) {
                $picarr = array();
                $picarr = explode(',', $pics); //将图片字符串装入数组
                $order_info["pics"] = $picarr; //dump($pic);dump($order_info["pics"][0]);
                foreach ($order_info["pics"] as $k => $v) {
                    if ($order_info["pics"][$k] == $pic) {
                        unset($order_info["pics"][$k]);
                    }
                }
                $path = implode(",", $order_info["pics"]);
            } else {
                $path = '';
            }
            $data["pics"] = $path;
            $orderid = M("display_product")->where('id=' . $id)->save($data); //取出晒单信息
            if ($orderid > 0) {
                UtilApi::getInfo(200, '删除成功');
            } else {
                UtilApi::getInfo(518, '删除失败');
            }
        } else {
            UtilApi::getInfo(500, '晒单id或图片不能为空');
            return;
        }
    }

}
