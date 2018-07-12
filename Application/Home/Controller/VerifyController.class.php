<?php

namespace Home\Controller;

use Home\Common\UtilApi;

header("Access-Control-Allow-Origin:*");

/**
 * 验证码
 * 验证码发送、保存、检查、删除
 * Author: joan
 */
class VerifyController extends HomeController {
    /* 发送手机号验证码 */

    public function getVerify($cellphone = 0) {
        if (!empty($_POST["cellphone"])) {
            $data["cellphone"] = floatval(I("post.cellphone"));
            $cellphone = $data["cellphone"];
            $code = $this->checkMobile($cellphone); //检查手机号是否合法
            $state = intval(I("post.state"));
            $status = intval(I("post.status"));
            if ($state != '1') {//判断是否需要登录。需要登录后才能用
                $user_token = I("post.user_token");
                $uid = $this->is_login($user_token); //检查是否登录，是则返回uid，否则退出                
            } else {//未登录时用，用于忘记密码
                $captcha = I("post.captcha");
                if (!check_verify($captcha)) {
                    echo json_encode(array('code' => '500', 'info' => '验证码不正确'));
                    exit;
                }
                $this->checkMobile($cellphone, 1); //检查手机号是否合法
            }
            //接口类型(1注册，2忘记密码，3改手机号，4增加收货地址，5修改收货地址,6中奖通知,15绑定手机号)
            $type = intval(I("post.type"));
            if ($type < 0) {
                UtilApi::getInfo(500, '短信type不能为空');
                return;
            }
            if ($status == '1') {
                $userid = M("member")->field('uid')->where('mobile=' . $cellphone)->find();
                if ($userid > 0) {
                    UtilApi::getInfo(500, '该手机号码已被注册!');
                    return;
                }
            }

            if ($code) {
                $last_time = S($cellphone);
                //将获取的缓存时间转换成时间戳加上120秒后与当前时间比较，小于当前时间即为过期          
                if (isset($last_time) && ((intval($last_time) + 120) > time())) {
                    echo json_encode(array('code' => 500, 'info' => "发送验证码间隔时间不得小于2分钟！"));
                    exit;
                }
                $Sms = A("Home/Sms")->index($cellphone, $type); //获取发送短信的时间、验证码、手机号
                $status_code = $Sms["code"];
                if ($status_code == '200') {
                    $data['cellphone'] = $cellphone;
                    $data['verify'] = $Sms['verify'];
                    $data['creat_time'] = $Sms['register_time'];
                    $data['type'] = $type;
                    $verify = D("Home/verify")->verifyAdd($data); //将验证码存到数据库
                    echo json_encode(array('code' => '200', 'info' => '发送成功'));

                    S($cellphone, time(), 24 * 3600); //存储手机号token
                } else if ($status_code == '160040') {
                    UtilApi::getInfo(506, '发送次数超过当天限制5次');
                } else {
                    UtilApi::getInfo(500, '发送失败');
                }
            }
        } else {
            echo json_encode(array('code' => 509, 'info' => "手机号为空"));
        }
    }

    /* 检查手机号验证码是否正确 */

    public function checkVerify($cellphone = 0, $verify = 0, $state = 0, $port_type = 0) {
        $status = intval(I("status"));
        $cellphone = ($cellphone == 0) ? floatval(I("cellphone")) : $cellphone;
        $verify = floatval(I("verify"));
        $code = $this->checkMobile($cellphone); //检查手机号是否合法
        if (empty($port_type)) {
            $type = intval(I("post.type")); //接口类型(1注册，2忘记密码，3添加收货地址，4修改地址，5改手机号)
        }
        $type = ($state == '2') ? $port_type : $type; //判断是否是收货地址接口的短信type
        if ($type < 0) {
            UtilApi::getInfo(500, '短信type不能为空');
            return;
        }
        if ($status != '1') {//判断是否需要登录。需要登录
            $user_token = I("post.user_token");
            $uid = $this->is_login($user_token); //检查是否登录，是则返回uid，否则退出
        } else {//忘记密码用
            $captcha = I("post.captcha"); //验证码
            if (!check_verify($captcha)) {
                echo json_encode(array('code' => '500', 'info' => '验证码不正确'));
                exit;
            }
            $this->checkMobile($cellphone, 1); //检查手机号是否合法
        }
        if ($code) {
            $info = D("verify")->getVerify($cellphone, $verify, $type); //得到验证码、时间
            //将获取的缓存时间转换成时间戳加上600秒后与当前时间比较，小于当前时间即为过期
            if (!empty($info['creat_time']) && ((intval($info['creat_time']) + 600) < time())) {
                echo json_encode(array('code' => '507', 'info' => '验证码已过期，请重新获取！'));
                exit;
            } else {
                if (($info['verify'] != $verify) || empty($info['verify'])) {//判断验证码是否正确
                    echo json_encode(array('code' => '500', 'info' => '验证码不正确'));
                    exit;
                } else {
                    if ($state == '1') {//修改手机号
                        $mobiles = floatval(I("mobile"));
                        $mobile = M("member")->field("mobile")->where("uid=" . $uid)->find();
                        $mobile = $mobile["mobile"];
                        $data = array("mobile" => $cellphone);
                        if ($mobiles != $mobile) {
                            echo json_encode(array('code' => 500, 'info' => "手机号修改失败"));
                            exit;
                        }
                        $uid = M("member")->where("uid=" . $uid . "  and mobile=" . $mobile)->save($data);
                        if ($uid > 0) {
                            echo json_encode(array('code' => 200, 'info' => "验证码正确"));
                        } else {
                            echo json_encode(array('code' => 524, 'info' => "手机号修改失败"));
                        }
                    } else if ($state == '2') {//收货地址
                        M("verify")->where('cellphone=' . $info['cellphone'])->save(array("status" => 1, "type" => -1));
                        return 200;
                    } else {
                        echo json_encode(array('code' => 200, 'info' => "验证码正确"));
                    }
                    M("verify")->where('cellphone=' . $info['cellphone'])->save(array("status" => 1, "type" => -1));
                }
            }
        }
    }

}
