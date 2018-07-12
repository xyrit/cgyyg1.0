<?php

namespace Home\Controller;

use Home\Common\UtilApi;

header("Access-Control-Allow-Origin:*");

/**
 * 用户管理中心
 * 包括我的账户、个人资料修改、设置
 * Author: joan
 */
class UcenterController extends HomeController {
    /* 个人中心
     * 搜索开始时间、结束时间
     * 为空判断
     */

    public function sosoTimeCheck() {
        $start_Time = I("post.startTime");
        $end_Time = I("post.endTime");
        if (empty($start_Time) || empty($end_Time)) {
            UtilApi::getInfo(516, '搜索时间不能为空');
            exit;
        }
    }

    /* 个人中心-搜索时间转换 */

    public function transForm($soso, $start_Time = '', $end_Time = '') {
        $list = array();
        switch ($soso) {
            case 0:
                break;
            case 1:
                $timeStart = strtotime(date("Y-m-d ", time()));
                $timeEnd = strtotime(date("Y-m-d 24:00:00", time()));
                break;
            case 2:
                $timeStart = strtotime(date('Y-m-d 24:00:00', strtotime('last Sunday')));
                $timeEnd = strtotime(date('Y-m-d 24:00:00', strtotime('Sunday')));
                break;
            case 3:
                $timeStart = strtotime(date('Y-m-01 00:00:00', time()));
                $timeEnd = strtotime(date('Y-m-d H:i:s', time()));
                break;
            case 4:
                $timeStart = strtotime(date('Y-m-01 00:00:00', strtotime('-2 month')));
                $timeEnd = strtotime(date('Y-m-d 24:00:00', strtotime('Sunday')));
                break;
            case 5:
                $timeStart = strtotime($start_Time);
                $timeEnd = strtotime($end_Time);
                break;
        }
        $list[0] = $timeStart;
        $list[1] = $timeEnd;
        return $list;
    }

    /* 检查页码是否为空 */

    public function pageSizeCheck($pageSize = '') {
        if ($pageSize == 0) {
            $arr = array(
                'code' => 500,
                'info' => 'pageSize必须大于0'
            );
            echo json_encode($arr);
            exit;
        }
    }

    /* 个人中心-资金明细 */

    public function moneyRecords($uid = '', $pageSize = '', $pageIndex = '', $timeStart = '', $timeEnd = '', $state = '') {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $state = I('post.state');
        $pageSize = intval(I('post.pageSize')); //每页数量
        $pageIndex = intval(I('post.pageIndex')); //第几页，从0开始
        $soso = intval(I('post.soso'));
        if ($soso == '5') {
            A("ucenter")->sosoTimeCheck($soso);
        }
        $transForm = A("ucenter")->transForm($soso, $start_Time, $end_Time); //今天本周日期转时间戳
        $timeStart = $transForm[0];
        $timeEnd = $transForm[1];
        $total = D("LotteryAttend")->rechargeRecordsCount($uid, $pageIndex, $pageSize, $state, $timeStart, $timeEnd); //echo $total;
        $pageCount = UtilApi::getPage($pageSize, $total);
        //$buyRecords = ($state === '1') ? D("LotteryAttend")->rechargeRecords($uid, $pageIndex, $pageSize, $state, $timeStart, $timeEnd) : D("LotteryAttend")->buyRecords($uid, $pageIndex, $pageSize, $timeStart, $timeEnd); //1充值，0消费
        $buyRecords = D("LotteryAttend")->rechargeRecords($uid, $pageIndex, $pageSize, $state, $timeStart, $timeEnd);
        $arr = array('code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pageCount' => $pageCount,
            'list' => $buyRecords);
        echo json_encode($arr);
    }

    /* 个人中心-获取用户个人信息 */

