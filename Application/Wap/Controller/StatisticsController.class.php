<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/3/9
 * Time: 14:56
 * 数据统计接口
 */

namespace Home\Controller;



header("Access-Control-Allow-Origin:*");

class UcenterController extends HomeController
{

    /*
     * 统计出一级好友，二级好友
     */

    public function Statistics_count($uid=0,$ifd=0,$ofid=0)
    {

        $redis = new \Redis();
        $redis->connect("192.168.1.164","6379");  //php客户端设置的ip及端口
        $statis=$redis->get('statis');
        $time=date('Y-m-d',time());

        if($ifd)
        {
            $twolevel_count=$statis['twolevel_count']+1;   //二级
        }
        if($ofid)
        {
            $onelevel_count=$statis['onelevel_count']+1;  //一级
        }

        $arr=array(
            'time'=>time(),
            'onelevel_count'=>$onelevel_count,
            'twolevel_count'=>$twolevel_count,
            'atime'=>date('Y-m-d',time()),
        );
        $get_arr=array(
            'time',
            'onelevel_count',
            'twolevel_count',
            'atime'

        );
        var_dump($arr);exit;
        $redis->set('statis',$arr);
        var_dump($redis->get($get_arr));
    }
}
