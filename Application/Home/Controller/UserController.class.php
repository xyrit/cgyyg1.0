<?php

namespace Home\Controller;

header("Content-Type: text/html; charset=utf-8");

use Think\Controller;
use Home\Common\UtilApi; //引入工具类

header("Access-Control-Allow-Origin:*");

/**
 * 注册、登录控制器
 * 包括用户登录、退出及注册，手机号验证、忘记密码
 * Author: joan
 */
class UserController extends HomeController {
    /*
     * 注册页面-发送手机号验证码
     *  
     */

    public function register_check() {
        if (!empty($_REQUEST["mobile"])) {
            $othertoken = I("post.othertoken"); //第三方登陆的token
            $data["mobile"] = floatval(I("post.mobile"));
            $tel = $data["mobile"];
            if (empty($othertoken)) {
                $captcha = I("post.captcha"); //验证码
                if (!check_verify($captcha)) {
                    echo json_encode(array('code' => '500', 'info' => '验证码不正确'));
                    exit;
                }
            } else {
                $login_type = $this->check_othertoken($othertoken, $tel); //检查$othertoken
            }
            $this->checkMobile($tel); //检查手机号是否合法
            $uid = (!empty($othertoken)) ? 0 : D("Home/Member")->getUid($tel);
            if ($uid > 0) {//查询用户是否已注册
                echo UtilApi::getInfo(503, '该手机号已注册');
                return;
            } else {
                $last_time = S($tel);
                if (isset($last_time) && ((intval($last_time) + 120) > time())) {//将获取的缓存时间转换成时间戳加上120秒后与当前时间比较，小于当前时间即为过期          
                    echo UtilApi::getInfo(500, '发送验证码间隔时间不得小于2分钟');
                    exit;
                }
                $Sms = A("Home/Sms")->index($tel, 1); //调用发送短信接口，发短信验证码
                $status_code = $Sms["code"];
                if ($status_code == '200') {
                    echo json_encode(array('code' => 200, 'info' => '可以注册', "login_type" => $login_type));
                    $data['cellphone'] = $tel;
                    $data['verify'] = $Sms['verify'];
                    $data['creat_time'] = $Sms['register_time'];
                    $data['type'] = 1;
                    M("verify")->data($data)->add(); //将验证码、时间存到数据库。
                    S($tel, time(), 24 * 3600); //存储手机号token
                } else if ($status_code == '160040') {
                    echo UtilApi::getInfo(506, '发送次数超过当天限制5次');
                } else {
                    echo UtilApi::getInfo(500, '发送失败');
                }
            }
        } else {
            echo UtilApi::getInfo(509, '手机号不能为空');
            return;
        }
    }

    /* 注册页面-手机验证码提交 */

    public function verify() {
        if (!empty($_REQUEST["mobile"]) && !empty($_REQUEST["verify"])) {
            $data["mobile"] = floatval(I("post.mobile"));
            $invite_code = I("post.invite_code"); //邀请码
            if (!empty($invite_code)) {//邀请码不为空检测
                $code = M("member")->where('code=' . "'" . $invite_code . "'")->find();
                if (!$code) {
                    echo UtilApi::getInfo(500, '邀请码不存在');
                    exit;
                }
            }
            $verify = I("post.verify");
            $othertoken = I("post.othertoken");
            if (empty($othertoken)) {
                $captcha = I("post.captcha"); //验证码
                if (!check_verify($captcha)) {
                    echo UtilApi::getInfo(500, '验证码不正确');
                    exit;
                }
            }
            $reg_data["cellphone"] = $data["mobile"];
            $reg_data["verify"] = $verify;
            $reg_data["type"] = 1; //短信类型
            if ($verify < 1) {
                echo UtilApi::getInfo(500, '短信验证码不正确');
                return;
            }

            $this->checkMobile($data["mobile"]); //检查手机号
	    $reg_data['cellphone']=$data['mobile'];
            $verify_info = D("Member")->getVerify($reg_data); //得到验证码信息
            if (!empty($verify_info['creat_time']) && ((floatval($verify_info['creat_time']) + 6000) < time())) {//将获取的缓存时间转换成时间戳加上600秒后与当前时间比较，小于当前时间即为过期          
                echo UtilApi::getInfo(507, '短信验证码已过期，请重新获取！');
            } else {
                if (floatval($verify_info['verify']) != $verify) {
                    echo UtilApi::getInfo(500, '短信验证码不正确');
                    exit;
                } else {
                    S(array('expire' => 3600)); //设置缓存有效期1h，以秒为单位
                    $mobile_token = UtilApi::getToken();
                    S($mobile_token, $data["mobile"]); //存储手机号token
                    if (!empty($othertoken)) {
                        $this->check_res($data["mobile"], $othertoken);
                    }
                    echo json_encode(array('code' => 200, 'info' => "验证码正确", 'mobile_token' => $mobile_token));

                    M("verify")->where('cellphone=' . $verify_info['cellphone'])->save(array("status" => 1,"type"=>-1)); // 删除验证成功的验证码数据记录
                    exit; //status
                }
            }
        } else {
            echo UtilApi::getInfo(509, '手机号或短信验证码不能为空');
            exit;
        }
    }

