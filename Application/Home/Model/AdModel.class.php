<?php

namespace Home\Model;

use Think\Model;

/**
 * 广告模型
 * 更新时间 2015.12.23
 */
class AdModel extends Model {
    /*
     * 获取首页三个广告列表
     */

    public function getList() {
        $prefix = C('DB_PREFIX');
        $sql = "select a.url,p.path from " . $prefix . "ad a join " . $prefix . "picture p on a.icon=p.id where a.status=1 and a.ypid=0 and p.status=1 order by a.place limit 3";
        $list = $this->query($sql);
        return $list;
    }

    /*
     * 获取汽车广告列表
     */

    public function getCarList() {
        $prefix = C('DB_PREFIX');
        $sql = "select a.url,p.path from " . $prefix . "ad a "
                . "join " . $prefix . "picture p on a.icon=p.id where a.status=1 and a.ypid=1 and p.status=1 order by a.place limit 3";
        $list = $this->query($sql);
        return $list;
    }
}
