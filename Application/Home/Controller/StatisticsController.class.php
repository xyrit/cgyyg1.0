<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/3/9
 * Time: 14:56
 * 数据统计接口
 */

namespace Home\Controller;
use Think;

header("Access-Control-Allow-Origin:*");

class StatisticsController extends HomeController
{

            private $redis;

        public function __construct()
        {
            if(!$this->redis)
            {
                $this->redis= new Think\RedisContent();
            }

        }



    /*
     * 统计出一级好友，二级好友
     */
    public function statistics_User_Count($fid=0,$ofid=0)
    {
         $redis=$this->redis->redis();
      
        if($fid)
        {
          $redis->Incr('onelevel_count');
        }
        if($ofid)
        {

            $redis->Incr('twolevel_count');
        }

        return true;
    }
        // 统计消费
    public function statistics_Commission_Count($one_consumption=0,$two_consumption=0)
    {
        $redis = self::getredis();
      
        if ($one_consumption) {
            $redis->Incr('one_consumption', $one_consumption);
        }
        if ($two_consumption) {
            $redis->Incr('two_consumption', $two_consumption);
        }
    }

        // 统计消费佣金  withdrawals  提现   accounts 转账
        public function consumption_Commission_Count($withdrawals,$accounts)
    {
        $redis = self::getredis();
      
        if ($withdrawals) {
            $redis->Incr('withdrawals', $withdrawals);
        }
        if ($accounts) {
            $redis->Incr('accounts', $accounts);
        }
    }



    //每天记录12:00插入数据库,清除元素       分销数据

    public function Distribution_in_database()
    {
        $redis = self::getredis();
      
        //新增人数
        $onelevel_count=$redis->get('onelevel_count');
        $twolevel_count=$redis->get('twolevel_count');
        //计算好友总消费  sum_consumption
        $one_consumption=$redis->get('one_consumption');
        $two_consumption=$redis->get('two_consumption');
        $sum_consumption=$one_consumption+$two_consumption;  //总消费
       // $sum_commission=$one_consumption+$two_consumption;  //总佣金
        $withdrawals=$redis->get('withdrawals');
        $accounts=$redis->get('accounts');
        $sum_cp = $withdrawals+$accounts;               //总消费
        $array_mget=array('onelevel_count','twolevel_count','one_consumption','two_consumption','withdrawals','accounts');
        $redis->del($array_mget);
        $time=time();
        $atime=date('Y-m-d',$time);
        $year=date('Y',$time);
        $month=date('m',$time);
        $day=date('d',$time);
        $arr=array(
            'time'=>$time,
            'onelevel_count'=>$onelevel_count,
            'twolevel_count'=> $twolevel_count,
            'one_consumption'=>$one_consumption,
            'two_consumption'=>$two_consumption,
            'sum_consumption'=>$sum_consumption,         //总消费
            'sum_commission'=>$sum_commission,          //总佣金
            'withdrawals'=>$withdrawals,
            'accounts'=>$accounts,
            'sum_cp'=>$sum_cp,
            'atime'=>$atime,
            'year'=>$year,
            'month'=>$month,
            'day'=>$day,
        );
        $rs=M('distribution_statistics')->save($arr);

        if($rs)
        {
            return true;
        }
        else
        {
            return false;
        }

    }

        //统计访问次数
    public function visits()
    {
        $redis=$this->redis->redis();
      
        $redis->Incr('visits');
    }
    //统计新增用户
    public function user_count()
    {
        $redis=$this->redis->redis();
      
        $redis->Incr('new_user');          //新增用户
        $redis->Incr('old_usercount');     //累计总用户
    }
    //统计活跃用户           登录执行
    public function active_users($uid=0)
    {

        $redis=$this->redis->redis();
        $redis->sadd('user_id',$uid);

    }
    //统计充值用户 ,新充值用户
    public function new_paying_customers($id,$money)
    {

            $redis=$this->redis->redis();
        $redis->Incr('recharge_user_count');
        $redis->Incr('recharge_user_money_count',$money);
        // 查询是否充值过
       $rs= M('recharge')->where('uid='.$id)->find();
        if(!$rs)
        {

            //新充值用户
            $redis->Incr('new_paying_customers');
            $redis->Incr('new_paying_customers_money',$money);
        }

    }