    /* 注册页面-提交 */

    public function register($password = "", $mobile_token = "") {
        if (!empty($_POST["mobile_token"]) && !empty($_POST["password"]) && !empty($_POST["repassword"])) {

            $mobile_token = I("post.mobile_token");
            $invite_code = I("post.invite_code"); //邀请码
            $othertoken = I("post.othertoken"); //第三方登陆的token

            $data["mobile"] = floatval(S($mobile_token));
            $data["uc_username"] = "Cg" . str_shuffle($data["mobile"]) . rand(10, 99);
            $data["password"] = md5(I("post.password"));
            $data["repassword"] = md5(I("post.repassword"));
            $this->checkMobile($data["mobile"]);
            $data["nickname"] = substr($data["mobile"], 7);
            $data["reg_time"] = time();
            $data["reg_ip"] = $_SERVER['REMOTE_ADDR'];
            $info = S($othertoken);
            if ($info && !empty($othertoken)) {
                $login_type = substr($info["login_type"], 0, 1);
                if ($login_type == '1') {
                    $data["qq_openid"] = $info["qq_openid"];
                } else if ($login_type == '2') {
                    $data["wb_openid"] = $info["wb_openid"];
                } else if ($login_type == '3') {
                    $data["wx_openid"] = $info["wx_openid"];
                    $data["unionid"] = $info["unionid"];
                }
                $data["nickname"] = $info["nickname"];
                $data["sex"] = $info["sex"];
                $data["face"] = $info["path"];
                $data["login_type"] = $login_type;
            }
            if (!empty($data["password"]) && ($data["password"] != $data["repassword"])) {
                echo json_encode(array('code' => 502, 'info' => "密码和重复密码不一致"));
                exit;
            } else {
                $uc_username = $data["uc_username"];
                //插入与ucenter同步的uc_uid,同步唯一标识
                $ucenter_uid = A("Home/UcService")->uc_register($uc_username, $data["password"]); //获取ucenter用户id  
                $data["uc_uid"] = (!empty($ucenter_uid)) ? $ucenter_uid : 0;
                $res_info = M("member")->field('uid')->where('mobile=' . $data["mobile"])->find();
                if (!$res_info) {
                    $uid = M("member")->data($data)->add(); //插入注册用户信息 
                }

                if ($uid > 0) {
                    $activity_id = S('activity_id'); //扫码统计
                    if (!empty($activity_id)) {
                        $activity_id = M("activity_statistics")->where('activity_id=' . $activity_id)->find();
                        // A("Home/Activity")->activityScan_code($activity_id);
                    }
                    //A("Home/Commission")->CreateInvite_code($uid); //生成专属邀请码,二维码
                    if (!empty($invite_code)) {
                        A("Home/Commission")->bind_user($uid, $invite_code); //绑定用户
                    }
                    if ($info && !empty($othertoken)) {//判断是否是第三方登陆
                        $this->login($data["mobile"], $data["password"], 0, 1, $load, 1); //调用登陆,激活各应用同步登陆
                    } else {
                        $this->login($data["mobile"], $data["password"], 0, 1, $load, 0); //调用登陆,激活各应用同步登陆 
                    }
                } else {
                    UtilApi::getInfo(500, '注册失败');
                    exit;
                }
            }
        } else {
            if (empty($_POST["$mobile_token"])) {
                echo json_encode(array('code' => 500, 'info' => "手机号token为空"));
                exit;
            } else {
                echo json_encode(array('code' => 514, 'info' => "密码不能为空"));
                exit;
            }
        }
    }

