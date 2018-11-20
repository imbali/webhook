<?php

/*
 * 路由定义设置
 */

return [

    'URL_ROUTER_ON'          => false, // 是否开启URL路由

    /* 定义路由规则 */
    'URL_ROUTE_RULES' => [
    ],

    /* 静态路由 */
    'URL_MAP_RULES' => [
    ],

    'APP_SUB_DOMAIN_DEPLOY'  => false, // 是否开启子域名部署
    /* 子域名部署规则 */
    'APP_SUB_DOMAIN_RULES'   => [
        'auth'      => 'Admin',
        'admin'     => 'Home',
        'api'       => 'Center',
    ],

];