    public function userInfo() {
        if (IS_POST && (!empty($_POST))) {
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token);
            $userInfo = D("Member")->getOne($uid);
            $userInfo["phost"] = C('PICTURE');
            echo json_encode(array('code' => 200, 'info' => '成功', 'host' => C('HOST'), 'list' => $userInfo));
        } else {
            UtilApi::getInfo(517, '请登录后再操作');
        }
    }

    /* 个人中心-我的账户 */

    public function MyAccount() {
        if (IS_POST && (!empty($_POST))) {
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token);
            $userInfo = D("Member")->getOne($uid);
            $list['account'] = $userInfo["account"];
            $list['red_packet'] = $userInfo["red_packet"];
            $list['brokerage'] = $userInfo["brokerage"];
            $list['score'] = $userInfo["score"];
            echo json_encode(array('code' => 200, 'info' => '成功', 'host' => C('HOST'), 'list' => $list));
        } else {
            UtilApi::getInfo(517, '请登录后再操作');
        }
    }

    /* 个人中心-个人资料修改 */

    public function userInfoUp() {
        if (IS_POST && (!empty($_POST["nickname"]))) {
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token);
            $nickname = I('post.nickname');
            $pcre_name = "/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_]{1,8}$/u";
            if (!preg_match($pcre_name, $nickname)) {//匹配用户名、昵称
                UtilApi::getInfo(500, '昵称必须是1-8位的字母、数字、汉字或下划线组成');
                exit;
            }
            $sex = I('post.sex');
            $birthday = I('post.birthday');
            $qq = I('post.qq');
            $data['nickname'] = $nickname;
            $data['sex'] = intval($sex);
            $data['birthday'] = $birthday;
            $data['qq'] = $qq;
            if (!empty($qq)) {
                if (!preg_match('/^[1-9][0-9]{4,9}$/', $qq)) {//匹配qq号
                    UtilApi::getInfo(500, '请输入正确的QQ号');
                    exit;
                }
            }
            $birthdays = strtotime($data['birthday']);
            $last_time = M("Member")->field('update_time,birthday')->where('uid=' . $uid)->find();
            $old_birthday = $last_time["birthday"];
            $last_time = $last_time["update_time"];
            if ((strlen($old_birthday) == '10') && ($old_birthday != $birthday)) {
                if ($data['update_time'] - $last_time < 360 * 24 * 3600) {//控制生日修改
                    UtilApi::getInfo(500, '出生日期一年只能修改一次，谨慎填写');
                    exit;
                }
                if ($birthdays > $data['update_time']) {
                    UtilApi::getInfo(500, '出生日期不能超过当前时间');
                    exit;
                }
                $data['update_time'] = time();
            }
            $saveState = D("member")->userInfoSave($uid, $data);
            $saveState = $saveState == '0' ? 1 : $saveState;
            $this->saveState($saveState);
        } else {
            UtilApi::getInfo(500, '昵称不能为空');
            exit;
        }
    }

    /* 获取用户头像 */

    public function upPhoto($sessionid = '') {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $path = M("Member")->field('face')->where('uid=' . $uid)->find();
        $path = $path["face"];
//        if (!empty($path)) {//判断数据库是否存在
//            $path = C('PICTURE') . "/" . $path;
//        }
        echo json_encode(array('code' => 200, 'info' => '成功', 'host' => C('HOST'), 'pic_host' => C('PICTURE'), 'path' => $path));
    }

    /* 修改手机号 */

    public function upMobile($sessionid = '') {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $mobile = D("Member")->getField($uid, 'mobile'); //取出手机号
        $Sms = A("Sms")->index($mobile); //调用短信接口
        if ($Sms) {
            $data['sessionid'] = $sessionid;
            $data['mobile'] = $sessionid;
            $data['verify'] = $Sms['verify'];
            $data['register_time'] = $Sms['register_time'];
            $saveState = D("session")->sessionSave($sessionid, $data); //修改手机号
            $this->saveState($saveState);
        }
    }

    /* 修改密码 */

    public function upPassword() {
        $oldpassword = md5(I('post.oldpassword')); //原始密码
        $newpassword = I('post.newpassword'); //新密码
        $renewpassword = I('post.renewpassword'); //重复新密码
        $status = intval(I("post.status"));
        if ($status == '1') {
            $cellphone = floatval(I("post.cellphone"));
            $this->checkMobile($cellphone, 1);
            if (empty($newpassword) || empty($renewpassword)) {//判断密码是否为空
                UtilApi::getInfo(514, '密码不能为空');
                exit;
            }
            if ($newpassword != $renewpassword) {//两次密码是否相同
                echo json_encode(array('code' => 502, 'info' => '密码和重复密码不一致'));
                exit;
            } else {
                $data['password'] = md5($newpassword);
                $saveState = M("member")->where('mobile=' . $cellphone)->save($data);
                echo json_encode(array('code' => 200, 'info' => '成功'));
                exit;
                $this->saveState($saveState);
            }
        } else {
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token);
            $user = D("member")->getField($uid, 'password');
            $password = $user['password'];
            if ($password != $oldpassword) {
                echo json_encode(array('code' => 501, 'info' => '密码不正确'));
                exit;
            } else {
                if ($newpassword != $renewpassword) {
                    echo json_encode(array('code' => 502, 'info' => '密码和重复密码不一致'));
                    exit;
                } else {
                    $data['password'] = md5($newpassword);
                    $saveState = D("member")->userInfoSave($uid, $data);
                    //$this->saveState($saveState);
                    echo json_encode(array('code' => 200, 'info' => '成功'));
                }
            }
        }
    }

    /* 修改状态 */

    public function saveState($saveState = '') {
        if ($saveState > 0) {
            echo json_encode(array('code' => 200, 'info' => '成功'));
        } else {
            echo json_encode(array('code' => 524, 'info' => '修改失败'));
        }
    }

    /* 我的签到--检测是否签到 */

    public function sign() {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        if (S($uid) == '1') {
            UtilApi::getInfo(500, '你已经签到');
            //S($uid, 'null');//清空签到记录
        } else {
            UtilApi::getInfo(530, '未签到');
        }
    }

    /* 我的签到--签到 */

    public function signAdd() {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        if (S($uid) <> '1') {//判断是否签到
            $data['uid'] = $uid;
            $data['status'] = '1';
            $data['sign_time'] = time();
            $signid = M("sign")->data($data)->add(); //插入签到记录
            if ($signid > 0) {
                echo json_encode(array('code' => 200, 'info' => '成功'));
                $time = time(); //当前时间
                $deadline = strtotime(date("Y-m-d 24:00:00", time())); //截止时间
                $limit_time = $deadline - $time;
                S(array('expire' => $limit_time)); //设置缓存有效期1天，以秒为单位
                S($uid, $data['status']); //  dump(S($uid));
                //添加积分表记录
                $condition["score"] = 10;
                $condition["type"] = '1';
                $condition["serial_number"] = time();
                $condition["get_time"] = time();
                $condition["uid"] = $uid;
                M('score_get')->data($condition)->add();

                //更改用户表积分总额
                $score = D("member")->getField($uid, 'score');
                $score = $score["score"];
                $data["score"] = intval($score) + intval($condition["score"]);
                M("member")->where('uid=' . $uid)->save($data);
            } else {
                echo json_encode(array('code' => 527, 'info' => '签到失败'));
            }
        } else {
            echo json_encode(array('code' => 500, 'info' => '你已经签到'));
            //S($uid, 'null');//清空签到记录
        }
    }

    /* 我的红包 */

    public function redPacket($uid = '', $pageSize = '', $pageIndex = '', $timeStart = '', $timeEnd = '', $state = '') {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $pageSize = I('post.pageSize'); //第几页，从0开始
        $pageIndex = I('post.pageIndex'); //每页数量
        $state = I('post.state');
        $total = M("red_packet")->where('uid=' . $uid . ' and status=' . $state)->count();
        $pageCount = UtilApi::getPage($pageSize, $total);
        if ($uid > 0) {
            $list = M("red_packet")->where('uid=' . $uid . ' and status=' . $state)->select();
            $listold = $list[0];
            $status = $listold["status"];
            $time_end = $listold["time_end"];
            if ($status < '1') {
                $data["status"] = ($time_end < time()) ? 0 : 2;
                M("red_packet")->where('uid=' . $uid . ' and status=' . $state)->save($data);
            }
            $list = D("ScoreGet")->redPacket($uid, $pageSize, $pageIndex, $timeStart, $timeEnd, $state);
            echo json_encode(array('code' => 200, 'info' => '成功', 'host' => C('HOST'), 'pageCount' => $pageCount, 'redPacket' => $list));
        } else {
            UtilApi::getInfo(504, '用户名不存在');
            return;
        }
    }

    /* 个人中心-充值 */

    public function recharge() {
        $user_token = I("post.user_token"); //获取user_token令牌
        $uid = $this->is_login($user_token); //判断是否登陆
        $charge_type = I('post.charge_type'); //充值类型，暂不需要
        $money = floatval(I('post.money')); //充值金额
        if (!(preg_match("/^[1-9]\d*$/", $money)) || $money > 10000) {//匹配充值金额
            UtilApi::getInfo(500, '充值金额不能大于￥10000，请输入1-10000的正整数!');
            exit;
        }
        if (($_SERVER['SERVER_NAME'] == 'test.cgyyg.com') && ($money == 1000)) {
            $money = 0.50;
        }
        /*
         * 跳转支付页面  
         */
        $order_number = UtilApi::build_order_no(); //订单号
        $order_name = $uid . 'S充值橙果币'; //订单名称
        $charge_token = UtilApi::getToken(); //支付缓存令牌
        $charge_token = substr($charge_token, 0, 20);
        $WIDre_common_url = C("WIDre_url");
        $WIDre_url = $WIDre_common_url . "recharge?pay_token=" . $charge_token; //显回调地址【同步】
        $payinfo = array(
            "WIDout_trade_no" => $uid . "S" . $order_number, //存储订单号
            "WIDsubject" => $order_name, //存储商品名称
            "WIDtotal_fee" => $money, //存储金额
            "WIDre_url" => $WIDre_url, //显回调地址
            "WIDno_url" => $WIDre_url, //隐回调地址【异步】
            "uid" => $uid, //存储用户id
            "os" => 2, //支付设备 1为wap
            "pay_type" => 1, //支付类型 1充值，2支付
            "order_number" => $order_number//存储订单号
        );
        S($charge_token, $payinfo, 600); //存储支付信息
        echo json_encode(array('code' => 200, 'info' => '成功', 'charge_token' => $charge_token));
    }

    /* 个人中心-充值测试 */

