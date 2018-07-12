<?php

namespace Home\Model;

use Think\Model;

/**
 * 公告和文章模型
 * 更新时间 2016.1.16
 */
class DocumentModel extends Model {
    /*
     * 获取最新3个公告标题列表
     */

    public function getNewNotice() {
        $map = array('id', 'title','create_time');
        $list = $this->field($map)->order('id desc')->where("category_id='56' and display=1")->limit(3)->select();
        return $list;
    }

    /*
     * 获取所有公告标题列表
     */

    public function getNoticeList() {
        $map = array('id', 'title','create_time');
        $list = $this->field($map)->order('id desc')->where("category_id='56' and display=1")->limit(3)->select();
        return $list;
    }

    /*
     * 获取公告详情
     */

    public function getNoticeDetail($id) {
        $prefix = C('DB_PREFIX');
        $sql = "select d.title,d.create_time,da.content from " . $prefix . "document d "
                . "left join " . $prefix . "document_article da on d.id=da.id "
                . "where d.category_id='56' and d.display=1 and d.id=".$id;
        $detail = $this->query($sql);
        return $detail;
    }

    /*
     * 获取文章详情
     */

    public function getDetail($id) {
        $map = array('content');
        $detail = M('document_article')->field($map)->order('id desc')->where("id=$id")->select();
        return $detail;
    }
}
