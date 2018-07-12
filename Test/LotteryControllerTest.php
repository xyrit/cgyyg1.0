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
     * 测试验证参与记录
     */

    public function ddtestValidateAttend() {
        $content = '{"list": [{
            "id": 100,
            "lottery_id": "1000000175",
            "pid": "412",
            "uid": "322",
            "attend_count": "2",
            "name": "商品1"
        }]}';
        $arr = array('mobile' => 18666290378,'content' => $content,'money'=>1,'payMoney'=>0,'balance'=>5);
        $json_string = HttpRequest::httpPost($this->host . "Home/Lottery/validateAttend", $arr);
        print_r($json_string);
    }

    /*
     * 测试添加参与记录
     */

    public function testAddAttendLottery() {
        $content = '{"list": [{
            "id": 100,
            "lottery_id": "1000000195",
            "pid": "455",
            "uid": "322",
            "attend_count": "3",
            "name": "商品2"
        }]}';
        $arr = array('mobile' => 18666290378,'attendDevice'=>'PC网站','content' => $content,'money'=>3,'payMoney'=>0,'balance'=>6,'payType'=>0);
        $json_string = HttpRequest::httpPost($this->host . "Home/Lottery/addAttendLottery", $arr);
        print_r($json_string);
    }

    /*
     * 揭晓即将揭晓记录
     */

    public function ddtestLotteryResult() {
        $arr = array();
        $json_string = HttpRequest::httpPost($this->host . "Home/Lottery/lotteryResult", $arr);
        print_r($json_string);
    }

    /*
     * 获取缓存幸运码
     */

    public function ddtestAttendLottery() {
        $arr = array('mobile' => 18666290378);
        $json_string = HttpRequest::httpPost($this->host . "Home/Lottery/attendLottery", $arr);
        print_r($json_string);
    }

}
