<?php

namespace Wap\Controller;

use Home\Common\ArrayUtil;
use Home\Common\TimeUtil;
use Home\Common\UtilApi;

header("Access-Control-Allow-Origin:*");

/*
 * 关于添加参与记录和开奖相关操作
 * 更新时间 2016.1.19
 */

class LotteryController extends HomeController {
    /*
     *  验证金额是否正常和参与次数是否超过剩余次数和是否超过限制的购买人次
     */

    public function validateAttend() {
        $content = I('post.content'); //购买商品列表
        $user_token = I('post.user_token'); //用户token
        $money = I('post.money'); //商品列表购买总金额
        $balance = I('post.balance'); //用户余额
        $payMoney = I('post.payMoney'); //需要支付的金额
        $payType = I('post.payType'); //支付类型
        $attendDevice = I('post.attendDevice'); //操作来源

        if (empty($user_token)) {
            UtilApi::getInfo(500, "token不能为空");
            return;
        }
        if (empty($money)) {
            UtilApi::getInfo(500, "购买金额不能为空");
            return;
        }
        if ($payType == null) {
            UtilApi::getInfo(500, "支付类型不能为空");
            return;
        }
        if ($payMoney == null) {
            UtilApi::getInfo(500, "实际支付金额不能为空");
            return;
        }
        if ($payMoney < 0) {
            UtilApi::getInfo(500, "支付金额不能小于0");
            return;
        }
        if ($balance == null) {
            UtilApi::getInfo(500, "balance不能为空");
            return;
        }
        if ($attendDevice == null) {
            UtilApi::getInfo(500, "操作来源不能为空");
            return;
        }
        $uid = $this->is_login($user_token); //判断是否登录
        $newContent = str_replace("&quot;", "\"", $content); //需要先替换"&quot;字符，不然不能正常解析
        $array = json_decode($newContent, true);
        $flag = 0;
        $total = 0;
        if (isset($array)) {
            $arrList = new \Org\Util\ArrayList();
            foreach ($array['list'] as $value) {
                $arr = D('Home/lotteryAttend')->validateAttendProduct($value, $uid); //验证购买数量是否有效
                if ($arr['code'] == 503) {
                    $info = $value['name'] . "人次已参加完，请参加新一期";
                    $arrList->add(array('tip' => $info));
                    $flag = -1;
                } else if ($arr['code'] == 504) {
                    $info = $value['name'] . "只剩下" . $arr['info'] . "人次可参加";
                    $arrList->add(array('tip' => $info));
                    $flag = -1;
                } else if ($arr['code'] == 501) {
                    $info = $value['name'] . "每次最低参与人次为" . $arr['info'];
                    $arrList->add(array('tip' => $info));
                    $flag = -1;
                } else if ($arr['code'] == 502) {
                    $info = $value['name'] . "每次最高参与人次为" . $arr['info'];
                    $arrList->add(array('tip' => $info));
                    $flag = -1;
                } else if ($arr['code'] == 505) {
                    $info = $value['name'] . "的商品id与这一期的商品id不一致";
                    $arrList->add(array('tip' => $info));
                    $flag = -1;
                } else if ($arr['code'] == 506) {
                    if ($arr['info'] <= 0) {
                        $info = $value['name'] . "你已达到最多的参与人次，无法再继续参与";
                    } else {
                        $info = $value['name'] . "你最多只能再参与" . $arr['info'] . "人次";
                    }
                    $arrList->add(array('tip' => $info));
                    $flag = -1;
                }
                $total = $total + $value['attend_count'];
            }
            if ($flag == -1) {
                $arr = array(
                    'code' => 500,
                    'info' => '验证失败',
                    'list' => $arrList->toArray()
                );
                echo json_encode($arr);
            } else {
                //验证购买的金额和用户的余额是否正确，防止篡改数据
                $arr = D('Home/lotteryAttend')->valdateBalance($uid, $total, $money, $payMoney, $balance, $payType);
                $arr['list'] = array();

                if ($arr["code"] == 200) {
                    if ($payMoney == 0 && $payType == 0) {//全部余额支付
                        echo json_encode($arr);
                    } else {    //网银支付
                        $pay_token = UtilApi::getToken(); //支付令牌
                        $pay_token = substr($pay_token, 0, 20);
                        $order_number = UtilApi::build_order_no(); //商品订单号
                        $order_name = $uid . 'S购买商品'; //订单名称
                        $WIDre_common_url = C("WIDre_url");
                        $WIDre_url = $WIDre_common_url . "repayfor?pay_token=" . $pay_token; //显回调地址【同步】
                        $payinfo = array(
                            "WIDout_trade_no" => $uid . "S" . $order_number, //存储订单号
                            "WIDsubject" => $order_name, //存储商品名称
                            "WIDtotal_fee" => $payMoney, //存储金额
                            "WIDre_url" => $WIDre_url, //显回调地址
                            "WIDno_url" => $WIDre_url, //隐回调地址【异步】
                            "content" => $content, //购买商品列表
                            "user_token" => $user_token, //用户token
                            "money" => $money, //商品列表购买总金额
                            "balance" => $balance, //用户余额
                            "payMoney" => $payMoney, //需要支付的金额
                            "payType" => $payType, //支付类型
                            "attendDevice" => $attendDevice, //操作来源
                            "uid" => $uid, //存储用户id
                            "os" => 1, //支付设备 1为wap
                            "ip" => get_client_ip(),
                            "pay_type" => 2, //支付类型 1充值，2支付
                            "order_number" => $order_number//存储订单号
                        );
                        S($pay_token, $payinfo, 600); //存储购买信息
                        echo json_encode(array('code' => 200, 'info' => '成功', 'pay_token' => $pay_token));
                    }
                } else {
                    echo json_encode($arr);
                }
            }
        } else {
            UtilApi::getInfo(500, 'content不能为空');
        }
    }

