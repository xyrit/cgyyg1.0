<?php

// +----------------------------------------------------------------------
// | 活动接口控制器
// +----------------------------------------------------------------------
// | Author: charles
// +----------------------------------------------------------------------
// | ps：由于是临时活动，红包活动采取写死状态
// +----------------------------------------------------------------------
namespace Wap\Controller;
use Home\Common\UtilApi;
use Addons\Cqssc\CqsscApi;
header("Access-Control-Allow-Origin:*");

class IpadController extends HomeController {
    protected $Referer;
    protected $Host;
    protected $Agent;
    protected $user_token;
    protected $Ip;
    protected $hosturl;
    protected $lottery_id;
    protected $pid;

    /**
     *  判断用户充值的总额。多少
     */
    public function __construct(){
        //获取Referer
        $this->Referer = $_SERVER['HTTP_REFERER'];
        //获取Host
        $this->Host = $_SERVER['HTTP_HOST'];
        //获取Agent
        $this->Agent = $_SERVER['HTTP_USER_AGENT'];
        $this->hosturl=array(
            'local.cgyyg.com',
            'test.cgyyg.com',
            'www.cgyyg.com',

        );
        $this->lottery_id=I('get.lottery_id');
        //获取访问者IP
        self::createIp();
        //获取访客信息和cookie
     //   self::createToken();
        $this->user_token=I('get.user_token','');
        $this->pid=I('get.pid',0);
    }

   public function product(){
       $this->pid='480';//指定商品id;
       //商品id
       $content=I('get.content',1);

       $detail = D('Home/lotteryProduct')->getDetailByIdForEvent($this->pid,$this->lottery_id);

       if(!$detail){
           UtilApi::getInfo('500', '该活动已下架');
           return;
       }
       if($this->lottery_id){
          if($detail['uid'] >0){
              $zhong= M('EventRecord')->where(array('uid'=>$detail['uid'],'lottery_id'=>$this->lottery_id))->field('attend_ip,lucky_code,create_time,ip_address,attend_count')->select();
              $total_attend=0;
              foreach($zhong as &$zv){
                  $total_attend+=$zv['attend_count'];
                  unset($zv['attend_ip']);
                  unset($zv['ip_address']);
              }
              $userinfo=unserialize($detail['user_info']);
              $userinfo['attend_ip']=long2ip($userinfo['attend_ip']);
              $userinfo['uid']=$detail['uid'];
              $userinfo['attend_count']=$total_attend;
              $users=getUserInfo($detail['uid']);
              $userinfo['nickname']=$users['nickname'];
              $userinfo['face']=getfullPath($users['face'],'100');
              $detail['userinfo']=$userinfo;
              $detail['attend_info']=$zhong;
          }

       }
       $ids=$detail['pics'];
       $pic_arr=array();
       if($ids){
           $con['id'] = array('in', $ids);
           $list = M('picture')->field('path')->where($con)->select();
          foreach($list as &$v){
              $v='http://img.cgyyg.com'.$v['path'];
          }
           $pic_arr=$list;
       }
       $detail['pics']=$pic_arr;
       $detail['countdown']=$detail['closing_time']-time();  //截止倒计时
       $detail['lotterytime']=$detail['lotterydown']-time();  //开奖倒计时

       if($content==1){ // 内容地址转换
           preg_match_all( '/<img[^>]*?\s(?:data-)?src="([^"]*)"[^>]*>/i' , $detail['content'] , $results );
           $replace_url=array();
           foreach($results[1] as $k1=>$v1){
               $replace_url[]='http://img.cgyyg.com'.$v1;
           }
           $detail['content']= str_replace($results[1], $replace_url,  $detail['content']);
       }
       $detail['start_code']='10000001';
       $data['code']='200';
       $data['items']=$detail;
       echo json_encode($data,JSON_HEX_TAG);
       exit;

   }