    /**
     * 登录页面
     * @param  string $mobile 手机号
     * @param  string $password 密码
     * @return string  $member  用户信息    
     */
    public function login($mobile = "", $password = "", $type = '', $register = '', $load = 0, $other = 0) {
        $type = floatval(I("get.type"));
        if (empty($load)) {
            $load = floatval(I("post.load"));
        }
        switch ($type) {
            case 1://qq登录
                $qq = A("OtherLogin")->qqlogin($type);
                break;
            case 2://微博登录
                $qq = A("OtherLogin")->wblogin($type);
                break;
            case 3://微信登录
                $qq = A("OtherLogin")->wxlogin($type);
                break;
            default:
                $captcha = I("captcha");
                if ($register != '1') {//正常登陆用
                    $data["password"] = md5(I("post.password"));
                    $data["mobile"] = floatval(I("post.mobile"));
                    if (!check_verify($captcha)) {
                        echo json_encode(array('code' => '500', 'info' => '验证码不正确'));
                        exit;
                    }
                    if (empty($_REQUEST["mobile"]) || empty($_REQUEST["password"])) {
                        echo json_encode(array('code' => 509, 'info' => "手机号或密码不能为空"));
                    }
                } else {//注册接口调用
                    $data["mobile"] = $mobile;
                    $data["password"] = $password;
                }
                $data["mobile"] = $this->checkMobile($data["mobile"]);
                /* 登录用户 */
                $member = D("Member")->getUser($data); //查询用户信息
                if (empty($member['nickname'])) {
                    $member['nickname'] = substr($member['mobile'], 7);
                }
                $uid = $member['uid'];

                if ($uid > 0) {//登录成功                  
                    $memberinfo['uid'] = $member['uid'];
                    $memberinfo['nickname'] = $member['nickname'];
                    $memberinfo['path'] = $member['face'];
                    $memberinfo['mobile'] = $data["mobile"];
                    $user_token = UtilApi::getToken("login");
                    S($user_token, $uid, 24 * 3600); //登陆成功存储user_token
                    $memberinfo['user_token'] = $user_token;
                    $condition["last_login_time"] = time();
                    $condition["last_login_ip"] = $_SERVER['REMOTE_ADDR'];
                    $condition["login"] = $member['login'] + 1;
                    $condition["login_device"] = $_SERVER['HTTP_USER_AGENT'];
                    $condition['log_info'] = getTimeInfo();
                    //获取用户登录info结束
                    $code = M("Member")->where('uid=' . $uid)->save($condition); //保存用户登录信息
                    $memberinfo['login_type'] = 0;
                    $memberinfo['load'] = floatval($load);
                    $uc_username = $member["uc_username"];
                    if (!empty($uc_username)) {
                        $memberinfo["ucenter_url"] = A("Home/UcService")->uc_login($uc_username, $data["password"]); //获取ucenter返回的url
                    }
                    if ($other == '1') {
                        $other_token = UtilApi::getToken();
                        S($other_token, $memberinfo); //登陆成功存储user_token     
                        $load_url = C("pc_load_url") . "/login_guide.html?token=" . $other_token;
                        $memberinfo["load_url"] = $load_url;
                        echo json_encode(array('code' => 200, 'info' => "successful", 'user' => $memberinfo));
                        exit;
                    } else {
                        echo json_encode(array('code' => 200, 'info' => '  登陆成功', 'pic_host' => C('PICTURE'), 'user' => $memberinfo));
                        exit;
                    }
                } else {
                    if ($member['password'] != $data["password"]) { //登录失败
                        echo json_encode(array('code' => 501, 'info' => "用户名或密码不正确"));
                    } else if ($member['mobile'] != $data["mobile"]) {
                        echo json_encode(array('code' => 504, 'info' => "用户名不存在"));
                    }
                }
        }
    }

    /* 获得图片验证码 */

    public function verify_code() {
        $verify = new \Think\Verify(array('length' => 4));
        $verify->entry(1);
    }

    /* 获取图片验证码token */

    public function verify_token() {
        $encrypt_key = session_id();
        echo json_encode(array('code' => 200, 'info' => "successful", 'encrypt_key' => $encrypt_key));
    }

    /* 退出登录 */

    public function logout() {
        $user_token = I("post.user_token");
        if (empty($user_token)) {
            UtilApi::getInfo(500, 'user_token不能为空');
            return;
        }
        $uid = $this->is_login($user_token); //检查登录
        $logout = A("UcService")->uc_logout();
        echo json_encode(array('code' => 200, 'info' => "退出成功", "logout_url" => $logout));
        S($user_token, null); //清空缓存
    }

    /* 根据token得到已绑定第三方登录信息 */

    public function getInfo() {
        $token = I("post.token");
        $user_info = S($token); //取出缓存中第三方登录信息
        unset($user_info["wx_openid"]);
        unset($user_info["unionid"]);
        echo json_encode(array('code' => 200, 'info' => "成功", "pic_host" => C('PICTURE'), 'user' => $user_info));
    }

    /* 根据token得到已绑定第三方登录信息 */

