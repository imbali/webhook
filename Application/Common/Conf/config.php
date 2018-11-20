<?php

define('PUBLIC_PATH', __ROOT__.'/Public');

return array(
    'DEFAULT_MODULE'         => 'Home', // 默认模块

//*************************************附加设置***********************************
    'SHOW_PAGE_TRACE'        => false,                           // 是否显示调试面板
    'TAGLIB_BUILD_IN'        => 'Cx,Common\Tag\My',              // 加载自定义标签
    'LOAD_EXT_CONFIG'        => 'db,my,routes,vendor,allow,tags',               // 加载网站设置文件

//*************************************常用路径***********************************
    'TMPL_PARSE_STRING'      => array(
        '__PUBLIC__'         => PUBLIC_PATH,
        '__STATIC__'         => PUBLIC_PATH.'/statics',
        '__HOME_CSS__'       => PUBLIC_PATH.'/app/home/css',
        '__HOME_JS__'        => PUBLIC_PATH.'/app/home/js',
        '__HOME_IMAGES__'    => PUBLIC_PATH.'/app/home/images',
        '__ADMIN_CSS__'      => PUBLIC_PATH.'/app/admin/css',
        '__ADMIN_JS__'       => PUBLIC_PATH.'/app/admin/js',
        '__ADMIN_IMAGES__'   => PUBLIC_PATH.'/app/admin/images',
        '__ADMIN_ACEADMIN__' => PUBLIC_PATH.'/statics/aceadmin',
        '__PUBLIC_CSS__'     => PUBLIC_PATH.'/app/public/css',
        '__PUBLIC_JS__'      => PUBLIC_PATH.'/app/public/js',
        '__PUBLIC_IMAGES__'  => PUBLIC_PATH.'/app/public/images',
        '__USER_CSS__'       => PUBLIC_PATH.'/app/user/css',
        '__USER_JS__'        => PUBLIC_PATH.'/app/user/js',
        '__USER_IMAGES__'    => PUBLIC_PATH.'/app/user/images',
    ),

//***********************************URL设置**************************************
//    'MODULE_ALLOW_LIST'      => array('Home','Admin','Center'), //允许访问列表
    'MODULE_DENY_LIST'       => array('Common','Api','User'), //禁止访问列表
    'URL_CASE_INSENSITIVE'   => true, // 默true 表示URL不区分大小写 false则表示区分大小写
    'URL_HTML_SUFFIX'        => 'html',  // URL伪静态后缀设置
    'URL_MODEL'              => 1, // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
    // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式

//***********************************SESSION设置**********************************
    'SESSION_AUTO_START'     => true,
    'SESSION_OPTIONS'        => array(
        'name'               => 'ADMIN',//设置session名
        'expire'             => 3600*24*7, //SESSION保存7天
        'use_trans_sid'      => 1,//跨页传递
        'use_only_cookies'   => 0,//是否只开启基于cookies的session的会话方式
    ),

//***********************************页面设置**************************************
    'TMPL_EXCEPTION_FILE'    => APP_DEBUG ? THINK_PATH.'Tpl/think_exception.tpl' : './Template/default/Home/Public/404.html',
    'TMPL_ACTION_ERROR'      => TMPL_PATH.'/Public/dispatch_jump.tpl', // 默认错误跳转对应的模板文件
    'TMPL_ACTION_SUCCESS'    => TMPL_PATH.'/Public/dispatch_jump.tpl', // 默认成功跳转对应的模板文件

//***********************************auth设置**********************************
    'AUTH_CONFIG'            => array(
            'AUTH_USER'      => 'users'                         //用户信息表
        ),

//***********************************缓存设置**********************************
//    'DATA_CACHE_TIME'        => 1800,        // 数据缓存有效期
//    'DATA_CACHE_PREFIX'      => 'tp_',      // 缓存前缀
//    'DATA_CACHE_TYPE'        => 'Redis', // 数据缓存类型,
//    'REDIS_HOST'             => '127.0.0.1', // 服务器ip
//    'REDIS_PORT'             => 6379,
//    'REDIS_PASSWORD'         => '',

//***********************************语言包设置**********************************
    // 布局设置
    'TMPL_ENGINE_TYPE'      =>  'Think',     // 默认模板引擎 以下设置仅对使用Think模板引擎有效
    /* 默认设定 */
    'DEFAULT_LANG'          =>  'zh-cn', // 默认语言
    /* 语言包功能 */
    'LANG_SWITCH_ON'        =>  true,   // 开启语言包功能
    'LANG_AUTO_DETECT'      =>  true, // 自动侦测语言 开启多语言功能后有效
    'LANG_LIST'             =>  'zh-cn', // 允许切换的语言列表 用逗号分隔
    'VAR_LANGUAGE'          =>  'l', // 默认语言切换变量


);