    /**
     * 检测是否登陆，是否绑定手机
     */
    public function checkuser(){
       $uid = $this->is_login($this->user_token);
        //检测是否绑定手机
        $mobile =M('Member')->where(array('uid'=>$uid))->getField('mobile');
        if(!$mobile){
            echo json_encode(array('code' => 520, 'info' => "没有绑定手机"));
            exit;
        }
        echo json_encode(array('code'=>200,'info'=>'正常'));

    }

    /**
     * 检测今日领取状态
     */
    function check_recevie(){
        $c_type=I('get.ctype','0');
        $uid=$this->is_login($this->user_token);
        $dtype=array('1','2','3');
        $today_time = strtotime('0:00');
        $lottery_id=I('get.lottery_id');
        if(!$lottery_id){
            echo json_encode(array('code'=>'533','期号不能为空'));
            exit;
        }
        $pid=I('get.pid',0);
        $return= array(
                 '1'=>1,
                 '2'=>0,
                 '3'=>0,
            );
        /**
         * 查询该用户是否参与过
         */
        $canyu=M('EventRecord')->where(array('uid'=>$uid))->count();
        if($canyu){
            $data['tiao']=1;
        }else{
            $data['tiao']=0;
        }
        if($c_type){
            echo json_encode($data);
            exit;
        }
        $list = M('EventRecord')->where(array('uid'=>$uid,today_time=>$today_time))->select();
        if($list){
            $types=getSubByKey($list,'type');
            foreach($types as $k1=>$v1){
                $return[$v1]=2;//已领取
            }
            $diff = array_diff($dtype,$types);
            if($diff){
                foreach($diff as $k=>$v){
                    if($v =='3'){//查询今天是否云购
                        $map['uid']=$uid;
                        $map['create_time'][]=array('egt',$today_time);
                        $map['create_time'][]=array('lt',$today_time+24*3600);
                        $res= M('LotteryAttend')->where($map)->select();
                        $return['3']=$res?1:0;
                    }elseif($v=='1'){
                        $r= $this->insertRecord(1,$lottery_id,$pid);
                        $return['1']=$r?2:0;
                    }else{
                        $return[$v]=0;
                    }
                }
            }
        }else{
            if(!$data['tiao']){  // 自动参与 时 检测当前商品是否已经禁止
                $closing_time=M('LotteryEvent')->where(array('lottery_id'=>$lottery_id))->getField('closing_time');
                if(time() <= $closing_time){
                    $r= $this->insertRecord(1,$lottery_id,$pid);
                    $return['1']=$r?2:1;
                }
            }

            //查询今天是否云购
            $map['uid']=$uid;
            $map['create_time'][]=array('egt',$today_time);
            $map['create_time'][]=array('lt',$today_time+24*3600);
            $res= M('LotteryAttend')->where($map)->select();
            $return['3']=$res?1:0;

        }
        $ulist=M('EventRecord')->where(array('uid'=>$uid,'lottery_id'=>$lottery_id))->field('attend_count')->select();
        $num=0;
       foreach($ulist as $v){
         $num+=$v['attend_count'];
       }
        //
        $total=M('LotteryEvent')->where(array('lottery_id'=>$lottery_id))->getField('attend_count');
        $ratio=$total?(sprintf("%.4f", $num/$total)*100).'%':'0%';
        $data['code']='200';
        $data['items']['info']=$return;
        $data['items']['num']=$num;
        $data['items']['ratio']=$ratio;
        echo json_encode($data);
        exit;


    }

    /**
     * 查看幸运码
     */
    public function viewcode(){
        $uid=$this->is_login($this->user_token);
        $map['uid']=$uid;
        $map['lottery_id']=$this->lottery_id;
        $list=  M('EventRecord')->where($map)->field('lucky_code,create_time,id,type,attend_count')->order('create_time desc')->select();
        $source=array('1'=>0,'2'=>0,'3'=>0);
        $num=0;
        $ratio='0%';
        if($list){

            foreach($list as $k=>$v){
                $source[$v['type']]+=$v['attend_count'];
                $num+=$v['attend_count'];
            }
            $total=M('LotteryEvent')->where(array('lottery_id'=>$this->lottery_id))->getField('attend_count');

            $ratio=$total?(sprintf("%.4f", $num/$total)*100).'%':'0%';
        }else{
            echo (json_encode(array('code'=>'200','info'=>'无相关记录')));
            exit;
        }

        $data['code']='200';
        $data['items']=$list;
        $data['num']=$num;
        $data['ratio']=$ratio;
        $data['source']=$source;
        echo (json_encode($data));

    }

