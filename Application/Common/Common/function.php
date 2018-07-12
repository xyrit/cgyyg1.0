<?php

function get_curl_data($url, $compression = '', $agent = '', $refer = '') {
    if (empty($agent)) {
        $agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36';
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($refer) {
        curl_setopt($ch, CURLOPT_REFERER, $refer); //带来的Referer
    }
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 设置超时限制防止死循环
    if ($compression != '') {
        curl_setopt($ch, CURLOPT_ENCODING, $compression); //压缩
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return array($info, $result);
}

/**
 * 返回数组中指定的一列
 * @param $pArray 需要取出数组列的多维数组(或结果集)
 * @param $pKey 需要返回值的列,它可以是索引数组的列索引,或者是关联数组的列的键
 * @param $pCondition 作为返回数组的索引/键的列,它可以是该列的整数索引,或者字符串键值
 * @return 返回新的一维数组
 */
function getSubByKey($pArray, $pKey = "", $pCondition = "") {
    if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
        return array_column($pArray, $pKey, $pCondition);
    }
    $result = array();
    $i = 0;
    if (is_array($pArray)) {
        foreach ($pArray as $temp_array) {
            is_object($temp_array) && $temp_array = (array) $temp_array;
            $result[$pCondition && isset($temp_array[$pCondition]) ? $temp_array[$pCondition] : $i] = ("" == $pKey) ? $temp_array : isset($temp_array[$pKey]) ? $temp_array[$pKey] : "";
            $i++;
        }
        return $result;
    } else {
        return false;
    }
}


function check_verify($code, $id = 1) {
    $verify = new \Think\Verify(array('length' => 4, 'reset' => false));
    return $verify->check($code, $id);
}

function getUserInfo($uid=0,$field=''){ // 后期需要优化
   $userinfo= M('Member')->where(array('uid'=>$uid))->field('nickname,face')->find();
    if($field && isset($userinfo[$field])){
        return $userinfo[$field];
    }else{
        return $userinfo;
    }

}

/**
 * 获取绝对地址
*/
function getfullPath($url='',$type=''){
    if(!$url){
        return '';
    }
   $endfile='';
    if($type =='100' && strpos($url,'img.cgyyg.com')!==false){
        $endfile='@!cg100';
    }
    if(strpos($url,'http://')!==0){
        return 'http://img.cgyyg.com/'.ltrim($url,'/').$endfile;
    }else{
      return $url.$endfile;

    }
}
?>