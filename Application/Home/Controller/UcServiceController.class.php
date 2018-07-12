<?php

namespace Home\Controller;

header("Content-Type: text/html; charset=utf-8");

use Think\Controller;
use Home\Common\UtilApi; //引入工具类

include DOC_ROOT_PATH . 'uc_client/config.inc.php'; //引入ucenter数据库配置文件
include DOC_ROOT_PATH . 'uc_client/client.php'; //引入ucenter客户端文件
header("Access-Control-Allow-Origin:*");

/*
 * ucenter同步登陆的类
 */

class UcServiceController extends HomeController {
    /*
     * 返回同步登录成功后的url地址
     */

    public function uc_login($mobile = '', $password = '') {
        $ucenter_uid = D("Home/member")->check_ultrax_user($mobile);
        $ucenter_uid = $ucenter_uid[0]["uid"]; 
        if (!$ucenter_uid) { 
            return '0';
        }
        //通过接口判断登录帐号的正确性，返回值为数组
        list($uc_uid, $mobile, $password, $email) = uc_user_login($mobile, $password);
        setcookie('Example_auth', '', -86400);
        if ($uc_uid > 0) {//判断ucenter的uid是否存在于当前应用的用户表
            $count = D("Home/member")->uc_uid_check($uc_uid); //获取用户uid的数量
            if (!$count) {
                //判断用户是否存在于用户表，不存在则跳转到激活页面,重新注册添加
                $activeuser = uc_get_user($mobile); //获取用户名、密码
                list($uid, $mobile) = $activeuser;
                if ($mobile) {
//                    dump($count);
//                    dump($uc_uid);dump($mobile);
                    return;
                    $data["nickname"] = substr($mobile, 7); //昵称
                    $data["reg_time"] = time(); //注册时间
                    $data["mobile"] = $mobile; //注册账号手机号
                    $data["uc_username"] = $mobile;
                    $data["password"] = md5(I("post.password")); //密码
                    $data["reg_ip"] = $_SERVER['REMOTE_ADDR']; //注册IP
                    $data["uc_uid"] = $uid;
                    $uid = M("member")->data($data)->add(); //插入注册用户信息
                    $info = M("member")->field('uc_uid')->where('uid=' . $uid)->find();
                    $uc_uid = $info["uc_uid"];
                }
            }
            //用户登陆成功，设置 Cookie，加密直接用 uc_authcode 函数，用户使用自己的函数
            setcookie('Example_auth', uc_authcode($uc_uid . "\t" . $mobile, 'ENCODE')); //dump($uc_uid);
            //生成同步登录的代码
            $ucsynlogin = uc_user_synlogin($uc_uid); //取出其他应用的api地址，逗号隔开
            //dump($ucsynlogin);exit;
            $url_arr = array();
            $url_arr = explode(",", $ucsynlogin); //取出discuz的uc接口的地址
            $discuz_url = $url_arr[1];
            return $discuz_url;
        } /* elseif ($uc_uid == -1) {
          UtilApi::getInfo(500, '用户不存在,或者被删除');
          exit;
          } elseif ($uc_uid == -2) {
          UtilApi::getInfo(500, '密码错误');
          exit;
          } else {
          UtilApi::getInfo(500, '未定义');
          exit;
          } */
    }

    /*
     * 退出的公共方法
     */

    public function uc_logout() {
        setcookie('Example_auth', '', -86400); //清空cookie
        $ucsynlogout = uc_user_synlogout(); //dump($ucsynlogout);
        $url_arr = array();
        $url_arr = explode(",", $ucsynlogout); //取出discuz的uc接口的地址
        $logout = $url_arr[1];
        return $logout;
    }

    /*
     * 返回ucenter同步注册的uid
     */

    public function uc_register($mobile = '', $password = '') {
        $uc_uid = uc_user_register($mobile, $password); //获取ucenter注册的uid
        $uc_uid = floatval($uc_uid);
        if ($uc_uid <= 0) {
            if ($uc_uid == -1) {
                UtilApi::getInfo(500, '用户名不合法');
                exit;
            } elseif ($uc_uid == -2) {
                UtilApi::getInfo(500, '包含不允许注册的词语');
                exit;
            } elseif ($uc_uid == -3) {
                UtilApi::getInfo(500, '用户名已经存在');
                exit;
            } else {
                UtilApi::getInfo(500, '注册失败');
                exit;
            }
        } else {
            return $uc_uid;
        }
    }

}