    //12点执行方法插入数据     用户数据 新增数据
    public function news_user_in_database()
    {
        //数据取出
        $redis=$this->redis->redis();
      
        $new_user=$redis->get('new_user');   //新用户
        $old_usercount=$redis->get('old_usercount');        //累计用户
        $users=$redis->smembers('user_id');  //活跃用户
        $active_users=count($users);        //活跃用户数目
        $new_paying_customers_money = $redis->get('new_paying_customers_money'); //新用户总充值金额
        $new_paying_customers = $redis->get('new_paying_customers');  //新用户充值人数
        $customers_arppu = round($new_paying_customers_money/$new_paying_customers,2); //新用户付费ARPPU
        //统计存留率
        //查询昨天注册今天登录的用户
        $daybegin = strtotime(date('Y-m-d', strtotime("-1 day")));  //前天凌晨
        $dayend=$daybegin+86400;
        $today = strtotime(date('Y-m-d', time()));
        //查询昨天注册的用户
        $yesterday_rate=D('Statistics')->survival_rate($daybegin,$dayend,$today);

        //查询前七天注册今天登录的用户
        $daybegin_7 = strtotime(date('Y-m-d', strtotime("-7 day")));  //前七天凌晨
        $dayend_7=$daybegin_7+86400;
        $dayend_7_rate=D('Statistics')->survival_rate($daybegin_7,$dayend_7,$today);

        //查询前30天注册今天登录的用户
        $daybegin_30 = strtotime(date('Y-m-d', strtotime("-30 day")));  //前三十天凌晨
        $dayend_30=$daybegin_30+86400;
        $dayend_30_rate=D('Statistics')->survival_rate($daybegin_30,$dayend_30,$today);
        $arr=array(
            'new_user'=>$new_user,
            'old_usercount'=>$old_usercount,
            'active_users'=>$active_users,
            'new_paying_customers_money'=>$new_paying_customers_money,
            'new_paying_customers'=>$new_paying_customers,
            'customers_arppu'=>$customers_arppu,
            'yesterday_rate'=>$yesterday_rate,
            'yesterday_7_rate'=>$dayend_7_rate,
            'yesterday_30_rate'=>$dayend_30_rate,

        );
       M('os_news_user_count')->add($arr);

    }

    //测试
    function cs()
    {

        $obj= new Think\RedisContent();

        $obj->redis()->set('b',22);

    }

    //12点插入充值数据  充值数据
    function recharge_data(){

        //数据取出
        $redis=$this->redis->redis();

        $users=$redis->smembers('user_id');  //活跃用户
        $active_users=count($users);        //活跃用户数目
        $recharge_user_count=$redis->smembers('recharge_user_count');      //充值人数
        $recharge_user_money_count=$redis->smembers('recharge_user_money_count'); // 充值总额
        $arpu= round($recharge_user_money_count/$active_users,2); // arpu   充值总额/活跃用户
        $arppu= round($recharge_user_money_count/$recharge_user_count,2); // arppu 充值总额/充值用户
        $new_paying_customers_money = $redis->get('new_paying_customers_money'); //新用户总充值金额
        $new_paying_customers = $redis->get('new_paying_customers');  //新用户充值人数                                                                   //首冲用户
        $new_paying_arpu = round($new_paying_customers_money/$new_paying_customers,2);   //首冲 arpu  首冲总额/首冲用户

        //时间转换
        $time=time();
        $atime=date('Y-m-d',$time);
        $year=date('Y',$time);
        $month=date('m',$time);
        $day=date('d',$time);

        $arr=array(
         'active_users'=>$active_users,  //活跃用户数目
         'recharge_user_count'=>$recharge_user_count,    //充值人数
         'recharge_user_money_count'=>$recharge_user_money_count,   //充值总额
         'arpu'=>$arpu,
         'arppu'=>$arppu,
         'new_paying_customers_money'=>$new_paying_customers_money,
         'new_paying_customers'=>$new_paying_customers,
         'new_paying_arpu'=>$new_paying_arpu,
         'time'=>$time,
         'atime'=>$atime,
         'year'=>$year,
         'month'=>$month,
         'day'=>$day,
        );
        M('tj_recharge_data')->add($arr);

    }

    //统计消费数据
    function xiaofeiyonghu($uid,$money)
    {

        $redis=$this->redis->redis();
        $redis->sadd('consumption_user_id',$uid);   //总消费用户
        $redis->Incr('consumption_user_money',$money); //总消费
        // 查询是消费过
        $rs= M('lottery_attend')->where('uid='.$uid)->find();
        if(!$rs)
        {
            $redis->sadd('new_consumption_user_id');    // 新消费用户
            $redis->Incr('new_consumption_user_money',$money);    //新消费用户总额
        }

    }


