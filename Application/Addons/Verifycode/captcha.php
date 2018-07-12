<?php
session_start();
require './ValidateCode.class.php';  //先把类包含进来，实际路径根据实际情况进行修改。
$_vc = new ValidateCode();		     //实例化一个对象
$_vc->doimg();		
$_SESSION['authnum_session'] = $_vc->getCode();//验证码保存到SESSION中
var_dump($_SESSION['authnum_session']);
$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
$txt = $_SESSION['authnum_session'];
fwrite($myfile, $txt);
//$txt = "Steve Jobs\n";
//fwrite($myfile, $txt);
fclose($myfile);
?>