<?php

namespace Wap\Controller;

use Think\Controller;
use Addons\OtherLogin\SaeTOAuthV2; //引入新浪微博的类 
use Addons\OtherLogin\OAuthException; //引入OAuthException类
use Addons\OtherLogin\SaeTClientV2; //引入新浪微博的类 
use Addons\OtherLogin\Qqconnect; //引入qq的类 
use Home\Common\UtilApi; //引入工具类

class OtherLoginController extends Controller {

    //weibo_denglu            
    public function wbLogin() {
        $param = C('WEIBO');
        $param = $param;
        $o = new SaeTOAuthV2($param['WB_AKEY'], $param['WB_SKEY']);
        $code_url = $o->getAuthorizeURL($param['WAP_CALLBACK_URL']);
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

    public function other_common($column = '', $value = '', $list = '', $login_type = '', $wx_openid = '', $url_type = 0) {
        $model = M('member');
        $field = 'mobile,password,nickname,face,sex,uid,login,uc_username';
        $where_a = $column . "=" . "'" . $value . "'";
        if (!empty($value)) {
            $res = $model->field($field)->where($where_a)->find();
        }
        $mobile = $res["mobile"];
        $password = $res["password"];
        $uc_username = $res["uc_username"];
        $wap_load_url = C('wap_load_url');
        if ($res) {//数据库有qqopenid，已绑定
            $discuz_url = A("Home/UcService")->uc_login($uc_username, $password); //获取ucenter返回的url
            if (empty($discuz_url)) {
                $discuz_url = 'http://quanzi.cgyyg.com';
            }
            $result['nickname'] = $res['nickname'];
            $result['path'] = $res['face'];
            $result['sex'] = $res['sex'];
            $uid = $res["uid"];
            $result['uid'] = $uid;
            $condition["login"] = $res["login"] + 1;
            $condition["face"] = $result['path'];
            $login = M('member')->where('uid=' . $uid)->save($condition);
            //#####[debug]下面是页面跳转
            S(array('expire' => 24 * 7 * 3600)); //设置缓存有效期1天，以秒为单位
            $user_token = UtilApi::getToken();
            S($user_token, $uid); //登陆成功存储user_token
            $result['user_token'] = $user_token;
            $result['ucenter_url'] = urlencode($discuz_url);
            $result['login_type'] = $login_type; //截取登录类型
            cookie('user_token', $user_token);
            cookie('ucenter_url', $discuz_url);
            cookie('othertoken', 0);
            $token = UtilApi::getToken();
            S($token, $result); //登陆成功存储user_token     
            if ($url_type > 0) {
                return $token;
            } else {
                header("Location: " . $wap_load_url);
                exit;
            }
        } else {//跳转绑定页面
            S(array('expire' => 24 * 7 * 3600)); //设置缓存有效期1天，以秒为单位
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
            S($othertoken, $info, 24 * 7 * 3600); //登陆成功存储user_token
            cookie('nickname', urlencode($info['nickname']));
            cookie('path', urlencode($info['path']));
            cookie('othertoken', $othertoken);
            cookie('test', '22222');
            S($othertoken, $info); //存储第三方openid    
            if ($url_type > 0) {
                return $othertoken;
            } else {
                header("Location:" . $wap_load_url . "/banding_mobile.html?othertoken=" . $othertoken);
            }
        }
    }

    //qq登录
    public function qqlogin() {
        $qq = C('QQ');
        $qq["callback"] = $qq["callback_wap"];
        $qqobj = new Qqconnect($qq);//exit;
        $qqobj->getAuthCode();
    }

    //qq回调
    public function qqCallback() {
        $qq = C('QQ');
        $qq["callback"] = $qq["callback_wap"];
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
        $callback = $weixin["callback_wap"];
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

    /*
     * 微信公众号登陆
     */

    public function oauth() {

        function httpGet($url, $param = array()) {
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
            $httph = curl_init($url);
            curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($httph, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($httph, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
            curl_setopt($httph, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($httph, CURLOPT_HEADER, 0);
            $rst = curl_exec($httph);
            curl_close($httph);
            return $rst;
        }

        if (isset($_GET['code'])) {
            $code = $_GET['code']; //获取code
            $url_type = $_GET['state'];
            $weixin = C('weixin');
            $appid = $weixin["appid"];
            $secret = $weixin["secret"];
            $arr = array('appid' => $appid, 'secret' => $secret, 'code' => $code, 'grant_type' => 'authorization_code');
            //使用code换取access_token
            $arr = httpGet("https://api.weixin.qq.com/sns/oauth2/access_token", $arr);
            $arr = json_decode($arr, true);
            //获取refresh_token
            $arr = array('appid' => $appid, 'grant_type' => 'refresh_token', 'refresh_token' => $arr['refresh_token']);
            $arr = httpGet("https://api.weixin.qq.com/sns/oauth2/refresh_token", $arr);
            //获取openid
            $wx_info = json_decode($arr, true);
            //dump($wx_info);
            //获取用户信息  
            $wx_arr = array('access_token' => $wx_info['access_token'], 'openid' => $wx_info['openid']);
            $wx_arr = httpGet("https://api.weixin.qq.com/sns/userinfo", $wx_arr);
            $wx_arr = json_decode($wx_arr, true);
            $list['nickname'] = $wx_arr['nickname'];
            $list['face'] = $wx_arr['headimgurl'];
            $list['sex'] = $wx_arr['sex'];
            $column = 'unionid';
            $value = $wx_arr['unionid'];
            if (empty($value)) {
                echo "微信授权失败";
                exit;
            } else {
                $token = $this->other_common($column, $value, $list, 3, '', 3);
            }
            $wap_load_url = C('wap_load_url');
            switch ($url_type) {
                case 1://马上云购
                    $url = 'index.html';
                    break;
                case 2://了解云购
                    $url = 'guidance.html';
                    break;
                case 3://pc个人中心
                    $url = '';
                    break;
                case 5://最新揭晓//0元夺宝
                    $url = 'special_maShang_index.html';
                    break;
                case 6://晒单分享
                    $url = 'hongbao.html';
                    break;
                case 8://个人中心
                    $url = '35-personal.html';
                    break;
                case 9://云购纪录
                    $url = '21-Part_record.html';
                    break;
                case 10://中奖记录
                    $url = '22-win-record.html';
                    break;
                case 11://常见问题
                    $url = '29-Setup-2.html';
                    break;
                case 12://0元ipad
                    $url = 'special_maShang_index.html';
                    break;
                case 13://0元ipad
                    $url = 'hongbao.html';
                    break;
                case 14://商品详情页      
                    $url = 'index.html';
                    break;
                default:
                    //$url = 'index.html';
                    $url_type = floatval($url_type);
                    $lottery_product = M("lottery_product")->field("pid")->where("lottery_id=" . $url_type)->find();
                    $pid = $lottery_product["pid"];
                    $url = "3_goods-ing.html?pid=" . $pid . "&lottery_id=" . $url_type;
            }
            $wap_load_url = C('wap_load_url');
            header("Location: " . $wap_load_url . "/" . $url); //跳转
        } else {
            echo "NO CODE";
        }
    }

}
