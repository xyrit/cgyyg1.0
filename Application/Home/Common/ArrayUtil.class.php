<?php

namespace Home\Common;

/**
 * 数组操作类
 * 2015.12.15
 */
class ArrayUtil {
   
    /*
     * 随机获取数组的多个下标
     */
    public static function getRandArray($arr,$num) {
        srand((float) microtime() * 10000000);
        $rand_keys = array_rand($arr, $num); //如果随机一位，则直接返回随机数，如果随机多位数，则返回数组
        return $rand_keys;
    }
}
