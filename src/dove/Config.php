<?php 
declare(strict_types=1);

namespace dove;
use Exception;
use dove\Debug;

class Config
{

    public static $_config = [];
    public static $_ext = '.php';

    /**
     * 获取配置
     */
    public static function get($name,$con='*',$def='')
    {
        if(!isset(self::$_config[$name])) {
			static::pull($name);
		}
        if($con == '*') {
			return isset(self::$_config[$name])?self::$_config[$name]:[];
		}
        return isset(self::$_config[$name][$con])?self::$_config[$name][$con]:$def;
    }

    /**
     * 临时设置或修改配置
     */
    public static function set($name,$set=[])
    {
		if(isset(self::$_config[$name])) {
			self::$_config[$name] = array_merge(self::$_config[$name], $set);
		} else {
			self::$_config[$name] = $set;
		}
		return true;
    }
    
    /**
     * 拉取配置
     */
    public static function pull($name)
    {
        // if(isset(self::$_config[$name])) return self::$_config[$name];
        $f = DOVE_CONFIG_DIR.$name.self::$_ext;
        if(!file_exists($f)) {
			throw new Exception("配置文件 {$conf} 不存在",500);
		}
        return self::$_config[$name] = require $f;
    }
}