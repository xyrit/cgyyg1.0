<?php

namespace Wap\Controller;

use Home\Common\UtilApi;

header("Access-Control-Allow-Origin:*");

/**
 * 
 * 云支付
 * Author: joan
 */
class YunPayController extends HomeController {
    /* 充值 */

    public function charge() {
        $charge_token = $_GET['charge_token']; 
        A("Home/YunPay")->index($charge_token);
    }

    /* 付款 */

    public function payfor() {
        $pay_token = $_GET['pay_token'];
        A("Home/YunPay")->index($pay_token);
    }

}