    /**
     * 往期揭晓
     */
    public function getListforPast(){
       $map['m.pid']=$this->pid;
       $map['m.lottery_time']=array('gt','0');
        $prefix = C('DB_PREFIX');
        $l_table  = $prefix.'lottery_event'; // 活动开奖表
        $r_table  = $prefix.'event_record';   //  活动参与表

        $info  = M() ->field('m.pid,a.lottery_id,m.lottery_code as lucky_code,m.lottery_time,m.uid,m.user_info,sum(a.attend_count) as attend_count')
            ->table($l_table.' m')
            ->join($r_table.' a ON m.uid=a.uid and m.lottery_id =a.lottery_id ')
            ->where($map)
            ->group('a.lottery_id')
            ->order('m.lottery_id desc')
            ->select();

       // $info=M('LotteryEvent')->field('pid,lottery_id,lottery_code as lucky_code,lottery_time,m.uid')
        if($info){
            foreach($info as &$v){
                $userinfo=unserialize($v['user_info']);
                $users=getUserInfo($v['uid']);
                $v['attend_ip']=long2ip($userinfo['attend_ip']);
                $v['ip_address']=$userinfo['ip_address'];
                $v['nickname']=$users['nickname'];
                $v['face']=getfullPath($users['face'],'100');
                unset($v['user_info']);
            }
        }else{
            $info=array();
        }

        echo json_encode(array('code'=>200,'items'=>$info));
        exit;
    }
    /**
     * 领取幸运码
     */
    public function receive_code(){
        //检测Referer与Host
      if(!$this->Referer){
            echo json_encode(array('code' => 523, 'info' => "不要作弊"));
            exit;
        }elseif(!in_array($this->Host,$this->hosturl)){
            echo json_encode(array('code' => 524, 'info' => "不要作弊"));
            exit;
        }

        static $code;
        $lottery_id=I('get.lottery_id');
        /**
         *  根据时间调整。
         */
        $closing_time=M('LotteryEvent')->where(array('lottery_id'=>$this->lottery_id))->getField('closing_time');
       if(time() > $closing_time){
           echo json_encode(array('code'=>500,'info'=>'该商品已截止'));
           exit;
       }

        $pid=I('get.pid',''); //
        $type=I('get.type',''); // 1,每日领取，2,分享微信领取，3.每日云购
        $res=$this->insertRecord($type,$lottery_id,$pid);
        if($res){
            echo json_encode(array('code'=>200,'info'=>'领取成功'));
            exit;
        }else{
            echo json_encode(array('code'=>500,'info'=>'已经领取'));
            exit;
        }

    }