//
//    public function recharges() {
//        $user_token = I("post.user_token"); //获取user_token令牌
//        $uid = $this->is_login($user_token); //判断是否登陆
//        $charge_type = I('post.charge_type'); //充值类型，暂不需要
//        $money = floatval(I('post.money')); //充值金额
//
//        if (!(preg_match("/^[1-9]\d*$/", $money)) || $money > 10000) {//匹配充值金额
//            UtilApi::getInfo(500, '充值金额不能大于￥10000，请输入1-10000的正整数!');
//            exit;
//        }
//        $data['uid'] = $uid; //用户id
//        $data['status'] = 1; //充值状态，1完成
//        $data['create_time'] = time(); //创建时间
//        $data['charge_type'] = 1; //充值类型
//        $data['charge_number'] = date("YmdHis"); //充值流水号
//        $data['money'] = $money; //金额
//        $data['info'] = getTimeInfo(); //后台统计用
//
//        $rechargeid = M("recharge")->data($data)->add(); //添加到充值表
//        S($pay_token, 'null'); //清空缓存
//        $result = M("member")->field('account')->where('uid=' . $uid)->find(); //查找当前用户账户余额
//        $account = $result["account"];
//        $total_account = $money + $account;
//        $condition["account"] = $total_account;
//        $total = M("member")->where('uid=' . $uid)->save($condition); //更新账户余额
//        //充值获得提成
//        A("Commission")->saveCommission($uid, $money);
//        if ($rechargeid > 0) {
//            echo json_encode(array('code' => 200, 'info' => '充值成功'));
//        } else {
//            echo json_encode(array('code' => 523, 'info' => '添加失败'));
//        }
//    }

    /*
     * 查询当前用户论坛积分信息
     */

    public function score_info() {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $info = M("member")->field('uc_uid,score')->where('uid=' . $uid)->find();
        $score_total = $info["score"]; //累计总积分
        $uc_uid = $info["uc_uid"]; //ucenter对应的uid
        $score_remain = D("member")->get_discuz_score($uc_uid); //discuz剩余积分
        $score_remain = floatval($score_remain);
        if ($score_total < 1) {
            $data["score"] = floatval($score_remain);
            M("member")->where('uid=' . $uid)->save($data);
        } else {
            $data["score"] = floatval($score_total) + $score_remain;
        }
        $score_expense = D("member")->score_expense($uid); //积分消费
        $score_expense = floatval($score_expense);
        echo json_encode(array('code' => 200, 'info' => '成功', 'score_total' => $data["score"], 'score_expense' => $score_expense, 'score_remain' => $score_remain));
    }

    /*
     * 积分兑换橙果币
     */

    public function score_exchange() {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $score = floatval(I("post.score_remain")); //积分    
        $info = M("member")->field('account,score')->where('uid=' . $uid)->find();
        $account = $info["account"]; //账户橙果币
        $score_num = $info["score"]; //账户积分
        $uc_uid = D("member")->get_uc_uid($uid); //获取ucenter同步的uc_uid
        $score_num = D("member")->get_discuz_score($uc_uid); //查询论坛积分总数
        $score_level = '200'; //积分兑换等级
        if (($score < '200') || $score_num < 200) {
            UtilApi::getInfo(500, '最低200积分才可以兑换');
            return;
        }
        //$coin = floor($score / $score_level); //可兑换的橙果币
        $coin = 1;
        $coin_remain = $coin * 200; //兑换的积分
        $score_remain = $score - $coin_remain; //剩余积分
        $account = $account + $coin; //兑换后的橙果币总数


        $data["account"] = $account;
        D("member")->save_discuz_score($uc_uid, $score_remain); //更新论坛的积分余额
        $code = M("member")->where('uid=' . $uid)->save($data); //更新数据库账户余额
        $data["type"] = 1;
        $data["score"] = $coin_remain;
        $data["serial_number"] = date("mdHis") . rand(101, 999);
        $data["use_time"] = date("Y-m-d H:i:s", time());
        $data["uid"] = $uid;
        M("score_use")->add($data);
        if ($code > 0) {
            echo json_encode(array('code' => 200, 'info' => '成功', 'coin' => $coin, 'score_remain' => $score_remain));
        } else {
            UtilApi::getInfo(500, '兑换失败');
            return;
        }
    }

    /* 获取他人用户信息 */

    public function getOtherInfo() {
        $uid = floatval(I("post.uid"));
        if ((!empty($uid)) && $uid > 0) {
            $userInfo = D("Home/Member")->getField($uid, 'nickname,sex,face');
            echo json_encode(array('code' => 200, 'info' => '成功', 'host' => C('HOST'), 'pic_host' => C('PICTURE'), 'list' => $userInfo));
        } else {
            UtilApi::getInfo(500, 'uid不能为空');
        }
    }

}
