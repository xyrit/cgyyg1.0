<?php

namespace Home\Controller;

header("Content-Type: text/html; charset=utf-8");

use Think\Controller;
use Think\Exception;

/**
 * 注册、登录控制器
 * 包括用户登录、退出及注册，手机号验证、忘记密码
 * Author: joan
 */
class WeChatController extends HomeController {

    public function verify_token() {
        if (isset($_GET['echostr'])) {
            $this->valid();
        } else {
            $this->responseMsg();
        }
    }

    public function valid() {
        $echoStr = $_GET["echostr"];
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }

    //验证token
    public function checkSignature() {
        //如果调试状态，直接返回真
        //   if ($this->debug) return true;
        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        if (empty($signature) || empty($timestamp) || empty($nonce)) {
            return false;
        }
        $token = 'cgyyg';
        if (!$token)
            return false;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        return sha1($tmpStr) == $signature;
    }

    //获取access_token
    public function get_access_token() {
        //$access_token = cache::store('vcode')->get('access_token');   //取出缓存
        // $access_token =  $_COOKIE['access_token'];
        //if(!$access_token)
        // {

        $wxconfig= M('watch_config')->find();



      //  $appid = 'wxc10af3f90aced41f';
       // $appsecret = '16d11e688c1b734b13ac1ff320deca70';
        $appid =$wxconfig['appid'];
        $appsecret =$wxconfig['appsecret'];
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $rs = $this->https_request($url);
        $arrrs = json_decode($rs, 1);
        $access_token = $arrrs['access_token'];
        // cache::store('vcode')->put('access_token',$access_token, 7200);  //缓存
        // setcookie("access_token",$access_token, time()+7200);
        // $this->access_token = $access_token;
        return $access_token;
        //  }
        //   else
        //  {
        //   $this->access_token = $access_token;
        //   return $access_token;
        // }
    }

    //提交菜单
    public function menu() {
        $access_token= $this->get_access_token();
        $pc_load_url = C('pc_load_url');
        $appid = C('weixin')["appid"];
        $arrmenu = array(
            'button' =>
            array(
                array(
                    'name' => '发现云购', //左起第一
                    "type" => "view",
                    'sub_button' => array(
                        array(
                            "type" => "view",
                            "name" => "马上云购", //1
                            "url" => "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$pc_load_url."/cgyyg1.0/wap.php/OtherLogin/oauth&response_type=code&scope=snsapi_userinfo&state=1&#wechat_redirect"
                        ),
                        array(
                            "type" => "view",
                            "name" => "了解云购", //2
                            "url" => "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$pc_load_url."/cgyyg1.0/wap.php/OtherLogin/oauth&response_type=code&scope=snsapi_userinfo&state=2&#wechat_redirect"
                        ),
                    )
                ),
                array(
                    'name' => '敬请期待', //左起第一
                    "type" => "view"
                    )
                ),
                array(
                    'name' => '我的云购', //左起第三
                    "type" => "view",
                    'sub_button' => array(
                        array(
                            "type" => "view",
                            "name" => "个人中心", //8
                            "url" => "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$pc_load_url."/cgyyg1.0/wap.php/OtherLogin/oauth&response_type=code&scope=snsapi_userinfo&state=8&#wechat_redirect"
                        ),
                        array(
                            "type" => "view",
                            "name" => "云购记录", //9
                            "url" => "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$pc_load_url."/cgyyg1.0/wap.php/OtherLogin/oauth&response_type=code&scope=snsapi_userinfo&state=9&#wechat_redirect"
                        ),
                        array(
                            "type" => "view",
                            "name" => "中奖记录", //10
                            "url" => "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$pc_load_url."/cgyyg1.0/wap.php/OtherLogin/oauth&response_type=code&scope=snsapi_userinfo&state=10&#wechat_redirect"
                        ),
                        array(
                            "type" => "view",
                            "name" => "常见问题", //11
                            "url" => "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$pc_load_url."/cgyyg1.0/wap.php/OtherLogin/oauth&response_type=code&scope=snsapi_userinfo&state=11&#wechat_redirect"
                        ),
                        array(
                            "type" => "view",
                            "name" => "在线客服", //11
                            "url" => "http://wpa.qq.com/msgrd?v=3&uin=3171928104&site=qq&menu=yes"
                        ),
                    )
                ),
            )
        );

