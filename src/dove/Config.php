<?php

declare(strict_types=1);

namespace dove;

use Exception;

/**
 * DoveAPI 框架配置模块
 * @package dove
 */
class Config
{
    /**
     * 所有已加载配置
     * @var array
     */
    public static $_config = [];

    /**
     * 配置文件后缀名
     * @var string
     */
    public static $_ext = '.php';

    /**
     * 获取配置
     * @param string $name 配置名
     * @param string $con 获取的范围
     * @param mixed $def 默认的内容
     * @return mixed 配置内容
     */
    public static function get($name, $con = '*', $def = '')
    {
        if (!isset(self::$_config[$name])) {
            static::pull($name);
        }
        if ($con == '*') {
            return isset(self::$_config[$name]) ? self::$_config[$name] : [];
        }
        return isset(self::$_config[$name][$con]) ? self::$_config[$name][$con] : $def;
    }

    /**
     * 临时设置或修改配置
     * @param string $name 配置名
     * @param array 覆盖的内容
     * @return bool
     */
    public static function set($name, $set = [])
    {
        if (isset(self::$_config[$name])) {
            self::$_config[$name] = array_merge(self::$_config[$name], $set);
        } else {
            self::$_config[$name] = $set;
        }
        return true;
    }

    /**
     * 拉取配置
     * @param string $clconfigaconfigss 配置文件名
     * @return array 该配置文件全部内容
     */
    public static function pull($config)
    {
        $file = DOVE_CONFIG_DIR . $config . self::$_ext;
        if (!file_exists($file)) {
            throw new Exception("配置文件 {$config} 不存在", 500);
        }
        return self::$_config[$config] = require $file;
    }
}