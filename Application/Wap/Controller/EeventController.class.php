<?php

// +----------------------------------------------------------------------
// | 活动接口控制器
// +----------------------------------------------------------------------
// | Author: charles
// +----------------------------------------------------------------------
// | ps：由于是临时活动，红包活动采取写死状态
// +----------------------------------------------------------------------
namespace Wap\Controller;
use Home\Common\UtilApi;
header("Access-Control-Allow-Origin:*");



class EeventController extends HomeController {

    private $uid; //用户uid
    private $user_token; //用户uid
    private $total_money; //用户uid
    private $RedpacketUser;
    private $red_config=array(
       // 'red1'=>array(0,1),
        'red2'=>array(20,2),
        'red3'=>array(50,3),
        'red4'=>array(100,5),
        'red5'=>array(200,10),
        'red6'=>array(500,30),
        'red7'=>array(1000,50)
    );
    private $default_return=array(
       // 'red1'=>1,
        'red2'=>0,
        'red3'=>0,
        'red4'=>0,
        'red5'=>0,
        'red6'=>0,
        'red7'=>0
    );
    /**
     *  判断用户充值的总额。多少
     */
    public function __construct(){
        $this->user_token=I('get.user_token',''); //用户token
        if (empty($this->user_token)) {
            UtilApi::getInfo(517, "未登录");
            exit;
        }
        $this->uid=$this->is_login($this->user_token); //获取到uid

        $this->RedpacketUser=M('RedpacketUser');
        $this->getamount($this->uid);
    }

    /**
     * @param uid  用户uid
     * 用户的充值总额
     */
    public function amount(){
         // 充值总额
        echo json_encode(array('code' => 200, 'info' => '成功', 'total_money' => $this->total_money));
    }

    public function getRedDetails(){
        $details = $this->RedpacketUser->where('uid='.$this->uid)->find();
        !$details && $details=$this->default_return;

        foreach($this->red_config as $k => $v){
                if($this->total_money >= $v[0]){
                    if($details[$k] <= 1){
                        $details[$k]='1'; // 将未领取变为可领取
                    }
                }
        }
        unset($details['uid']);
        echo json_encode(array('code'=>'200','info'=>'成功','items'=>$details));

    }
    /**
     * @param uid  用户id
     * @param type  用户id
     */
    public function getRed(){ // 领取红包
        $type=I('get.type','');
        if (empty($type) || !in_array($type,array_keys($this->red_config))) {
            UtilApi::getInfo(500, "红包类型不正确");
            exit;
        }
        if($this->total_money < $this->red_config[$type][0]){
            UtilApi::getInfo(500, "暂时没有资格领取");
            exit;
        }
        $red_details=M('red_details');
        $map['uid']=$this->uid;
        $map['money']=$money=$this->red_config[$type][1];  //红包类型的金额
        if(!$red_details->where($map)->count()){
            $map['create_time']=time();
            $red_details->startTrans(); //开启事物
            $lastid=$red_details->add($map);  //
            if($lastid){
                $insert=array('uid'=>$this->uid,$type=>'2');
                if($this->RedpacketUser->where('uid='.$this->uid)->count()){
                   $result_id= $this->RedpacketUser->save($insert);
                }else{
                   $result_id= $this->RedpacketUser->add($insert);
                }
                // 修改账户余额
                $mem_id= M('Member')->where('uid='.$this->uid)->setInc('account',$money);
                if($result_id && $mem_id){
                    $red_details->commit();
                }else{
                    $red_details->rollback();
                    UtilApi::getInfo(500, "领取失败");
                    exit;
                }
            }
            UtilApi::getInfo(200, "领取成功");
        }else{
            echo json_encode(array('code'=>500,'info'=>'已经领取过，不能重复领取'));
        }
        exit;

    }

    private function getamount($uid){
        $money=D('Home/Recharge')->getamount($uid);
        $this->total_money= $money?intval($money):0;
        return $this->total_money;

    }


}
