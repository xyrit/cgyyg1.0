<?php

namespace Home\Model;

use Think\Model;
use Home\Common\TimeUtil;
use Home\Common\RunTimeUtil;

/*
 * 开奖，订单，幸运码模型
 * 更新时间 2016.1.27
 */

class LotteryAttendModel extends Model {
    /*
     * 验证参与次数是否大于等于剩余次数和是否超过设置的人次限制
     */

    public function validateAttendProduct($arr, $uid) {
        $field = 'id,pid,lottery_pid,need_count,attend_count,attend_limit,max_attend_limit,max_per_attend_limit';
        $condition['lottery_id'] = $arr['lottery_id']; //期号id
        $lottery = M('lottery_product')->field($field)->where($condition)->select(); //获取该商品这一期的开奖记录
        $remain_count = $lottery[0]['need_count'] - $lottery[0]['attend_count']; //这一期剩余的参与次数
        if ($lottery[0]['attend_limit'] != 0) {  //判断每次是否有最低购买人次的限制
            if ($arr['attend_count'] < $lottery[0]['attend_limit']) {
                return array('code' => 501, 'info' => $lottery[0]['attend_limit']);
            }
        }
        if ($lottery[0]['max_per_attend_limit'] != 0) {   //判断每次是否有最多购买人次的限制
            if ($arr['attend_count'] > $lottery[0]['max_per_attend_limit']) {
                return array('code' => 502, 'info' => $lottery[0]['max_per_attend_limit']);
            }
        }
        if ($lottery[0]['max_attend_limit'] != 0) {   //判断总的购买次数是否有最多购买人次的限制
            $acon = array();
            $acon['lottery_id'] = $arr['lottery_id'];
            $acon['uid'] = $uid;
            $attend_sum = M('lottery_attend')->where($acon)->sum('attend_count'); //查询用户这一期已经购买了多少人次
            if (($attend_sum + $arr['attend_count']) > $lottery[0]['max_attend_limit']) {
                return array('code' => 506, 'info' => ($lottery[0]['max_attend_limit'] - $attend_sum));
            }
        }
        if ($remain_count == 0) {   //剩余次数为0
            return array('code' => 503, 'info' => 1);
        }
        if ($arr['attend_count'] <= $remain_count) { //参与次数小于等于剩余次数,可以正常购买
            return array('code' => 200, 'info' => 1);
        } else {  //剩余次数少于参与次数，无法参与
            return array('code' => 504, 'info' => $remain_count);
        }
        if ($arr['pid'] != $lottery[0]['pid']) { //商品id与这一期实际的商品id不一致
            return array('code' => 505, 'info' => 1);
        }
    }

    /*
     * 验证账号金额和购物金额是否一致
     */

    public function valdateBalance($uid, $total, $money, $payMoney, $balance, $payType) {    
        if ($total != $money) { //验证总金额是否一致
            return array('code' => 500, 'info' => '总金额与参与的总金额不一致');
        }
         $field = 'account';
         $condition['uid'] = $uid;
         $m = M('member')->field($field)->where($condition)->select(); //查询余额
         $bal = $m[0]['account'];
        if ($bal < $balance) {  //验证用户金额是否一致
            return array('code' => 500, 'info' => '用户余额不足');
        }
        if(!in_array($payType,array(0,1))){
            return array('code' => 500, 'info' => '支付类型不正确');
        }
        if($payType == 1){
            // 金钱支付 或者  余额跟金钱支付
            $balance= intval($balance < 0 ? 0:$balance);
             if(($payMoney+$balance) !=$total){
                  return array('code' => 500, 'info' => '支付错误');
             }
        }
        return array('code' => 200, 'info' => '成功');
    }

    /*
     * 扣掉余额
     */

