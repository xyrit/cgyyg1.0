<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class HttpRequest {

    static function httpGet($url, $param = array()) {
        if (!is_array($param)) {
            throw new Exception("参数必须为array");
        }
        $p = '';
        foreach ($param as $key => $value) {
            $p = $p . $key . '=' . $value . '&';
        }
        if (preg_match('/\?[\d\D]+/', $url)) {//matched ?c
            $p = '&' . $p;
        } else if (preg_match('/\?$/', $url)) {//matched ?$
            $p = $p;
        } else {
            $p = '?' . $p;
        }
        $p = preg_replace('/&$/', '', $p);
        $url = $url . $p;
        //echo $url;
        $httph = curl_init($url);
        curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($httph, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($httph, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");

        curl_setopt($httph, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($httph, CURLOPT_HEADER, 0);

        $rst = curl_exec($httph);
        curl_close($httph);
        return $rst;
    }

    /*
     * http request tool post method
     */

    static function httpPost($url, $param = array()) {
        if (!is_array($param)) {
            throw new Exception("参数必须为array");
        }
        $httph = curl_init($url);
        curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($httph, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($httph, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($httph, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
        curl_setopt($httph, CURLOPT_POST, 1); //设置为POST方式   
        curl_setopt($httph, CURLOPT_POSTFIELDS, $param);
        curl_setopt($httph, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($httph, CURLOPT_HEADER, 1);
        $rst = curl_exec($httph);
        curl_close($httph);
        return $rst;
    }
}
