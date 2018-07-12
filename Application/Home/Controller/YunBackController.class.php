<?php

namespace Home\Controller;

use Home\Common\ArrayUtil;
use Home\Common\TimeUtil;
use Home\Common\UtilApi;

require_once("../cgyyg1.0/Application/Addons/yunpay/yun.config.php");
require_once("../cgyyg1.0/Application/Addons/yunpay/yun_md5.function.php");
header("Access-Control-Allow-Origin:*");

/**
 * 
 * 云支付
 * Author: joan
 */
class YunBackController extends HomeController {
    /*
     * 云支付配置参数
     */

    public function pay_config() {
        A("Home/Log")->setlog("pay_config", __LINE__, __METHOD__);
        $yun_config['partner'] = C('yun_partner'); //合作身份者id
        $yun_config['key'] = C('yun_key'); //安全检验码
        $seller_email = C('yun_email'); //云会员账户（邮箱）
        //计算得出通知验证结果
        $yunNotify = md5Verify($_REQUEST['i1'], $_REQUEST['i2'], $_REQUEST['i3'], $yun_config['key'], $yun_config['partner']);
        return $yunNotify;
    }

    /*
     * 云支付公用部分
     * 
     */

    public function pay_common($pay_token = '') {
        $yunNotify = $this->pay_config();
        A("Home/Log")->setlog("pay_config", __LINE__, __METHOD__);
        if ($yunNotify) {//验证成功           
            $out_trade_no = $_REQUEST['i2']; //商户订单号      
            $trade_no = $_REQUEST['i4']; //云支付交易号           
            $yunprice = $_REQUEST['i1']; //价格
            $payinfo = S($pay_token);
            $money = $payinfo["WIDtotal_fee"]; //缓存的金额
            $order_number = $payinfo["WIDout_trade_no"]; //缓存的订单号
            $payinfo["trade_no"] = $trade_no;
            if (($out_trade_no != $order_number) || ($money != $yunprice)) {
                A("Home/Log")->setlog("云支付金额或订单号错误", __LINE__, __METHOD__);
                UtilApi::getInfo(500, '金额或订单号错误');
                exit;
            } else {
                A("Home/Log")->setlog("云支付参数验证成功", __LINE__, __METHOD__);
                return $payinfo;
            }
        } else {
            echo "验证失败";
            A("Home/Log")->setlog("云支付验证失败", __LINE__, __METHOD__);
            exit;
        }
    }

    /*
     * 云支付，充值
     * @param  pay_token
     */

