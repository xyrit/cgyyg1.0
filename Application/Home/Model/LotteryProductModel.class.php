<?php

namespace Home\Model;

use Think\Model;

/*
 * 商品模型
 * 更新时间 2016.1.5
 */

class LotteryProductModel extends Model {
    /*
     * 获取首页热门推荐或人气推荐
     */

    public function getHotList($num = 8) {
        $prefix = C('DB_PREFIX');
        $sql = "select dp.is_hot_sort,lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on lp.pid = d.id "
                . "left join " . $prefix . "picture p on d.cover_id=p.id "
                 . "left join " . $prefix . "document_product dp on dp.id=lp.pid "
                . "where d.display = 1 and lp.last_attend_time=0 order by dp.is_hot_sort desc limit $num";

        $list = $this->query($sql);

        return $list;
    }

    /*
* 获取Wap首页值得购买
*/

    public function WapgetWorthist($num) {
        $prefix = C('DB_PREFIX');
        $sql = "select dp.worth_id,lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
            . "join " . $prefix . "document d on lp.pid = d.id "
            . "left join " . $prefix . "picture p on d.cover_id=p.id "
            . "left join " . $prefix . "document_product dp on dp.id=lp.pid "
            . "where d.display = 1 and lp.last_attend_time=0 order by dp.worth_id desc limit $num";

        $list = $this->query($sql);

        return $list;
    }


/*
 * 获取首页值得购买
 */

    public function getWorthist($num = 8) {
        $prefix = C('DB_PREFIX');
        $sql = "select dp.worth_id,lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
            . "join " . $prefix . "document d on lp.pid = d.id "
            . "left join " . $prefix . "picture p on d.cover_id=p.id "
            . "left join " . $prefix . "document_product dp on dp.id=lp.pid "
            . "where d.display = 1 and lp.last_attend_time=0 order by dp.worth_id desc limit $num";

        $list = $this->query($sql);

        return $list;
    }

    /*
     * 获取上架新品热门推荐
     */

    public function getNewList($num = 8) {
        $prefix = C('DB_PREFIX');
        $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on lp.pid = d.id "
                . "left join " . $prefix . "picture p on d.cover_id=p.id "
                . "where d.display = 1 and lp.last_attend_time=0 order by lp.create_time desc limit $num";
        $list = $this->query($sql);
        return $list;
    }

    /*
     * 获取分类id对应的或者全部商品总数量
     */

    public function getByCategoryCount($id, $type) {
        $prefix = C('DB_PREFIX');
        if ($id != 0) {
            if ($type == 4) { //即将揭晓
                $sql = "select count(lp.lottery_id) c from " . $prefix . "lottery_product lp "
                        . "join " . $prefix . "document d on lp.pid = d.id "
                        . "where d.display =1 and lp.last_attend_time=0 and lp.attend_ratio>0.5 and d.category_id=$id";
            } else {
                $sql = "select count(lp.lottery_id) c from " . $prefix . "lottery_product lp "
                        . "join " . $prefix . "document d on lp.pid = d.id "
                        . "where d.display =1 and lp.last_attend_time=0 and d.category_id=$id";
            }
        } else { //全部商品
            if ($type == 4) { //即将揭晓
                $sql = "select count(lp.lottery_id) c from " . $prefix . "lottery_product lp "
                        . "join " . $prefix . "document d on lp.pid = d.id "
                        . "where d.display =1 and lp.last_attend_time=0 and lp.attend_ratio>0.5";
            } else {
                 $sql = "select count(lp.lottery_id) c from " . $prefix . "lottery_product lp "
                    . "join " . $prefix . "document d on lp.pid = d.id "
                    . "where d.display =1 and lp.last_attend_time=0";
            }
        }

        $count = $this->query($sql);
        $total = $count[0]['c'];
        return $total;
    }

    /*
     * 获取所有分类正在进行的商品总数量
     */

    public function getByAllCount() {
        $prefix = C('DB_PREFIX');
        $sql = "select count(lp.lottery_id) c from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on lp.pid = d.id "
                . "where d.display =1 and lp.last_attend_time=0";
        $count = $this->query($sql);
        $total = $count[0]['c'];
        return $total;
    }

