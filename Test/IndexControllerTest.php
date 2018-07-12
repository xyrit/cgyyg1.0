<?php

/*
 * Generated by PHPUnit_SkeletonGenerator on 2015-12-03 at 13:52:39.
 */

require_once 'HttpRequest.php';

class IndexControllerTest extends PHPUnit_Framework_TestCase {
    /*
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */

    protected $host;

    protected function setUp() {
        $this->host = "http://localhost/cgyyg1.0/index.php/";
    }

    /*
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */

    protected function tearDown() {
        
    }

    /*
     * 测试首页数据
     */

    public function ddtestHomePage() {
        // Remove the following lines when you implement this test.
        $arr = array();
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/homePage", $arr);
        //$obj = json_decode($json_string,true);
        print_r($json_string);
    }

    /*
     * 测试商品分类数据
     */

    public function ddtestCategory() {
        $arr = array();
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/category", $arr);
        print_r($json_string);
    }

    /*
     * 测试获取公告详情
     */

    public function bbtestNoticeDetail() {
        // Remove the following lines when you implement this test.
        $arr = array('id' => '376');
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/noticeDetail", $arr);
        print_r($json_string);
    }

    /*
     * 测试根据分类Id分页获取商品
     */

    public function ddtestCategoryById() {
        // Remove the following lines when you implement this test.
        $arr = array('id' => '107', 'pageIndex' => '1', 'pageSize' => '4', 'type' => '3');
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/categoryById", $arr);
        print_r($json_string);
    }

    /*
     * 测试搜索名称获取商品
     */

    public function ddtestSearchByName() {
        // Remove the following lines when you implement this test.
        $arr = array('name' => 'iphone6s', 'pageIndex' => '0', 'pageSize' => '1');
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/searchByName", $arr);
        print_r($json_string);
    }

    /*
     * 测试即将揭晓专区数据
     */

    public function ddtestLotterySoon() {
        // Remove the following lines when you implement this test.
        $arr = array();
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/lotterySoon", $arr);
        print_r($json_string);
    }

    /*
     * 测试未揭晓商品详情
     */

    public function ddtestProductByDetail() {
        $arr = array('pid' => '452', 'uid' => '322', 'lotteryId' => '1000000172', 'a_pageIndex' => '0', 'a_pageSize' => '7', 'p_pageIndex' => '0', 'p_pageSize' => '1');
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/productByDetail", $arr);
        print_r($json_string);
    }

    /*
     * 测试根据商品id获取未揭晓商品详情
     */

    public function ddtestProductDetailById() {
        $arr = array('pid' => '453', 'uid' => '322', 'a_pageIndex' => '0', 'a_pageSize' => '7', 'p_pageIndex' => '0', 'p_pageSize' => '1');
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/pDetailById", $arr);
        print_r($json_string);
    }

    /*
     * 测试等待揭晓商品详情
     */

    public function ddtestProductWaitByDetail() {
        $arr = array('pid' => '426',
            'uid' => '322', 'lotteryId' => '1000000159',
            'a_pageIndex' => '0', 'a_pageSize' => '2',
            'p_pageIndex' => '0', 'p_pageSize' => '1');
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/productWaitByDetail", $arr);
        print_r($json_string);
    }

    /*
     * 测试已经揭晓商品详情
     */

    public function ddtestProductPrizeByDetail() {
        $arr = array('pid' => '425',
            'uid' => '322', 'lotteryId' => '1000000158',
            'a_pageIndex' => '0', 'a_pageSize' => '2',
            'p_pageIndex' => '0', 'p_pageSize' => '1');
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/productPrizeByDetail", $arr);
        print_r($json_string);
    }

    /*
     * 测试根据期号查询计算结果，所有参与记录
     */

    public function ddtestProductPrizeById() {
        $arr = array('lotteryId' => '1000000118',
            'pageIndex' => '0', 'pageSize' => '2',
            'lastAttendTime' => '1449730710');
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/productPrizeById", $arr);
        print_r($json_string);
    }

    /*
     * 测试获取中奖人信息
     */

    public function ddtestPrizeUserById() {
        $arr = array('uid' => '12');
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/prizeUserById", $arr);
        print_r($json_string);
    }

    /*
     * 测试根据期号获取开奖信息和中奖用户信息
     */

    public function ddtestPrizeLotteryById() {
        $arr = array('lotteryId' => '1000000120');
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/prizeLotteryById", $arr);
        print_r($json_string);
    }

    /*
     * 测试某一期参与记录分页
     */

    public function ddtestAttendListById() {
        $arr = array('lotteryId' => '1000000120', 'pageIndex' => '1', 'pageSize' => '2');
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/attendListById", $arr);
        print_r($json_string);
    }

    /*
     * 测试限购专区
     */

    public function ddtestLimitProduct() {
        $arr = array();
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/limitProduct", $arr);
        print_r($json_string);
    }

    /*
     * 测试限购专区
     */

    public function fftestCarModule() {
        $arr = array();
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/carModule", $arr);
        print_r($json_string);
    }

    /*
     * 测试商品开奖分页查询
     */

    public function ddtestPrizeLottery() {
        $arr = array('pid' => 452, 'pageIndex' => 0, 'pageSize' => 1);
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/prizeLottery", $arr);
        print_r($json_string);
    }

    /*
     * 测试商品详情
     */

    public function ddtestDetail() {
        $arr = array('pid' => 381, 'lotteryId' => 1000000121);
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/detail", $arr);
        print_r($json_string);
    }

    /*
     * 测试所有公告标题列表
     */

    public function ddtestNoticeList() {
        $arr = array();
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/noticeList", $arr);
        print_r($json_string);
    }

    /*
     * 测试公告详情
     */

    public function ddtestNoticeDetail() {
        $arr = array('id' => 376);
        $json_string = HttpRequest::httpPost($this->host . "Home/Index/noticeDetail", $arr);
        print_r($json_string);
    }

    /*
     * demo
     */

    public function testDemo() {
        /* $code = '';
          for ($i = 1; $i <= 1234567; $i++) {
          $code .=$i .',';
          }
          $code = substr($code, 0, -1); //去除最后一个逗号
          print_r($code); */

        /* $ids = '111,222,333';
          $arr = explode(",", $ids);
          $str = "";
          foreach ($arr as $id) {
          $str .= $id . ",";
          }
          $str = substr($str, 0, -1);
          echo 'str=============' . $str; */

        /*srand((float) microtime() * 10000000);
        $input = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19");
        $rand_keys = array_rand($input, 3);
        print $input[$rand_keys[0]] . " ";
        print $input[$rand_keys[1]] . " ";
        print $input[$rand_keys[2]] . " ";*/
        
        $nowTime = strtotime('now');
        print $nowTime;
    }
}