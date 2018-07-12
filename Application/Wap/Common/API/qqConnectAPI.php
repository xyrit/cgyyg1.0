<?php
session_start();
/* PHP SDK
 * @version 2.0.0
 * @author connect@qq.com
 * @copyright © 2013, Tencent Corporation. All rights reserved.
 */

require_once(dirname(__FILE__)."/comm/config.php");
/*
 * define("ROOT",dirname(dirname(__FILE__))."/");
 * define("CLASS_PATH",ROOT."class/");
 * "ROOT"=>"Connect2.1/API/
 * "CLASS_PATH"=>"Connect2.1/API/class/"
 */
require_once(CLASS_PATH."QC.class.php");
/*
 * "Connect2.1/API/class/QC.class.php"
 * QC extend Oauth
 * public function __construct($access_token = "", $openid = "");
 * Oauth
 *  public function qq_login(){
        $appid = $this->recorder->readInc("appid");
        $callback = $this->recorder->readInc("callback");
        $scope = $this->recorder->readInc("scope");
 */
