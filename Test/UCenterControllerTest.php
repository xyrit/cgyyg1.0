<?php

require_once 'HttpRequest.php';

class LotteryControllerTest extends PHPUnit_Framework_TestCase {
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
     * 测试关注商品
     */

    public function ddtestFocusProduct() {
        $arr = array('mobile' => 18666290378, 'uid' => 322, "pid" => 427, "lotteryId" => 1000000160);
        $json_string = HttpRequest::httpPost($this->host . "Home/Ucenter/focusProduct", $arr);
        print_r($json_string);
    }

    /*
     * 测试关注商品列表
     */

    public function ddtestFocusList() {
        $arr = array('mobile' => 18666290378, 'uid' => 322, 'pageIndex' => 0, 'pageSize' => 2);
        $json_string = HttpRequest::httpPost($this->host . "Home/Ucenter/focusList", $arr);
        print_r($json_string);
    }

    /*
     * 测试删除关注商品
     */

    public function ddtestDelFocus() {
        $arr = array('mobile' => 18666290378, 'uid' => 322, 'lotteryId' => '1000000159,1000000160');
        $json_string = HttpRequest::httpPost($this->host . "Home/Ucenter/delFocus", $arr);
        print_r($json_string);
    }

    /*
     * 测试记录浏览商品记录
     */

    public function ddtestReadProduct() {
        $arr = array('mobile' => 18666290378, 'uid' => 322, "pid" => 428, "lotteryId" => 1000000162);
        $json_string = HttpRequest::httpPost($this->host . "Home/Ucenter/readProduct", $arr);
        print_r($json_string);
    }

    /*
     * 测试浏览商品列表
     */

    public function ddtestReadList() {
        $arr = array('mobile' => 18666290378, 'uid' => 322, 'pageIndex' => 0, 'pageSize' => 2);
        $json_string = HttpRequest::httpPost($this->host . "Home/Ucenter/readList", $arr);
        print_r($json_string);
    }

    /*
     * 测试删除浏览商品
     */

    public function testDelRead() {
        $arr = array('mobile' => 18666290378, 'uid' => 322, 'lotteryId' => '1000000162');
        $json_string = HttpRequest::httpPost($this->host . "Home/Ucenter/delRead", $arr);
        print_r($json_string);
    }

}