    private function insertRecord($type='1',$lottery_id,$pid){
        if(!in_array($type,array('1','2','3'))){
            echo json_encode(array('code' => 522, 'info' => "非法参数"));
            exit;
        }
        $uid=$this->is_login($this->user_token);
        $EventRecord=M('EventRecord');
        $LotteryEvent=M('LotteryEvent');
        $num_arr=array('1'=>'1','2'=>'3','3'=>'1');
        $today_time = strtotime('0:00');
        $parma['type']=$type;
        $parma['today_time']=$today_time;
        $parma['uid']=$uid;
        if($EventRecord->where($parma)->count()==0){ // 今天未参与
            $max_code=$this->getLastcode($lottery_id);
            for($i=1;$i <= $num_arr[$type];$i++){
                $code=$max_code+$i;
                $lucky_code.=$code.',';
            }
            $lucky_code=rtrim($lucky_code,',');
            $LotteryEvent->startTrans();
            $save['attend_count']=array('exp',"attend_count+$num_arr[$type]");
            $save['lucky_code']=$code;
            $saveid=$LotteryEvent->where(array('lottery_id'=>$lottery_id))->save($save);
            $parma['pid']=$pid;
            $parma['attend_count']=$num_arr[$type];
            $parma['lucky_code']=$lucky_code;
            $parma['create_time']=time();
            $parma['attend_ip']=$this->Ip;
            $parma['lottery_id']=$lottery_id;
            $parma['ip_address']=UtilApi::getIpLookup(long2ip($this->Ip)); //获取ip地址所在的省和城市;
            $parma['attend_device']='attend_device'; //参与设备
            $parma['create_date_time']= date('Y-m-d H:i:s') . substr(microtime(), 1, 4); //参与时间 显示2016-01-11 11:07:22.123格式
            $parma['sfm_time'] = str_replace('.', '', str_replace(':', '', substr($parma['create_date_time'], 11, 12))); //时分秒格式，截取和替换字符串，显示为110722123

            $addid= $EventRecord->add($parma);
            if($saveid && $addid){
                $LotteryEvent->commit();
            }else{
                $LotteryEvent->rollback();
            }
            return true;
        }else{
            return false;
        }
    }
    /**
     * @param $lottery_id
     * @return string 领取最大的幸运码
     */
    private function getLastcode($lottery_id){
       $max_code= M('LotteryEvent')->where(array('lottery_id'=>$lottery_id))->max('lucky_code');
        !$max_code && $max_code='10000000';
        return $max_code;
    }

    public function weixinshare(){

    }

    public function getSign(){
        $params=$this->getWxJsConf();

        $Refer=I('get.url','','urldecode');

        $signature_str=http_build_query($params)."&url=".$Refer;

        $params['signature'] = sha1($signature_str);
        $wxapp = C('weixin');
        $params['appId']=$wxapp['appid'];
        $params['url']=$Refer;

      //  unset($params['jsapi_ticket']);
        $data['status']='1';
        $data['info']=$params;
       echo json_encode($data);
    }
    /**
     * 获取微信JS配置
     */
    public function getWxJsConf(){
        //获取tiket
        $jsapi_ticket = $this->wxjsapitiket();
        //获取随即串
        $noncestr = \Org\Util\String::randString(50);
        //拼合返回值
        return array(
            'jsapi_ticket'=>$jsapi_ticket,
            'noncestr'=>$noncestr,
            'timestamp'=>NOW_TIME,
        );
    }
    /**
     * 获取微信JS中需要的Ticket
     */
    private function wxjsapitiket(){
        $key = 'wx_jsapi_tiket1';
        $tiket = S($key);
        if(!$tiket || NOW_TIME > $tiket['my_expire_time']){
            $token = $this->wxjstoken();
            $url ='https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$token.'&type=jsapi';
            $tiket = get_curl_data($url);
            $tiket = json_decode($tiket[1],true);
            if($tiket['errcode'] == '0'){
                $tiket['my_expire_time'] = NOW_TIME + $tiket['expires_in'] - 100;
                S($key,$tiket,$tiket['expires_in'] - 100);
            }
        }
        return $tiket['ticket'];
    }

    /**
     * 获取微信JS中需要的Access Token
     */
    private function wxjstoken(){
        $key = 'wx_jsapi_token1';
        $wx_config=C('weixin');
        $token = S($key);
        if(!$token || NOW_TIME > $token['my_expire_time']){
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$wx_config['appid'].'&secret='.$wx_config['secret'];
            $token = get_curl_data($url);
            $token = json_decode($token[1],true);

            if($token['access_token']){
                $token['my_expire_time'] = NOW_TIME + $token['expires_in'] - 100;
                S($key,$token,$token['expires_in'] -100);
            }
        }
        return $token['access_token'];
    }

    /**
     * 获取当前访客IP
     */
    private function createIp(){
        $this->Ip = get_client_ip(1,true);
    }

