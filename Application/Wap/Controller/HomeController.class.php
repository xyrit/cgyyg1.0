<?php

// +----------------------------------------------------------------------
// | 前台公共控制器
// +----------------------------------------------------------------------
// | Author: joan
// +----------------------------------------------------------------------

namespace Wap\Controller;

use Home\Common\UtilApi;
use Think\Controller;

/**
 * 前台公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 */
class HomeController extends Controller {
    /*
     * 空操作，用于输出404页面 
     */

    public function _empty() {
        $arr = array(
            'code' => 404,
            'info' => '访问错误'
        );
        echo json_encode($arr);
    }

    /*
     * 用户登录检测
     */

    public function is_login($user_token = '') {

        $uid = floatval(S($user_token));
        if ($uid < '1') {
            echo json_encode(array('code' => 517, 'info' => "请登录后再操作"));
            exit;
        } else {
            return $uid;
        }
    }

    /* 检测手机号 */

    public function checkMobile($cellphone = '', $status = '') {     
        $cellphone = trim($cellphone);
         $phoneLength = strlen($cellphone);
        if ($phoneLength == "11") {
            if (preg_match("/1[3578]{1}\d{9}$/", $cellphone)) {
                if ($status == '1') {
                    $result = M("member")->field('uid')->where('mobile=' . $cellphone)->find();
                    $uid = $result["uid"];
                    if ($uid < 1) {
                        UtilApi::getInfo(504, '用户名不存在');
                        exit;
                    }
                } else {
                    return $cellphone;
                }
            } else {
                echo json_encode(array('code' => 511, 'info' => "手机号码格式不正确"));
                exit;
            }
        } else if ($phoneLength <= "1") {
            echo json_encode(array('code' => 509, 'info' => "手机号为空"));
            exit;
        } else {
            echo json_encode(array('code' => 510, 'info' => "手机号长度必须是11位"));
            exit;
        }
    }

    /* 检查密码 */

    public function checkPassword($password = '') {var_dump(454545)
        $password = str_replace(" ", "", "$password");
        $pLength = strlen($password);
        if ($pLength > 16 || $pLength < 6) {
            UtilApi::getInfo(528, '密码必须是6-11位');
            exit;
        } else {
            if (preg_match('/^[@_0-9a-z]{6,16}$/i', $password)) {
                return $password;
            } else {
                UtilApi::getInfo(529, '密码只允许数字、@、字母、下划线');
                exit;
            }
        }
    }

}
