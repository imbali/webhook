<?php

/*
 * 自定义设置
 */

//超级管理员
define('ADMIN_USERNAME', 'root');  //超级管理员用户名
define('ADMIN_PASSWORD', '123456');//超级管理员用户密码


//加密验证key
define('AUTH_ID',  '2bqToMzKpqKTP8DE');

//加密验证key
//define('AUTH_KEY', 'cMZunRfz3jG8W4Y6fi9lDEwswqM7QIhL');
define('AUTH_KEY', 'y@dta8@AgrXe4)%+jpc;S(+NCzV1S(lE');

//登录验证是否使用接口方式
define('LOGIN_INTERFACE_USED', false);
//登录验证的后台地址
//define('LOGIN_STAGE_ADDR', 'http://admin.tanzhifeng.com');
define('LOGIN_STAGE_ADDR', 'http://www.admin2.com');

//是否开启审核模式
define( 'REVIEW_MOD', true);

//部署平台区域,参考数据库的设置
define( 'DEPLOYMENT_ID', '1' ); //0=单点登录平台

//中心服地址
//define('CENTER_ADDRESS', 'http://api.tanzhifeng.com/index.php/skynet/parser');
define('CENTER_ADDRESS', 'http://192.168.15.195:8082/index.php/center/skynet/parser');
//中心服ansible地址
define('CENTER_ANSIBLE', 'http://192.168.15.195:8888/playbook');

return [

    'PLATFORM_NAME'         => '黎明游戏GMT', //后台名称

    // 'DEFAULT_MODULE'        => 'Admin', // 默认模块

    //登录是否校验验证码
    'IS_CHECK_VERIFY_CODE'  => false,

    //是否允许超级管理员登录
    'PERMIT_ADMIN_LOGIN'    => true,

    //允许超级管理员登录的模块(PERMIT_ADMIN_LOGIN==true时生效)
    'PERMIT_ADMIN_MODULE'   => ['Home', 'Admin'],

    //是否限制IP登录
    'LOGIN_LIMIT_IP'    => false,

];
