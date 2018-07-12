<?php

require_once 'HttpRequest.php';

class ShopCartControllerTest extends PHPUnit_Framework_TestCase {
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
     * 测试添加参与记录
     */

    public function testincNum() {
       $content = '{"list": [{
            "id": 252,
            "lottery_id": "1000000120",
            "pid": "380",
            "uid": "183",
            "attend_count": "2"
        }]}';
        $arr = array('content' => $content);
        $json_string = HttpRequest::httpPost($this->host . "Home/ShopCart/incNum", $arr);
        print_r($json_string);
    }
}
