<?php

namespace Home\Common;

/**
 * 工具操作类
 * 2015.12.24
 */
class UtilApi {
    /*
     * 获取用户ip，地理位置信息
     */

    public static function getIpLookup($ip = '') {
        if (empty($ip)) {
            return '请输入IP地址';
        }
        $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
        if (empty($res)) {
            return false;
        }
        $jsonMatches = array();
        preg_match('#\{.+?\}#', $res, $jsonMatches);
        if (!isset($jsonMatches[0])) {
            return false;
        }
        $json = json_decode($jsonMatches[0], true);

        if (isset($json['ret']) && $json['ret'] == 1) {
            $json['ip'] = $ip;
            unset($json['ret']);
        } else {
            return false;
        }
        return $json['province'] . $json['city'];
    }

    /*
     * 获取访问IP的位置信息
     */

    public static function getLocation() {
        $getIpLookup = UtilApi::getIpLookup(get_client_ip());
        return $getIpLookup;
    }

    /*
     * thinkphp自带获取IP地址位置(获取不准确)
     */

    public static function getIPAddress($ip = '') {
        $Ip = new \Org\Net\IpLocation(); // 实例化类
        $location = $Ip->getlocation($ip); // 获取某个IP地址所在的位置
        return $location;
    }

    /*
     * 产生6位数字字符串验证码
     */

    static function createCode($length = 6) {
        $randcode = '';
        for ($i = 0; $i < $length; $i++) {
            $randcode.=chr(mt_rand(48, 57));
        }
        return $randcode;
    }

    /*
     * 随机产生token，包含字母数字
     */

    static function getToken($action = "") {
        //sha1()函数，“安全散列算法（SHA1)
        $data = $action . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . time() . rand();
        return sha1($data);
    }

    /*
     * 返回json code和info信息
     */

    static function getInfo($code, $info) {
        $arr = array(
            'code' => $code,
            'info' => $info
        );
        echo json_encode($arr);
    }

    /*
     * 分页
     * 返回$pageCount
     */

    static function getPage($pageSize, $total) {
        if ($pageSize == 0) {
            $arr = array(
                'code' => 500,
                'info' => 'pageSize必须大于0'
            );
            echo json_encode($arr);
            exit;
        }
        $pageCount = floor($total / $pageSize);
        if ($total % $pageSize > 0) {
            $pageCount = $pageCount + 1;
        }
        return $pageCount;
    }

    static function checkcase($str) {
        if (strtoupper($str) === $str) {
            $str = strtolower($str);
            return $str;
        } else {
            return $str;
        }
    }

    /*
     * 生成唯一的订单号
     */

    static function build_order_no() {
        return date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }

}