    public function recharge() {
        $pc_load_url = C('pc_load_url');
        $wap_load_url = C('wap_load_url');
        $pay_token = $_GET["pay_token"];
        $payinfo = $this->pay_common($pay_token);
        //$payinfo = S($pay_token);//DUMP($payinfo);//EXIT;
        $data["order_number"] = $payinfo["WIDout_trade_no"]; //商户订单号
        $data["trade_no"] = $payinfo["WIDout_trade_no"]; //云支付交易号
        $data["price"] = $payinfo["WIDtotal_fee"]; //价格
        $data["trade_time"] = date("Y-m-d H:i:s", time()); //交易时间
        $data["trade_type"] = 1; //交易类型,1充值，2支付
        $condition["order_number"] = $payinfo["WIDout_trade_no"]; //商户订单号

        $uid = floatval($payinfo["uid"]); //缓存的uid
        $money = $payinfo["WIDtotal_fee"]; //缓存的金额
        $os = $payinfo["os"]; //判断pc还是手机
        $data['uid'] = $uid; //用户id

        $data['create_time'] = time(); //创建时间
        $data['charge_type'] = 1; //充值类型
        $data['charge_number'] = $payinfo["WIDout_trade_no"]; //充值订单号
        $data['money'] = $money; //金额
        $condition["uid"] = $uid;
        $condition["charge_number"] = $data['charge_number'];
        $insert = M("recharge")->where($condition)->find();
        $log_data = array(
            'pay_token' => $pay_token,
            'pay_info' => array(
                '订单号out_trade_no' => $payinfo["WIDout_trade_no"],
                '支付金额total_fee' => $payinfo["WIDtotal_fee"],
            )
        );
        A("Home/Log")->setlog("充值入库前recharge订单信息", $log_data, __METHOD__);
//        if (($payinfo["WIDtotal_fee"] != $money)) {
//            echo "非法访问";
//            exit;
//        }
        if (empty($data["order_number"])) {
            echo json_encode(array('code' => 500, 'info' => '订单号不能为空'));
            A("Home/Log")->setlog("订单号不能为空", __LINE__, __METHOD__);
            exit;
        }

        if (!$insert) {
            $model = M('yunpay');
            $model->startTrans(); //开启事务
            if (!empty($data["order_number"])) {
                $data['status'] = 0; //充值状态，0，初始状态；1完成;
                $insert_id = M('yunpay')->add($data);
                A("Home/Log")->setlog("插入yunpay表【充值事物1】", __LINE__, __METHOD__);
            }
            $data['status'] = 1; //充值状态，1完成
            $rechargeid = M("recharge")->data($data)->add(); //添加到充值表
            A("Home/Log")->setlog("添加到充值表recharge【充值事物2】", __LINE__, __METHOD__);
            if ($rechargeid > 0 && $insert_id > 0) {//充值成功
                $model->commit();
                $result = M("member")->field('account')->where('uid=' . $uid)->find(); //查找当前用户账户余额
                //A("Commission")->saveCommissionc($uid, $money);//分销系统提成
                $account = $result["account"];
                $total_account = $money + $account;
                $condition["account"] = $total_account;
                $total = M("member")->where('uid=' . $uid)->save($condition); //更新账户余额
                $this->pay_status($payinfo["WIDout_trade_no"]);
                A("Home/Log")->setlog("充值成功", $log_data, __METHOD__);
            } else {//充值失败
                $model->rollback();
                A("Home/Log")->setlog("充值失败回滚", __LINE__, __METHOD__);
                echo json_encode(array('code' => 523, 'info' => '充值失败'));
                exit;
            }
        }
        if ($os == 1) {
            header("Location: " . $wap_load_url . "/35-personal.html?c");
        } else {
            header("Location: " . $pc_load_url . "/personal-index.html?c");
        }
    }

    /*
     * 云支付，订单支付
     * @param  pay_token
     */

