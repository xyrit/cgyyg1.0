<?php

namespace Home\Common;

/*
 * 时间操作工具类
 * 2015.12.24
 */

class TimeUtil {
    /*
     * 获取现在时间的时间戳
     */

    static function timeStamp() {
        date_default_timezone_set(PRC);
        $nowtime = date("Y-m-d G:i:s");
        $dateline = strtotime($nowtime);
        return $dateline;
    }

    /*
     * 获取当前时间
     */

    static function getTime() {
        return date('Y-m-d H:i:s', time());
    }

}
