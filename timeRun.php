<?php


/*
 * 在time.txt写入调用时间，用于测试定时器是否调用
 * 2016.1.13
 */

error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('PRC');

$name = 'time';
//设置缓存文件
//$cache_url = "/home/wwwroot/cgyygPCWeb1.0/cgyyg1.0/" . $name . ".txt"; //linux系统
$cache_url = "D:/www/Eric/Server/cgyyg1.0/" . $name . ".txt"; //windows系统
//缓存文件（最后更新时间）
//$filemtime = filemtime($cache_url);
$data = 'timeRun==========' . date('Y-m-d H:i:s', time());
file_put_contents($cache_url, $data, LOCK_EX);

/*
 * 揭晓幸运码
 * 2016.1.13
 */

$ch = curl_init();
// 2. 设置选项，包括URL
curl_setopt($ch, CURLOPT_URL, "http://192.168.1.106/cgyyg1.0/index.php/Home/Lottery/lotteryResult"); //本地服务器
//curl_setopt($ch, CURLOPT_URL, "http://test.cgyyg.com/cgyyg1.0/index.php/Home/Lottery/lotteryResult"); //外网服务器
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
curl_setopt($ch, CURLOPT_POST, 1); //设置为POST方式   
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
// 3. 执行并获取HTML文档内容
$output = curl_exec($ch);
// 4. 释放curl句柄
curl_close($ch);

