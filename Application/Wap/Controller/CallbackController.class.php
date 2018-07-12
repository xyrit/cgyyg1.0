<?php

namespace Wap\Controller;

use Home\Common\UtilApi;

header("Access-Control-Allow-Origin:*");

/**
 * 验证码
 * 验证码发送、保存、检查
 * Author: joan
 */
class CallbackController extends HomeController {
    /* 获取手机验证码 */

    public function index() {
        $id = I("get.id");
        $type = I("get.type");

        // 1.获取OSS的签名header和公钥url header
        $authorizationBase64 = "";
        $pubKeyUrlBase64 = "";
        /*
         * 注意：如果要使用HTTP_AUTHORIZATION头，你需要先在apache或者nginx中设置rewrite，以apache为例，修改
         * 配置文件/etc/httpd/conf/httpd.conf(以你的apache安装路径为准)，在DirectoryIndex index.php这行下面增加以下两行
          RewriteEngine On
          RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
         * */
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (isset($_SERVER['HTTP_X_OSS_PUB_KEY_URL'])) {
            $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'];
        }

        if ($authorizationBase64 == '' || $pubKeyUrlBase64 == '') {
            header("http/1.1 403 Forbidden");
            exit();
        }

        // 2.获取OSS的签名
        $authorization = base64_decode($authorizationBase64);

        // 3.获取公钥
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);
        if ($pubKey == "") {
            //header("http/1.1 403 Forbidden");
            exit();
        }

        // 4.获取回调body
        $body = file_get_contents('php://input');
        $str = explode("&", $body);
        $pic = $str[0];
        $filename = substr($pic, 9);
        $filename = str_replace("%2F", "/", $filename);

        error_reporting(E_ALL ^ E_NOTICE);
        date_default_timezone_set('PRC');

        // 5.拼接待签名字符串
        $authStr = '';
        $path = $_SERVER['REQUEST_URI'];
        $pos = strpos($path, '?');
        if ($pos === false) {
            $authStr = urldecode($path) . "\n" . $body;
        } else {
            $authStr = urldecode(substr($path, 0, $pos)) . substr($path, $pos, strlen($path) - $pos) . "\n" . $body;
        }
        json_encode($authStr);
        // 6.验证签名
        $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
        if ($ok == 1) {
            $return_data = array("Status" => "Ok");
            $return_data = json_encode($return_data);
            header("Content-Type: application/json");
            header("Content-Length: " . strlen($return_data));
            echo $return_data;
        } else {
            //header("http/1.1 403 Forbidden");
            exit();
        }
        switch ($type) {
            case 1://上传图像
                $data["uid"] = $id;
                $filename = "/" . $filename;
                $data["face"] = $filename;
                $data["apply_time"] = date("Y-m-d H:i:s", time());
                $orderid = M("member")->where('uid=' . $id)->save($data); //修改数据库图片
                $info = M("audit_face")->field('uid')->where('uid=' . $id . ' and status=1')->find();
                $uid = $info["uid"];
                if ($uid > 0) {//数据库已经有了，覆盖
                    M("audit_face")->where('uid=' . $id)->save($data); //修改审核头像
                } else {//插入
                    M("audit_face")->data($data)->add(); //插入审核头像
                }

                break;
            case 2://上传晒单图片
                $arr = array();
                $picture = M("display_product")->field('pics')->where('id=' . $id)->find();
                $picture = $picture["pics"];
                if (!empty($picture)) {
                    $picture .=",/" . $filename;
                } else {
                    $picture = "/" . $filename;
                }


                $data["pics"] = $picture;
                $data["user_name"] = 'joan' . rand(01, 99);
                $orderid = M("display_product")->where('id=' . $id)->save($data); //修改数据库图片
                break;
        }
    }

}
