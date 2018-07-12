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

class ActivityController extends HomeController
{

    //统计活动新增用户

    public function activityScan_code()
    {

        $activity_id= session('activity_id');
        var_dump($activity_id);
        exit;
        //$activity_id=66;
        S('activity_id',null);
        if(!$activity_id)
        {
            return false;
        }
        $time=date('Ymd',time());
       // $time=  20160324;
        $where='atime='.$time.' and activity_id='.$activity_id;


        $rs1= M('activity_statistics')->where($where)->find();
        if($rs1)
        {
            $rs= M('activity_statistics')->where($where)->setInc('new_users');
        }
        else
        {
            $arr1 = array(
                'atime' => $time,
                'activity_id' => $activity_id,
                'new_attention' => 1,
                'time' => time(),
                'year' => date('Y', time()),
                'month' => date('m', time()),
                'day' => date('d', time()),
            );
          $rs=  M('activity_statistics')->add($arr1);

        }

         return rs;

    }

    //统计扫码消费
    public function Recharge_code($user_id)
    {

        $activity_id=M('member')->where('uid='.$user_id.' and activity_id > 0')->getField('activity_id');
        if($activity_id)
        {
            $time=date('Ymd',time());
            $where='atime='.$time.' and activity_id='.$activity_id;
            $rs= M('activity_statistics')->where($where)->setInc('new_users');
        }

    }



}