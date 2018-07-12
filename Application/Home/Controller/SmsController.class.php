<?php

/**
 * 短信接口控制器,返回验证码
 * joan
 */

namespace Home\Controller;

use Think\Controller;
use Addons\Message\Rest;

header("Content-type: text/html; charset=UTF8");

class SmsController extends Controller {

    public function index($mobile = '', $port_type = '', $nickname = '', $lottery_id = '', $product = '') {
        //主帐号
        $accountSid = '8a48b551516c09cd01517a12c8391868';
        //主帐号Token
        $accountToken = 'd3c1e13b5e8d4e64a602cc35f04a8da0';
        //应用Id
        $appId = 'aaf98f89516bf50b01517a1ec8c3191b';
        //请求地址，格式如下，不需要写https://
        $serverIP = 'sandboxapp.cloopen.com';
        //请求端口 
        $serverPort = '8883';
        //REST版本号
        $softVersion = '2013-12-26';
        // 初始化REST SDK
        $rest = new Rest($serverIP, $serverPort, $softVersion);
        srand((double) microtime() * 1000000); //create a random number feed.
        $ychar = "0,1,2,3,4,5,6,7,8,9";
        $list = explode(",", $ychar);
        for ($i = 0; $i < 4; $i++) {
            $randnum = rand(0, 9); // 10+26;
            $authnum.=$list[$randnum];
            $verifyCode = $authnum;
            $LimtTime = 10;
        }
        $rest->setAccount($accountSid, $accountToken);
        $rest->setAppId($appId);
        // 发送模板短信
        //echo "<br/>Sending TemplateSMS to $mobile <br/>";
        switch ($port_type) {
            case 1://注册会员
                $templateid = '67474';
                break;
            case 2://忘记密码
                $templateid = '68276';
                break;
            case 3://修改手机号
                $templateid = '68277';
                break;
            case 4://增加收货地址
                $templateid = '68388';
                break;
            case 5://修改收货地址
                $templateid = '68279';
                break;
            case 6://中奖通知
                $templateid = '78615';
                break;
            case 15://绑定手机号
                $templateid = '68747';
                break;
            case 7://佣金提现
                $templateid = '77535';
                break;
            case 8://添加银行卡
                $templateid = '77536';
                break;
            case 9://修改银行卡
                $templateid = '77537';
                break;
        }

        $tiparr = array();
        $tiparr = ($port_type == 6) ? array($nickname, $lottery_id, $product) : array($verifyCode, $LimtTime);
        $result = $rest->sendTemplateSMS($mobile, $tiparr, $templateid);
        if ($result == NULL) {
            echo "result error!";
            exit;
        }
        if ($result->statusCode != 0) {
            //echo "error code :" . $result->statusCode . "<br>";
            // return  $result->statusCode;
            return array('code' => $result->statusCode);
            //echo "error msg :" . $result->statusMsg . "<br>";
            //TODO 添加错误处理逻辑
            //echo "短信验证码发送失败，请联系客服<br/>";
        } else {
            // echo "Sendind TemplateSMS success!<br/>";
            // 获取返回信息
            $smsmessage = $result->TemplateSMS;
            // echo "dateCreated:".$smsmessage->dateCreated."<br/>";
            // echo "smsMessageSid:".$smsmessage->smsMessageSid."<br/>";
            //TODO 添加成功处理逻辑
            return array('code' => 200, 'verify' => $verifyCode, 'register_time' => time());
        }
    }

}
