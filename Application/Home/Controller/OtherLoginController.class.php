<?php

namespace Home\Controller;

use Think\Controller;
use Addons\OtherLogin\SaeTOAuthV2; //引入新浪微博的类 
use Addons\OtherLogin\OAuthException; //引入OAuthException类
use Addons\OtherLogin\SaeTClientV2; //引入qq的类
use Addons\OtherLogin\Qqconnect; //引入qq的类 
//use Home\Common\API\QC; ////引入qq的类
use Home\Common\UtilApi; //引入工具类

class OtherLoginController extends Controller {

    //测试第三方类是否加载了
    public function testClass() {
        $param = C('WEIBO');
        $client_id = $param['WB_AKEY'];
        $client_secret = $param['WB_SKEY'];
        $t = new SaeTOAuthV2($client_id, $client_secret);
        //var_dump($t);
    }

    /**
     * 测试微博登录
     * 1.生成微博授权申请的url
     * 2.实现回调地址对应的方法，获取信息存储到数据库
     * 3.跳转到回调地址
     */
    public function wbLogin() {
        $param = C('WEIBO');
        $param = $param;
        $o = new SaeTOAuthV2($param['WB_AKEY'], $param['WB_SKEY']);
        $code_url = $o->getAuthorizeURL($param['PC_CALLBACK_URL']);
        header("Location:" . $code_url);
    }

    //wb回调方法
    public function wbCallback() {
        $param = C('WEIBO');
        $o = new SaeTOAuthV2($param['WB_AKEY'], $param['WB_SKEY']);
        if (isset($_REQUEST['code'])) {
            $keys = array();
            $keys['code'] = $_REQUEST['code'];
            $keys['redirect_uri'] = $param['PC_CALLBACK_URL'];
            try {//获取access_token、uid
                $token = $o->getAccessToken('code', $keys);
            } catch (OAuthException $e) {
                
            }
        }
        if ($token) {
            //获取access_token
            $_SESSION['token'] = $token;
            setcookie('weibojs_' . $o->client_id, http_build_query($token));
            //echo "授权完成";
            $c = new SaeTClientV2($param['WB_AKEY'], $param['WB_SKEY'], $token['access_token']);
            $ms = $c->home_timeline(); // done
            $uid_get = $c->get_uid();
            $openid = $uid_get['uid']; //获取openid
            $user_message = $c->show_user_by_id($openid); //根据ID获取用户等基本信息
            $login_type = I("get.type");
            $list["nickname"] = $user_message['screen_name'];
            $list["sex"] = ($user_message["gender"] === 'f') ? '1' : '0';
            $list["face"] = $user_message['avatar_hd'];
            $column = "wb_openid";
            $value = $openid;
            $this->other_common($column, $value, $list, 2);
        } else {
            echo '授权失败';
        }
    }

