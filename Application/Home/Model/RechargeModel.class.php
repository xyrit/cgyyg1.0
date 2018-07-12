<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | Author: charles <banhuajie@163.com>
// +----------------------------------------------------------------------

namespace Home\Model;
use Think\Model;

/**
 * 用户充值记录模型类
 * Class AuthGroupModel
 * @author charles <zhuyajie@topthink.net>
 */
class RechargeModel extends Model {


    public function getamount($uid){
        $total_money=$this->where('uid='.$uid)->sum('money');
        return $total_money;
    }




}