    /**
     *  开奖结果
     */
    public function getResult(){
        $lottery_model=M('LotteryEvent');
        //  根据时间判断， 如果时间不对， 跳出
        $lottery=$lottery_model->where(array('lottery_id'=>$this->lottery_id))->find();

        if($lottery['lottery_time'] > 0){
            echo json_encode(array('info'=>'已经揭晓','status'=>'-1'));
            exit;
        }
        $now_time=time();
        if($now_time < $lottery['closing_time']){  //
            echo json_encode(array('info'=>'非法请求','status'=>'-2'));
            exit;
        }elseif($now_time >= $lottery['closing_time'] && $now_time < $lottery['lotterydown']){ //揭晓中
            if($lottery['total_time'] > 0 ){

                echo json_encode(array('info'=>'最近50条记录之和已经返回','status'=>'-3'));
                exit;
            }else{
                // 计算 50 个 最近的记录
                $result = CqsscApi ::getCqssc(); //获取时时彩数据
                $code_array = json_decode($result, true);
                foreach ($code_array as $key1 => $value1) {
                    //上一次时时彩数据号码分解
                    S('event_hour_id',$key1);
                    break;
                }


                $total_time= $this->getTotalTime($this->lottery_id);

                $lottery_model->where(array('lottery_id'=>$this->lottery_id))->save(array('total_time'=>$total_time));
                echo json_encode(array('info'=>'最近50条记录已经计算完','status'=>'-4'));
                exit;
            }
        }elseif($now_time >= $lottery['lotterydown']){
            // 开始计算结果
            $result = CqsscApi ::getCqssc(); //获取时时彩数据
            $array = json_decode($result, true);
            foreach ($array as $key => $value) {
                //时时彩数据号码分解
                $hour_code_id = $key;
                $hour_code = '';
                $number = explode(",", $array[$key]['number']);
                foreach ($number as $k => $v) {
                    if($k==0 && $v ==0) {
                        continue;
                    }
                    $hour_code .= $v;
                }
                break;
            }

            $mod_code = intval(fmod(floatval($lottery['total_time']) + floatval($hour_code), $lottery['attend_count']));
            $lottery_code = $mod_code+10000001;
            $data = array();
            $data['hour_lottery'] = $hour_code; //时时彩号码
            $data['mod'] = $mod_code; //余数
            $data['hour_lottery_id'] = $hour_code_id; //时时彩id
            $data['lottery_code'] = $lottery_code; //已揭晓的幸运码
            $data['lottery_time'] = time(); //揭晓幸运码时间

            //根据期号查找某个已开奖期号的所有用户参与记录
            $where['lottery_id']=$this->lottery_id;
            $where['lucky_code']=array('like',"%$lottery_code%");
            $zhong_yonghu = M('EventRecord')->field('id,uid,attend_count,lucky_code,create_date_time,attend_ip,ip_address')->where($where)->find();
            $data['uid'] = $zhong_yonghu['uid']; //中奖用户id
            $data['aid'] = $zhong_yonghu['id']; //参与记录id
            $user=getUserInfo( $data['uid']);
            $data['user_info']=serialize(array(
                'lucky_code'=>$zhong_yonghu['lucky_code'],
                'attend_ip'=>$zhong_yonghu['attend_ip'],
                'ip_address'=>$zhong_yonghu['ip_address'],
                'face'=>$user['face'],
                'nickname'=>$user['nickname'],
            ));

            $lottery_model->startTrans();// 开启事物
            $save_id= $lottery_model->where(array('lottery_id'=>$this->lottery_id))->save($data);//更新开奖表中奖用户id和参与记录id
            //开启下一次
            // 下周五 下午3点截止时间
            //判断今天是周几
             $weeks=date('l',time());
             if($weeks == 'Monday'){
                  $nextFriday_1=strtotime('Thursday')+15*60*60;
             }elseif($weeks =='Thursday'){
                  $nextFriday_1=strtotime('next Monday')+15*60*60;
             }
            $nextFriday_1=strtotime('next Thursday')+15*60*60;
           // $nextFriday_1=strtotime("0.00")+60*60*24*2+16*60*60+30*60; // 临时两天一次奖
            $nextFriday_2=$nextFriday_1+15*60;
            $insert['pid']=$lottery['pid'];
            $insert['create_time']=time();
            $insert['lottery_id']=$this->lottery_id +1;
            $insert['closing_time']=$nextFriday_1;
            $insert['lotterydown']=$nextFriday_2;
            $insertid=$lottery_model->add($insert);
            if($save_id && $insertid){
                $lottery_model->commit();
            }else{
                $lottery_model->rollback();
            }

        }
        echo json_encode(array('info'=>'马上揭晓','status'=>'1'));
    }

