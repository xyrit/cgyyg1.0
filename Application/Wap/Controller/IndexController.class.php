<?php

namespace Wap\Controller;

use Home\Common\RunTimeUtil;
use \Home\Common\UtilApi;

header("Access-Control-Allow-Origin:*");

/*
 * 首页和商品相关接口数据
 * 更新时间 2016.2.16
 */

class IndexController extends HomeController {
    /*
     * 首页数据
     */

    public function homePage() {
        //开始访问记录的数据统计，不考虑并发的问题
        //获取访问者IP地址
//        $user_IP = getIP();
//        $visit_data['ip'] = ip2long($user_IP); //ip地址转为整形
//        //判断用户是否登录，获取IP地址
//        $user_token = I("post.user_token");
//        if (!empty($user_token)) {
//           $uid = floatval(S($user_token));//dump($user_token);dump(S($user_token));exit;
//           if ($uid > 0) {//用户已经登录
//                $visit_data['uid'] = $uid;
//           }
//        }
//        $visit_data['visit_time'] = getCurrentTime();
//        $visit_data['info'] = date('YmdH', time());
//        $time = getTimeInfo();
//        $visit = S('visit_' . $time);
//        $visit[] = $visit_data;
//        S('visit_' . $time, $visit);
        //访问次数统计结束
        $home = S('Waphome');
        //最新揭晓
        $is_lottery = 0;
        $resultProduct = D('Home/lotteryProduct')->getNewProduct();
        if($resultProduct){
            foreach ($resultProduct as $key => &$value) {
                $value['waitTime']=$value['expecttime']-NOW_TIME;
            }
        }
        if(!$resultProduct)
        {
            $is_lottery = 1;
            $resultProduct = D('Home/lotteryProduct')->getAlready_announced();
        }
//        echo '<pre>';
//        print_r($resultProduct);exit;
        /*$nextWaitTime = RunTimeUtil::runNextTime();
        $hourTime = RunTimeUtil::cqsscTimestamp();
        $waitTime = RunTimeUtil::runTime();*/
        if ($home) {
          /*  $home['nextWaitTime']= $nextWaitTime;
            $home['cqsscTimestamp']= $hourTime;
            $home['waitTime']= $waitTime;*/
            $home['waitProduct']= $resultProduct;
            echo json_encode($home); //直接显示首页缓存的数据
        } else {
            //首页幻灯片广告
            $slide = D('Home/slide')->getList(2);
            //热门推荐
            //$hotProduct = D('Home/lotteryProduct')->getHotList(20);
            $hotProduct = D('Home/lotteryProduct')->WapgetWorthist(20);

            /* custom s damon */

            $user_token = I("post.user_token");
            if (!empty($user_token)) {
                $uid = floatval(S($user_token));
                if ($uid > 0) {
                    $shopcartNum = M("shopcart")->field('id')->where('uid=' . $uid)->count(); //获取购物车数量
                } else {
                    $shopcartNum = 0;
                }
            } else {
                $shopcartNum = 0;
            }
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'pic_host' => C('PICTURE'),
                'is_lottery' => $is_lottery,
                'slide' => $slide,
                /*'waitTime'=>$waitTime,
                'hourTime'=>$hourTime,
                'nextWaitTime'=>$nextWaitTime,*/
                //'ad' => $ad,
                'waitProduct'=>$resultProduct,
                'hotProduct' => $hotProduct,
                'shopcartNum' => $shopcartNum
            );
            echo json_encode($arr);
            S(array('expire' => 1)); //首页缓存1分钟
            S('Waphome', $arr);
        }
    }

    /*
     * wap端首页商品排序
     */

    public function homegoods() {

        //attend_count 热度  create_time 新品  need_count 价格  attend_ratio 最快
       // $array = array('attend_count','create_time','need_count','attend_ratio');
        $array = array('hot','new','price','fast');          // 热度  最新  价格  最快
        $array2 = array('desc','asc');
        $type=I('post.type','hot');  //字段名
        $sort = I('post.h_sort','desc');  //排序方式
        $pageIndex = I('post.pageIndex','0');  //页数
        $pageSize = I('post.pageSize','10');  //数量

        //转化成字段
        switch($type)
        {
            case 'hot':
                $protype='worth_id';
                break;
            case 'new':
                $protype='create_time';
                break;
            case 'price':
                $protype='need_count';
                break;
            case 'fast':
                $protype='attend_ratio';
                break;
            default:
                $protype='attend_count';
                break;
        }
        if(!in_array($type,$array))
        {
            $sort='asc';
        }
        if(!in_array($sort,$array2))
        {
            $sort='desc';
        }

        $oneLegend = D('Home/lotteryProduct')->getlistgoods($protype,$sort,$pageIndex,$pageSize);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'category' => $oneLegend
        );

        echo json_encode($arr);

    }

    /*
     * 获取商品分类
     */

    public function category() {
        //商品分类
        $category = D('Home/category')->getCategory();
        array_unshift($category, array('id' => 0, 'name' => 'qb', 'pid' => 0, 'title' => '全部商品', 'child' => array()));
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'category' => $category
        );
        echo json_encode($arr);
    }

    /*
     * 获取所有公告标题
     */

    public function noticeList() {
        $list = D('document')->getNoticeList();
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'list' => $list
        );
        echo json_encode($arr);
    }

    /*
     * 获取公告详情
     */

    public function noticeDetail() {
        $id = I('post.id');
        if ($id != null) {
            $detail = D('document')->getNoticeDetail($id);
            if ($detail) {
                $arr = array(
                    'code' => 200,
                    'info' => '成功',
                    'detail' => $detail);
                echo json_encode($arr);
            } else {
                UtilApi::getInfo('500', '获取公告详情失败');
            }
        } else {
            UtilApi::getInfo('500', '公告id不能为空');
        }
    }

    /*
     * 获取文章详情
     */
    public function docDetail() {
        $id = I('post.id');
        if ($id != null) {
            $detail = D('document')->getdetail($id);
            if ($detail) {
                $arr = array(
                    'code' => 200,
                    'info' => '成功',
                    'detail' => $detail);
                echo json_encode($arr);
            } else {
                UtilApi::getInfo('500', '获取文章详情失败');
            }
        } else {
            UtilApi::getInfo('500', '文章id不能为空');
        }
    }

    /*
     * 根据分类获取对应的商品数据
     */

    public function categoryById() {
//        $id = I('post.id');
//        $pageSize = I('post.pageSize');
//        $pageIndex = I('post.pageIndex');
        $id = I('id',0);
        $pageSize = I('pageSize',10);
        $pageIndex = I('pageIndex',0);
        $type = I('post.type','price');         //类型
        $sort = I('post.h_sort','desc');           //排序方式
        $array = array('hot','new','price','fast');          // 热度  最新  价格  最快
        $array2 = array('desc','asc');
        //转化成字段
        switch($type)
        {
            case 'hot':
                $protype='lottery_pid';
                break;
            case 'new':
                $protype='create_time';
                break;
            case 'price':
                $protype='need_count';
                break;
            case 'fast':
                $protype='attend_ratio';
                break;
            default:
                $protype='attend_count';
                break;
        }
        if(!in_array($type,$array))
        {
            $sort='hot';
        }
        if(!in_array($sort,$array2))
        {
            $sort='desc';
        }

            if ($pageSize != null) {
                if ($pageSize <= 0) {
                    UtilApi::getInfo('500', 'pageSize必须大于0');
                    return;
                }
            } else {
                UtilApi::getInfo('500', 'pageSize不能为空');
                return;
            }

            //商品总数量
            $total = D('Home/lotteryProduct')->getByCategoryCount($id, $protype);
            $pageCount = floor($total / $pageSize);
            if ($total % $pageSize > 0) {
                $pageCount = $pageCount + 1;
            }
            $list = D('Home/lotteryProduct')->getByWapCategory($id, $pageIndex, $pageSize, $protype, $sort);

            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'total' => $total,
                'pageCount' => $pageCount,
                'list' => $list);
            echo json_encode($arr);

    }

    /*
     * wap根据名称搜索获取商品数据
     */

    public function searchByName() {
        $name = I('post.name');
        $pageSize = I('post.pageSize');
        $pageIndex = I('post.pageIndex');
        if ($name != null) {
            if ($pageSize != null) {
                if ($pageSize <= 0) {
                    UtilApi::getInfo('500', 'pageSize必须大于0');
                    return;
                }
            } else {
                UtilApi::getInfo('500', 'pageSize不能为空');
                return;
            }
            if ($pageIndex == null) {
                UtilApi::getInfo('500', 'pageIndex不能为空');
                return;
            }
            if ($pageIndex < 0) {
                UtilApi::getInfo('500', 'pageIndex不能小于0');
                return;
            }
            $total = D('Home/lotteryProduct')->getByNameCount($name);
            $list = D('Home/lotteryProduct')->getByName($name, $pageIndex, $pageSize);
            $pageCount = floor($total / $pageSize);
            if ($total % $pageSize > 0) {
                $pageCount = $pageCount + 1;
            }
            $category = D('Home/category')->getCategory(); //获取商品分类
            $allTotal = D('Home/lotteryProduct')->getByAllCount(); //获取所有商品分类下正在进行的商品数量
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'allTotal' => $allTotal,
                'category' => $category,
                'sTotal' => $total,
                'pageCount' => $pageCount,
                'list' => $list);
            echo json_encode($arr);
        } else {
            UtilApi::getInfo('500', '商品搜索名称不能为空');
        }
    }

    /*
     * 即将揭晓专区数据
     */

    public function lotterySoon() {
        //即将揭晓
        $soonProduct = D('Home/lotteryProduct')->getSoonProduct();
        //等待揭晓
        $waitProduct = D('Home/lotteryProduct')->getWaitProduct();
        //已经揭晓
        $resultProduct = D('Home/lotteryProduct')->getResultProduct();
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'nextWaitTime' => RunTimeUtil::runNextTime(),
            'hourTime' => RunTimeUtil::cqsscTimestamp(),
            'waitTime' => RunTimeUtil::runTime(),
            'soonProduct' => $soonProduct,
            'waitProduct' => $waitProduct,
            'resultProduct' => $resultProduct);

        echo json_encode($arr);
    }

    /*
     * 获取商品详情
     */

    public function productWapByDetail() {
        $uid = I('post.uid');
        $pid = I('post.pid');  //,380
        $lotteryId = I('post.lotteryId');  //,1000000120   10000333
        $a_pageSize = I('post.a_pageSize',2);  //,1
        $a_pageIndex = I('post.a_pageIndex',1);  //,2
        if (empty($pid)) {
            UtilApi::getInfo('500', '商品id不能为空');
            return;
        }
        if (empty($lotteryId)) {
            $detail = D('Home/lotteryProduct')->getDetailById($pid);
            $lotteryId = $detail['product'][0]['lottery_id'];
        }
        if ($a_pageSize == null) {
            UtilApi::getInfo('500', 'a_pageSize不能为空');
            return;
        }
        if ($a_pageSize <= 0) {
            UtilApi::getInfo('500', 'a_pageSize必须大于0');
            return;
        }
        if ($a_pageIndex == null) {
            UtilApi::getInfo('500', 'a_pageIndex不能为空');
            return;
        }

        //个人参与幸运码
        $luckyCode = D('Home/lotteryProduct')->getAttendById($lotteryId, $uid);

        //参与人员总数
        $a_total = D('Home/lotteryProduct')->getAttendListCount($lotteryId);
        $a_pageCount = floor($a_total / $a_pageSize);
        if ($a_total % $a_pageSize > 0) {
            $a_pageCount = $a_pageCount + 1;
        }
        //参与记录
        $attendList = D('Home/lotteryProduct')->getAttendListById($lotteryId,$a_pageIndex,$a_pageSize);

        //商品开奖期号总数
        $p_total = D('Home/lotteryProduct')->getPrizeLotteryCount($pid);

        //商品信息
        $p_infos = D('Home/lotteryProduct')->getWapInfo($pid, $lotteryId);
        $waitTime=0;
        if($p_infos[0]['expecttime']){
           $waitTime=$p_infos[0]['expecttime']-NOW_TIME;
        }

        //获取商品等待开奖的记录
        $prizeList = D('Home/lotteryProduct')->getPrizeLotteryById($lotteryId);

        if($prizeList)
        {
        $prizeUser = D('Home/lotteryProduct')->getPrizeUser($prizeList['uid']);

            if(!$prizeUser)
            {
                $prizeUser=array();
            }
        }
        else
        {
          $prizeUser=array();

        }
        $prize = array('prizeList' => $prizeList, 'prizeUser' =>$prizeUser);

        //开奖计算结果
//        $lastAttendTime = $prize['prizeList'][0]['last_attend_time'];
//        if (empty($lastAttendTime)) {
//            $countList = array();
//        } else {
//            $countList = D('Home/lotteryAttend')->getLastAttendTimeList($lastAttendTime);
//        }

        //获取最新一期正在进行的商品
        $newLottery = D('Home/lotteryProduct')->getNewProductLottery($pid);
        $lottery_code = $prize['prizeList'][0]['lottery_code']; //判断是否已开奖
       
//        if ($lottery_code > 0) {
//            $waitTime = 0; //如果已开奖则返回等待时间为0
//        } else if ($hour_time > $lastAttendTime) { //如果开奖时间大于最后参与时间，则在这一期的时时彩开奖结果揭晓
//            $waitTime = RunTimeUtil::runTime();
//        } else { //如果开奖时间小于最后参与时间，则等下一期时时彩开奖结果后揭晓
//            $waitTime = RunTimeUtil::runNextTime();
//        }
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'srcCode' => C('SRC_CODE'),
            'waitTime'=>$waitTime,
            'a_pageCount' => $a_pageCount,
         //   'p_pageCount' => 0, //暂时不使用
            'detail' => $p_infos,
            'luckyCode' => $luckyCode,
            'prize' => $prize,
           // 'countList' => $countList,
            'attendList'=>$attendList,
            'newLottery' => $newLottery
        );
        echo json_encode($arr);
    }

    /*
     * wap商品详情
     */
    public function productInfo() {
        //该商品详情
        $pid = I('pid');
        $lotteryId = I('lotteryId');
        $detail = D('Home/lotteryProduct')->getProContent($pid, $lotteryId);

        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'detail' => $detail,
        );
        echo json_encode($arr);
    }

    /*
     * wap商品往期开奖记录
     */

    public function productPast() {
        //商品开奖期号记录
        $pid = I('post.pid',382);
        $p_pageSize = I('post.p_pageSize',10);
        $p_pageIndex = I('post.p_pageIndex',0);

        $prizeList = D('Home/lotteryProduct')->getPrizeLotteryList($pid, $p_pageIndex, $p_pageSize);

        //正在开奖的期号
       $tprizeList=M('lottery_product')->field('lottery_id')->where('pid ='.$pid.' and lottery_code=0 and last_attend_time>0')->select();
        if(!$tprizeList)
        {
            $tprizeList=array();
        }
        foreach($prizeList as $key=>$row)
        {
            $prizeUser[] = D('Home/lotteryProduct')->getPrizeUser($row['uid']);
            $prizeList[$key]['lottery_time']=date("Y-m-d H:i:s",$row['lottery_time']);
        }
        $prize = array('prizeList' => $prizeList, 'prizeUser' => $prizeUser);
//        echo '<pre>';
//        print_r($tprizeList);exit;
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'prize' => $prize,
            'tprizeList'=>$tprizeList
        );
        echo json_encode($arr);
    }

    /*
     * wap商品晒单
     */

    public function productshare() {
        //晒单
        $pid = I('post.pid');
        $disProduct = D('documentProduct')->displayRecord($pid);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'disList' => $disProduct,
        );
        echo json_encode($arr);
    }

    /*
     * 获取根据商品id获取未揭晓商品详情
     */

    public function pDetailById() {
        $uid = I('post.uid');
        $pid = I('post.pid');
        $a_pageSize = I('post.a_pageSize');
        $a_pageIndex = I('post.a_pageIndex');
        $p_pageSize = I('post.p_pageSize');
        $p_pageIndex = I('post.p_pageIndex');
        if (empty($pid)) {
            UtilApi::getInfo('500', '商品id不能为空');
            return;
        }
        if ($a_pageSize == null) {
            UtilApi::getInfo('500', 'a_pageSize不能为空');
            return;
        }
        if ($a_pageSize <= 0) {
            UtilApi::getInfo('500', 'a_pageSize必须大于0');
            return;
        }
        if ($a_pageIndex == null) {
            UtilApi::getInfo('500', 'a_pageIndex不能为空');
            return;
        }
        if ($p_pageSize == null) {
            UtilApi::getInfo('500', 'p_pageSize不能为空');
            return;
        }
        if ($p_pageSize <= 0) {
            UtilApi::getInfo('500', 'p_pageSize必须大于0');
            return;
        }
        if ($p_pageIndex == null) {
            UtilApi::getInfo('500', 'p_pageIndex不能为空');
            return;
        }
        if ($p_pageIndex < 0) {
            UtilApi::getInfo('500', 'p_pageIndex不能小于0');
            return;
        }
        //该商品详情
        $detail = D('lotteryProduct')->getDetailById($pid);
        // var_dump($detail);
        // exit;
        $lotteryId = $detail['product'][0]['lottery_id'];
        // var_dump($detail['product'][0]['lottery_id']);
        //  exit;
        //个人参与幸运码
        $luckyCode = D('lotteryProduct')->getAttendById($lotteryId, $uid);
        //参与人员总数
        $a_total = D('lotteryProduct')->getAttendListCount($lotteryId);
        $a_pageCount = floor($a_total / $a_pageSize);
        if ($a_total % $a_pageSize > 0) {
            $a_pageCount = $a_pageCount + 1;
        }
        //所有参与记录
        $attendList = D('lotteryProduct')->getAttendListById($lotteryId, $a_pageIndex, $a_pageSize);
        //晒单
        $disProduct = D('documentProduct')->displayRecord($pid);

        //商品开奖期号总数
        $p_total = D('lotteryProduct')->getPrizeLotteryCount($pid);
        $p_pageCount = floor($p_total / $p_pageSize);
        if ($p_total % $p_pageSize > 0) {
            $p_pageCount = $p_pageCount + 1;
        }
        //商品开奖期号记录
        $prizeList = D('lotteryProduct')->getPrizeLottery($pid, $p_pageIndex, $p_pageSize);
        if ($prizeList) {
            $prizeUser = D('lotteryProduct')->getPrizeUser($prizeList[0]['uid']);
            $prize = array('prizeList' => $prizeList, 'prizeUser' => $prizeUser);
        } else {
            $prize = array('prizeList' => $prizeList, 'prizeUser' => array());
        }
        //获取中奖者信息
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'a_pageCount' => $a_pageCount,
            'p_pageCount' => $p_pageCount,
            'detail' => $detail,
            'luckyCode' => $luckyCode,
            'attendList' => $attendList,
            'disList' => $disProduct,
            'prize' => $prize
        );
        echo json_encode($arr);
    }

    /*
     * 获取已经揭晓商品详情页面
     */

    public function productPrizeByDetail() {
        $uid = I('post.uid');
        $pid = I('post.pid');
        $lotteryId = I('post.lotteryId');
        $a_pageSize = I('post.a_pageSize');
        $a_pageIndex = I('post.a_pageIndex');
        $p_pageSize = I('post.p_pageSize');
        $p_pageIndex = I('post.p_pageIndex');

        if ($pid == null) {
            UtilApi::getInfo('500', '商品id不能为空');
            return;
        }
        if ($lotteryId == null) {
            UtilApi::getInfo('500', '期号id不能为空');
            return;
        }
        if ($a_pageSize == null) {
            UtilApi::getInfo('500', 'a_pageSize不能为空');
            return;
        }
        if ($a_pageSize <= 0) {
            UtilApi::getInfo('500', 'a_pageSize必须大于0');
            return;
        }
        if ($a_pageIndex == null) {
            UtilApi::getInfo('500', 'a_pageIndex不能为空');
            return;
        }
        if ($a_pageIndex < 0) {
            UtilApi::getInfo('500', 'a_pageIndex不能小于0');
            return;
        }
        if ($p_pageSize == null) {
            UtilApi::getInfo('500', 'p_pageSize不能为空');
            return;
        }
        if ($p_pageSize <= 0) {
            UtilApi::getInfo('500', 'p_pageSize必须大于0');
            return;
        }
        if ($p_pageIndex == null) {
            UtilApi::getInfo('500', 'p_pageIndex不能为空');
            return;
        }
        if ($p_pageIndex < 0) {
            UtilApi::getInfo('500', 'p_pageIndex不能小于0');
            return;
        }
        //商品详情
        $detail = D('lotteryProduct')->getInfo($pid, $lotteryId);
        //个人参与幸运码
        $luckyCode = D('lotteryProduct')->getAttendById($lotteryId, $uid);
        //参与人员总数
        $a_total = D('lotteryProduct')->getAttendListCount($lotteryId);
        $a_pageCount = floor($a_total / $a_pageSize);
        if ($a_total % $a_pageSize > 0) {
            $a_pageCount = $a_pageCount + 1;
        }
        //所有参与记录
        $attendList = D('lotteryProduct')->getAttendListById($lotteryId, $a_pageIndex, $a_pageSize);
        //获取商品开奖和中奖人信息
        $prizeList = D('lotteryProduct')->getPrizeLotteryById($lotteryId);
        if ($prizeList) {
            $prizeUser = D('lotteryProduct')->getPrizeUser($prizeList['uid']);
            $prize = array('prizeList' => $prizeList, 'prizeUser' => $prizeUser);
        } else {
            $prize = array('prizeList' => $prizeList, 'prizeUser' => array());
        }

        //开奖计算结果
        $lastAttendTime = $prize['prizeList'][0]['last_attend_time'];
        if (empty($lastAttendTime)) {
            $countList = array();
        } else {
            $countList = D('lotteryAttend')->getLastAttendTimeList($lastAttendTime);
        }
        //获取最新一期正在进行的商品
        $newLottery = D('lotteryProduct')->getNewProductLottery($pid);
        //晒单列表
        $disProduct = D('documentProduct')->displayRecord($pid);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'srcCode' => C('SRC_CODE'),
            'a_pageCount' => $a_pageCount,
            'p_pageCount' => 0,
            'detail' => $detail,
            'luckyCode' => $luckyCode,
            'attendList' => $attendList,
            'prize' => $prize,
            'countList' => $countList,
            'disList' => $disProduct,
            'newLottery' => $newLottery,
        );
        echo json_encode($arr);
    }

    /*
     * 获取等待揭晓商品详情页面
     */

    public function productWaitByDetail() {
        $uid = I('post.uid');
        $pid = I('post.pid');
        $lotteryId = I('post.lotteryId');
        $a_pageSize = I('post.a_pageSize');
        $a_pageIndex = I('post.a_pageIndex');
        $p_pageSize = I('post.p_pageSize');
        $p_pageIndex = I('post.p_pageIndex');
        if ($pid == null) {
            UtilApi::getInfo('500', '商品id不能为空');
            return;
        }
        if ($lotteryId == null) {
            UtilApi::getInfo('500', '期号id不能为空');
            return;
        }
        if ($a_pageSize == null) {
            UtilApi::getInfo('500', 'a_pageSize不能为空');
            return;
        }
        if ($a_pageSize <= 0) {
            UtilApi::getInfo('500', 'a_pageSize必须大于0');
            return;
        }
        if ($a_pageIndex == null) {
            UtilApi::getInfo('500', 'a_pageIndex不能为空');
            return;
        }
        if ($a_pageIndex < 0) {
            UtilApi::getInfo('500', 'a_pageIndex不能小于0');
            return;
        }
        if ($p_pageSize == null) {
            UtilApi::getInfo('500', 'p_pageSize不能为空');
            return;
        }
        if ($p_pageSize <= 0) {
            UtilApi::getInfo('500', 'p_pageSize必须大于0');
            return;
        }
        if ($p_pageIndex == null) {
            UtilApi::getInfo('500', 'p_pageIndex不能为空');
            return;
        }
        if ($p_pageIndex < 0) {
            UtilApi::getInfo('500', 'p_pageIndex不能小于0');
            return;
        }
        //商品详情
        $detail = D('lotteryProduct')->getInfo($pid, $lotteryId);
        //某个商品个人参与幸运码
        $luckyCode = D('lotteryProduct')->getAttendById($lotteryId, $uid);
        //某个商品参与人员总数
        $a_total = D('lotteryProduct')->getAttendListCount($lotteryId);
        $a_pageCount = floor($a_total / $a_pageSize);
        if ($a_total % $a_pageSize > 0) {
            $a_pageCount = $a_pageCount + 1;
        }
        //某个商品所有参与记录
        $attendList = D('lotteryProduct')->getAttendListById($lotteryId, $a_pageIndex, $a_pageSize);

        //获取商品等待开奖的记录
        $prizeList = D('lotteryProduct')->getPrizeLotteryById($lotteryId);
        $prize = array('prizeList' => $prizeList, 'prizeUser' => array());

        //开奖计算结果
        $lastAttendTime = $prize['prizeList'][0]['last_attend_time'];
        if (empty($lastAttendTime)) {
            $countList = array();
        } else {
            $countList = D('lotteryAttend')->getLastAttendTimeList($lastAttendTime);
        }

        //获取最新一期正在进行的商品
        $newLottery = D('lotteryProduct')->getNewProductLottery($pid);
        //晒单列表
        $disProduct = D('documentProduct')->displayRecord($pid);
        $lottery_code = $prize['prizeList'][0]['lottery_code']; //判断是否已开奖
        $hour_time = D('lotteryAttend')->cqsscTimestamp();
        if ($lottery_code > 0) {
            $waitTime = 0; //如果已开奖则返回等待时间为0
        } else if ($hour_time > $lastAttendTime) { //如果开奖时间大于最后参与时间，则在这一期的时时彩开奖结果揭晓
            $waitTime = D('lotteryAttend')->runTime();
        } else { //如果开奖时间小于最后参与时间，则等下一期时时彩开奖结果后揭晓
            $waitTime = D('lotteryAttend')->runNextTime();
        }
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'srcCode' => C('SRC_CODE'),
            'hourTime' => $hour_time,
            'waitTime' => $waitTime,
            'a_pageCount' => $a_pageCount,
            'p_pageCount' => 0, //暂时不使用
            'detail' => $detail,
            'luckyCode' => $luckyCode,
            'attendList' => $attendList,
            'prize' => $prize,
            'countList' => $countList,
            'disList' => $disProduct,
            'newLottery' => $newLottery
        );
        echo json_encode($arr);
    }

    /*
     * 根据期号查询计算结果，所有参与记录
     */

    public function productPrizeById() {
        $lotteryId = I('post.lotteryId');
        $pageSize = I('post.pageSize');
        $pageIndex = I('post.pageIndex');
        $lastAttendTime = I('post.lastAttendTime');
        if ($lotteryId == null) {
            UtilApi::getInfo('500', '期号id不能为空');
            return;
        }
        if ($pageSize == null) {
            UtilApi::getInfo('500', 'pageSize不能为空');
            return;
        }
        if ($pageSize <= 0) {
            UtilApi::getInfo('500', 'pageSize必须大于0');
            return;
        }
        if ($pageIndex == null) {
            UtilApi::getInfo('500', 'pageIndex不能为空');
            return;
        }
        if ($pageIndex < 0) {
            UtilApi::getInfo('500', 'pageIndex不能小于0');
            return;
        }
        if ($lastAttendTime == null) {
            UtilApi::getInfo('500', '最后参与时间不能为空');
            return;
        }
        //某个商品参与人员总数
        $total = D('lotteryProduct')->getAttendListCount($lotteryId);
        $pageCount = floor($total / $pageSize);
        if ($total % $pageSize > 0) {
            $pageCount = $pageCount + 1;
        }
        //某个商品所有参与记录
        $attendList = D('lotteryProduct')->getAttendListById($lotteryId, $pageIndex, $pageSize);
        //计算结果
        $countList = D('lotteryAttend')->getLastAttendTimeList($lastAttendTime);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'pageCount' => $pageCount,
            'attendList' => $attendList,
            'countList' => $countList
        );
        echo json_encode($arr);
    }

    /*
     * 根据uid获取中奖人信息
     */

    public function prizeUserById() {
        $uid = I('post.uid');
        if ($uid == null) {
            UtilApi::getInfo('500', '用户id不能为空');
            return;
        }
        if ($uid <= 0) {
            UtilApi::getInfo('500', '无效的用户id');
            return;
        }
        $user = D('lotteryProduct')->getPrizeUser($uid);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'user' => $user
        );
        echo json_encode($arr);
    }

    /*
     * 根据期号获取开奖信息和用户信息
     */

    public function prizeLotteryById() {
        $lotteryId = I('post.lotteryId');
        if ($lotteryId == null) {
            UtilApi::getInfo('500', '期号id不能为空');
            return;
        }
        if ($lotteryId <= 0) {
            UtilApi::getInfo('500', '无效的期号id');
            return;
        }
        $prizeList = D('lotteryProduct')->getPrizeLotteryById($lotteryId);
        if ($prizeList) {
            $prizeUser = D('lotteryProduct')->getPrizeUser($prizeList[0]['uid']);
            $prize = array('prizeList' => $prizeList, 'prizeUser' => $prizeUser);
        } else {
            $prize = array('prizeList' => $prizeList, 'prizeUser' => array());
        }
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'prize' => $prize
        );
        echo json_encode($arr);
    }

    /*
     * 商品参与记录分页查询
     */

    public function attendListById() {
        $lotteryId = I('post.lotteryId');
        $pageSize = I('post.pageSize');
        $pageIndex = I('post.pageIndex');
        if ($lotteryId == null) {
            UtilApi::getInfo('500', '期号id不能为空');
            return;
        }
        if ($pageSize == null) {
            UtilApi::getInfo('500', 'pageSize不能为空');
            return;
        }
        if ($pageSize <= 0) {
            UtilApi::getInfo('500', 'pageSize必须大于0');
            return;
        }
        if ($pageIndex == null) {
            UtilApi::getInfo('500', 'pageIndex不能为空');
            return;
        }
        if ($pageIndex < 0) {
            UtilApi::getInfo('500', 'pageIndex不能小于0');
            return;
        }
        //参与记录
        $attendList = D('Home/lotteryProduct')->getAttendListById($lotteryId, $pageIndex, $pageSize);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'attendList' => $attendList
        );
        echo json_encode($arr);
    }

    /*
     * 限购专区
     */

    public function limitProduct() {
        $list = D('lotteryProduct')->getLimitProduct();
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'list' => $list
        );
        echo json_encode($arr);
    }


    /*
     * 根据商品id查询开奖期号分页查询
     */

    public function prizeLottery() {
        $pid = I('post.pid');
        $pageSize = I('post.pageSize');
        $pageIndex = I('post.pageIndex');
        if (empty($pid)) {
            UtilApi::getInfo('500', '商品id不能为空');
            return;
        }
        if ($pageSize == null) {
            UtilApi::getInfo('500', 'pageSize不能为空');
            return;
        }
        if ($pageSize <= 0) {
            UtilApi::getInfo('500', 'pageSize必须大于0');
            return;
        }
        if ($pageIndex == null) {
            UtilApi::getInfo('500', 'pageIndex不能为空');
            return;
        }
        if ($pageIndex < 0) {
            UtilApi::getInfo('500', 'pageIndex不能小于0');
            return;
        }
        //商品开奖期号记录
        $prizeList = D('lotteryProduct')->getPrizeLottery($pid, $pageIndex, $pageSize);
        //获取中奖者信息
        if ($prizeList) {
            $prizeUser = D('lotteryProduct')->getPrizeUser($prizeList[0]['uid']);
            $prize = array('prizeList' => $prizeList, 'prizeUser' => $prizeUser);
        } else {
            $prize = array('prizeList' => $prizeList, 'prizeUser' => array());
        }
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'prize' => $prize
        );
        echo json_encode($arr);
    }

    /*
     * wap 晒单分享
     */
    public function orderShare(){

        $pageSize = I('post.pageSize',0);
        $pageIndex = I('post.pageIndex',10);
        $orderlist = D('Home/documentProduct')->ordershare($pageIndex,$pageSize);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'data' => $orderlist,
            'pageCount'=>$orderlist['page']
        );

        echo json_encode($arr);
    }

    /*
   * wap 晒单详情
   */
    public function orderShareInfo(){

        $did= I('Did',109);
        $orderinfo = D('Home/documentProduct')->ordershareInfo($did);


        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'info' => $orderinfo
        );

        echo json_encode($arr);
    }

    /*
     * wap 最新揭晓
     */

    public function newAnnounced(){
        $pageIndex = I('post.pageIndex','12');  //页数
        $pageSize = I('post.pageSize','10');  //数量
        $endtime = I('post.endtime',1450836403);  //最后数据时间
        $soonProductlist = D('Home/lotteryProduct')->newAnnounced($pageIndex,$pageSize,$endtime);

        $page=ceil($soonProductlist['count']/$pageSize);
        unset($soonProductlist['count']);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'list' => $soonProductlist,
            'pageCount' => $page
        );
        echo json_encode($arr);
    }

    /*
     * 获取单个开奖结果
     */
    public function Onelottery(){

        $lotteryId = I('post.lotteryId',1000000120);

        $lotteryInfo = D('Home/lotteryProduct')->getOnelottery($lotteryId);

            $lottery_uid=$lotteryInfo[0]['uid'];

            $prizeUser = D('Home/lotteryProduct')->getPrizeUser($lottery_uid);

        $prizeCount = M('lottery_attend')->where('lottery_id='.$lotteryId.' and uid='.$lottery_uid)->sum('attend_count');
        $attend_ip = M('lottery_attend')->where('lottery_id='.$lotteryId.' and uid='.$lottery_uid)->getField('attend_ip');

        if(!$prizeCount)
                {
                    $prizeUser=array();
                }
        else
        {
            $prizeUser['last_attend_time']=$lotteryInfo[0]['last_attend_time'];
            $prizeUser['prizeCount']=$prizeCount;
            $prizeUser['lottery_time']=$lotteryInfo[0]['lottery_time'];
            $prizeUser['attend_ip']=$attend_ip;
            $prizeUser['lottery_code']=$lotteryInfo[0]['lottery_code'];
            $prizeUser['hour_lottery_id']=$lotteryInfo[0]['hour_lottery_id'];
            $prizeUser['hour_lottery']=$lotteryInfo[0]['hour_lottery'];
            $prizeUser['total_time']=$lotteryInfo[0]['total_time'];
        }

        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'userInfo' => $prizeUser
        );
        echo json_encode($arr);


    }

    /*
    *  wap 获取商品晒单分享
  */
    public function proOrderShare(){

        $pid = I('post.pid',385);
        $pageIndex = I('post.pageIndex','0');  //页数
        $pageSize = I('post.pageSize','1');  //数量


        $orderlist = D('Home/documentProduct')->proOrderShare($pid,$pageIndex,$pageSize);

        //计算所有页数


        $pageCount = ceil(intval($orderlist['sharecount']['sharecount']) / intval($pageSize));

        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'userInfo' => $orderlist,
            'pageCount'=>$pageCount,
        );
        echo json_encode($arr);


    }

    /*
     * wap 查看计算详情
     */

    public function wapCountList(){

        $lastAttendTime =  I('post.lastAttendTime');
        $countList = D('Home/lotteryAttend')->getLastAttendTimeList($lastAttendTime);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'srcCode' => C('SRC_CODE'),
            'countList' => $countList,
            'data'=> I('post.data'),
        );
        echo json_encode($arr);
    }
}

