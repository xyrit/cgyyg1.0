<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/2/29
 * Time: 19:00
 * 提成
 */

namespace Wap\Controller;

use Home\Common\UtilApi;
use Think\Exception;

header("Access-Control-Allow-Origin:*");

class CommissionController extends HomeController{


    /*
     *  loweruid  可选  单个用户记录
     * 提成记录佣金来源
     */
    public function commissionList(){

        $user_token = I("post.user_token");
        $loweruid=I('post.loweruid');

        $pageIndex = I('post.pageIndex','0');  //页数
        $pageSize = I('post.pageSize','10');  //数量



        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        }
        else
        {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }
//      $uid = 10000323;
//        $loweruid = 10000325;

        $list = D('Home/Commissions')->getcommissionList($uid,$pageIndex,$pageSize,$loweruid);
        $pagecount= M('Commissions')->where('ofid='.$uid)->count();
        $pagecount= ceil($pagecount/$pageSize);

        $list['pagecount']=$pagecount;
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
    public function getFidCommission()
    {
        $fid=I('fid',1);
        $name=M('member')->where('uid='.$fid)->getField('nickname');

        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'nickname' => $name
        );
        echo json_encode($arr);
    }

    /*
     *我的推荐人列表
     */
    function recommendedList(){

        $user_token = I("post.user_token");

        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        }
        else
        {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
        }

      //$uid = 10000323; // 测试数据

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
     * 提现记录
     */
    function consumptionsList(){
        $user_token = I("post.user_token");
        $pageIndex = I("post.pageIndex",0);
        $pageSize = I("post.pageSize",10);
        //时间筛选
        $starttime=I("post.starttime");
        $endtime=strtotime(I("post.endtime"));
        $sort=I("post.sort");
        //时间转换
        if(!$starttime)
        {
            $starttime = time();
        }
        else
        {
            $starttime=strtotime($starttime);
        }


        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        }
        else
        {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }

        /// $uid = 10000323; // 测试数据
        $sort='';
        $list = D('Home/Commissions')->getconsumptionsList($uid,$pageIndex,$pageSize,$starttime,$endtime,$sort);

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
    function member_banks(){

        $user_token = I("post.user_token");
        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        }
        else
        {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }

     //   $uid = 10000323; // 测试数据
        $list = M('member_banks')->where('uid='.$uid)->limit(3)->select();
