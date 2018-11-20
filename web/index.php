<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.5.0','<'))  die('require PHP > 5.5.0 !');

/**
 * 系统调试设置
 * 建议开发阶段开启
 * 项目正式部署后请注释或者设为false
 */
define('APP_DEBUG', true);


/**
 * 应用目录设置
 * 安全期间，建议安装调试完成后移动到非WEB目录
 */
define('PROJECT_ROOT', dirname(dirname(__FILE__)));
define('APP_PATH', PROJECT_ROOT.'/Application/');


/**
 * 缓存目录设置
 * 此目录必须可写，建议移动到非WEB目录
 */
define ('RUNTIME_PATH', PROJECT_ROOT.'/Runtime/');


// 定义模板文件默认目录
define("TMPL_PATH", PROJECT_ROOT.'/Template/');


/**
 * 引入composer(第三方)扩展类库
 */
require PROJECT_ROOT.'/vendor/autoload.php';


/**
 * 引入核心入口
 * ThinkPHP亦可移动到WEB以外的目录
 */
require PROJECT_ROOT.'/ThinkPHP/ThinkPHP.php';