    /*
     * 添加商品参与记录----使用全部余额支付付款到账户
     */

    public function addAttendLottery() {
        $content = I('post.content'); //购买商品列表
        $user_token = I('post.user_token'); //用户token
        $attendDevice = I('post.attendDevice'); //操作来源
        $money = I('post.money'); //商品列表总购买金额
        $balance = I('post.balance'); //用户余额
        $payType = I('post.payType'); //支付类型
        $payMoney = I('post.payMoney'); //实际支付金额
        if (empty($user_token)) {
            UtilApi::getInfo(500, "user_token不能为空");
            return;
        }
        if (empty($money)) {
            UtilApi::getInfo(500, "购买金额不能为空");
            return;
        }
        if ($payType == null) {
            UtilApi::getInfo(500, "支付类型不能为空");
            return;
        }
        if ($payMoney == null) {
            UtilApi::getInfo(500, "实际支付金额不能为空");
            return;
        }
        if ($payMoney < 0) {
            UtilApi::getInfo(500, "支付金额不能小于0");
            return;
        }
        if ($balance == null) {
            UtilApi::getInfo(500, "用户余额不能为空");
            return;
        }
        if ($attendDevice == null) {
            UtilApi::getInfo(500, "操作来源不能为空");
            return;
        }
        $uid = $this->is_login($user_token); //判断是否登录
        $newContent = str_replace("&quot;", "\"", $content);  //需要先替换"&quot;字符，不然不能正常解析
        $array = json_decode($newContent, true);
        $flag = 0;
        $total = 0;
        $order_number = UtilApi::build_order_no(); //订单号
        if ($array != null) {
            $arrList = new \Org\Util\ArrayList();
            $errList = new \Org\Util\ArrayList();
            foreach ($array['list'] as $value) {
                $total = $total + $value['attend_count']; //重新计算参与的总金额，防止数据被篡改
            }
            $model = M('lottery_product');
            $model->startTrans(); //开启事务，确保更新数据一致
            //验证购买的金额和用户的余额是否正确，防止篡改数据
            $bal_arr = D('Home/lotteryAttend')->valdateBalance($uid, $total, $money, $payMoney, $balance, $payType);
            //先扣款
            if ($bal_arr['code'] == 200) {
                if ($payMoney == 0 && $balance > 0) { //选择余额支付，余额充足，用余额支付全额
                    $state = D('Home/lotteryAttend')->plusBalance($uid, $money); //在用户余额上扣除金额
                    if ($state == -1) {
                        UtilApi::getInfo(500, "您的余额不足，请充值或直接支付");
                        return;
                    } else if ($state == 0) {
                        UtilApi::getInfo(500, "扣除余额失败");
                        return;
                    }
                } else { //余额不足，选择部分余额支付，剩余用其他支付方式 
                    UtilApi::getInfo(500, "余额支付失败");
                    return;
                }
            } else {
                echo json_encode($bal_arr);
                return;
            }
            foreach ($array['list'] as $value) {
                $field = 'id,pid,lottery_pid,need_count,attend_count,lucky_code,attend_limit,max_attend_limit,max_per_attend_limit';
                $condition['lottery_id'] = $value['lottery_id']; //期号id
                $lottery = $model->field($field)->where($condition)->select(); //获取该商品这一期的记录
                $remain_count = $lottery[0]['need_count'] - $lottery[0]['attend_count']; //剩余的参与人次
                if ($value['pid'] != $lottery[0]['pid']) { //商品id与这一期实际的商品id不一致
                    $info = $value['name'] . "的商品id与这一期的商品id不一致";
                    $errList->add(array('tip' => $info));
                    $flag = -1;
                    continue;
                }
                if ($lottery[0]['attend_limit'] != 0) {  //判断每次是否有最低购买人次的限制
                    if ($value['attend_count'] < $lottery[0]['attend_limit']) {
                        $info = $value['name'] . "每次最低参与次数不能低于" . $lottery[0]['attend_limit'];
                        $errList->add(array('tip' => $info));
                        $flag = -1;
                        continue;
                    }
                }
                if ($lottery[0]['max_per_attend_limit'] != 0) {   //判断每次是否有最大购买人次的限制
                    if ($value['attend_count'] > $lottery[0]['max_per_attend_limit']) {
                        $info = $value['name'] . "每次最大参与次数不能超过" . $lottery[0]['max_per_attend_limit'];
                        $errList->add(array('tip' => $info));
                        $flag = -1;
                        continue;
                    }
                }
                if ($lottery[0]['max_attend_limit'] != 0) {   //判断总的购买次数是否有最多购买人次的限制
                    $acon = array();
                    $acon['lottery_id'] = $value['lottery_id']; //期号id
                    $acon['uid'] = $uid; //用户id
                    $attend_sum = M('lottery_attend')->where($acon)->sum('attend_count'); //查询用户这一期已经购买了多少人次
                    if (($attend_sum + $value['attend_count']) > $lottery[0]['max_attend_limit']) {
                        $r_count = $lottery[0]['max_attend_limit'] - $attend_sum;
                        if ($r_count <= 0) {
                            $info = $value['name'] . "你已达到最多的参与人次，无法再继续参与";
                        } else {
                            $info = $value['name'] . "你最多只能再参与" . $r_count . "人次";
                        }
                        $errList->add(array('tip' => $info));
                        $flag = -1;
                        continue;
                    }
                }
                if ($remain_count == 0) {   //判断剩余次数是否为0
                    $info = $value['name'] . "已经全部参加完，请参加下一期";
                    $errList->add(array('tip' => $info));
                    $flag = -1;
                    continue;
                }
                if ($value['attend_count'] <= $remain_count) { //参加次数小于或等于剩余次数
                    $lucky_code = $lottery[0]['lucky_code']; //这一期未分配的幸运码
                    $arr_lucky_code = explode(",", $lucky_code); //以,号分割幸运码
                    $c = count($arr_lucky_code);
                    if ($c == 0) { //判断幸运码是否已分配完
                        $info = $value['name'] . "幸运码已分配完";
                        $errList->add(array('tip' => $info));
                        $flag = -1;
                        continue;
                    }
                    $index = $c - 1; //获取最后一位数的位置
                    $keys = ArrayUtil::getRandArray($arr_lucky_code, $value['attend_count']); //获取多个随机数，如果只有一位随机数，则直接返回随机数
                    $attendCode = '';
                    for ($i = 0; $i < $value['attend_count']; $i++) {  //随机获得幸运码和删除已分配的幸运码
                        if ($value['attend_count'] == 1) {
                            $code = $arr_lucky_code[$keys]; //只有一个随机数
                            $attendCode.=$arr_lucky_code[$keys] . ',';
                        } else {
                            $code = $arr_lucky_code[$keys[$i]]; //多个随机数
                            $attendCode.=$arr_lucky_code[$keys[$i]] . ',';
                        }
                        if ($code != $arr_lucky_code[$index]) {
                            $code.=',';
                        } else {
                            $code = ',' . $code; //最后一位数需要把,号加在前面  
                        }
                        $pos = strpos($lucky_code, $code); //获取分配幸运码的位置
                        $lucky_code = substr_replace($lucky_code, '', $pos, strlen($code)); //删除已分配的幸运码
                    }
                    $attendCode = substr($attendCode, 0, -1); //获取这次分配的幸运码，截取掉最后面的,号
                    $ip = get_client_ip(); //获取ip地址
                    $ip_address = UtilApi::getIpLookup($ip); //获取ip地址所在的省和城市
                    $attendTime = TimeUtil::timeStamp(); //参与时间戳
                    $create_date_time = date('Y-m-d H:i:s') . substr(microtime(), 1, 4); //参与时间 显示2016-01-11 11:07:22.123格式 
                    $sfm_time = str_replace('.', '', str_replace(':', '', substr($create_date_time, 11, 12))); //时分秒格式，截取和替换字符串，显示为110722123 
                    $attendArr = array(
                        'lottery_id' => $value['lottery_id'], //期号id
                        'uid' => $uid, //用户id
                        'pid' => $value['pid'], //商品id
                        'attend_count' => $value['attend_count'], //参与次数
                        'lucky_code' => $attendCode, //获取的幸运码
                        'create_time' => $attendTime, //参与时间
                        'create_date_time' => $create_date_time, //参与时间，以2016-01-11 11:07:22.123格式显示 
                        'sfm_time' => $sfm_time, //时分秒格式 11:07:22.123,
                        'attend_ip' => $ip, //参与的ip地址
                        'ip_address' => $ip_address, //ip地址所在的省和城市
                        'attend_device' => $attendDevice, //参与设备
                        'order_number' => $order_number//订单号    
                    );

                    /**
                     * 获取付费info
                     */
                    $attendArr['info'] = getTimeInfo();
                    //获取付费Info结束

                    M('lottery_attend')->add($attendArr); //添加参与记录
                    $data = array();
                    $data['id'] = $lottery[0]['id'];  //期号id 用于更新信息
                    $data['lucky_code'] = $lucky_code; //更新幸运码
                    $data['attend_count'] = $lottery[0]['attend_count'] + $value['attend_count']; //更新参与次数
                    $data['attend_ratio'] = $data['attend_count'] / $lottery[0]['need_count']; //更新参与次数
                    if ($value['attend_count'] == $remain_count) { //参与人次达到总需求人次,进入即将揭晓状态
                        $data['expectTime'] = \Think\Cqssc::getExpectTime();
                        $data['last_attend_time'] = TimeUtil::timeStamp(); //这一期最后参与时间
                        $data['last_attend_date_time'] = $create_date_time; //这一期最后参与时间的时分秒毫秒格式
                        $pdata = array();
                        $pdata['pid'] = $lottery[0]['pid']; //商品id
                        $pdata['lottery_pid'] = $lottery[0]['lottery_pid'] + 1; //该商品已经开了多少期，以1开始
                        $count = $lottery[0]['need_count'];
                        $pdata['need_count'] = $count;  //总需人次
                        $code = '';
                        //根据总需人次，产生对应数量的幸运码，最多到10万
                        for ($i = 1; $i <= $count; $i++) {
                            $code .=$i . ',';
                        }
                        $code = substr($code, 0, -1); //去除最后一个逗号
                        $pdata['lucky_code'] = $code; //新开一期的所有幸运码
                        $pdata['attend_limit'] = $lottery[0]['attend_limit']; //最低参与人次限制
                        $pdata['max_attend_limit'] = $lottery[0]['max_attend_limit']; //最大参与人次限制
                        $pdata['create_time'] = TimeUtil::timeStamp(); //最新新一期的创建时间
                        //exit;
                        $lid = M('lottery_product')->add($pdata);  //添加该商品新一期记录
                        $updata = array();
                        $updata['id'] = $lid; //添加新一期的id
                        $updata['lottery_id'] = C('START_CODE') + $lid; //新一期期号
                        M('lottery_product')->save($updata); //更新新一期的期号
                    }
                    if ($model->save($data)) { //更新这一期对应的更新信息
                        $id = $value['id']; //购物车id
                        M('shopcart')->where("id=$id")->delete(); //删除购物车信息
                    }
                    $attendInfo = array('code' => '0', 'name' => $value['name'], 'lottery_id' => $value['lottery_id'],
                        'pid' => $value['pid'], 'attendCode' => $attendCode, 'attend_count' => $value['attend_count'], 'attend_time' => $create_date_time);
                    $info = $value['name'] . '获取幸运码成功';
                    $arrList->add(array('tip' => $info, 'attendCode' => $attendInfo));
                } else {  //剩余次数少于参与次数，无法参与
                    $info = $value['name'] . "只剩下" . $remain_count . "人次可参加";
                    $errList->add(array('tip' => $info));
                    $flag = -1;
                }
                $total = $total + $value['attend_count'];
            };
            if ($flag == -1) { //有错误，无法添加参与记录
                $model->rollback(); //不更新所有操作数据
                $tiparr = $errList->toArray();
                $k = count($tiparr) - 1;
                $tiparr = $tiparr[$k]["tip"];
                // dump($tiparr);
                $arr = array(
                    'code' => 500,
                    'info' => $tiparr,
                    'errList' => $errList->toArray()
                );
            } else {
                $model->commit(); //提交事务，更新所有操作数据
                //由于没提交事务，导致获取全站最近50条记录的时间之和数据记录不正确，所以等提交完事务后再获取全站最近50条记录的时间之和和记录
                foreach ($array['list'] as $value) {
                    $lp_model = M('lottery_product');
                    $field1 = 'id,need_count,attend_count,last_attend_time';
                    $con1['lottery_id'] = $value['lottery_id'];
                    $lottery1 = $lp_model->field($field1)->where($con1)->select(); //获取该商品这一期的记录
                    if ($lottery1[0]['need_count'] == $lottery1[0]['attend_count']) {
                        $data1 = array();
                        $data1['id'] = $lottery1[0]['id'];
                        $data1['total_time'] = D('Home/lotteryAttend')->getLastAttendTotalTime($lottery1[0]['last_attend_time']);  //获取全站最近50条记录的时间之和
                        $lp_model->save($data1); //更新这一期的全站最近50条记录的时间之和
                    }
                }
                $arr = array(
                    'code' => 200,
                    'info' => '成功',
                );
                S(array('expire' => 60)); //设置刚购买的幸运码记录缓存有效期60秒，以秒为单位
                S($uid, $arrList->toArray()); //用户id，不能使用该token保存数据，该token已经保存了用户id
            }
            echo json_encode($arr);
        } else {
            UtilApi::getInfo(500, "content不能为空");
            echo json_encode($arr);
        }
    }

