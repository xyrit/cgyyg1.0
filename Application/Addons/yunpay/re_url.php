<?php
/* *
 * 功能：服务器同通知页面
 */

require_once("yun.config.php");
require_once("lib/yun_md5.function.php");
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <?php
//计算得出通知验证结果
        $yunNotify = md5Verify($_REQUEST['i1'], $_REQUEST['i2'], $_REQUEST['i3'], $yun_config['key'], $yun_config['partner']);
        if ($yunNotify) {//验证成功
            //商户订单号
            $out_trade_no = $_REQUEST['i2'];
            //云支付交易号
            $trade_no = $_REQUEST['i4'];
            //价格
            $yunprice = $_REQUEST['i1'];

            /*
              加入您的入库及判断代码;
              判断返回金额与实金额是否想同;
              判断订单当前状态;
              完成以上才视为支付成功
             */
//dump($_REQUEST);
            echo "商户订单号" . $out_trade_no . ";云支付交易号" . $trade_no . ";价格" . $yunprice . $_REQUEST['i3'];
            header("Location: http://test.cgyyg.com");
        } else {
            //验证失败
            echo "验证失败";
        }
        ?>
        <title>云支付接口</title>
    </head>
    <body>
    </body>
</html>