    public function plusBalance($uid, $balance) {
        $field = 'account';
        $condition['uid'] = $uid;
        $m = M('member')->field($field)->where($condition)->select(); //查询余额
        $bal = $m[0]['account'];
        if ($bal < $balance) {
            return -1;
        } else {
            $flag = M('member')->where($condition)->setField('account', ($bal - $balance));
            if ($flag) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    /*
     * 获取截止某个商品购买完成后时间网站所有商品的最后50条购买时间的时间记录
     */

    public function getLastAttendTimeList($lastAttendTime) {
        $prefix = C('DB_PREFIX');
        $sql = "select la.lottery_id,la.pid,la.uid,la.attend_count,la.create_time,la.create_date_time,la.sfm_time,d.title,u.nickname from " . $prefix . "lottery_attend la "
                . "join " . $prefix . "document d on la.pid=d.id "
                . "left join " . $prefix . "member u on la.uid=u.uid "
                . "where la.create_time<=" . $lastAttendTime . " order by la.create_time desc limit 50";
        $list = $this->query($sql);
        return $list;
    }

    /*
     * 获取截止某个商品购买完成后时间网站所有商品的最后50条购买时间之和
     */

    public function getLastAttendTotalTime($lastAttendTime) {
        $prefix = C('DB_PREFIX');
        $sql = "select la.sfm_time from " . $prefix . "lottery_attend la "
                . "join " . $prefix . "document d on la.pid=d.id "
                . "where la.create_time<=" . $lastAttendTime . " order by la.create_time desc limit 50";
        $list = $this->query($sql);
        $totalTime = 0;
        foreach ($list as $value) {
            $totalTime+= $value['sfm_time'];
        }
        return $totalTime;
    }

    /*
     * 根据最新的时时彩揭晓即将揭晓商品的开奖结果
     */

    public function lotteryResult() {
        RunTimeUtil::cqssc_cache(); //更新最新时时彩记录
        if (F('isNew') == 1) { //最新时时彩彩票揭晓
            F('isNew', 2); //标识已使用最新时时彩的数据
            $hour_time = strtotime(F('dateline')); //最新一期时时彩开奖时间
            $field = 'lottery_id,pid,need_count,total_time';
            //获取时时彩开奖时间之前所有即将揭晓商品的记录
            $lottery = M('lottery_product')->field($field)->where('last_attend_time >0 and lottery_code=0 and last_attend_time <' . $hour_time)->select();
            $len = count($lottery); //所有即将揭晓商品记录的数量
            for ($i = 0; $i < $len; $i++) { //一条一条开奖
                //揭晓幸运码（幸运码计算规则：(用户购买完最后一条记录的时间时当时全站所有商品最近购买的50条记录时间之和+最新一期的时时彩号码)%商品的总需人次）+1
                $lottery_code = intval(fmod(floatval($lottery[$i]['total_time']) + floatval(F('hour_code')), $lottery[$i]['need_count'])) + 1;
                $data = array();
                $data['hour_lottery'] = F('hour_code'); //时时彩号码
                $data['hour_lottery_id'] = substr(F('hour_code_id'), 0, 8) . '-' . substr(F('hour_code_id'), 8); //时时彩id
                $data['lottery_code'] = $lottery_code; //已揭晓的幸运码
                $data['lottery_time'] = TimeUtil::timeStamp(); //揭晓幸运码时间

                $con['lottery_id'] = $lottery[$i]['lottery_id'];
                //根据期号查找某个已开奖期号的所有用户参与记录
                $lottery_attend = M('lottery_attend')->field('id,uid,attend_count,lucky_code,create_date_time')->where($con)->select();
                $s = count($lottery_attend); //用户参与记录的数量
                $uid = 0;
                $aid = 0;
                //匹配参与记录的的幸运码，找出参与记录中的中奖码
                for ($k = 0; $k < $s; $k++) {
                    $lucky_code = $lottery_attend[$k]['lucky_code']; //用户参与的幸运码
                    $arr_code = explode(',', $lucky_code); //分割用户参与的幸运码
                    $c = count($arr_code);
                    for ($h = 0; $h < $c; $h++) {
                        if ($lottery_code == $arr_code[$h]) {
                            $uid = $lottery_attend[$k]['uid']; //用户id
                            $aid = $lottery_attend[$k]['id']; //参与记录id
                            $attend_date_time = $lottery_attend[$k]['create_date_time']; //参与时间时分秒毫秒格式 
                            $attend_count = $lottery_attend[$k]['attend_count']; //用户参与人次
                            break;  //找到中奖码后直接跳出
                        }
                    }
                    if ($uid != 0) { //找到中奖码后直接跳出
                        break;
                    }
                }
                if ($uid != 0) {
                    $data['uid'] = $uid; //中奖用户id
                    $data['aid'] = $aid; //参与记录id
                    M('lottery_product')->where($con)->save($data); //更新开奖表中奖用户id和参与记录id
                    $map = array('title', 'cover_id', 'virtual');
                    $pcon = array();
                    $pcon['model_id'] = 5; //标识是商品
                    $pcon['display'] = 1; //可用的商品
                    $pcon['id'] = $lottery[$i]['pid']; //商品id
                    $wdata = array();
                    $product = M('document')->field($map)->where($pcon)->select(); //查询商品标题和图片id
                    if ($product) {
                        $wdata['pid'] = $lottery[$i]['pid']; //商品id
                        $wdata['title'] = $product[0]['title']; //商品标题
                        $wdata['virtual'] = $product[0]['virtual']; //是否为实物或虚拟商品
                        $picture = M('picture')->field('path')->where("id=" . $product[0]['cover_id'])->select(); //查找商品图片路径
                        if ($picture) {
                            $wdata['thumbnail'] = $picture[0]['path']; //商品路径
                        }
                    }

                    $last_attend_count_total = M("lottery_attend")->field("sum(attend_count) as last_attend_total")->where("uid=" . $uid . " and lottery_id=" . $lottery[$i]['lottery_id'])->find();
                    $last_attend_count_total = floatval($last_attend_count_total["last_attend_total"]); //取出当前中奖用户之前的总参与记录
                    $wdata['uid'] = $uid; //中奖用户id
                    $wdata['lottery_id'] = $lottery[$i]['lottery_id']; //期号id
                    $wdata['apply_time'] = date("Y-m-d H:i:s", $data['lottery_time']); //幸运码揭晓时间
                    $wdata['attend_time'] = $attend_date_time; //用户中奖码记录的参与时间时分秒毫秒格式
                    $wdata['attend_count'] = $last_attend_count_total; //用户中奖时的参与人次
                    $acon = array();
                    $acon['uid'] = $uid; //中奖用户id
                    $acon['status'] = 0; //标识为隐藏的收货地址
                    $address = M('address')->field('id,address')->where($acon)->select();
                    if ($address) { //用户已经创建了隐藏的收货地址
                        $wdata['address_id'] = $address[0]['id']; //地址id
                        if (empty($address[0]['address'])) {
                            $wdata['address_status'] = 2; //收货地址为空
                        } else {
                            $wdata['address_status'] = 1; //收货地址不为空
                        }
                    } else {
                        $adata = array();
                        $adata['uid'] = $uid; //中奖用户id
                        $adata['status'] = 0; //标识为隐藏的收货地址
                        $id = M('address')->add($adata); //创建隐藏的收货地址
                        $wdata['address_id'] = $id; //地址id
                        $wdata['address_status'] = 2; //收货地址为空
                    }
                    M('win_prize')->add($wdata); //添加到中奖记录

                    $lottery_id = $lottery[$i]['lottery_id']; //期号
                    $p_title = $product[0]['title']; //商品名称
                    $user_info = D("Member")->getField($uid, 'mobile,nickname'); //获取用户信息
                    $mobile = $user_info["mobile"]; //手机号
                    $nickname = $user_info["nickname"]; //昵称
                    A("Sms")->index($mobile, 6, $nickname, $lottery_id, $p_title); //给用户发送中奖信息
                }
            }
            RunTimeUtil::cqssc_cache(); //再次更新最新时时彩记录
        }
        date_default_timezone_set('PRC');
        F('runTime', strtotime('now'));  //保存定时器运行时间
    }

    /*
     * 如遇福彩中心通讯故障，无法获取上述期数的中国福利彩票“老时时彩”开奖结果，
     * 且24小时内该期“老时时彩”开奖结果仍未公布，则默认“老时时彩”开奖结果为00000
     * 用于特殊情况下手动执行开奖,保险起见必须在测试服务器测试成功才能执行
     */

    public function lotteryResultNoCqssc() {
        $cqssc = RunTimeUtil::cqssc_cache(); //获取时时彩开奖记录
        $hour_time = strtotime($cqssc['dateline']); //最新时时彩开奖时间
        $limit_time = $hour_time + 60 * 60 * 24; //计算大于最新时时彩开奖时间后24小时内的所有参与记录
        //最新时时彩彩票揭晓
        $field = 'lottery_id,pid,need_count,total_time,lottery_time';
        //获取时时彩开奖时间之前所有即将揭晓商品的记录
        $lottery = M('lottery_product')->field($field)->where('lottery_code=0 and last_attend_time<' . $limit_time)->select();
        $len = count($lottery); //所有即将揭晓商品记录的数量
        for ($i = 0; $i < $len; $i++) { //一条一条开奖
            //揭晓幸运码（幸运码计算规则：(用户购买完最后一条记录的时间时当时全站所有商品最近购买的50条记录时间之和+最新一期的时时彩号码)%商品的总需人次）+1
            $lottery_code = intval(fmod(floatval($lottery[$i]['total_time']) + 0, $lottery[$i]['need_count'])) + 1;
            $data = array();
            $data['hour_lottery'] = '00000'; //时时彩号码
            $data['hour_lottery_id'] = 0; //时时彩id
            $data['lottery_code'] = $lottery_code; //已揭晓的幸运码
            $data['lottery_time'] = TimeUtil::timeStamp(); //揭晓幸运码时间

            $con['lottery_id'] = $lottery[$i]['lottery_id'];
            //根据期号查找某个已开奖期号的所有用户参与记录
            $lottery_attend = M('lottery_attend')->field('id,uid,attend_count,lucky_code,create_date_time')->where($con)->select();
            $s = count($lottery_attend); //用户参与记录的数量
            $uid = 0;
            $aid = 0;
            //匹配参与记录的的幸运码，找出参与记录中的中奖码
            for ($k = 0; $k < $s; $k++) {
                $lucky_code = $lottery_attend[$k]['lucky_code']; //用户参与的幸运码
                $arr_code = explode(',', $lucky_code); //分割用户参与的幸运码
                $c = count($arr_code);
                for ($h = 0; $h < $c; $h++) {
                    if ($lottery_code == $arr_code[$h]) {
                        $uid = $lottery_attend[$k]['uid']; //用户id
                        $aid = $lottery_attend[$k]['id']; //参与记录id
                        $attend_date_time = $lottery_attend[$k]['create_date_time']; //参与时间时分秒毫秒格式
                        $attend_count = $lottery_attend[$k]['attend_count']; //用户参与人次
                        break;  //找到中奖码后直接跳出
                    }
                }
                if ($uid != 0) { //找到中奖码后直接跳出
                    break;
                }
            }
            if ($uid != 0) {
                $data['uid'] = $uid; //中奖用户id
                $data['aid'] = $aid; //参与记录id
                M('lottery_product')->where($con)->save($data); //更新开奖表中奖用户id和参与记录id
                $map = array('title', 'cover_id', 'virtual');
                $pcon = array();
                $pcon['model_id'] = 5; //标识是商品
                $pcon['display'] = 1; //可用的商品
                $pcon['id'] = $lottery[$i]['pid']; //商品id
                $wdata = array();
                $product = M('document')->field($map)->where($pcon)->select(); //查询商品标题和图片id
                if ($product) {
                    $wdata['pid'] = $lottery[$i]['pid']; //商品id
                    $wdata['title'] = $product[0]['title']; //商品标题
                    $wdata['virtual'] = $product[0]['virtual']; //是否为实物或虚拟商品
                    $picture = M('picture')->field('path')->where("id=" . $product[0]['cover_id'])->select(); //查找商品图片路径
                    if ($picture) {
                        $wdata['thumbnail'] = $picture[0]['path']; //商品路径
                    }
                }
                $wdata['uid'] = $uid; //中奖用户id
                $wdata['lottery_id'] = $lottery[$i]['lottery_id']; //期号id
                $wdata['apply_time'] = date("Y-m-d H:i:s", $data['lottery_time']); //幸运码揭晓时间
                $wdata['attend_time'] = $attend_date_time; //用户中奖码记录的参与时间时分秒毫秒格式
                $wdata['attend_count'] = $attend_count; //用户中奖时的参与人次
                $acon = array();
                $acon['uid'] = $uid; //中奖用户id
                $acon['status'] = 0; //标识为隐藏的收货地址
                $address = M('address')->field('id,address')->where($acon)->select();
                if ($address) { //用户已经创建了隐藏的收货地址
                    $wdata['address_id'] = $address[0]['id']; //地址id
                    if (empty($address[0]['address'])) {
                        $wdata['address_status'] = 2; //收货地址为空
                    } else {
                        $wdata['address_status'] = 1; //收货地址不为空
                    }
                } else {
                    $adata = array();
                    $adata['uid'] = $uid; //中奖用户id
                    $adata['status'] = 0; //标识为隐藏的收货地址
                    $id = M('address')->add($adata); //创建隐藏的收货地址
                    $wdata['address_id'] = $id; //地址id
                    $wdata['address_status'] = 2; //收货地址为空
                }
                M('win_prize')->add($wdata); //添加到中奖记录
                $lottery_id = $lottery[$i]['lottery_id']; //期号
                $p_title = $product[0]['title']; //商品名称
                $user_info = D("Member")->getField($uid, 'mobile,nickname'); //获取用户信息
                $mobile = $user_info["mobile"]; //手机号
                $nickname = $user_info["nickname"]; //昵称
                A("Sms")->index($mobile, 6, $nickname, $lottery_id, $p_title); //给用户发送中奖信息
            }
        }
    }

    /* 个人中心-消费记录 */

    public function buyRecords($uid, $pageIndex, $pageSize, $timeStart, $timeEnd) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        if (!empty($timeStart) && !empty($timeEnd)) {
            $BETWEEN = " and (la.create_time BETWEEN " . $timeStart . " AND " . $timeEnd . ")";
        } else {
            $BETWEEN = '';
        }
        $sql = "select la.id,la.lottery_id,la.attend_count,la.create_time,la.charge_number,d.title,p.path from  " . $prefix . "lottery_attend as la " .
                " left join  " . $prefix . "document as d on la.pid = d.id " .
                " left join  " . $prefix . "picture as p on d.cover_id = p.id where la.uid=" . $uid . $BETWEEN . "  order by la.id limit $start,$end";
        //echo $sql;
        $list = $this->query($sql);
        return $list;
    }

    /* 个人中心-充值记录 */

    public function rechargeRecords($uid, $pageIndex, $pageSize, $state, $timeStart, $timeEnd) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $BETWEEN = (!empty($timeStart) && !empty($timeEnd)) ? " and (r.create_time BETWEEN " . $timeStart . " AND " . $timeEnd . ")" : '';
        $WHERE = "WHERE ((r.uid=" . $uid . ")" . $BETWEEN . ")";
        $sql = "select r.id,r.charge_type,r.money,r.charge_number,r.`status`,r.create_time from " . $prefix .
                "recharge as r " . $WHERE . " order by r.id desc limit $start,$end";
        //echo $sql;
        $list = $this->query($sql);
        return $list;
    }

    /* 个人中心-充值记录总数 */

    public function rechargeRecordsCount($uid, $pageIndex, $pageSize, $state, $timeStart, $timeEnd) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $BETWEEN = (!empty($timeStart) && !empty($timeEnd)) ? " and (r.create_time BETWEEN " . $timeStart . " AND " . $timeEnd . ")" : '';
        $WHERE = "WHERE ((r.uid=" . $uid . ")" . $BETWEEN . ")";
        $sql = "select count(*) as total from(select r.id from " . $prefix . "recharge as r " . $WHERE . " order by r.id)temp  ";
        //echo $sql;
        $list = $this->query($sql);
        $count = $list[0]["total"];
        return $count;
    }

}
