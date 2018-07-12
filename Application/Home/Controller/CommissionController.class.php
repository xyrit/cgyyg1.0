<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/2/29
 * Time: 19:00
 * 提成
 */

namespace Home\Controller;

use Home\Common\UtilApi;
use Think\Exception;

header("Access-Control-Allow-Origin:*");

class CommissionController extends HomeController {

        //邀请有奖
    public function invitation()
    {
        $user_token = I("post.user_token");
        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        } else {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }
       $data=M('member')->field('code,code_img')->where('uid='.$uid)->find();
        if($data)
        {
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'data' => $data
            );
            echo json_encode($arr);
        }
        else
        {
            $arr = array(
            'code' => 200,
            'info' => '失败',
            'host' => C('HOST'),

             );
            echo json_encode($arr);
        }


    }


    /*
     *  loweruid  可选  单个用户记录
     * 提成记录佣金来源
     */

    public function commissionList() {

        $user_token = I("post.user_token");
        $loweruid = I('post.loweruid');

        $pageIndex = I('post.pageIndex', '0');  //页数
        $pageSize = I('post.pageSize', '10');  //数量



        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        } else {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }
    //$uid = 10000424;
//        $loweruid = 10000325;

        $list = D('Home/Commissions')->getcommissionList($uid, $pageIndex, $pageSize, $loweruid);
//       echo '<pre>';
//        print_r($list);exit;
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'list' => $list
        );
        echo json_encode($arr);
    }

    /*
     * 提成记录获取上级用户名称
     */

    public function getFidCommission() {
        $fid = I('fid', 1);
        $name = M('member')->where('uid=' . $fid)->getField('nickname');

        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'nickname' => $name
        );
        echo json_encode($arr);
    }

    /*
     * 我的推荐人列表
     */

    function recommendedList() {

        $user_token = I("post.user_token");
        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        } else {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
        }

       // $uid = 10000412; // 测试数据

        $list = D('Home/Commissions')->getrecommendedList($uid);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'list' => $list
        );
        echo json_encode($arr);
    }

    /*
     * 提现记录  佣金消费
     */

    function consumptionsList() {

        $user_token = I("post.user_token");
        $pageIndex = I("post.pageIndex", 0);
        $pageSize = I("post.pageSize", 10);
        //时间筛选
        $starttime = I("post.starttime");
        $endtime = strtotime(I("post.endtime"));
        $sort = I("sort");
        //时间转换
        if (!$starttime) {
            $starttime = time();
        } else {
            $starttime = strtotime($starttime);
        }


        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        }
        else
        {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }

       // $uid = 10000424; // 测试数据

        $list = D('Home/Commissions')->getconsumptionsList($uid, $pageIndex, $pageSize, $starttime, $endtime, $sort);

        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'list' => $list
        );
        echo json_encode($arr);
    }

    /*
     * 银行卡列表
     */

    function member_banks() {

        $user_token = I("post.user_token");
        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        } else {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }

        //   $uid = 10000323; // 测试数据
        $list = M('member_banks')->where('uid=' . $uid)->limit(3)->select();
