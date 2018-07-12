<?php
/**
 * 从共文件夹，提供许多公用的函数
 */

//获取年月日时组成的数字
function getTimeInfo(){
    $time = date('YmdH', time());
    return $time;
}
/*获取当前时间*/
function getCurrentTime(){
    return date('Y-m-d H:i:s', time());
}
/**
 *获取访问者IP地址，返回的是字符串形式的IP地址 
 *@return '127.0.0.1'
 */
function getIP() {
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    }
    elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    }
    elseif (getenv('HTTP_X_FORWARDED')) {
        $ip = getenv('HTTP_X_FORWARDED');
    }
    elseif (getenv('HTTP_FORWARDED_FOR')) {
        $ip = getenv('HTTP_FORWARDED_FOR');

    }
    elseif (getenv('HTTP_FORWARDED')) {
        $ip = getenv('HTTP_FORWARDED');
    }
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;


}