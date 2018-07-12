<?php

namespace Home\Controller;

use Home\Common\UtilApi;
use Home\Common\TimeUtil;
use Home\Common\RunTimeUtil;

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
        $user_IP = getIP();
        $visit_data['ip'] = ip2long($user_IP); //ip地址转为整形
        //判断用户是否登录，获取IP地址
        $user_token = I("post.user_token");
        if (!empty($user_token)) {
            $uid = floatval(S($user_token));
            if ($uid > 0) {//用户已经登录
                $visit_data['uid'] = $uid;
            }
        }
        $visit_data['visit_time'] = getCurrentTime();
        $visit_data['info'] = date('YmdH', time());
        $time = getTimeInfo();
        $visit = S('visit_' . $time);
        $visit[] = $visit_data;
        S('visit_' . $time, $visit);
        //访问次数统计结束

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
        $home = S('home');
        if ($home) {
            $home['shopcartNum'] = $shopcartNum; //更新购物车数量
            echo json_encode($home); //直接显示首页缓存的数据
        } else {
            //商品分类
            $category = D('category')->getCategory();
            //添加全部商品分类
            array_unshift($category, array('id' => 0, 'name' => 'qb', 'pid' => 0, 'title' => '全部商品', 'child' => array()));
            //首页幻灯片广告
            // 2  移动端   1 PC端
            $slide = D('Home/slide')->getList(1);
            //最新3个公告
            $notice = D('document')->getNewNotice();
            //推荐
            $WorthProduct = D('lotteryProduct')->getWorthist(8);
            //热门推荐
            $hotProduct = D('lotteryProduct')->getHotList(8);
            //上架新品
            $newProduct = D('lotteryProduct')->getNewList(8);
            //一元传奇
            $oneLegend = D('lotteryProduct')->getOneLegend();

            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'pic_host' => C('PICTURE'),
                'category' => $category,
                'slide' => $slide,
                'notice' => $notice,
                'WorthProduct' => $WorthProduct,
                '$hotProduct' => $hotProduct,
                '$newProduct' => $newProduct,
                '$oneLegend' => $oneLegend,
                'shopcartNum' => $shopcartNum
            );
            echo json_encode($arr);
            S(array('expire' => 60)); //首页缓存1分钟
            S('home', $arr);
        }
    }

    /*
     * 获取商品分类
     */

    public function category() {
        //商品分类
        $category = D('category')->getCategory();
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
        $id = I('post.id','0');
        $pageSize = I('post.pageSize',10);
        $pageIndex = I('post.pageIndex',1);

        $type = I('post.type',3);
        if ($id != null) {
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
            $total = D('lotteryProduct')->getByCategoryCount($id, $type);
            $pageCount = floor($total / $pageSize);
            if ($total % $pageSize > 0) {
                $pageCount = $pageCount + 1;
            }
            //前端页面修改添加   2016年3月29日 13:44:47
           $pageIndex=$pageIndex-1;
            $list = D('lotteryProduct')->getByCategory($id, $pageIndex, $pageSize, $type);
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'total' => $total,
                'pageCount' => $pageCount,
                'list' => $list);
            echo json_encode($arr);
        } else {
            UtilApi::getInfo('500', '分类id不能为空');
        }
    }

    /*
     * 根据名称搜索获取商品数据
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
            $total = D('lotteryProduct')->getByNameCount($name);

            //前端页面修改添加   2016年3月29日 13:44:47
            $pageIndex=$pageIndex-1;

            $list = D('lotteryProduct')->getByName($name, $pageIndex, $pageSize);
            $pageCount = floor($total / $pageSize);
            if ($total % $pageSize > 0) {
                $pageCount = $pageCount + 1;
            }
            $category = D('category')->getCategory(); //获取商品分类
            $allTotal = D('lotteryProduct')->getByAllCount(); //获取所有商品分类下正在进行的商品数量
            $arr = array(
                'code' => 200,
                'info' => '成功',
                'host' => C('HOST'),
                'allTotal' => $allTotal,
                'category' => $category,
               // 'sTotal' => $total,  //测试分页
                'total' => $total,
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
        $soonProduct = D('lotteryProduct')->getSoonProduct();
        //等待揭晓
        $waitProduct = D('lotteryProduct')->getWaitProduct();
        if($waitProduct){
            foreach ($waitProduct as $key => &$value) {
                $value['waitTime']=$value['expecttime']-NOW_TIME;
            }
        }
        //已经揭晓
        $resultProduct = D('lotteryProduct')->getResultProduct();
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            /*'nextWaitTime' => RunTimeUtil::runNextTime(),
            'hourTime' => RunTimeUtil::cqsscTimestamp(),
            'waitTime' => RunTimeUtil::runTime(),*/
            'soonProduct' => $soonProduct,
            'waitProduct' => $waitProduct,
            'resultProduct' => $resultProduct);
        echo json_encode($arr);
    }

    /*
     * 获取未揭晓商品详情
     */

    public function productByDetail() {
        $uid = I('post.uid');
        $pid = I('post.pid');
        $lotteryId = I('post.lotteryId');
        $a_pageSize = I('post.a_pageSize');
        $a_pageIndex = I('post.a_pageIndex');
        $p_pageSize = I('post.p_pageSize');
        $p_pageIndex = I('post.p_pageIndex');
        if (empty($pid)) {
            UtilApi::getInfo('500', '商品id不能为空');
            return;
        }
        if (empty($lotteryId)) {
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
        $detail = D('lotteryProduct')->getDetail($pid, $lotteryId);
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
            $prizeList=array();
            $prizeUser=array();
        }
        $prize = array('prizeList' => $prizeList, 'prizeUser' =>$prizeUser);

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
            $prizeUser = D('lotteryProduct')->getPrizeUser($prizeList['uid']);
            $prize = array('prizeList' => $prizeList, 'prizeUser' => $prizeUser);
        } else
        {
            $prize = array('prizeList' => array(), 'prizeUser' => array());
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
        $lastAttendTime = $prize['prizeList']['last_attend_time'];
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
        $prize = array('prizeList' => $prizeList);
        if($prizeList['uid'])
        {
            $prizeUser = D('lotteryProduct')->getPrizeUser($prizeList['uid']);


        }
        else
        {
            $prizeUser=array();
        }
        $prize['prizeUser'] = $prizeUser;


        //开奖计算结果
        $lastAttendTime = $prize['prizeList']['last_attend_time'];
        if (empty($lastAttendTime)) {
            $countList = array();
        } else {
            $countList = D('lotteryAttend')->getLastAttendTimeList($lastAttendTime);
        }

        //获取最新一期正在进行的商品
        $newLottery = D('lotteryProduct')->getNewProductLottery($pid);
        //晒单列表
        $disProduct = D('documentProduct')->displayRecord($pid);
        $lottery_code = $prize['prizeList']['lottery_code']; //判断是否已开奖
       // $hour_time = RunTimeUtil::cqsscTimestamp();
      
        $waitTime=$detail['product'][0]['expecttime']- NOW_TIME;
     
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'pic_host' => C('PICTURE'),
            'startCode' => C('START_CODE'),
            'srcCode' => C('SRC_CODE'),
            //'hourTime' => $hour_time,
            'waitTime' => $waitTime,
            //'nextWaitTime' => RunTimeUtil::runNextTime(),
            
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
        $attendList = D('lotteryProduct')->getattendListById($lotteryId, $pageIndex, $pageSize);

        //查询总数


        $count=  M('lottery_attend')->where('lottery_id='.$lotteryId)->count();
            $a_pageCount = ceil($count/$pageSize);
       
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'a_pageCount' => $a_pageCount,
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
     * 汽车板块
     */

    public function carModule() {
        //汽车板块广告位
        $ad = D('ad')->getlist();
        //汽车分类
        $category = D('category')->getCarCategory();
        //车主服务
        $carService = D('lotteryProduct')->getByCategory(165, 0, 4, -1);
        //汽车养护
        $carCare = D('lotteryProduct')->getByCategory(166, 0, 4, -1);
        //汽车生活
        $carLife = D('lotteryProduct')->getByCategory(167, 0, 4, -1);
        $arr = array(
            'code' => 200,
            'info' => '成功',
            'host' => C('HOST'),
            'ad' => $ad,
            'category' => $category,
            'carService' => $carService,
            'carCare' => $carCare,
            'carLife' => $carLife
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
            $prizeUser = D('lotteryProduct')->getPrizeUser($prizeList['uid']);
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
     * 清空所有商品的开奖记录和参与记录，增加所有商品一期的记录，该接口请谨慎操作
     * use Home\Common\TimeUtil;
     */

    public function cleanLotteryProduct() {
        M('lottery_attend')->where('id')->delete(); //清空参与记录
        M('lottery_product')->where('id')->delete(); //清空商品开奖记录
        $con['model_id'] = 5;
        $con['display'] = 1;
        $list = M('document')->field('id,price')->where($con)->select();
        foreach ($list as $value) {
            $pdata = array();
            $pdata['pid'] = $value['id'];
            $pdata['lottery_pid'] = 1;
            $count = intval($value['price']);
            $pdata['need_count'] = $count;
            $code = '';
            //最多到10万
            for ($i = 1; $i <= $count; $i++) {
                $code .=$i . ',';
            }
            $code = substr($code, 0, -1); //去除最后一个逗号
            $pdata['lucky_code'] = $code; //这一期的所有幸运码
            $pdata['create_time'] = TimeUtil::timeStamp();
            $lid = M('lottery_product')->add($pdata);
            $updata = array();
            $updata['id'] = $lid; //添加新一期的id
            $updata['lottery_id'] = C('START_CODE') + $lid; //新一期期号
            M('lottery_product')->save($updata); //更新新一期的期号
        }
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
        $ip_address = M('lottery_attend')->where('lottery_id='.$lotteryId.' and uid='.$lottery_uid)->getField('ip_address');

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
            $prizeUser['ip_address']=$ip_address;
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

     public function cs()
    {
        
            $nextWaitTime= RunTimeUtil::cqsscTimestamp();
            $hourTime= RunTimeUtil::runTime();
            $waitTime= RunTimeUtil::runNextTime();

            $arr=array(
               'hourTime'=>$hourTime,
               'waitTime'=>$waitTime       

                );
            dump($arr);
    }
    
}