//        echo '<pre>';
//        print_r($list);exit;
        $count = M('member_banks')->where('uid=' . $uid)->count();
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'list' => $list,
            'count' => $count
        );
        echo json_encode($arr);
    }

    /*
     * uid  用户ID, account_name 开户姓名,bank_name  开户银行,bank_code  银行帐号,sub_branch  开户支行,addtime 新增时间
     * 银行卡修改，新增
     */

    public function member_banksEdit() {
        $data = I('post.');
        if (!$data) {
            UtilApi::getInfo('500', '数据不存在');
            return;
        }

        $user_token = I("post.user_token");
        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        } else {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }

        if(!$uid)
        {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }


        //根据用户id 查询出手机号
        $phone = M('member')->where('uid='.$uid)->getField('mobile');

        if(!$data['code'])
        {
            UtilApi::getInfo('500', '验证码不能为空');
            return;
        }

        $start = 2;
        $tpl =I('post.type');

        // phone 手机号  code 验证码  start 状态 2  模版   tpl 1
        A('Verify')->checkVerify($phone,$data['code'],$start,$tpl);



        $data['addtime']=time();
        $data['uid']=$uid;
        try {
            if (empty($data['id'])) {



            $result = M('member_banks')->add($data);
        }

  else {


            $result = M('member_banks')->where('id='.$data['id'])->save($data);
        }
        }catch (Exception $e)
        {
            UtilApi::getInfo('500',$e);

                $result = M('member_banks')->filter('uid,account_name,bank_name,bank_code,sub_branch,addtime')->where('id=' . $data['id'])->save($data);
            }
        if ($result) {
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
            );
        } else {
            $arr = array(
                'code' => 500,
                'info' => '失败',
                'host' => C('HOST'),
            );
        }
        echo json_encode($arr);
    }

    /*
     * id  银行卡id
     * 银行卡删除
     */

    public function member_banksdel() {
        $id = I('post.id', 1);
        $result = M('member_banks')->where('id=' . $id)->delete();

        if ($result) {
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
            );
        } else {
            $arr = array(
                'code' => 500,
                'info' => '失败',
                'host' => C('HOST'),
            );
        }
        echo json_encode($arr);
    }

    /*
     * id     consumption_money   金额    bank_id  银行ID
     * 提现
     */

    public function transfer_accounts() {

        $user_token = I("post.user_token");
        $consumption_money = I("post.consumption_money");
        $code=I('post.code');
        $bank_id = I("post.bank_id");
        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        } else {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }

        //根据用户id 查询出手机号
        $phone = M('member')->where('uid='.$uid)->getField('mobile');

        if(!$code)
        {
            UtilApi::getInfo('500', '验证码不能为空');
            return;
        }

        $start = 2;

        $tpl =I('post.type');

        // phone 手机号  code 验证码  start 状态 2  模版   tpl 1
        A('Verify')->checkVerify($phone,$code,$start,$tpl);

        $consumption_money=intval($consumption_money);

        //  $uid = 10000323; // 测试数据

        if(preg_match("/^[1-9]*$/",$consumption_money)){
            UtilApi::getInfo('500', '请输入整数');
            return;
        }
        if($consumption_money < 0)
        {
            UtilApi::getInfo('500', '请输入正整数');
            return;
        }

        if($consumption_money % 100 != 0)
        {
            UtilApi::getInfo('500', '请输入100的倍数');
            return;
        }
        //查询账户余额
        $money = M('member')->where('uid=' . $uid)->getField('brokerage');

        if ($consumption_money > $money) {
            UtilApi::getInfo('500', '您的余额不足');
            return;
        } else {
            $remain_money = $money - $consumption_money;
        }

        $data = array(
            'consumption_patterns' => '提现到银行卡',
            'consumption_status' => 0,
            'consumption_money' => $consumption_money,
            'addtime' => time(),
            'seriall_number' => date("Ymdhis", time()) . $uid . rand(1000, 9999),
            'uid' => $uid,
            'remain_money' => $remain_money,
            'bank_id' => $bank_id
        );

        $Commissions = D('Home/Commissions');
        $Commissions->startTrans();

        $result = $Commissions->transfer_accounts($data);
        if ($result) {
            $Commissions->commit();
        } else {
            $Commissions->rollback();
        }
        if ($result) {
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
            );
        } else {
            $arr = array(
                'code' => 500,
                'info' => '失败',
                'host' => C('HOST'),
            );
        }
        echo json_encode($arr);
    }

    /*
     * id     consumption_money   金额
     * 转账
     */

    public function bring_forward() {

        $user_token = I("post.user_token");
        $consumption_money = I("post.consumption_money");

        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        } else {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }

        $consumption_money=intval($consumption_money);


        if(preg_match("/^[1-9]*$/",$consumption_money)){
            UtilApi::getInfo('500', '请输入整数');
            return;
        }

        if($consumption_money < 0)
        {
            UtilApi::getInfo('500', '请输入正整数');
            return;
        }
        //  $uid = 10000323; // 测试数据

        //查询账户余额
        $money = M('member')->where('uid=' . $uid)->getField('brokerage');

        if ($consumption_money > $money) {
            UtilApi::getInfo('500', '您的余额不足');
            return;
        } else {
            $remain_money = $money - $consumption_money;
        }

        $data = array(
            'consumption_patterns' => '转账到帐号',
            'consumption_status' => 1,
            'consumption_money' => $consumption_money,
            'addtime' => time(),
            'seriall_number' => date("Ymdhis", time()) . $uid . rand(1000, 9999),
            'uid' => $uid,
            'remain_money' => $remain_money,
        );
        $member = M('member');
        $account = $member->where('uid=' . $uid)->getField('account');
        $newaccount = $account + $consumption_money;

        $mdata = array(
            'brokerage' => $remain_money,    //余额增加
             'account' => $newaccount       //减去佣金
        );


        //将金额加入用户帐号
        $Commissions = D('Home/Commissions');
        $member= M('member');
        $Commissions->startTrans();
        $member->startTrans();

        $result1 = $Commissions->transfer_accounts($data);
        $result2 = $member->where('uid='.$uid)->save($mdata);

        if ($result1 && $result2) {
            $Commissions->commit();
            $member->commit();
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
            );
        } else {
            $Commissions->rollback();
            $member->rollback();
            $arr = array(
                'code' => 500,
                'info' => '失败',
                'host' => C('HOST'),
            );
        }

        echo json_encode($arr);
    }

    /*
     *   uid 用户ID
     * 生成专属邀请码,二维码   调用
     */

    public function CreateInvite_code($uid = '') {
        //生成唯一的邀请码



        $code = $uid;

        //生成二维码
        $url = 'http://www.cgyyg.com/api/uc/register.html?code=' . $code;
        $file = 'Q' . $uid;    //文件名称
        $imgurl= $this->qrcode($url, $file);

        $arr = array('code' => $code,'code_img'=>$imgurl);
        $result = M('member')->where('uid=' . $uid)->save($arr);

        return $result;
    }

    /**
     * 生成二维码
     * $url 生成链接地址参数
     * $outfile 输出地址  文件名称
     * level  容错率
     * size 图片大小
     */
    public function qrcode($url = 1, $name = 0, $level = 3, $size = 4) {

        Vendor('phpqrcode.phpqrcode');
        $errorCorrectionLevel = intval($level); //容错级别
        $matrixPointSize = intval($size); //生成图片大小
//生成二维码图片
        //  echo $_SERVER['REQUEST_URI'];
        $object = new \QRcode();
        $outfile = BASE_PATH . "/qrcode/" . $name . '.png';
        $object::png($url, $outfile, $errorCorrectionLevel, $matrixPointSize, 2);
        $filenameulr1="cg-code/code-".$name.'.png';
        $this->upImages($outfile,$filenameulr1);
        return $filenameulr1;
    }

    /*
     * uid 用户ID
     * 绑定用户关系       调用
     */

    public function bind_user($uid, $code) {

        //通过
        $fid = M('member')->where('code='.$code)->getField('uid');
        if (!$fid) {
            return false;
        }
        if($fid==$uid)   //如果是自己的
        {
            return false;
        }

        $ofid = M('relation_member')->where('uid=' . $fid)->getField('fid');
        if (!$ofid) {
            $ofid = 0;
        }

        $arr = array(
            'uid' => $uid,
            'fid' => $fid,
            'ofid' => $ofid,
            'addtime' => time()
        );
        $result = M('relation_member')->add($arr);

        return $result;
    }

    /*
     * uid 用户ID
     * 提成计算存入      调用
     */

    public function saveCommission($uid = 10000412, $money=10000) {
        //根据用户查询出用户的上级
       // $uid = 10000412; // 测试数据
        $fids = M('relation_member')->field('fid,ofid')->where('uid=' . $uid)->find();
        $fid = $fids['fid'];
        $ofid = $fids['ofid'];
        //如果没有上级
        if (!$fid) {
            return true;
        }

        //如果有2级
        if ($ofid) {

            //根据ID查询他们所需要获取的提成
            $f_info1 = M('member')->field('commission_proportion_1,brokerage')->where('uid=' . $fid)->find();
            $f_info2 = M('member')->field('commission_proportion_2,brokerage')->where('uid=' . $ofid)->find();
            $f_Commission_money1 = round($money * ($f_info1['commission_proportion_1'] / 100), 2);
            $f_Commission_money2 = round($money * ($f_info2['commission_proportion_2'] / 100), 2);
            $brokerage1 = $f_Commission_money1 + $f_info1['brokerage'];
            $brokerage2 = $f_Commission_money2 + $f_info2['brokerage'];
            $f_arr1 = array('brokerage' => $brokerage1);        //一级用户
            $of_arr1 = array('brokerage' => $brokerage2);        //二级用户
            $f_arr2 = array(
                'uid' => $uid,
                'fid' => $fid,
                'addtime' => time(),
                'commission' => $f_Commission_money1,
                'consumption' => $money,
                'level' => 1,
            );                                                          //提成记录

            $of_arr2 = array(
                'uid' => $uid,
                'fid' => $ofid,
                'addtime' => time(),
                'commission' => $f_Commission_money2,
                'consumption' => $money,
                'level' => 2,

            );                                                        //二级提成记录

            $member = M('member');
            $commissions = M('commissions');
            $member->startTrans();
            $f_rs1 = $member->where('uid=' . $fid)->save($f_arr1);
            $of_rs1 = $member->where('uid=' . $ofid)->save($of_arr1);
            $f_rs2 = $commissions->add($f_arr2);
            $of_rs2 = $commissions->add($of_arr2);
            if ($f_rs1 && $of_rs1 && $f_rs2 && $of_rs2) {

                $member->commit(); //成功则提交
            } else {

                $member->rollback(); //不成功，则回滚
                return false;
            }
        } else {
            $f_info1 = M('member')->field('commission_proportion_1,brokerage')->where('uid=' . $fid)->find();
            $f_Commission_money1 = round($money * ($f_info1['commission_proportion_1'] / 100), 2);
            $brokerage1 = $f_Commission_money1 + $f_info1['brokerage'];
            $f_arr1 = array('brokerage' => $brokerage1);        //一级用户
            $f_arr2 = array(
                'uid' => $uid,
                'fid' => $fid,
                'addtime' => time(),
                'commission' => $f_Commission_money1,
                'consumption' => $money,
                'level' => 1,
                'ofid' => $ofid
            );                                                          //提成记录
            $member = M('member');
            $commissions = M('commissions');
            $member->startTrans();
            $f_rs1 = $member->where('uid=' . $fid)->save($f_arr1);
            $f_rs2 = $commissions->add($f_arr2);
            if ($f_rs1 && $f_rs2) {

                $member->commit(); //成功则提交
            } else {

                $member->rollback(); //不成功，则回滚
                return false;
            }
        }

        return true;
    }

    /*
     * 图片上传至阿里云服务器
     */

    public function upImages($filePath='',$object='') {
        $id = '08iJabGVcaucodBT';   //阿里云Access Key ID
        $key = 'RkyzXVJI7TRPlM0e8SrgyTsS2RU4P7'; //阿里云Access Key Secret
        $host = 'http://pic.cgyyg.com';
      
        require_once BASE_PATH . '\Application\Addons\upload\php\aaa\autoload.php';
        //echo 222;exit;
        $bucket = "cgchengguo";

        try {
            $ossClient = new \OSS\OssClient($id, $key, $host, true);

            $ossClient->uploadFile($bucket, $object, $filePath);
        } catch (OssException $e) {
            print $e->getMessage();
            return false;
        }

        return true;
    }

}