//        echo '<pre>';
//        print_r($list);exit;
        $count= M('member_banks')->where('uid='.$uid)->count();
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'list' => $list,
            'count'=>$count
        );
        echo json_encode($arr);

    }




    /*
     * wap端佣金消费
     */

    public function countcommission()
    {
        $user_token = I("post.user_token");
        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        }
        else
        {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }

      //  $uid = 10000323; // 测试数据

        $sumcommission=M('commissions')->where('ofid='.$uid)->sum('commission');   //累计获得佣金
        $withdraw_bank=M('consumptions')->where('uid='.$uid.' and bank_id > 0 and consumption_status="1"')->sum('consumption_money');  //累计提现到银行卡
        $withdraw_user=M('consumptions')->where('uid='.$uid.' and bank_id = 0 and consumption_status="1"')->sum('consumption_money');   //累计转账到帐号
        $sumconsumption=M('consumptions')->where('uid='.$uid.' and consumption_status="1"')->sum('consumption_money');     //累计消费佣金
        $remain=M('member')->where('uid='.$uid)->getField('brokerage');      //剩余可用佣金

        if(!$sumcommission)
        {
            $sumcommission=0;
        }
        if(!$withdraw_bank)
        {
            $withdraw_bank=0;
        }
        if(!$withdraw_user)
        {
            $withdraw_user=0;
        }
        if(!$sumconsumption)
        {
            $sumconsumption=0;
        }
        if(!$remain)
        {
            $remain=0;
        }
        $list=array(
            'sumcommission'=>$sumcommission,
            'withdraw_bank'=>$withdraw_bank,
            'withdraw_user'=>$withdraw_user,
            'sumconsumption'=>$sumconsumption,
            'remain'=>$remain,
            );

        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'list' => $list,
        );
        echo json_encode($arr);

    }

    /*
     * wap 消费明细列表
     */

    public function wapconsumptionsList()
    {

        $user_token = I("post.user_token");
        $pageIndex = I("post.pageIndex",0);
        $pageSize = I("post.pageSize",10);
        $start = $pageIndex * $pageSize;
        $end = $pageSize;

        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
        }
        else
        {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }

        //$uid = 10000323; // 测试数据
        $list = M('consumptions')->field('id,bank_id,consumption_money,consumption_status,addtime')->where('uid='.$uid)->order('addtime desc')->limit($start,$end)->select();
        $count = M('consumptions')->where('uid='.$uid)->count();
        $pagecount=ceil($count/$pageSize);

        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'list' => $list,
            'count' => $pagecount,
        );
        echo json_encode($arr);

    }

    /*
     * wap 消费明细详情
     */

    public function wapconsumptionsInfo()
    {

        $id=I('id',1);
        $info = M('consumptions')->field('consumption_status,consumption_money,addtime,seriall_number,bank_id,remain_money')->where('id='.$id)->find();

//        echo '<pre>';
//        print_r($info);exit;
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'info' => $info,
        );
        echo json_encode($arr);

    }

    /* 银行卡修改新增页面 */

    public function member_bankInfo()
    {
        $id=I('post.id',1);
        if($id)
        {
        $result = M('member_banks')->where('id='.$id)->find();
        }
        else
        {
         $result=array();
        }
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'info' => $result,
        );
        echo json_encode($arr);
    }


    /************操作***********************/

    /*
 * uid  用户ID, account_name 开户姓名,bank_name  开户银行,bank_code  银行帐号,sub_branch  开户支行,addtime 新增时间
 * 银行卡修改，新增
 */

    public function member_banksEdit()
    {



        $data=I('post.');
        if(!$data)
        {
            UtilApi::getInfo('500', '数据不存在');
            return;
        }
        $user_token = I("post.user_token");
        if (!empty($user_token)) {
            $uid = floatval(S($user_token));


            if(!$uid)
            {
                UtilApi::getInfo('500', '登录超时,请重新登陆!');
                return;
            }

        }
        else
        {
            UtilApi::getInfo('500', '登录超时,请重新登陆!');
            return;
        }

        //根据用户id 查询出手机号
        $phone = M('member')->where('uid='.$uid)->getField('mobile');
        $code =$data['code'];
        if(!$code)
        {
            UtilApi::getInfo('500', '验证码不能为空');
            return;
        }

        $start = 2;
        $tpl = I("post.type");

    // phone 手机号  code 验证码  start 状态 2  模版   tpl 1
        A('Verify')->checkVerify($phone,$code,$start,$tpl);


//        $data=array(
//          'uid'=>123,
//            'account_name'=>123,
//            'bank_code'=>123,
//        'sub_branch'=>123
//
//        );
        $data['addtime']=time();
        $data['uid']=$uid;


        try{
            if(empty($data['id']))
            {
                $bankcount= M('member_banks')->where('uid='.$uid)->count();
                if($bankcount >= 3)
                {
                    UtilApi::getInfo('500', '您已经保存了3张银行卡');
                    return;
                }

                $result = M('member_banks')->add($data);
            }
            else
            {

                $result = M('member_banks')->where('id='.$data['id'])->save($data);
            }
        }catch (Exception $e)
        {
            UtilApi::getInfo('500',$e);
            return;
        }
        if($result)
        {
            $arr=array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
            );
        }
        else
        {
            $arr=array(
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

    public function member_banksdel()
    {
        $id=I('post.id');
        $result = M('member_banks')->where('id='.$id)->delete();
        if($result)
        {
            $arr=array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
            );
        }
        else
        {
            $arr=array(
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
        $bank_id = I("post.bank_id");
        $code = I("post.code");
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
        $tpl = I("post.type");

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
        $money = M('member')->where('uid='.$uid)->getField('brokerage');

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




}