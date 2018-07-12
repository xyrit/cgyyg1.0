<?php

namespace Home\Model;

use Think\Model;

/**
 * 前台用户登录、注册
 * joan
 */
class MemberModel extends Model {

    /**
     * 根据用户id获取单条用户所有信息
     * @param  integer $uid 
     * @return $User     
     */
    public function getOne($uid = '') {
        $prefix = C('DB_PREFIX');
        $sql = "SELECT m.uid,m.nickname,m.sex,m.birthday,m.qq,m.mobile,m.score,m.brokerage,m.red_packet,m.account,m.password,m.face,m.login_type FROM " .
                $prefix . "member as m  WHERE (m. uid = " . $uid . ")";
        $User = $this->query($sql);
        $User = $User[0];
        return $User;
    }

    /**
     * 用户登录,根据用户名、密码匹配用户信息
     * @param  integer $mobile 
     * @return $User     
     */
    public function getUser($condition = '') {
        $prefix = C('DB_PREFIX');
        $sql = "SELECT m.uid,m.nickname,m.sex,m.face,m.login,m.uc_uid,m.uc_username FROM " .
                $prefix . "member as m  WHERE (m. mobile = " . $condition['mobile'] . " and password='" . $condition['password'] . "')";
        $User = $this->query($sql);
        $User = $User[0];
        return $User;
    }

    /**
     * 根据用户id获取单条用户列信息
     * @param  integer $uid 
     * @param  integer $field 
     * @return $User     
     */
    public function getField($uid = '', $field = '') {
        $prefix = C('DB_PREFIX');
        $sql = "SELECT " . $field . " FROM " .
                $prefix . "member as m  WHERE (m. uid = " . $uid . ")";
        //echo $sql;
        $User = $this->query($sql);
        return $User[0];
    }

    /*
     * 得到用户uid
     * 
     */

    public function getUid($mobile) {
        $uid = M("member")->field('uid')->where('mobile=' . $mobile)->find();
        return $uid["uid"];
    }

    /* 获取手机号验证码信息 */

    public function getVerify($condition = '', $field = '') {
        $verify_info = M("verify");
        $condition["status"] = 0;
        $verify_info = $verify_info->field('verify,creat_time,cellphone')->where($condition)->find();
        return $verify_info;
    }

    /**
     * 修改用户资料
     * @return $uid
     */
    public function userInfoSave($uid = '', $data = '') {
        $user = M("member")->where('uid=' . $uid)->save($data); // 根据条件保存修改的用户资料
        return $user;
    }

    /*
     * 查询ucenter的用户id是否存在于当前应用的用户表中
     */

    public function uc_uid_check($uid = '') {
        $prefix = C('DB_PREFIX');
        $sql = "SELECT count(*) as count FROM " . $prefix . "member WHERE uc_uid=" . $uid;
        //echo $sql;
        $info = M("member")->query($sql); //dump($info);exit;
        return $info[0]["count"];
    }

    /*
     * 连接ucenter获取discuz积分信息
     */

    public function get_discuz_score($uc_uid = '') {
        $discuzpre = C('discuzpre');
        $ultrax = C('ultrax');
        $sql = 'select credits from ' . $discuzpre . 'common_member where uid=' . $uc_uid;
        $score_num = $this->db(1, $ultrax)->query($sql); //dump($score_num);
        $credits = $score_num[0]["credits"];
        return $credits;
    }

    /*
     * 连接ucenter更新discuz积分信息
     */

    public function save_discuz_score($uc_uid = '', $score_remain = '') {
        $discuzpre = C('discuzpre');
        $ultrax = C('ultrax');
        $sql = 'update  ' . $discuzpre . 'common_member set credits =' . $score_remain . ' where uid=' . $uc_uid;
        $score_num = $this->db(1, $ultrax)->query($sql);
        $credits = $score_num[0]["credits"];
        return $credits;
    }

    /*
     * 检查当前登录用户是否存在ucenter和discuz中
     */

    public function check_ultrax_user($username = '') {
        $discuzpre = C('discuzpre');
        $ultrax = C('ultrax');
        $sql = "select u.uid from " . $discuzpre . "ucenter_members as u where u.username='" . $username . "' order by u.uid";
        //echo $sql;exit;
        $info = $this->db(1, $ultrax)->query($sql);
        return $info;
    }

    /*
     * @param $uid
     * 根据用户的uid获取与ucenter同步的uc_uid
     * 返回  uc_uid
     */

    public function get_uc_uid($uid = '') {
        $info = $this->getField($uid, 'uc_uid');
        $uc_uid = $info["uc_uid"];
        return $uc_uid;
    }

    /*
     * 获取用户已经消费的积分
     * 
     */

    public function score_expense($uid = '') {
        $prefix = C('DB_PREFIX');
        $form = M();
        $sql = "SELECT sum(score)as scores  FROM " . $prefix . "score_use WHERE uid=" . $uid . " group by score";
        $info = $form->query($sql);
        $score_expense = floatval($info[0]["scores"]);
        return $score_expense;
    }

}
