<?php
//CURL的二次验证串
define( 'AUTHOR', 'z8k3afuqk70wkw13' );

//时间
define( 'TIMESTAMP', time() );
define( 'TIME_YMD', date('Y-m-d', TIMESTAMP) );

//加密的密文有效期
define( 'AUTH_EXPIRY', 43200 ); //12个小时
//自定义日志开关
define( 'LOGGER', true );

//是否开启活动多语言false
define( 'ACT_LANG', false);

// 简化目录分隔符常量
define( 'DS', DIRECTORY_SEPARATOR );

//游戏服mongodb的数据名
define('GAME_DB_NAME', 'demo');

define( 'SYSDIR_OUTPUT', '/data/hero/output' ); //目录要求php拥有读写权限
return [

     'ipAllowList' => array(

        //格式：完整的ip或者用*表示一个段,如：192.168.15.*
        '127.0.0.1',
        '192.168.15.*',
        '192.168.10.*',
        '181.15.212.242',
    ),
    'globalIndexList' => array(

        // 角色ID
        'role_index' => [
            'offset'	=> 100000, 	//首次申请的起始值,(10W以下是怪物ID)
            'amount'	=> 1000,	//每次申请的数量
            'interval'	=> 10,  	//同一个服务器两次申请最小时间间隔(单位:秒)
        ]
    ),

    'globalGuildList' => array(

        // 角色ID
        'guild_index' => [
            'offset'	=> 1000, 	//首次申请的起始值,(10W以下是怪物ID)
            'amount'	=> 1000,	//每次申请的数量
            'interval'	=> 10,  	//同一个服务器两次申请最小时间间隔(单位:秒)
        ]
    ),

    'PCode' => array(
            // PayApp类
        'P_SERVER_ERROR' 	=> '1001',		//服务器ID或信息错误
        'P_MONGO_ERROR' 	=> '1002',		//连接Mongo失败

        // Deliver类
        'D_ROLE_EMPTY'		=> '2001',		//查询不到角色信息
        'D_MYSQL_CONFLICT'	=> '2002',		//mysql订单号重复
        'D_MYSQL_FAIL'		=> '2003',		//mysql写订单失败
        'D_MONGO_CONFLICT'	=> '2004',		//mongo订单号重复
        'D_MONGO_FAIL'		=> '2005',		//mongo写订单失败

        // Shop类
        'S_ORDERID_MISS'	=> '3001',		//游戏订单号错误
        'S_CONNECT_ERROR'	=> '3002',		//连接错误
        'S_RECHARGE_ERROR'	=> '3003',		//recharge_way流水有误
        'S_ROLE_NOT_MATCH'  => '3004',		//订单角色不一致
        'S_MONEY_NOT_MATCH' => '3005',		//订单金额不一致

        //EfunfunPay类
        'EP_SC_ERROR' 		=> '4001',		//服务器ID或信息错误
        'EP_CONNECT_ERROR'	=> '4002',		//连接错误
        'EP_ROLE_EMPTY'		=> '4003',		//uid与roleid不对应

        //EfunPay类
        'EF_SC_ERROR' 		=> '5001',		//服务器ID或信息错误
        'EF_CONNECT_ERROR'	=> '5002',		//连接错误
        'EF_ROLE_EMPTY'		=> '5003',		//uid与roleid不对应

        //K3gamePay类
        'K3_SC_ERROR' 		=> '6001',		//服务器ID或信息错误
        'K3_CONNECT_ERROR'	=> '6002',		//连接错误
        'K3_ROLE_EMPTY'		=> '6003',		//uid与roleid不对应
    ),

    'PayType' => array(
        'GAME_SHOP'   => 1,	//游戏内置商店
        'QQ_YSDK' 	=> 2,	//腾讯YSDK
        'EFUNFUN' 	=> 3,	//晶绮efunfun
        'EFUN' 		=> 4,	//易幻efun
        'K3GAME_IN'	=> 5,	//K3游戏内购
        'K3GAME_EXT'	=> 6,	//K3第三方支付购买
    ),

    'PLog' => array(
        //日志跟踪位置,(错误日志名)
        'IN_SDK'	=> 'sdk',		//SDK校验
        'PAYAPP'	=> 'payapp',	//发货处理的父类
        'SHOP' 		=> 'shop',		//游戏内购买商品
        'DELIVER'	=> 'deliver',	//统一发货流程
        'SUCCESS' 	=> 'success',	//最终成功的订单
        'EFUNFUN' 	=> 'efunfun',	//晶绮Efunfun
        'EFUN' 		=> 'efun',		//易幻efun
        'K3GAME' 	=> 'k3game',	//k3game

        //SDK校验失败的日志备注
        'I_ARGS'  => 'error_params',	    //缺失参数
        'I_SIGN'  => 'error_sign',		    //验证sign失败
        'I_APPID' => 'error_appid',		    //appid错误
        'I_BILL'  => 'error_sdk_order',	    //无效订单
        'I_STAT'  => 'error_pay_status',	//支付状态失败的
    ),

];