    /*
     * 获取分类id对应的商品或者全部商品
     */

    public function getByCategory($id, $pageIndex, $pageSize, $type) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        if ($id != 0) {
            if ($type == 1) { //总需人次升序
                $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                        . "join " . $prefix . "document d on lp.pid = d.id "
                        . "left join " . $prefix . "picture p on d.cover_id=p.id "
                        . "where d.display =1 and lp.last_attend_time=0 and d.category_id=$id order by lp.need_count asc limit $start,$end";
            } else if ($type == 2) { //总需人次降序
                $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                        . "join " . $prefix . "document d on lp.pid = d.id "
                        . "left join " . $prefix . "picture p on d.cover_id=p.id "
                        . "where d.display =1 and lp.last_attend_time=0 and d.category_id=$id order by lp.need_count desc limit $start,$end";
            } else if ($type == 3) { //剩余人次升序
                $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                    . "join " . $prefix . "document d on lp.pid = d.id "
                    . "left join " . $prefix . "picture p on d.cover_id=p.id "
                    . "where d.display =1 and lp.last_attend_time=0 and d.category_id=$id order by  (lp.need_count-lp.attend_count) asc limit $start,$end";
            } else if ($type == 4) { //即将揭晓
                $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                        . "join " . $prefix . "document d on lp.pid = d.id "
                        . "left join " . $prefix . "picture p on d.cover_id=p.id "
                        . "where d.display =1 and lp.last_attend_time=0 and lp.attend_ratio>0.5 and d.category_id=$id order by lp.attend_ratio desc limit $start,$end";
            } else { //默认不排序
                $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                        . "join " . $prefix . "document d on lp.pid = d.id "
                        . "left join " . $prefix . "picture p on d.cover_id=p.id "
                        . "where d.display =1 and lp.last_attend_time=0 and d.category_id=$id limit $start,$end";
            }
        } else { //全部商品
            if ($type == 1) { //总需人次升序
                $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                        . "join " . $prefix . "document d on lp.pid = d.id "
                        . "left join " . $prefix . "picture p on d.cover_id=p.id "
                        . "where d.display =1 and lp.last_attend_time=0 order by lp.need_count asc limit $start,$end";
            } else if ($type == 2) { //总需人次降序
                $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                        . "join " . $prefix . "document d on lp.pid = d.id "
                        . "left join " . $prefix . "picture p on d.cover_id=p.id "
                        . "where d.display =1 and lp.last_attend_time=0 order by lp.need_count desc limit $start,$end";
            } else if ($type == 3) { //剩余人次升序
                $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                        . "join " . $prefix . "document d on lp.pid = d.id "
                        . "left join " . $prefix . "picture p on d.cover_id=p.id "
                        . "where d.display =1 and lp.last_attend_time=0 order by  (lp.need_count-lp.attend_count) asc limit $start,$end";




            } else if ($type == 4) { //即将揭晓
                $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                        . "join " . $prefix . "document d on lp.pid = d.id "
                        . "left join " . $prefix . "picture p on d.cover_id=p.id "
                        . "where d.display =1 and lp.last_attend_time=0 and lp.attend_ratio>0.5 order by lp.attend_ratio desc limit $start,$end";
            } else { //默认不排序
                 $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                    . "join " . $prefix . "document d on lp.pid = d.id "
                    . "left join " . $prefix . "picture p on d.cover_id=p.id "
                    . "where d.display =1 and lp.last_attend_time=0 limit $start,$end";
            }
          
        }
        $list = $this->query($sql);
        echo '<pre>';
        print_r($list);exit;
        return $list;
    }

    /*
     * wap 端商品输出排序
     */
    public function getByWapCategory($id, $pageIndex, $pageSize, $type, $sort) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
                if($id)
                {
                    $sql1=' and d.category_id='.$id;
                }
                $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                    . "join " . $prefix . "document d on lp.pid = d.id "
                    . "left join " . $prefix . "picture p on d.cover_id=p.id "
                    . "where d.display = 1 and lp.last_attend_time=0".$sql1." order by lp.".$type." ".$sort." limit $start,$end";

        $list = $this->query($sql);
        return $list;
    }


    /*
     * 根据搜索名称获取搜索商品的总数量
     */

    public function getByNameCount($name) {
        $prefix = C('DB_PREFIX');
        $sql = "select count(lp.lottery_id) c from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on lp.pid = d.id "
                . "where d.display =1 and lp.last_attend_time=0 and lp.lottery_code=0 and d.title like '%$name%'";
        $count = $this->query($sql);
        $total = $count[0]['c'];
        return $total;
    }

    /*
     * 根据搜索名称获取搜索商品的数据，按风分页查询
     */

    public function getByName($name, $pageIndex, $pageSize) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on lp.pid = d.id "
                . "left join " . $prefix . "picture p on d.cover_id=p.id "
                . "where d.display =1 and lp.last_attend_time=0 and lp.lottery_code=0 and d.title like '%$name%' limit $start,$end";
        $list = $this->query($sql);
        return $list;
    }

    /*
     * 获取即将揭晓商品
     */

    public function getSoonProduct() {
        $prefix = C('DB_PREFIX');
        $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on lp.pid = d.id "
                . "left join " . $prefix . "picture p on d.cover_id=p.id "
                . "where d.display =1 and lp.last_attend_time=0 and lp.attend_ratio>0.5 order by lp.attend_ratio desc limit 8";
        $list = $this->query($sql);
        return $list;
    }

    /*
     * 获取等待揭晓商品
     */

    public function getWaitProduct() {
        $prefix = C('DB_PREFIX');
        $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,lp.last_attend_time,d.title,lp.expectTime,p.path from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on lp.pid = d.id "
                . "left join " . $prefix . "picture p on d.cover_id=p.id "
                . "where d.display =1 and lp.last_attend_time > 0 and lp.lottery_code=0 order by lp.last_attend_time asc limit 8";
        $list = $this->query($sql);
        return $list;
    }

    /*
     * 获取已经揭晓商品
     */