        $jsonmenu = json_encode($arrmenu, JSON_UNESCAPED_UNICODE);
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $access_token;
        $result = $this->https_request($url, $jsonmenu);
        var_dump($result);
    }

    public function splash($status = 'success', $url = null, $msg = null, $ajax = false) {
        $status = ($status == 'failed') ? 'error' : $status;
        //如果需要返回则ajax
        if ($ajax == true || request::ajax()) {
            //status: error/success
            return response::json(array(
                        $status => true,
                        'message' => $msg,
                        'redirect' => $url,
            ));
        }

        if ($url && !$msg) {//如果有url地址但是没有信息输出则直接跳转
            return redirect::to($url);
        }

        $this->setLayoutFlag('splash');
        $pagedata['msg'] = $msg;
        return $this->page('topc/splash/error.html', $pagedata);
    }

    public function https_request($url, $data = null) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    public function a() {
        echo $this->access_token;
    }

    public function responseMsg() {

        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)) {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            switch ($RX_TYPE) {
                case "text":
                    $resultStr = $this->receiveText($postObj);
                    break;
                case "event":
                    $resultStr = $this->receiveEvent($postObj);
                    break;
                default:
                    $resultStr = "";
                    break;
            }
            echo $resultStr;
        } else {
            echo "";
            exit;
        }
    }

    private function receiveText($object) {
        $funcFlag = 0;
        $contentStr = "你发送的内容为：" . $object->Content;
        $resultStr = $this->transmitText($object, $contentStr, $funcFlag);
        return $resultStr;
    }

    private function receiveEvent($object) {
        $contentStr = "";
        switch ($object->Event) {
            case "subscribe":
                // $contentStr = "你好，欢迎关注橙果云购，我是果宝橙橙!";
                $activity_id = $object->EventKey;   //活动ID
                $open_id = $object->FromUserName;   //openid
                //要实现统计分析，则需要扫描事件写入数据库，这里可以记录 EventKey及用户OpenID，扫描时间
                $activity_id1 = $this->new_add_user($activity_id, $open_id);
                $str = "亲，欢迎您关注橙果云购，我是橙果宝宝橙橙，到菜单栏看看，点击有惊喜哟。";

                $contentStr = $str ;
                $resultStr = $this->transmitText($object, $contentStr);
                return $resultStr;
                break;
            case "SCAN":
                //统计扫码次数
                $activity_id = $object->EventKey;   //活动ID
                $open_id = $object->FromUserName;   //openid
                //要实现统计分析，则需要扫描事件写入数据库，这里可以记录 EventKey及用户OpenID，扫描时间
                $activity_id1 = $this->activity_code($activity_id, $open_id);
                $contentStr = "扫描 " . $activity_id;
                $resultStr = $this->transmitText($object, $contentStr);
                return $resultStr;
                break;
            case "unsubscribe":
                $open_id = $object->FromUserName;   //openid
                $rs = $this->cancel_attention($open_id);
                $contentStr = "扫描 " . $rs;
                $resultStr = $this->transmitText($object, $contentStr);
                return $resultStr;
                break;
            case "CLICK":
                switch ($object->EventKey) {
                    case "ptkf":
                        $contentStr[] = array("Title" => "平台客服推送",
                            "Description" => "平台客服推送",
                            "PicUrl" => "http://discuz.comli.com/weixin/weather/icon/cartoon.jpg",
                            "Url" => "http://baidu.com");
                        break;
                    default:
                        $contentStr[] = array("Title" => "默认菜单回复",
                            "Description" => "您正在使用的是方倍工作室的自定义菜单测试接口",
                            "PicUrl" => "http://discuz.comli.com/weixin/weather/icon/cartoon.jpg",
                            "Url" => "weixin://addfriend/pondbaystudio");
                        break;
                }
                break;
            default:
                break;
        }
        if (is_array($contentStr)) {
            $resultStr = $this->transmitNews($object, $contentStr);
        } else {
            $resultStr = $this->transmitText($object, $contentStr);
        }
        return $resultStr;
    }

    private function transmitText($object, $content, $funcFlag = 0) {
        $textTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
<FuncFlag>%d</FuncFlag>
</xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $funcFlag);
        return $resultStr;
    }

    private function transmitNews($object, $arr_item, $funcFlag = 0) {
        //首条标题28字，其他标题39字
        if (!is_array($arr_item))
            return;

        $itemTpl = "    <item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
    </item>
";
        $item_str = "";
        foreach ($arr_item as $item)
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);

        $newsTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<Content><![CDATA[]]></Content>