    //12点插入数据  消费数据
    function consumption_data()
    {
        $redis=$this->redis->redis();
        $users=$redis->smembers('user_id');  //活跃用户
        $active_users=count($users);        //活跃用户数目
        $consumption_user=$redis->smembers('consumption_user_id');  //总消费用户
        $consumption_user_count=count($consumption_user);        //总消费用户数目
        $consumption_user_money=$redis->get('consumption_user_money');  //用户总消费
        $consumption_arpu =round($consumption_user_money/$active_users,2);   //消费ARPU
        $consumption_arppu =round($consumption_user_money/$consumption_user_count,2);  //消费ARPPU

        $new_consumption_user=$redis->smembers('new_consumption_user_id');  //新消费用户
        $new_consumption_user_count=count($new_consumption_user);        //总消费用户数目
        $new_consumption_user_money=$redis->get('new_consumption_user_money');   //新用户总消费
        $new_consumption_arpu = round($new_consumption_user_money/$new_consumption_user_count,2);   //首次消费ARPU

        //时间转换
        $time=time();
        $atime=date('Y-m-d',$time);
        $year=date('Y',$time);
        $month=date('m',$time);
        $day=date('d',$time);


        $arr=array(
            'active_users'=>$active_users,
            'consumption_user_count'=>$consumption_user_count,
            'consumption_user_money'=>$consumption_user_money,
            'consumption_arpu'=>$consumption_arpu,
            'consumption_arppu'=>$consumption_arppu,
            'new_consumption_user'=>$new_consumption_user,
            'new_consumption_user_money'=>$new_consumption_user_money,
            'new_consumption_arpu'=>$new_consumption_arpu,
            'time'=>$time,
            'atime'=>$atime,
            'year'=>$year,
            'month'=>$month,
            'day'=>$day,

        );
        M('tj_consumption_data')->add($arr);

    }

    //活跃留存用户统计   //放在修改登录时间前

    function active_retained_user($uid){

        $last_login_time=  M('member')->where('uid='.$uid)->getField('last_login_time');
        $time=time();
        $redis=$this->redis->redis();
        $rtime= $time-$last_login_time;
        switch ($last_login_time)
        {
            case ($rtime > 86400 and $rtime < 2* 86400):
                $redis->Incr('30_active_user');
                break;
            case ($rtime >7 * 86400 and $rtime < 30 * 86400):
                $redis->Incr('7_active_user');
                 break;
            case $rtime >= 30 * 86400:
                $redis->Incr('yesterday_active_user');
                break;

        }

    }
    //12点执行方法插入数据     用户数据 活跃用户
    function active_user_in_database()
    {
        $redis=$this->redis->redis();
        $users=$redis->smembers('user_id');  // 当天活跃用户
        $active_users=count($users);        // 活跃用户数目
        $old_user =  M('member')->count();   //老用户

        $day_7 = time()-(7*86400);                                    //七天前时间
        $day_30 = time()-(30*86400);                                       //30天前时间


        $where_7 = array(

          'last_login_time'=>array('GT',$day_7)
        );
        $where_30 = array(

            'last_login_time'=>array('GT',$day_30)
        );
        $user_count_7=  M('member')->where($where_7)->count();                            //周活跃用户
        $user_count_30=  M('member')->where($where_30)->count();                           //月活跃用户
        $retention_yesterday =$redis->set('yesterday_active_user')/$active_users;          //次日留存率

        $retention_7 =$redis->set('7_active_user')/$active_users;      //7日留存率
        $retention_30 =$redis->set('30_active_user')/$active_users;     // 30日留存率


        //时间转换
        $time=time();
        $atime=date('Y-m-d',$time);
        $year=date('Y',$time);
        $month=date('m',$time);
        $day=date('d',$time);

        $arr=array(
            'active_users'=>$active_users,
            'old_user'=>$old_user,
            'user_count_7'=>$user_count_7,
            'user_count_30'=>$user_count_30,
            'retention_yesterday'=>$retention_yesterday,
            'retention_7'=>$retention_7,
            'retention_30'=>$retention_30,
            'time'=>$time,
            'atime'=>$atime,
            'year'=>$year,
            'month'=>$month,
            'day'=>$day,

        );
        M('tj_active_user_data')->add($arr);

    }



}