    /*
     * 获取用户刚刚参与的幸运码，有效期为60s
     */

    function attendLottery() {
        $user_token = I('post.user_token');
        if (empty($user_token)) {
            UtilApi::getInfo(500, "token不能为空");
            return;
        }
        $uid = floatval(S($user_token));
        //热门推荐
        $hotProduct = D('Home/lotteryProduct')->getHotList(4);
        if (S($uid)) {
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'startCode' => C('START_CODE'),
                'list' => S($uid),
                'hotProduct' => $hotProduct
            );
        } else {
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'startCode' => C('START_CODE'),
                'list' => array(),
                'hotProduct' => $hotProduct
            );
        }
        echo json_encode($arr);
    }

    /*
     * 揭晓幸运码
     */

    public function lotteryResult() {
        D('Home/lotteryAttend')->lotteryResult();
    }

    /*
     * 如遇福彩中心通讯故障，无法获取上述期数的中国福利彩票“老时时彩”开奖结果，
     * 且24小时内该期“老时时彩”开奖结果仍未公布，则默认“老时时彩”开奖结果为00000
     * 用于特殊情况下手动揭晓幸运码,
     * 为了保险起见执行此程序前需要在测试服务器测试通过后再执行
     */

    public function executelotteryResult() {
        D('Home/lotteryAttend')->lotteryResultNoCqssc();
    }

}