    public function other_common($column = '', $value = '', $list = '', $login_type = '', $wx_openid = '') {
        $model = M('member');
        $field = 'mobile,password,nickname,face,sex,uid,login';
        $where_a = $column . "=" . "'" . $value . "'";
        if (!empty($value)) {
            $res = $model->field($field)->where($where_a)->find();
        }
        $mobile = $res["mobile"];
        $password = $res["password"];
        $pc_load_url = C('pc_load_url');
        if ($res) {//数据库有qqopenid，已绑定          
            $discuz_url = A("UcService")->uc_login($mobile, $password); //获取ucenter返回的url
            $result['nickname'] = $res['nickname'];
            $result['path'] = $res['face'];
            $result['sex'] = $res['sex'];
            $uid = $res["uid"];
            $result['uid'] = $uid;
            $condition["login"] = $res["login"] + 1;
            $login = M('member')->where('uid=' . $uid)->save($condition);
            //#####[debug]下面是页面跳转
            S(array('expire' => 24 * 3600)); //设置缓存有效期1天，以秒为单位
            $user_token = UtilApi::getToken();
            S($user_token, $uid); //登陆成功存储user_token
            $result['user_token'] = $user_token;
            $result['ucenter_url'] = $discuz_url;
            $result['login_type'] = $login_type; //截取登录类型
            $token = UtilApi::getToken();
            S($token, $result); //登陆成功存储user_token     
            header("Location:" . $pc_load_url . "/login_guide.html?token=" . $token);
            //header("Location: " . $pc_load_url . "/?token=" . $token);
        } else {//跳转绑定页面
            S(array('expire' => 3600)); //设置缓存有效期1天，以秒为单位
            switch ($login_type) {
                case 1://qq登录
                    $info['qq_openid'] = $value;
                    break;
                case 2://微博登录
                    $info['wb_openid'] = $value;
                    break;
                case 3://微信登录
                    $info['wx_openid'] = $wx_openid;
                    $info['unionid'] = $value;
                    break;
            }
            $info['login_type'] = $login_type;
            $info['nickname'] = $list['nickname'];
            $info['path'] = empty($res['face']) ? $list['face'] : $res['face'];
            $info['sex'] = $list['sex'];
            $othertoken = UtilApi::getToken();
            S($othertoken, $info); //存储第三方openid    
            header("Location:" . $pc_load_url . "/bind_account.html?othertoken=" . $othertoken);
            exit;
        }
    }

    //qq登录
    public function qqlogin() {
        $qq = C('QQ');
        $qq["callback"] = $qq["callback_pc"];
        $qqobj = new Qqconnect($qq);
        $qqobj->getAuthCode();
    }

    //qq回调
    public function qqCallback() {
        $qq = C('QQ');
        $qq["callback"] = $qq["callback_pc"];
        $qqobj = new Qqconnect($qq);
        $list = $qqobj->getUsrInfo();
        $column = 'qq_openid';
        $list['face'] = $list['figureurl_2'];
        $list['sex'] = $list['gender'];
        $value = $list['openid'];
        $this->other_common($column, $value, $list, 1);
    }

    //weixinlogin
    public function wxLogin($login_type = '') {
        $weixin = C('weixin');
        $AppID = $weixin["appID"];
        $AppSecret = $weixin["appSecret"];
        $callback = $weixin["callback_pc"];
        //微信登录
        //-------生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), TRUE)); //dump($state); 降低安全性，让state避免从session中取的时候区不到
        // $_SESSION["wx_state"] = $state; //存到SESSION
        $_SESSION['wx_state'] = 1;
        $callback = urlencode($callback);
        $wxurl = "https://open.weixin.qq.com/connect/qrconnect?appid=" . $AppID . "&redirect_uri={$callback}&response_type=code&scope=snsapi_login&state={$state}#wechat_redirect";
        header("Location: $wxurl");
    }

    //weixinhuidiao
    public function wxCallback() {
        $_SESSION['wx_state'];
        //if ($_GET['state'] != $_SESSION["wx_state"]) {
        //  exit("5001++");
        //}
        // if ($_GET['wx_state'] != 1)
        $weixin = C('weixin');
        $AppID = $weixin["appID"];
        $AppSecret = $weixin["appSecret"];
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $AppID . '&secret=' . $AppSecret . '&code=' . $_GET['code'] . '&grant_type=authorization_code';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $json = curl_exec($ch);
        curl_close($ch);
        $arr = json_decode($json, 1); //获取openid、unionid、access_token
        $unionid = $arr['unionid'];
        if ($arr) {
            $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $arr['access_token'] . '&openid=' . $arr['openid'] . '&lang=zh_CN';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_URL, $url);
            $json = curl_exec($ch);
            curl_close($ch);
            $user_info = json_decode($json, 1); //获取用户信息
            $list['nickname'] = $user_info['nickname'];
            $list['sex'] = $user_info['sex'];
            $list['face'] = $user_info['headimgurl'];
            $list['wx_openid'] = $user_info['wx_openid'];
            $list['unionid'] = $user_info['unionid'];
            $column = 'unionid';
            $value = $unionid;
            $this->other_common($column, $value, $list, 3, $list['wx_openid']);
        }
    }

}