    public function repayfor() {
        $pay_token = $_GET["pay_token"];
        $payinfo = $this->pay_common($pay_token);
        //$payinfo = S($pay_token);//DUMP($payinfo);//EXIT;
        $os = $payinfo["os"]; //判断pc端还是手机端
        $pc_load_url = C('pc_load_url');
        $wap_load_url = C('wap_load_url');

        $data["order_number"] = $payinfo["WIDout_trade_no"]; //商户订单号
        $data["trade_no"] = $payinfo["WIDout_trade_no"]; //云支付交易号$payinfo["trade_no"] = $trade_no;
        $data["price"] = $payinfo["WIDtotal_fee"]; //价格
        $data["trade_time"] = date("Y-m-d H:i:s", time()); //交易时间
        $data["trade_type"] = 2; //交易类型,1充值，2支付
        $data["uid"] = floatval($payinfo["uid"]);
        $condition["order_number"] = $payinfo["WIDout_trade_no"]; //商户订单号

        $insert = M('yunpay')->where($condition)->find();
        $log_data = array(
            'pay_token' => $pay_token,
            'pay_info' => array(
                '订单号out_trade_no' => $payinfo["WIDout_trade_no"],
                '支付金额total_fee' => $payinfo["WIDtotal_fee"],
                'uid' => $payinfo["uid"]
            )
        );
        A("Home/Log")->setlog("支付入库前订单信息", $log_data, __METHOD__);

//        if (!empty($payinfo["payMoney"])) {
//            if (($payinfo["payMoney"] != $payinfo["money"]) || ($payinfo["WIDtotal_fee"] != $payinfo["payMoney"])) {
//                echo "非法访问";
//                exit;
//            }
//        }
        if ($insert) {
            A("Home/Log")->setlog("支付方法顶部跳转", __LINE__, __METHOD__);
            if ($os == 1) {
                header("Location: " . $wap_load_url . "/19-result.html?p");
            } else {
                header("Location: " . $pc_load_url . "/payfor3.html?p");
            }
        } else {
            $model = M('lottery_product');
            $model->startTrans(); //开启事务
            $recharge_arr = array(
                'uid' => floatval($payinfo["uid"]),
                'money' => $payinfo["WIDtotal_fee"],
                'create_time' => time(),
                'charge_type' => '2',
                'charge_number' => $payinfo["WIDout_trade_no"],
                'status' => '1'
            );
            $rechargeid = M("recharge")->data($recharge_arr)->add(); //添加到充值表
            A("Home/Log")->setlog("支付入库--开启事务【事物5】", __LINE__, __METHOD__);
            if (!empty($data["order_number"])) {
                A("Home/Log")->setlog("支付入库yunpay【事物1】", __LINE__, __METHOD__);
                $insert_id = M('yunpay')->add($data);
            }

            /*
             * 加入您的入库及判断代码;判断返回金额与实金额是否想同;判断订单当前状态;
             *  完成以上才视为支付成功
             */
            $content = $payinfo["content"]; //购买商品列表
            $user_token = $payinfo["user_token"]; //用户token
            $money = $payinfo["money"]; //商品列表购买总金额
            $balance = $payinfo["balance"]; //用户余额
            $payMoney = $payinfo["payMoney"];  //需要支付的金额
            $payType = $payinfo["payType"];  //支付类型
            $attendDevice = $payinfo["attendDevice"]; //操作来源
            $uid = $this->is_login($user_token); //判断是否登录
            $newContent = str_replace("&quot;", "\"", $content);  //需要先替换"&quot;字符，不然不能正常解析
            $array = json_decode($newContent, true);
            A("Home/Log")->setlog("支付入库repayfor", $payinfo, __METHOD__);
            $total = 0;
            if ($array != false) {
                $arrList = new \Org\Util\ArrayList();
                $errList = new \Org\Util\ArrayList();
                foreach ($array['list'] as $value) {
                    $total = $total + $value['attend_count']; //重新计算参与的总金额，防止数据被篡改
                }
                A("Home/Log")->setlog("支付content判断", __LINE__, __METHOD__);
                //开启事务确保更新数据一致
                //验证购买的金额和用户的余额是否正确，防止篡改数据
                $bal_arr = D('lotteryAttend')->valdateBalance($uid, $total, $money, $payMoney, $balance, $payType);

                //先扣款
                if ($bal_arr['code'] == 200) {
                    if ($balance != -1) { //使用部分余额支付，需要扣除余额
                        if ($balance > 0) {
                            $state_id = D('lotteryAttend')->plusBalance($uid, $balance); //在用户余额上扣除金额
                            if ($state_id == -1) {
                                UtilApi::getInfo(500, "您的余额不足，请充值或直接支付");
                                return;
                            } else if ($state_id == 0) {
                                UtilApi::getInfo(500, "扣除余额失败");
                                return;
                            }
                        } else {
                            UtilApi::getInfo(500, "余额为0，无法使用部分余额支付");
                            return;
                        }
                    }
                } else {
                    echo json_encode($bal_arr);
                    return;
                }
                $condition["lottery_id"] = $value['lottery_id'];
                $condition["order_number"] = $payinfo["WIDout_trade_no"];
                $insert = M("lottery_attend")->where($condition)->find();
                if (!$insert) {
                    A("Home/Log")->setlog("!insert判断", __LINE__, __METHOD__);
                    foreach ($array['list'] as $value) {
                        $field = 'id,pid,lottery_pid,need_count,attend_count,lucky_code,attend_limit,max_attend_limit,max_per_attend_limit';
                        $condition['lottery_id'] = $value['lottery_id']; //期号id
                        $lottery = M('lottery_product')->field($field)->where($condition)->select(); //获取该商品这一期的记录
                        $remain_count = $lottery[0]['need_count'] - $lottery[0]['attend_count']; //剩余的参与人次
                        if ($value['pid'] != $lottery[0]['pid']) { //商品id与这一期实际的商品id不一致
                            $info = $value['name'] . "的商品id与这一期的商品id不一致";
                            $errList->add(array('tip' => $info));
                            $flag = -1;
                            A("Home/Log")->setlog($info, __LINE__, __METHOD__);
                            continue;
                        }
                        if ($lottery[0]['attend_limit'] != 0) {  //判断每次是否有最低购买人次的限制
                            if ($value['attend_count'] < $lottery[0]['attend_limit']) {
                                $info = $value['name'] . "每次最低参与次数不能低于" . $lottery[0]['attend_limit'];
                                $errList->add(array('tip' => $info));
                                $flag = -1;
                                A("Home/Log")->setlog($info, __LINE__, __METHOD__);
                                continue;
                            }
                        }
                        if ($lottery[0]['max_per_attend_limit'] != 0) {   //判断每次是否有最大购买人次的限制
                            if ($value['attend_count'] > $lottery[0]['max_per_attend_limit']) {
                                $info = $value['name'] . "每次最大参与次数不能超过" . $lottery[0]['max_per_attend_limit'];
                                $errList->add(array('tip' => $info));
                                $flag = -1;
                                A("Home/Log")->setlog($info, __LINE__, __METHOD__);
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
                                A("Home/Log")->setlog($info, __LINE__, __METHOD__);
                                continue;
                            }
                        }
                        if ($remain_count == 0) {   //判断剩余次数是否为0
                            $info = $value['name'] . "已经全部参加完，请参加下一期";
                            $errList->add(array('tip' => $info));
                            $flag = -1;
                            A("Home/Log")->setlog($info, __LINE__, __METHOD__);
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
                                A("Home/Log")->setlog($info, __LINE__, __METHOD__);
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
                                A("Home/Log")->setlog($lucky_code, __LINE__, __METHOD__);
                            }
                            $attendCode = substr($attendCode, 0, -1); //获取这次分配的幸运码，截取掉最后面的,号
                            //$ip = get_client_ip(); //获取ip地址
                            //获取ip地址所在的省和城市
                            $ip = $payinfo["ip"];
                            $ip_address = UtilApi::getIpLookup($ip);
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
                                'order_number' => $payinfo["WIDout_trade_no"] //订单号
                            );

                            $lottery_attendid = M('lottery_attend')->add($attendArr); //添加参与记录
                            A("Home/Log")->setlog("支付事务2", __LINE__, __METHOD__);
                            $data = array();
                            $data['id'] = $lottery[0]['id'];  //期号id 用于更新信息
                            $data['lucky_code'] = $lucky_code; //更新幸运码
                            $data['attend_count'] = $lottery[0]['attend_count'] + $value['attend_count']; //更新参与次数
                            $data['attend_ratio'] = $data['attend_count'] / $lottery[0]['need_count']; //更新参与次数
                            if ($value['attend_count'] == $remain_count) { //参与人次达到总需求人次,进入即将揭晓状态
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
                                $lid = M('lottery_product')->add($pdata);  //添加该商品新一期记录///////////
                                $updata = array();
                                $updata['id'] = $lid; //添加新一期的id
                                $updata['lottery_id'] = C('START_CODE') + $lid; //新一期期号
                                $lottery_product_id = M('lottery_product')->save($updata); //更新新一期的期号
                            }
                            $lottery_list_id = M('lottery_product')->save($data);
                            A("Home/Log")->setlog("支付事务3", __LINE__, __METHOD__);
                            if ($lottery_list_id > 0) { //更新这一期对应的更新信息
                                $id = $value['id']; //购物车id
                                $shopcart_id = M('shopcart')->where("id=$id")->delete(); //删除购物车信息
                                A("Home/Log")->setlog("支付事务4[shopcart_id]", __LINE__, __METHOD__);
                            }
                            $attendInfo = array('code' => '0', 'name' => $value['name'], 'lottery_id' => $value['lottery_id'],
                                'pid' => $value['pid'], 'attendCode' => $attendCode, 'attend_count' => $value['attend_count'], 'attend_time' => $create_date_time);
                            $info = $value['name'] . '获取幸运码成功';
                            $arrList->add(array('tip' => $info, 'attendCode' => $attendInfo));
                        } else {  //剩余次数少于参与次数，无法参与
                            $info = $value['name'] . "只剩下" . $remain_count . "人次可参加";
                            $errList->add(array('tip' => $info));
                            $flag = -1;
                            A("Home/Log")->setlog($info, __LINE__, __METHOD__);
                        }
                        $total = $total + $value['attend_count'];
                    }
                } else {
                    UtilApi::getInfo(500, "content不能为空");
                    echo json_encode($arr);
                    A("Home/Log")->setlog($arr, __LINE__, __METHOD__);
                    return;
                }
            }
            if ($insert_id > 0 && $lottery_attendid > 0 && $lottery_list_id > 0 && $shopcart_id > 0 && $rechargeid > 0) {
                $model->commit();
                //由于没提交事务，导致获取全站最近50条记录的时间之和数据记录不正确，所以等提交完事务后再获取全站最近50条记录的时间之和和记录
                foreach ($array['list'] as $value) {
                    $lp_model = M('lottery_product');
                    $field1 = 'id,need_count,attend_count,last_attend_time';
                    $con1['lottery_id'] = $value['lottery_id'];
                    $lottery1 = $lp_model->field($field1)->where($con1)->select(); //获取该商品这一期的记录
                    if ($lottery1[0]['need_count'] == $lottery1[0]['attend_count']) {
                        $data1 = array();
                        $data1['id'] = $lottery1[0]['id'];
                        $data1['total_time'] = D('lotteryAttend')->getLastAttendTotalTime($lottery1[0]['last_attend_time']);  //获取全站最近50条记录的时间之和
                        $lp_model->save($data1); //更新这一期的全站最近50条记录的时间之和
                    }
                }
                $arr = array(
                    'code' => 200,
                    'info' => '成功',
                );
                S(array('expire' => 60)); //设置刚购买的幸运码记录缓存有效期60秒，以秒为单位
                S($uid, $arrList->toArray()); //用户id，不能使用该token保存数据，该token已经保存了用户id
                $this->pay_status($payinfo["WIDout_trade_no"]);
                A("Home/Log")->setlog("支付成功事务提交后", __LINE__, __METHOD__);
            } else {
                $model->rollback();
                if ($flag == -1) { //有错误，无法添加参与记录
                    $arr = array(
                        'code' => 500,
                        'info' => '失败',
                        'errList' => $errList->toArray()
                    );
                }
            }
            A("Home/Log")->setlog("支付失败回滚", __LINE__, __METHOD__);
            echo json_encode($arr);
        }
        A("Home/Log")->setlog("支付方法底部跳转", __LINE__, __METHOD__);
        if ($os == 1) {
            header("Location: " . $wap_load_url . "/19-result.html?p");
        } else {
            header("Location: " . $pc_load_url . "/payfor3.html?p");
        }
    }

    /*
     * 判断是否成功支付入库，如果成功入库，则更改支付表
     * 
     */

    public function pay_status($order_number = '') {
        $data["status"] = 1; //更改支付状态，1为成功
        $data["success_time"] = date("Y-m-d H:i:s", time()); //更改时间
        M("yunpay")->where('order_number=' . '"' . $order_number . '"')->save($data);
        A("Home/Log")->setlog("更改支付状态", __LINE__, __METHOD__);
    }

}
