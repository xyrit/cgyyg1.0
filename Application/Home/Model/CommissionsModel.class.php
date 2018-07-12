<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/2/29
 * Time: 19:17
 * 提成
 */
namespace Home\Model;

use Think\Model;

class CommissionsModel extends Model {


    /*
     * 提成列表
     */

    function getcommissionList($uid,$pageIndex,$pageSize,$loweruid){
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $prefix = C('DB_PREFIX');

        if($loweruid)
        {
            $where=' and uid='.$loweruid;
        }

        $sql1= "select * from " . $prefix . "commissions c where c.fid=".$uid.$where
            ." order by addtime desc "
            ."limit $start,$end";
        $list = $this->query($sql1);
        foreach($list as $key=>$row)
        {
            //一级分销
            $where = 'uid='.$row['uid'];
            $sql2= "select uid,nickname,face,mobile from "  . $prefix . "member m where ".$where;
            $userInfo =$this->query($sql2);
            $userInfo[0]['mobile']= $this->dphone($userInfo[0]['mobile']);
            $list[$key]['userInfo']=$userInfo[0];
        }

        $count=M('commissions')->where('fid='.$uid)->count();    //数量

        $pageCount= ceil($count/$pageSize);
        $onecommission=M('commissions')->where('fid='.$uid.' and level=1')->sum('consumption');  //一级好友消费
        $towcommission=M('commissions')->where('fid='.$uid.' and level=2')->sum('consumption');   //二级好友消费
        $countuser=M('relation_member')->where('fid='.$uid)->count();

        $sumcommission=M('commissions')->where('fid='.$uid.' or ofid='.$uid)->sum('commission');      //累计获得佣金

        return array('list'=>$list,'countuser'=>$countuser,'count'=>$pageCount,'onecommission'=>$onecommission,'towcommission'=>$towcommission,'sumcommission'=>$sumcommission);
    }

    /*
     * 获取我的推荐人
     */

    public function getrecommendedList($uid){

        $prefix = C('DB_PREFIX');
        $sql="select * from " . $prefix . "relation_member where uid=".$uid;
        $list=$this->query($sql);
            $i=0;
       foreach($list as $key=>$row)
       {
            if($row['fid'] > 0)
            {
            $where = 'uid='.$row['fid'];
                $list[$i]['level']=1;
                $list[$i]['commission'] = M('commissions')->where('uid='.$uid.' and fid='.$row['fid'])->sum('commission');
                $sql2= "select uid,nickname,face,mobile from "  . $prefix . "member m where ".$where;
                $userInfo =$this->query($sql2);
                $userInfo[0]['mobile']= $this->dphone($userInfo[0]['mobile']);
                $list[$i]['userInfo']=$userInfo[0];
            }
           if($row['ofid'] > 0)
           {
               $i+=1;
               $where = 'uid='.$row['ofid'];
               $list[$i]['level']=2;
               $list[$i]['commission'] = M('commissions')->where('uid='.$uid.' and fid='.$row['ofid'])->sum('commission');
           }
           $sql2= "select uid,nickname,face,mobile from "  . $prefix . "member m where ".$where;
           $userInfo =$this->query($sql2);
           $userInfo[0]['mobile']= $this->dphone($userInfo[0]['mobile']);
           $list[$i]['userInfo']=$userInfo[0];
       }

        return $list;
    }

    /*
     * 提现记录
     */

    public function getconsumptionsList($uid,$pageIndex,$pageSize,$starttime,$endtime,$sort)
    {
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $prefix = C('DB_PREFIX');


        //今天时间戳
        $y = date("Y");
        $m = date("m");
        $d = date("d");
        $todayTime= mktime(0,0,0,$m,$d,$y);
        //如果有结束时间则按区间查询
        if($sort=='all')
        {
            $where='';
        }
        elseif($sort=='today')
        {
            $where= ' and addtime >'.$todayTime;
        }
        elseif($sort=='week')     //本周
        {
            $endtime=mktime(0,0,0,date("m"),date("d")-date("w")+1,date("Y"));
            $where= ' and addtime <'.$todayTime.' and addtime >'.$endtime;
        }
        elseif($sort=='month')     //本月
        {
            $endtime=mktime(0,0,0,date('m'),1,date('Y'));
            $where= ' and addtime <'.$todayTime.' and addtime >'.$endtime;
        }
        elseif($sort=='three_months')     //三月内
        {
            $endtime=mktime(0,0,0,date('m'),1,date('Y')-3);
            $where= ' and addtime <'.$todayTime.' and addtime >'.$endtime;
        }
        elseif($sort=='time')
        {
            if($endtime)
            {

                $where= ' and addtime >'.$starttime.' and addtime < '.$endtime;

            }
            else
            {
                $where= ' and addtime <'.$starttime;
            }
        }



        $sql= "select bank_id,consumption_status,consumption_money,addtime,seriall_number from "  . $prefix . "consumptions cp where uid=".$uid
       .$where
        ." order by addtime desc "
        ."limit $start,$end";


        $sql2= "select count(*) as counts from "  . $prefix . "consumptions cp where uid=".$uid
            .$where;


        $list =$this->query($sql);
        $count =$this->query($sql2);

        $pageCount= ceil($count[0]['counts']/$pageSize);
        $sumcommission=M('commissions')->where('fid='.$uid.' or ofid='.$uid)->sum('commission');      //累计获得佣金
        $sumcommission = $sumcommission ?  $sumcommission:0;
        $withdraw_bank=M('consumptions')->where('uid='.$uid.' and bank_id > 0 and consumption_status="1"')->sum('consumption_money');  //累计提现到银行卡
        $withdraw_bank = $withdraw_bank ?  $withdraw_bank:0;
        $withdraw_user=M('consumptions')->where('uid='.$uid.' and bank_id = 0 and consumption_status="1"')->sum('consumption_money');   //累计转账到帐号
        $withdraw_user = $withdraw_user ?  $withdraw_user:0;
        $sumconsumption=M('consumptions')->where('uid='.$uid.' and consumption_status="1"')->sum('consumption_money');     //累计消费佣金
        $sumconsumption = $sumconsumption ?  $sumconsumption:0;
        $remain=M('member')->where('uid='.$uid)->getField('brokerage');      //剩余可用佣金
        $remain = $remain ?  $remain:0;
        return array('count'=>$pageCount,'list'=>$list,'sumcommission'=>$sumcommission,'withdraw_bank'=>$withdraw_bank,'withdraw_user'=>$withdraw_user,'sumconsumption'=>$sumconsumption,'remain'=>$remain);
    }
    public function transfer_accounts($data){
        $prefix = C('DB_PREFIX');
        $rs= M('consumptions')->add($data);

//        $sql=" Update "  . $prefix . "member SET brokerage=".$data['remain_money']." where uid=".$data['uid'];
//        $rs1 =$this->query($sql);
        $arr=array('brokerage'=>$data['remain_money']);
        $rs1= M('member')->where('uid='.$data['uid'])->save($arr);
        if($rs && $rs1)
        {
            return true;
        }
        else
        {
            return false;
        }


    }

    //替换手机号函数
    function dphone($phone)
    {

        $pattern1 = "/(1\d{1,2})\d\d(\d{0,3})/";
        $replacement1 = "\$1*****\$3";
        return preg_replace($pattern1, $replacement1, $phone);
    }

}