<ArticleCount>%s</ArticleCount>
<Articles>
$item_str</Articles>
<FuncFlag>%s</FuncFlag>
</xml>";

        $resultStr = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($arr_item), $funcFlag);
        return $resultStr;
    }

    //新用户关注 字段修改
    public function new_add_user($activity = 1, $openid = 12313) {
        //分割
        $activity_arr = explode('_', $activity);
        $activity_id = $activity_arr[1];
        $rs = $this->activity_code($activity_id, $openid);
        return $rs;
    }

    //如果是扫码用户
    public function activity_code($activity_id = 1, $openid = 12313) {

        $activity_id = intval($activity_id);
        //根据openid 换取 urid
        //统计活动扫码次数
        $time = date('Ymd', time());
        $where = 'atime=' . $time . ' and activity_id=' . $activity_id;
        $rs1 = M('activity_statistics')->where($where)->find();

        if ($rs1) {
            M('activity_statistics')->where($where)->setInc('sweep_number');
        } else {
            $arr1 = array(
                'atime' => $time,
                'activity_id' => $activity_id,
                'time' => time(),
                'year' => date('Y', time()),
                'month' => date('m', time()),
                'day' => date('d', time()),
            );
            M('activity_statistics')->add($arr1);
        }

        //查询是否是新关注用户

        $openid = (string) $openid;
        $arr2 = array(
            'openid' => $openid,
        );
        $rs = M('wechat_attention')->where($arr2)->find();

        if (!$rs) {
            //将用户记录插入
            $arr = array(
                'openid' => $openid,
                'activity_id' => $activity_id,
                'atime' => $time,
            );
            M('wechat_attention')->add($arr);
            //统计新关注
            M('activity_statistics')->where($where)->setInc('new_attention');
        }
        //将活动Id和openid存入session;
        setcookie("activity_id", $activity_id, time()+3600);
        S('activity_id',$activity_id);
       // $_SESSION['wechat']["activity_id"] = $activity_id;
       // S('activity_id',$activity_id);  //设置session
       // $_SESSION['wechat']["openid"] = $openid;
        return $activity_id;
    }

    //取消关注
    public function cancel_attention($appid = 11) {
        $appid = (string) $appid;
        //查询用户所在活动
        $arr2 = array(
            'openid' => $appid,
        );
        $activity = M('wechat_attention')->where($arr2)->find();
        if ($activity) {
            $arr = array(
                'activity_id' => $activity['activity_id'],
                'atime' => $activity['atime'],
            );
            M('activity_statistics')->where($arr)->setInc('cancel_attention');
        }

        //删除记录
        $rs = M('wechat_attention')->where($arr2)->delete();

        return $rs;
    }

    public function setsession(){
        session('[start]'); // 启动session
        session('activity_id',66);
        cookie("activity_id",37);//设置session
}

    public function getsession(){
      var_dump(session('activity_id'));
    }

}
