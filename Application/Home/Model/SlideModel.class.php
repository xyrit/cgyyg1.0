<?php

namespace Home\Model;

use Think\Model;

/**
 * 幻灯片广告模型
 * 更新时间 2015.12.08
 */
class SlideModel extends Model {

    /**
     * 获取幻灯片列表
     */
    public function getList($place) {
        $prefix = C('DB_PREFIX');
        $list = $this->query("select s.url,p.path from " . $prefix . "slide s join " . $prefix . "picture p on s.icon=p.id where s.status=1 and p.status=1 and s.place=".$place." order by s.place limit 6");
        $sql = "select la.id ,lp.lottery_id,la.create_time,lp.pid from  " . $prefix . "lottery_attend as la " .
                " left join " . $prefix . "lottery_product as lp on la.lottery_id=lp.lottery_id" .
                " WHERE (lp.last_attend_time<1) group by la.create_time desc limit 0,1";
        $ids = $this->query($sql);
        $pid = $ids[0]["pid"];
        $lottery_id = $ids[0]["lottery_id"];

        foreach ($list as $k => $v) {
            if($v['url']=='')
            {
                if($place==1)
                {
                    $list[$k]["url"] = "goods-details.html?pid=" . $pid . "&lottery_id=" . $lottery_id . "&flag=1";
                }
                elseif($place==2)
                {
                    $list[$k]["url"] = "3_goods-ing.html?pid=" . $pid . "&lottery_id=" . $lottery_id . "&flag=1";
                }

            }

        }
        return $list;
    }

}
