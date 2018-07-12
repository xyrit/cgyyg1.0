<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/2/24
 * Time: 9:56
 */
header("Access-Control-Allow-Origin:*"); //跨域问题

define('APP_DEBUG', true);
define('BIND_MODULE', 'Wap');
define('APP_PATH', './Application/');
// 引入ThinkPHP入口文件
define('DOC_ROOT_PATH', rtrim(dirname(__FILE__), '/\\') . DIRECTORY_SEPARATOR);
//define ( 'RUNTIME_PATH', '/home/cgyygRunTime/' ); //linux系统
define('RUNTIME_PATH', './cgyygRunTime/');  //windows系统
require './ThinkPHP/ThinkPHP.php';
