<?php

/*
 * 重庆时时彩API
 */

namespace Addons\Cqssc;

class CqsscApi {
    /*
     * 获取最近1条时时彩记录，每隔10s更新新的数据
     */

    public static function getCqssc() {
        error_reporting(E_ALL ^ E_NOTICE);
        date_default_timezone_set('PRC');

        //设置接口参数
        $name = 'cqssc'; //接口名称
        $uid = '152677'; //接口用户id
        $token = 'c680b755bcec4773c204e72783b75675ee926c5f'; //接口token
        //设置缓存文件
        $cache_url = $name . ".txt";

        //缓存文件（最后更新时间）
        $filemtime = filemtime($cache_url);

        //缓存文件（更新频率设置）以秒为单位
        $second = '10';

        if (time() - $filemtime > $second) {

            //设置参数
            $data = file_get_contents("http://api.caipiaokong.com/lottery/?name=" . $name . "&format=json&uid=" . $uid . "&token=" . $token . "&num=" . 1 . "");

            //$data缓存
            $array = json_decode($data, true);
            if (is_array($array)) {
                file_put_contents($cache_url, $data, LOCK_EX);
            }
        }

        //读取缓存
        $data = file_get_contents($cache_url);
        return $data;
    }

    /*
     * 获取时时彩开奖的间隔时间
     */

    public static function getCqsscInterval() {
        $h = date('H');
        if ($h >= 10 && $h <= 21) { //时时彩开奖间隔10分钟
            $s = 10 * 60; 
        } else if ($h >= 22 && $h <= 23) { //时时彩开奖间隔5分钟
            $s = 5 * 60;
        } else if ($h >= 0 && $h < 2) { //时时彩开奖间隔5分钟
            $s = 5 * 60; 
        } else {
            $s = 8 * 60 * 60 + 5*60; //时时彩停止开奖8小时 由于是01:55或者01:56最后一期不是02:00开奖，所以需要加5分钟的时间误差
        }
        return $s;
    }
    
}
