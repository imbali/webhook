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

];