    /**
     * @param int $lottery_id  获取之和。
     */
    protected function getTotalTime($lottery_id=0){
      $data=  M('EventRecord')->where(array('lottery_id'=>$lottery_id))->field('sfm_time')->order('create_time desc')->limit('50')->select();
        foreach($data as $k=>$v){
            $total_num+=$v['sfm_time'];
        }
        return $total_num;
    }

    /**
     * 查看计算结果
     */
    public function getResultDetail(){

        $data=  M('EventRecord')->where(array('lottery_id'=>$this->lottery_id))->field('create_date_time,uid,sfm_time')->order('create_time desc')->limit('50')->select();
        foreach($data as &$v){
            $v['nickname']=getUserInfo($v['uid'],'nickname');
        }
        $total_time=M('LotteryEvent')->where(array('lottery_id'=>$this->lottery_id))->field('total_time,hour_lottery,lottery_code')->find();
        $lottery_code=$total_time['lottery_code']?$total_time['lottery_code']:'';
        $hour_id=S('event_hour_id')?S('event_hour_id')+1:date('Ymd').'055';
        $items['record']=$data;
        $items['total_time']=$total_time['total_time'];
        $items['hour_code']=$total_time['hour_lottery'];
        $items['hour_id']=$hour_id;
        $items['lottery_code']=$lottery_code;

      echo json_encode(array('code'=>200,'items'=>$items));
        exit;

    }

    /**
     * 获取当前访客身份信息和cookie
     */
/*    private function createToken(){
        return;
        //检测终端信息
        if(!$this->Agent){
            $agent_status = '0';			       //非法agent
            $opersys = '0';				       //无效的设备系统
            $browse = '0';					       //无效的浏览终端
        }else{
            vendor('MobileDetect.MobileDetect');
            $detect = new \Mobile_Detect();
            //是否为移动设备
            $agent_status = $detect->isMobile() ? '1' : '0';
            if($agent_status == '0'){
                $opersys = '0';					//非移动端系统
                $browse = '0';						//非移动端浏览终端
            }else{
                //判断设备系统
                if($detect->isiOS()){
                    $opersys = '1';		//苹果系统
                }elseif($detect->isAndroidOS()){
                    $opersys = '2';		//安卓系统
                }elseif($detect->isWindowsMobileOS() || $detect->isWindowsPhoneOS()){
                    $opersys = '3';		//windows系统
                }else{
                    $opersys = '4';		//其他系统
                }
                //判断浏览终端
                if(strpos($this->Agent,'MicroMessenger') !== false){
                    $browse = '1';			//微信
                }elseif(strpos($this->Agent,'MQQBrowser') !== false){
                    $browse = '2';			//QQ
                }elseif(strpos($this->Agent,'Weibo') !== false){
                    $browse = '3';			//微博
                }else{
                    $browse = '4';			//其他
                }
            }
        }
        $token = array(
            'agent'=>$this->Agent,
            'agent_status'=>$agent_status,
            'opersys'=>$opersys,
            'browse'=>$browse
        );
        $token_id = cookie('TOKEN_ID');
        if(!$token_id){
            $encrypt = $token;
            $encrypt['add_time'] = NOW_TIME;
            $encrypt['random'] = uniqid('visitor_',true);
            $encrypt = think_encrypt(serialize($encrypt));
            $token_id = md5($encrypt.'ldfsfzlm');
            cookie('TOKEN_ID',$token_id);
        }
        $this->Token = $token;
        $this->Token_id = $token_id;
    }*/

}