    public function get_Bind_Info() {
        $othertoken = I("post.othertoken");
        $bind_info = S($othertoken); //取出缓存中第三方头像、昵称信息
        unset($bind_info["wx_openid"]);
        unset($bind_info["unionid"]);
        echo json_encode(array('code' => 200, 'info' => "成功", 'user' => $bind_info));
    }

    /*
     * 判断第三方othertoken缓存的openid是否存在
     */

    public function check_othertoken($othertoken = '', $mobile = '') {
        $info = S($othertoken);
        if (!$info) {
            UtilApi::getInfo(500, 'othertoken不正确');
            exit;
        }
        $login_type = $info["login_type"]; //登陆类型
        $field = 'qq_openid,wb_openid,unionid,qq';
        $other_info = M("member")->field($field)->where('mobile=' . $mobile)->find();
        $qq_openid = $other_info["qq_openid"];
        $wb_openid = $other_info["wb_openid"];
        $unionid = $other_info["unionid"];
        if ($login_type == '1') {//qq =10 or 11      
            $qq_openid = $other_info["qq_openid"];
            $qq = $other_info["qq"];
            if (!empty($qq_openid) || ($qq_openid == $info["qq_openid"])) {
                UtilApi::getInfo(500, '该手机号已绑定QQ');
                exit;
            }
        } else if ($login_type == '2') {//wb =20 or 21
            $wb_openid = $other_info["wb_openid"];
            if (!empty($wb_openid) || ($wb_openid == $info["wb_openid"])) {
                UtilApi::getInfo(500, '该手机号已绑定微博');
                exit;
            }
        } else if ($login_type == '3') {//wx =30or 31
            $unionid = $other_info["unionid"];
            if (!empty($unionid) || ($unionid == $info["unionid"])) {
                UtilApi::getInfo(500, '该手机号已绑定微信');
                exit;
            }
        }
        return $login_type;
    }

    /*
     * 对已注册用户的绑定
     */

    public function check_res($mobile = '', $other_token = '') {
        $other_info = S("$other_token");
        $data["mobile"] = $mobile;
        $user_info = M("member")->field('mobile,password,face')->where($data)->find();
        if ($user_info) {
            $login_type = $other_info["login_type"];
            if ($login_type == '1') {
                $datas["qq_openid"] = $other_info["qq_openid"];
            } else if ($login_type == '2') {
                $datas["wb_openid"] = $other_info["wb_openid"];
            } else if ($login_type == '3') {
                $datas["wx_openid"] = $other_info["wx_openid"];
                $datas["unionid"] = $other_info["unionid"];
            }
            $datas["face"] = (empty($user_info["face"])) ? $other_info["face"] : $user_info["face"];
            $bind = M("member")->where($data)->save($datas); //dump($data);dump($datas);
            if ($bind > 0) {
                $mobile = $user_info["mobile"];
                $password = $user_info["password"];
                $this->login($mobile, $password, 0, 1, 0, 1); //调用登陆,激活各应用同步登陆
                exit;
            }
        }
    }

    /*
     * 第三方应用解绑
     */

    public function unbind() {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $mobile = D("Home/Member")->getField($uid, 'mobile'); //取出手机号
        $data["mobile"] = $mobile["mobile"];
        $type = floatval(I("post.type")); //应用类型

        switch ($type) {
            case 1://qq登录
                $datas["qq_openid"] = '';
                break;
            case 2://微博登录
                $datas["wb_openid"] = '';
                break;
            case 3://微信登录
                $datas["unionid"] = '';
                break;
        }

        $unbind = M("member")->where($data)->save($datas);
        if ($unbind > 0) {
            UtilApi::getInfo(200, '解绑成功');
            exit;
        } else {
            UtilApi::getInfo(500, '解绑失败');
            exit;
        }
    }

    /*
     * 查询用户第三方绑定明细
     */

    public function bind_list() {
        $user_token = I("post.user_token");
        $uid = $this->is_login($user_token);
        $mobile = D("Member")->getField($uid, 'mobile'); //取出手机号
        $mobile = $mobile["mobile"];
        $data["mobile"] = $mobile;
        $bind_info = M("member")->field('qq_openid,wb_openid,unionid')->where($data)->find();
        if ($bind_info) {
            $info["qq_openid"] = empty($bind_info["qq_openid"]) ? 2 : 1; //1已经绑定，2未绑定
            $info["wb_openid"] = empty($bind_info["wb_openid"]) ? 2 : 1;
            $info["unionid"] = empty($bind_info["unionid"]) ? 2 : 1;
        }
        echo json_encode(array('code' => 200, 'info' => "成功", 'bind_list' => $info));
    }

}
