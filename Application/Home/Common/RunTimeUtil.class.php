<?php

namespace Home\Common;

use Addons\Cqssc\CqsscApi;

/*
 * 后台定时器和时时彩开奖时间计算类
 * 2016.02.24
 */

class RunTimeUtil {
    /*
     * 获取彩票时时彩并保存最新一条记录
     */

    public static function cqssc_cache() {
        $result = CqsscApi ::getCqssc(); //获取时时彩数据
        $array = json_decode($result, true);
        foreach ($array as $key => $value) {
            //时时彩数据号码分解
            $hour_code_id = $key;
            $hour_code = '';
            $number = explode(",", $array[$key]['number']);
            foreach ($number as $k => $v) {
                $hour_code .= $v;
            }
            $dateline = $array[$key]['dateline'];
            break;
        }
        if ((F('isNew'))) {
            if (F('isNew') == 2) {
                //缓存新一期的时时彩数据记录已经使用
                if (F('hour_code_id') != $hour_code_id) { //判断是否有最新一期的时时彩数据记录，缓存最新一期的时时彩数据记录
                    F('hour_code_id', $hour_code_id);
                    F('hour_code', $hour_code);
                    F('dateline', $dateline);
                    F('isNew', 1);
                }
            } else {
                //缓存的新一期的时时彩数据记录还没使用
            }
        } else {
            //第一次缓存
            F('hour_code_id', $hour_code_id);
            F('hour_code', $hour_code);
            F('dateline', $dateline);
            F('isNew', 2); //第一次请求默认为已经使用
        }
    }

    /*
     * 获取下一期时时彩距离开奖的时间，以秒为单位
     */

    public static function cqsscNextTime() {
        date_default_timezone_set('PRC');
        $prize_time = strtotime(F('dateline'));  //时时彩最近一期已开奖时间
        $minus_s = strtotime('now') - $prize_time; //时时彩最近一期开奖时间跟系统相差秒数
        if (F('isNew') == 1) { //标识这一期的时时彩已经开奖，直接获取下一期的开奖时间
            $sec = CqsscApi::getCqsscInterval() - $minus_s;
        } else { //标识这一期还没开奖，下一期的开奖时间直接加上时时彩下一期开奖的间隔时间
            $sec = CqsscApi::getCqsscInterval() - $minus_s + CqsscApi::getCqsscInterval();
        }
        return $sec;
    }

    /*
     * 获取最新时时彩距离开奖的时间，以秒为单位，如果时时彩已开奖，定时器还没执行，则返回0
     */

    public static function cqsscTime() {
        if (F('isNew') == 1) { //判断最新时时彩是否已开奖
            return 0;
        }
        date_default_timezone_set('PRC');
        $minus_s = strtotime('now') - strtotime(F('dateline')); //时时彩最近一期已开奖时间跟系统相差秒数
        $sec = CqsscApi::getCqsscInterval() - $minus_s;
        return $sec;
    }

    /*
     * 获取距离下一期时时彩开奖的时间，转换为时间戳
     */

    public static function cqsscTimestamp() {
        date_default_timezone_set('PRC');
        $prize_time = strtotime(F('dateline'));  //彩票最近一期已开奖时间
        return $prize_time + CqsscApi::getCqsscInterval();
    }

    /*
     * 获取等待揭晓剩余执行时间,以秒为单位
     */

    public static function runTime() {
        date_default_timezone_set('PRC');
        $nowTime = strtotime('now');
        if (F('runTime')) {
            $r_time = F('runTime');   //定时器上次运行时间
        } else {
            $r_time = $nowTime;
        }
        $time_run = C('TIME_RUN'); //定时器执行间隔时间
        $s = $nowTime - $r_time; //上次定时器运行时间跟系统相差秒数
        $sec = $time_run - $s; //定时器剩余执行的时间
        $cqsscTime = RunTimeUtil::cqsscTime(); //时时彩距离开奖时间
        if ($cqsscTime <= $sec) {
            //如果时时彩距离开奖的时间小于等于定时器剩余执行的时间，直接返回定时器剩余执行的时间 
        } else { //如果时时彩距离开奖的时间大于定时器剩余执行的时间，则返回下一次定时器剩余执行时间或者时时彩距离开奖的时间
            $sec = $sec + $time_run; //返回下一次定时器剩余执行时间
            if ($cqsscTime > $sec) {//如果时时彩开奖时间还是大于下一次定时器剩余执行时间
                $sec = $cqsscTime; //返回时时彩距离开奖剩余的时间
            }
        }
        if ($sec < 0) {
            $sec = 15 * 60; //如果等待时间小于0，则等待时间为15分钟
        }
        return ($sec + 2); //加2秒的执行误差
    }

    /*
     * 获取下一次等待揭晓剩余执行时间,以秒为单位
     */

    public static function runNextTime() {
        date_default_timezone_set('PRC');
        $nowTime = strtotime('now');
        if (F('runTime')) {
            $r_time = F('runTime');   //定时器上次运行时间
        } else {
            $r_time = $nowTime;
        }
        $time_run = C('TIME_RUN'); //定时器间隔时间执行时间
        $s = $nowTime - $r_time; //上次定时器运行时间跟系统相差秒数
        $sec = $time_run - $s + $time_run; //下一次定时器剩余执行的时间
        $cqsscNextTime = RunTimeUtil::cqsscNextTime();
        if ($cqsscNextTime <= $sec) {
            //如果下一次时时彩距离开奖的时间小于等于下一次定时器剩余执行的时间，直接返回下一次定时器剩余执行的时间 
        } else { //如果时时彩距离开奖的时间大于定时器剩余执行的时间，则返回下一次定时器剩余执行时间或者时时彩距离开奖的时间
            $sec = $sec + $time_run; //返回下下次定时器剩余执行时间
            if ($cqsscNextTime > $sec) {//如果时时彩开奖时间还是大于下一次定时器剩余执行时间
                $sec = $cqsscNextTime + $sec; //返回时时彩距离开奖剩余的时间加上下下次定时器剩余执行的时间
            }
        }
        if ($sec < 0) {
            $sec = 15 * 60; //如果等待时间小于0，则等待时间为15分钟
        }
        return ($sec + 2); //加2秒的执行误差
    }

}
