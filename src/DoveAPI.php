<?php 

/**
 *  DoveAPI - 使您快速编写API接口的PHP框架
 *
 *  @github: https://github.com/xcenweb/doveapi
 *  @author: guge
 *  @qqgroup:489921607
 *
 */
use dove\Debug;
use dove\Config;

define('DOVE_VERSION','1.1.1');
define('DOVE_START_TIME',microtime(true));

define('ROOT_DIR',str_replace(['\\','//'],'/',dirname(__DIR__)).'/');
define('DOVE_DIR',ROOT_DIR.'dove/');           // 核心目录
define('DOVE_LIBRARY',DOVE_DIR.'lib/');        // 支持目录
define('DOVE_APP_DIR',ROOT_DIR.'app/');        // 应用目录
define('DOVE_CONFIG_DIR',ROOT_DIR.'config/');  // 配置目录
define('DOVE_DATA_DIR',ROOT_DIR.'data/');      // 数据目录
define('DOVE_EXTEND_DIR',ROOT_DIR.'extend/');  // 扩展目录
define('DOVE_PUBLIC_DIR',ROOT_DIR.'public/');  // 公共访问目录
define('DOVE_RUNTIME_DIR',ROOT_DIR.'runtime/');// 运行目录

Debug::register();
Config::get('dove','debug')?error_reporting(E_ALL):error_reporting(0);
set_include_path(get_include_path().PATH_SEPARATOR.DOVE_EXTEND_DIR.'/');
date_default_timezone_set('PRC');
ob_start();