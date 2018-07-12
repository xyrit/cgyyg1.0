<?php

namespace Home\Controller;

use Home\Common\UtilApi;

header("Access-Control-Allow-Origin:*");

/**
 * 
 * 图片上传，用于上传头像、晒单图片等等。
 * Author: joan
 */
class UploadController extends HomeController {
    /*
     * 上传接口
     */

    public function get_file() {

        function gmt_iso8601($time) {


            $dtStr = date("c", $time);
            // $mydatetime = new DateTime($dtStr);
            $mydatetime = new \DateTime($dtStr);
            $expiration = $mydatetime->format(\DateTime::ISO8601);
            $pos = strpos($expiration, '+');
            $expiration = substr($expiration, 0, $pos);
            return $expiration . "Z";
        }

        require_once '../cgyyg1.0/Application/Addons/upload/oss_php_sdk/sdk.class.php'; //exit;
        $pc_load_url = C('pc_load_url');
        $id = '08iJabGVcaucodBT';   //阿里云Access Key ID
        $key = 'RkyzXVJI7TRPlM0e8SrgyTsS2RU4P7'; //阿里云Access Key Secret
        $host = 'http://pic.cgyyg.com';
        $host_url = C('HOST_URL');
        $dpid = $_GET['id'];
        $type = $_GET['type']; //判断晒单还是头像
        $os = $_GET['os']; //判断手机端还是pc端
        //callbackUrl和callbackHost的域名必须一致，否则无法上传
        $callback_body = '{"callbackUrl":"' . $pc_load_url . '/cgyyg1.0/index.php/Home/Upload/callback?id=' . $dpid . '&type=' . $type . '","callbackHost":"' . $host_url . '","callbackBody":"filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}","callbackBodyType":"application/x-www-form-urlencoded"}';

        $base64_callback_body = base64_encode($callback_body);
        if ($type == '1') {
            $dir = 'cg-head_img/'; //用户头像
        } else {
            $dir = 'cg-display/'; //晒单图片
        }
        $now = time();
        $expire = 30; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = gmt_iso8601($end);
        $oss_sdk_service = new \alioss($id, $key, $host);
        //$dir = 'cg-picture/';
        //最大文件大小.用户可以自己设置
        $condition = array(0 => 'content-length-range', 1 => 0, 2 => 1048576000);
        $conditions[] = $condition;

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0 => 'starts-with', 1 => '$key', 2 => $dir);
        $conditions[] = $start;

        $arr = array('expiration' => $expiration, 'conditions' => $conditions);

        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        $response['accessid'] = $id;
        $response['host'] = $host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64_callback_body;
        //这个参数是设置用户上传指定的前缀
        $response['dir'] = $dir;

        echo json_encode($response);
    }

    /*
     * 上传成功回调入库
     */

    public function callback() {
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
                $info = M("audit_face")->field('uid')->where('uid=' . $id . ' and type=1')->find();
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

                $orderid = M("display_product")->where('id=' . $id)->save($data); //修改数据库图片
                break;
        }
    }

}
