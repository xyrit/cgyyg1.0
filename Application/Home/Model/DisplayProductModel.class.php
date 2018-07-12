<?php

namespace Home\Model;

use Think\Model;

/**
 * 验证码
 *
 * @author joan
 */
class DisplayProductModel extends Model {
    /* 晒单详情 */

    public function orderInfo($orderid = '') {
        $prefix = C('DB_PREFIX');
        $sql = "select m.face,m.nickname,la.attend_count,lp.lottery_code,lp.create_time,dp.create_time as display_time,dp.titlename,dp.description," .
                " dp.picsid from  " . $prefix . "display_product as dp left join  " . $prefix . "member as m on dp.uid = m.uid " .
                " left join  " . $prefix . "lottery_product as lp  on m.uid=lp.uid " .
                " left join  " . $prefix . "lottery_attend  as la  on lp.aid=la.id where  dp.id=" . $orderid;
        $list = $this->query($sql);
        //echo M("picture")->getLastSql();
        return $list;
    }

    /* 晒单评论 */

    public function getComment($orderid = '') {
        $prefix = C('DB_PREFIX');
        $sql = "select m.nickname,m.face,c.id as commentid,c.create_time,c.content  from " . $prefix . "comment " .
                "as c left join " . $prefix . "member as m on c.uid =m.uid where  c.dpid=" . $orderid . " order by c.id";
        $comment = $this->query($sql);
        //echo M("picture")->getLastSql();
        return $comment;
    }

    /*
     * 查询是否签到
     * 
     */

    public function sign($uid = '') {
        $prefix = C('DB_PREFIX');
        $timeStart = strtotime(date("Y-m-d ", time())); //今天00:00
        $timeEnd = strtotime(date("Y-m-d 24:00:00", time())); //今天24:00
        $sql = "select id from " . $prefix . "sign where  (uid=" . $uid . " and sign_time between " . $timeStart . " and " . $timeEnd . ") order by sign_time desc ";
        $sign = $this->query($sql);
        //echo M("picture")->getLastSql();
        return $sign[0]["id"];
    }

}
