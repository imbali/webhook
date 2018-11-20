<?php

/*
 * 数据库配置文件
 */

return [

    /* MYSQL数据库配置 -- 后台权限等 */
    'DB_TYPE'           =>  'mysqli',           // 数据库类型
    'DB_HOST'           =>  '127.0.0.1',        // 服务器地址
    'DB_NAME'           =>  'app_admin',        // 数据库名
    'DB_USER'           =>  'root',             // 用户名
    'DB_PWD'            =>  'root',             // 密码
    'DB_PORT'           =>  '3306',             // 端口
    'DB_PREFIX'         =>  'admin_',           // 数据库表前缀

    /* Mongodb数据库配置 */
    'MONGODB_CONFIG'    => [
        'host'     => '192.168.15.199',
        'port'     => 27017,
        'database' => 'demo',
        'username' => 'admin',
        'password' => 'admin',
        'options' => [
            'database' => 'admin' // sets the authentication database required by mongo 3
        ],
    ],

    /* 数据统计 */
    'DB_CONFIG_DATA'    => [
        'DB_TYPE'   => 'mysqli',
        // 'DB_HOST'   => '127.0.0.1',
        // 'DB_NAME'   => 'app_data',
        'DB_HOST'   => '192.168.15.165',
        'DB_NAME'   => 'legend',
        'DB_USER'   => 'root',
        'DB_PWD'    => 'root',
        'DB_PORT'   => '3306',
    ],

    //后台数据库
    'DB_STAGE_DATA'         => array(
        'DB_TYPE'       => 'mysql',
        'DB_HOST'       => '127.0.0.1',
        'DB_NAME'       => 'app_stage',
        'DB_USER'       => 'root',
        'DB_PWD'        => 'root',
        'DB_PORT'       => 3306,
        'DB_PREFIX'     => 'st_',
    ),

];