//    public function getResultProduct() {
//    $prefix = C('DB_PREFIX');
//    $sql = "select lp.lottery_id,lp.aid,lp.pid,lp.uid,lp.need_count,lp.lottery_code,lp.lottery_time,d.title,p.path from " . $prefix . "lottery_product lp "
//        . "join " . $prefix . "document d on lp.pid = d.id "
//        . "left join " . $prefix . "picture p on d.cover_id=p.id "
//        . "where lp.lottery_code<>0 and d.display =1 order by lp.lottery_time desc limit 8";
//    $list = $this->query($sql);
//    $arrList = new \Org\Util\ArrayList();
//    if ($list) {
//        $count = count($list);
//        for ($i = 0; $i < $count; $i++) {
//            $sql = "select la.lottery_id,la.uid,ip_address,m.nickname,m.face path from " . $prefix . "lottery_attend la "
//                . "left join " . $prefix . "member m on m.uid=la.uid "
//                . "where la.id =" . $list[$i]['aid'];
//            $ulist = $this->query($sql);
//            echo '<pre>';
//            print_r($ulist);
//            if ($ulist) {
//                /* 计算出中奖人总次数 */
//                $sql2="select sum(attend_count) as attend_count  from ". $prefix ."lottery_attend where uid=".$ulist[0]['uid']." and lottery_id=".$list[0]['lottery_id'];
//                $list2 = $this->query($sql2);  // 查询出这一期中奖用户总共买了多少次
//        var_dump($sql2);echo '<br>';
//
//                /* 计算出中奖人总次数 */
//                $ulist = array('attend_count' => $list2[0]['attend_count'],
//
////                    'ip_address' => $ulist[0]['ip_address'],
////                    'nickname' => $ulist[0]['nickname'],
////                    'path' => $ulist[0]['path']
//                   );
//                $arrList->add($ulist);
//            }
//        }
//        echo '<pre>';
//        print_r( $arrList->toArray());exit;
//    }
//    return array('product' => $list, 'user' => $arrList->toArray());
//}

    /*
  * 获取已经揭晓商品
  */
    public function getResultProduct() {

        $prefix = C('DB_PREFIX');
        $sql = "select lp.lottery_id,lp.aid,lp.pid,lp.uid,lp.need_count,lp.lottery_code,lp.lottery_time,d.title,p.path from " . $prefix . "lottery_product lp "
            . "join " . $prefix . "document d on lp.pid = d.id "
            . "left join " . $prefix . "picture p on d.cover_id=p.id "
            . "where lp.lottery_code<>0 and d.display =1 order by lp.lottery_time desc limit 8";
        $list = $this->query($sql);

        foreach($list as  $key=>$row)
        {
            $sql = "select la.lottery_id,la.uid,ip_address,m.nickname,m.face path from " . $prefix . "lottery_attend la "
                . "left join " . $prefix . "member m on m.uid=la.uid "
                . "where la.id =" . $row['aid'];
            $ulist = $this->query($sql);

            /* 计算出中奖人总次数 */
            $sql2="select sum(attend_count) as attend_count  from ". $prefix ."lottery_attend where uid=".$ulist[0]['uid']." and lottery_id=".$ulist[0]['lottery_id'];
            $list2 = $this->query($sql2);  // 查询出这一期中奖用户总共买了多少次
            /* 计算出中奖人总次数 */

            $uslist[] = array(

                'attend_count' => $list2[0]['attend_count'],
                'ip_address' => $ulist[0]['ip_address'],
                    'nickname' => $ulist[0]['nickname'],
                    'path' => $ulist[0]['path']
                   );

        }

        return array('product' => $list, 'user' => $uslist);
    }


  /*
  * wap获取最新揭晓
  */
    public function getNewProduct(){

        $prefix = C('DB_PREFIX');
        $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,lp.last_attend_time,d.title,p.path,lp.expectTime from " . $prefix . "lottery_product lp "
            . "join " . $prefix . "document d on lp.pid = d.id "
            . "left join " . $prefix . "picture p on d.cover_id=p.id "
            . "where d.display =1 and lp.last_attend_time > 0 and lp.lottery_code=0 order by lp.last_attend_time asc limit 3";
        $list = $this->query($sql);
        return $list;
    }
    /*
* wap获取已经揭晓
*/
    public function getAlready_announced(){

        $prefix = C('DB_PREFIX');

        $sql = "select  u.last_login_ip,u.uid,u.nickname,u.face,lp.lottery_id,lp.aid,lp.pid,lp.uid,lp.need_count,lp.lottery_code,lp.lottery_time,d.title,p.path,lp.expectTime from " . $prefix . "lottery_product lp "
            . "join " . $prefix . "document d on lp.pid = d.id "
            . "left join " . $prefix . "picture p on d.cover_id=p.id "
            . "left join " . $prefix . "member u on u.uid=lp.uid "
            . "where lp.lottery_code<>0 and d.display =1 order by lp.lottery_time desc limit 3";


        $list = $this->query($sql);

        return $list;
    }

    /*
     *按条件查询商品
     */
    public function getlistgoods($condition,$acs,$pageIndex,$pageSize){

        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $prefix = C('DB_PREFIX');

        if($condition=='worth_id')
        {
            $sql = "select dp.worth_id,lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on lp.pid = d.id "
                . "left join " . $prefix . "picture p on d.cover_id=p.id "
                . "left join " . $prefix . "document_product dp on dp.id=lp.pid "
                . "where d.display = 1 and lp.last_attend_time=0 order by dp.worth_id desc limit $start,$end";
        }
        else
        {
            $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on lp.pid = d.id "
                . "left join " . $prefix . "picture p on d.cover_id=p.id "
                . "where d.display = 1 and lp.last_attend_time=0 order by lp.".$condition." ".$acs.",lp.lottery_id desc limit $start,$end";
        }
        $list = $this->query($sql);
        return $list;
    }

    /*
     * 根据图片id批量获取图片地址
     */

    public function getImagePath($ids) {
        if (empty($ids)) {
            return array();
        }
        $con['id'] = array('in', $ids);
        $list = M('picture')->field('path')->where($con)->select();
        return $list;
    }

    /*
     * 获取用户参与某期号的幸运码
     */

    public function getAttendById($lotteryId, $uid) {
        $prefix = C('DB_PREFIX');
        if (empty($uid)) {
            return array();
        } else {
            $sql = "select attend_count,lucky_code,create_date_time create_time from " . $prefix . "lottery_attend where lottery_id = $lotteryId and uid = $uid";
            $list = $this->query($sql);
            return $list;
        }
    }

    /*
     * 获取所有用户参与某期号的参与记录总数
     */

    public function getAttendListCount($lotteryId) {
        $total = M("lottery_attend")->field('lucky_code')->where('lottery_id=' . $lotteryId)->count();
        return $total;
    }

    /*
     * 获取所有用户参与某期号的参与记录按分页查询
     */

    public function getAttendListById($lotteryId, $pageIndex, $pageSize) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $sql = "select u.nickname,u.face path,la.uid,la.attend_count,la.lucky_code,la.create_date_time create_time,la.attend_ip,la.ip_address,la.attend_device from " . $prefix . "lottery_attend la "
                . "left join " . $prefix . "member u on la.uid = u.uid "
                . "where la.lottery_id = ".$lotteryId." order by la.create_time desc limit $start,$end";
        $list = $this->query($sql);


        return $list;
    }

    /*
     * 获取某个商品最近开奖的记录总数
     */

    public function getPrizeLotteryCount($pid) {
        $prefix = C('DB_PREFIX');
        $sql = "select count(lp.lottery_id) c from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "lottery_attend la on lp.aid=la.id where lp.pid=$pid";
        $count = $this->query($sql);
        $total = 0;
        if ($count) {
            $total = $count[0]['c'];
        }
        return $total;
    }

    /*
     * 获取某个商品最近开奖或等待揭晓的记录按分页查询
     */

    public function getPrizeLottery($pid, $pageIndex, $pageSize) {
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $prefix = C('DB_PREFIX');
        //查询已经揭晓数据按分页查询
        $sql = "select d.title,lp.lottery_id,lp.uid,lp.pid,lp.lottery_code,lp.need_count,lp.hour_lottery,lp.total_time,lp.lottery_time,lp.last_attend_time,la.attend_count,la.create_date_time create_time,la.lucky_code,la.ip_address from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "lottery_attend la on lp.aid=la.id "
               . "join " . $prefix . "document d on lp.pid=d.id "
                . "where lp.pid=$pid order by lp.lottery_id desc limit $start,$end";
        $list = $this->query($sql);
//        if ($list) {
//
//        } else {
//            //如果已经揭晓没有数据，则查询等待揭晓数据按分页查询
//            /* $sql = "select lp.lottery_id,lp.uid,lp.lottery_code,lp.need_count,lp.hour_lottery,lp.total_time,lp.lottery_time,lp.last_attend_time from " . $prefix . "lottery_product lp "
//              . "where lp.pid=$pid and lp.last_attend_time<>0 and lp.lottery_code=0 order by lp.lottery_id desc limit $start,$end";
//              $list = $this->query($sql); */
//        }
        return $list[0];
    }

    /*
    * 获取某个商品最近开奖或等待揭晓的记录按分页查询  重复
    */

    public function getPrizeLotteryList($pid, $pageIndex, $pageSize) {
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $prefix = C('DB_PREFIX');
        //查询已经揭晓数据按分页查询
        $sql = "select d.title,lp.lottery_id,lp.uid,lp.pid,lp.lottery_code,lp.need_count,lp.hour_lottery,lp.total_time,lp.lottery_time,lp.last_attend_time,la.attend_count,la.create_date_time create_time,la.lucky_code,la.ip_address from " . $prefix . "lottery_product lp "
            . "join " . $prefix . "lottery_attend la on lp.aid=la.id "
            . "join " . $prefix . "document d on lp.pid=d.id "
            . "where lp.pid=$pid order by lp.lottery_id desc limit $start,$end";
        $list = $this->query($sql);

        return $list;
    }



    /*
     * 获取某一期的开奖记录或等待即将揭晓的记录
     */

    public function getPrizeLotteryById($lotteryId) {

     // $lotteryId=10000705;
         //$lotteryId=10000714;
        $prefix = C('DB_PREFIX');


        $sql="select  lp.aid,lp.lottery_id,lp.pid,lp.uid,lp.lottery_code,lp.need_count,lp.hour_lottery_id,lp.hour_lottery,lp.total_time,lp.lottery_time,lp.last_attend_time,lp.last_attend_date_time from ". $prefix ."lottery_product lp where lottery_id=".$lotteryId;
            $list = $this->query($sql);

        if(!$list[0]['aid'])
        {
            return $list[0];
        }
        else
        {
       $sql3="select la.create_date_time create_time,la.lucky_code,la.ip_address,la.attend_ip from " . $prefix . "lottery_attend la where id=".$list[0]['aid'] ;
            $list3 = $this->query($sql3);
            $list[0]['create_time']=$list3[0]['create_time'];
            $list[0]['lucky_code']=$list3[0]['lucky_code'];
            $list[0]['ip_address']=$list3[0]['ip_address'];
            $list[0]['attend_ip']=$list3[0]['attend_ip'];
        }

        $sql2="select sum(attend_count) as attend_count  from ". $prefix ."lottery_attend where uid=".$list[0]['uid']." and lottery_id=".$lotteryId;
        $list2 = $this->query($sql2);  // 查询出这一期中奖用户总共买了多少次

        $list[0]['attend_count']=$list2[0]['attend_count'];

        if ($list) {
            return $list[0];
        } else {
            //如果没查询到数据则查询正在等待即将揭晓的记录

            $sql = "select lp.lottery_id,lp.pid,lp.uid,lp.lottery_code,lp.need_count,lp.hour_lottery_id,lp.hour_lottery,lp.total_time,lp.lottery_time,lp.last_attend_time,lp.last_attend_date_time from " . $prefix . "lottery_product lp "
                    . "where lp.lottery_id=".$lotteryId." and lp.last_attend_time > 0";

            $list = $this->query($sql);
        }

        return $list[0];
    }
    /*
     * 获取某一期开奖结果
     */

    public function getOnelottery($lotteryId) {

        $prefix = C('DB_PREFIX');
        $sql = "select lp.total_time,lp.hour_lottery,lp.hour_lottery_id,lp.lottery_code,lp.last_attend_time,lp.lottery_id,lp.uid,lp.lottery_time from " . $prefix . "lottery_product lp "
          ."where lp.lottery_id = ".$lotteryId;
        $info = $this->query($sql);
        return $info;
    }



    /*
     * 获取中奖人的信息
     */

    public function getPrizeUser($uid) {
        $prefix = C('DB_PREFIX');
        $sql = "select u.last_login_ip,u.uid,u.nickname,u.face path from " . $prefix . "member u "
                . "where u.uid=$uid";
        $info = $this->query($sql);
        return $info[0];
    }

    /*
     * 获取某个商品最新进行中的一期
     */

    public function getNewProductLottery($pid) {
        $prefix = C('DB_PREFIX');
        $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.expectTime,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on lp.pid = d.id "
                . "left join " . $prefix . "picture p on d.cover_id=p.id "
                . "where d.display =1 and lp.pid=$pid and lp.last_attend_time=0 order by lp.lottery_id desc limit 1";
        $list = $this->query($sql);
        return $list;
    }

    /*
     * 获取某个商品的详情
     */

    public function getDetail($pid, $lotteryId) {
        $prefix = C('DB_PREFIX');
        $sql = "select lp.lottery_code,lp.last_attend_time,c.title category,lp.pid,lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,dp.pics,dp.content,dp.parameters from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on d.id=lp.pid "
                . "join " . $prefix . "document_product dp on lp.pid=dp.id "
                . "left join " . $prefix . "category c on d.category_id=c.id "
                . "where lp.pid=$pid and lp.lottery_id=$lotteryId";
        $product = $this->query($sql);
        $product[0]['product_status']=2;
            if($product[0]['last_attend_time']>0)
                 {
                $product[0]['product_status']=1;
                  }
                if($product[0]['lottery_code']>0)
               {
            $product[0]['product_status']=0;
               }


        $pics = array();
        if ($product) {
            if ($product[0]['pics']) {
                $pics = $this->getImagePath($product[0]['pics']);
            }
        }
        return array('product' => $product, 'pics' => $pics);
    }

    /*
 * 获取某个商品的图文详情
 */

    public function getProContent($pid, $lotteryId) {
        $prefix = C('DB_PREFIX');
        $sql = "select dp.content from " . $prefix . "lottery_product lp "
            . "join " . $prefix . "document d on d.id=lp.pid "
            . "join " . $prefix . "document_product dp on lp.pid=dp.id "
            . "where lp.pid=".$pid." and lp.lottery_id=".$lotteryId;
        $product = $this->query($sql);

        return array('content' => $product);
    }

    /*
     * 根据某个商品id获取最新一期某个商品的详情
     */

    public function getDetailById($pid) {
        $prefix = C('DB_PREFIX');
        $sql = "select c.title category,lp.pid,lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,dp.pics,dp.content,dp.parameters from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on d.id=lp.pid "
                . "join " . $prefix . "document_product dp on lp.pid=dp.id "
                . "left join " . $prefix . "category c on d.category_id=c.id "
                . "where lp.pid=$pid and lp.last_attend_time=0";
        $product = $this->query($sql);
        $pics = array();
        if ($product) {
            if ($product[0]['pics']) {
                $pics = $this->getImagePath($product[0]['pics']);
            }
        }
        return array('product' => $product, 'pics' => $pics);
    }

    /**
     * @param $pid
     * @return array
     */
    public function getDetailByIdForEvent($pid,$lottery_id=1) {
        $map['m.pid']=$pid;
        if($lottery_id){
            $map['m.lottery_id']=$lottery_id;
        }else{
            $map['m.lottery_time']=0;
        }
        $prefix = C('DB_PREFIX');
        $l_table  = $prefix.'lottery_event'; // 活动开奖表
        $r_table  = $prefix.'document';   //  商品表
        $r_table2 = $prefix.'document_product';  //商品详情表
        $info  = M() ->field('m.pid,m.lottery_id,m.lottery_code,m.total_time,m.uid,m.aid,m.lottery_time,m.attend_count,m.closing_time,m.lotterydown,m.hour_lottery,m.user_info,m.mod,a.price,a.title,u.pics,u.content,u.parameters')
            ->table($l_table.' m')
            ->join($r_table.' a ON m.pid=a.id')
            ->join($r_table2.' u ON m.pid=u.id')
            ->where($map)
            ->order('lottery_id desc')
            ->find();
        return $info;
    }
    /*
     * 获取某个商品的信息，不包含详细content
     */

    public function getInfo($pid, $lotteryId) {
        $prefix = C('DB_PREFIX');
        $sql = "select c.title category,lp.pid,lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,dp.pics,dp.parameters,lp.expectTime from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on d.id=lp.pid "
                . "join " . $prefix . "document_product dp on lp.pid=dp.id "
                . "left join " . $prefix . "category c on d.category_id=c.id "
                . "where lp.pid=$pid and lp.lottery_id=$lotteryId";

        $product = $this->query($sql);
        $pics = array();
        if ($product) {
            if ($product[0]['pics']) {
                $pics = $this->getImagePath($product[0]['pics']);
            }
        }

        return array('product' => $product, 'pics' => $pics);
    }


    /*
    * Wap端判断商品是否开奖，获取商品详细信息
    */

    public function getWapInfo($pid, $lotteryId) {
        $prefix = C('DB_PREFIX');
        $sql = "select lp.lottery_code,lp.last_attend_time,c.title category,lp.pid,lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,dp.pics,dp.parameters from " . $prefix . "lottery_product lp "
            . "join " . $prefix . "document d on d.id=lp.pid "
            . "join " . $prefix . "document_product dp on lp.pid=dp.id "
            . "left join " . $prefix . "category c on d.category_id=c.id "
            . "where lp.pid=$pid and lp.lottery_id=$lotteryId";

        $product = $this->query($sql);
        $pics = array();
        if ($product) {
            if ($product[0]['pics']) {
                $pics = $this->getImagePath($product[0]['pics']);
            }
        }
        //判断商品状态     0已开奖    1 开奖中    2  未开奖
        $product[0]['product_status']=2;
        if($product[0]['last_attend_time']>0)
        {
            $product[0]['product_status']=1;
        }
        if($product[0]['lottery_code']>0)
        {
            $product[0]['product_status']=0;
        }


        return array('product' => $product, 'pics' => $pics);
    }

    /*
     *  获取某一期的开奖结果
     */

    public function getResultById($lotteryId) {
        $prefix = C('DB_PREFIX');
        $sql = "select need_count,lottery_code,hour_lottery,total_time,last_attend_time from " . $prefix . "lottery_product where lottery_id=$lotteryId and lottery_code<>0";
        $list = $this->query($sql);
        return $list;
    }

    /*
     * 获取一元传奇
     */

    public function getOneLegend() {
        $prefix = C('DB_PREFIX');
        $sql = "select lp.lottery_id,lp.pid,lp.uid,lp.lottery_time,lp.need_count,sum(la.attend_count) attend_count,d.title,m.nickname,m.face path from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "lottery_attend la on lp.aid = la.id "
                . "join " . $prefix . "document d on lp.pid=d.id "
                . "join " . $prefix . "member m on lp.uid=m.uid "
                . "group by la.uid,la.lottery_id having sum(la.attend_count)=1 limit 25 ";
        $list = $this->query($sql);
        return $list;
    }

    /*
     * 获取限购专区
     */

    public function getLimitProduct() {
        $prefix = C('DB_PREFIX');
        $sql = "select lp.lottery_id,lp.pid,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,d.title,p.path from " . $prefix . "lottery_product lp "
                . "join " . $prefix . "document d on lp.pid = d.id "
                . "left join " . $prefix . "picture p on d.cover_id=p.id "
                . "where d.display = 1 and lp.lottery_code=0 and lp.attend_limit>0 order by lp.lottery_id desc limit 8";
        $list = $this->query($sql);
        return $list;
    }


    /*
     *
     */
    /*
  * 获取最新揭晓商品
  */

    public function newAnnounced($pageIndex,$pageSize,$endtime) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        if($endtime)
        {
            $where=' and lp.lottery_time <'.$endtime;
        }

        $sql = "select lp.lottery_id,lp.aid,lp.pid,lp.uid,lp.need_count,lp.lottery_code,lp.lottery_time,d.title,p.path from " . $prefix . "lottery_product lp "
            . "join " . $prefix . "document d on lp.pid = d.id "
            . "left join " . $prefix . "picture p on d.cover_id=p.id "
            . "where lp.lottery_code<>0 and d.display =1 order by lp.lottery_time desc limit $start,$end";

        $sql1 = "select count(lp.aid) as scount from " . $prefix . "lottery_product lp "
            . "join " . $prefix . "document d on lp.pid = d.id "
            . "left join " . $prefix . "picture p on d.cover_id=p.id "
            . "where lp.lottery_code<>0 and d.display =1";

        $count1 = $this->query($sql1);
        $list = $this->query($sql);
        $arrList = new \Org\Util\ArrayList();
        if ($list) {
            $count = count($list);
            for ($i = 0; $i < $count; $i++) {
                $sql = "select la.uid,la.attend_count,ip_address,m.nickname,m.face path from " . $prefix . "lottery_attend la "
                    . "left join " . $prefix . "member m on m.uid=la.uid "
                    . "where la.id =" . $list[$i]['aid'];
                $ulist = $this->query($sql);
// 查询出这一期中奖用户总共买了多少次  //
                $sql2="select sum(attend_count) as attend_count  from ". $prefix ."lottery_attend where uid=".$list[$i]['uid']." and lottery_id=".$list[$i]['lottery_id'];
               
                $list2 = $this->query($sql2);
// 查询出这一期中奖用户总共买了多少次  //


                if ($ulist) {

                    $arrList->add($ulist);
                    $list[$i]['nickname']=$ulist[0]['nickname'];
                    $list[$i]['uid']=$ulist[0]['uid'];
                    $list[$i]['nickname']=$ulist[0]['nickname'];
                    $list[$i]['upath']=$ulist[0]['path'];
                    $list[$i]['attend_count']=$list2[0]['attend_count'];

                }
            }
        }
        $list['count']=$count1[0]['scount'];
        return $list;
    }

}
