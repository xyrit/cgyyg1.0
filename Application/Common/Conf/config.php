<?php

ini_set('session.cookie_domain', ".cgyyg.com"); //跨域访问Session
return array(
    /* 默认数据库配置 */
    'DB_TYPE' => 'mysql', // 数据库类
    'DB_HOST' => '192.168.1.251', // ----------------------------【内网】数据库服务器地址
    //'DB_HOST' => 'rds433q5145bogt7112d.mysql.rds.aliyuncs.com', //#############【外网】数据库【测试】服务器地址
    // 'DB_HOST' => 'rdsv1fa8p18494g8ui8n.mysql.rds.aliyuncs.com', //****************【外网】数据库【正式】服务器地址
    'DB_NAME' => 'oneshop', // 数据库名
    'DB_USER' => 'root', // ----------------------------内网用户
    //'DB_USER' => 'cgtest', //##################################【外网】数据库【测试】服务器用户
    //'DB_USER' => 'cgoscgdb', //****************************************【外网】数据库【正式】服务器用户
    'DB_PWD' => 'root', //---------------------------- 【密码】
    //'DB_PWD' => 'sO_YmPeMBgV4hywrrkmYOCb2PyvLILEV', //##################################【外网】数据库【测试】服务器【密码】
    //'DB_PWD' => 'Cg_cg123cg', //****************************************【外网】数据库【正式】服务器【密码】
    'DB_PORT' => '3306', // 端口
    'DB_PREFIX' => 'os_', // 数据库表前缀
    'DB_CHARSET' => 'utf8', // 数据库编码默认采用utf8
    //HOST配置
    'HOST' => 'http://img.cgyyg.com',
    'HOST_URL' => 'test.cgyyg.com',
    //图片配置
    'PICTURE' => 'http://img.cgyyg.com', //晒单，头像的域名地址
    //时时彩配置
    'START_CODE' => 10000000, //幸运码起始码/期号开始数
    'SRC_CODE' => 10000001, //幸运码原始数
    'TIME_RUN' => 300, //定时器时间300秒，5分钟

    /* 第二数据库配置 */
    //'ultrax' => 'mysql://root:root@localhost:3306/ultraxold',
    'ultrax' => 'mysql://cgtest:sO_YmPeMBgV4hywrrkmYOCb2PyvLILEV@rds433q5145bogt7112d.mysql.rds.aliyuncs.com:3306/ultraxs',
    // 'DISCUZ'=>'pre_', 
    'discuzpre' => 'pre_',
    //第三方云支付配置信息  
    'yun_partner' => '19021455681743', //合作身份者id  
    'yun_key' => 'NeFqTawVi9v4dXTDfy6mhByFGix6YDv6', //安全检验码
    'yun_email' => '2763776125@qq.com', //云会员账户（邮箱）
    'WIDre_url' => 'http://test.cgyyg.com/cgyyg1.0/index.php/Home/YunBack/', //云支付回调地址
    //jubaoyun
    'jubaoyun' => array(
        //'payurl_pc' => 'http://www.jubaopay.com/apitest.htm', //测试环境pc
        'payurl_pc' => 'http://www.jubaopay.com/apipay.htm', //正式环境pc
        //"partnerid" => '14061642390911131749', //测试环境pc
        "partnerid" => '16041203491386416211', //正式环境pc
        'payurl_wap' => 'http://www.jubaopay.com/apiwapsyt.htm', //正式环境wap
    ),
    //第三方登陆配置
    //微博
    'WEIBO' => array(
        'WB_AKEY' => '3076305584',
        'WB_SKEY' => '70a1d2298ba70aab2cb6c4d7872d4bfe',
        'PC_CALLBACK_URL' => 'http://test.cgyyg.com/cgyyg1.0/index.php/Home/OtherLogin/wbCallback/',
        'WAP_CALLBACK_URL' => 'http://test.cgyyg.com/cgyyg1.0/wap.php/OtherLogin/wbCallback/',
    ),
    //qq配置
    'QQ' => array(
        'appid' => '101267345',
        'appkey' => '07b168488647071c608659bdedb078e5',
        'callback_wap' => 'http://test.cgyyg.com/cgyyg1.0/wap.php/OtherLogin/qqCallback',
        'callback_pc' => 'http://test.cgyyg.com/cgyyg1.0/index.php/Home/OtherLogin/qqCallback',
        'callback' => '',
    ),
    //微信
    'weixin' => array(
        'appID' => 'wxfde25128a827d99f', //微信登陆
        'appSecret' => 'dd0a71a1866daf73fc07c6bd178593d1', //微信登陆
        'appid' => 'wx4087cd4cdf05b748', //公众号登陆
        'secret' => '0d3d3e9823e9ae1ab6bd517bcae3314f', //公众号登陆
        'callback_wap' => 'http://test.cgyyg.com/cgyyg1.0/wap.php/OtherLogin/wxCallback/',
        'callback_pc' => 'http://test.cgyyg.com/cgyyg1.0/index.php/Home/OtherLogin/wxCallback/',
    ),
    //第三方登陆后跳转地址
    'pc_load_url' => 'http://test.cgyyg.com',
    'wap_load_url' => 'http://test.cgyyg.com/m',
    // 'DATA_CACHE_PREFIX' => 'Redis_',//缓存前缀
    // 'DATA_CACHE_TYPE'=>'Redis',//默认动态缓存为Redis
    // 'REDIS_RW_SEPARATE' => false, //Redis读写分离 true 开启
    // 'REDIS_HOST'=>'192.168.1.164', //redis服务器ip，多台用逗号隔开；读写分离开启时，第一台负责写，其它[随机]负责读；
    // 'REDIS_PORT'=>'6379',//端口号
    // 'REDIS_TIMEOUT'=>'300',//超时时间
    // 'REDIS_PERSISTENT'=>false,//是否长连接 false=短连接
    // 'REDIS_AUTH'=>'test123',//AUTH认证密码
    // 'DATA_CACHE_TIME'  => 10800,      // 数据缓存有效期 0表示永久缓存
    'VAR_SESSION_ID' => 'session_id',
);

