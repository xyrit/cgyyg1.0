<?php

//系统默认PATHINFO模式,ThinkPHP支持的URL模式有四种：普通模式、PATHINFO、REWRITE和兼容模式
define('APP_DEBUG', TRUE);

header("Access-Control-Allow-Origin:*");


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/2/24
 * Time: 9:56
 */
define('THINK_PATH', './ThinkPHP/');
define('APP_NAME', 'Application');
//define('BIND_MODULE','Home');
define('APP_PATH', './Application/');
define('BASE_PATH', str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . "/");
define('DOC_ROOT_PATH', rtrim(dirname(__FILE__), '/\\') . DIRECTORY_SEPARATOR);
/**
 * 缓存目录设置
 * 此目录必须可写，建议移动到非WEB目录
 */
//define ( 'RUNTIME_PATH', '/home/cgyygRunTime/' ); //linux系统
define('RUNTIME_PATH', './cgyygRunTime/');  //windows系统
require(THINK_PATH . "ThinkPHP.php");





