<?php

namespace Home\Model;

use Think\Model;

/**
 * 验证码
 *
 * @author joan
 */
class VerifyModel extends Model {
    /* 添加手机号验证码 */

    public function verifyAdd($data = '') {
        $verify = M("verify");
        $verify = $verify->data($data)->add();
        //echo M("picture")->getLastSql();
        return $verify;
    }

    /* 查询手机号验证码 */

    public function getVerify($cellphone = 0, $verify = 0, $type = 0) {
        $prefix = C('DB_PREFIX');
        $sql = "SELECT max(id) as id,v.verify,v.cellphone,v.creat_time FROM " . $prefix . "verify as v  " .
                " where id=(select max(id) from os_verify where  (cellphone=" . $cellphone . " and type=" . $type . " and  verify=" . $verify . " and status=0))";
        //echo $sql;
        $info = $this->query($sql);
        return $info[0];
    }

}
