<?php

declare(strict_types=1);

namespace dove;

use Exception;
use dove\Route;

/**
 * DoveAPI 框架日志模块
 * @package dove
 */
class Log
{

    /**
     * 添加一条日志
     */
    public static function add($add = [], $type = 'NOTE')
    {
        if (!Config::get('log', 'log_switch', true)) {
            return true;
        }

        // ['a'=>123] -> a: 123
        $content = "\r\n[{$type}][" . date('Y-m-d H:i:s') . '][' . get_ip() . ']';
        $content .= "\r\n-请求链接: " . Route::url(true);
        $content .= "\r\n-加载时间: " . round(microtime(true) - DOVE_START_TIME, 8) . "(s)\r\n";
        foreach ($add as $name => $value) $content .= "-{$name}: {$value}\r\n";

        return static::write($content, DOVE_RUNTIME_DIR.'log/'.date('Ym'), date('d').'.log');
    }

    /**
     * 针对Debug模块的日志记录方法
     */
    public static function debug($errFile = 'null', $errInfo = 'null', $remarks = 'false')
    {
        if (!Config::get('log', 'debug_log') || !Config::get('log', 'log_switch', true)) {
            return true;
        }

        $content = "\r\n[ERROR][" . date('Y-m-d H:i:s') . '][' . get_ip() . ']';
        $content .= "\r\n-请求链接: " . Route::url(true);
        $content .= "\r\n-文件路径: " . $errFile;
        $content .= "\r\n-报错内容: " . $errInfo;
        $content .= "\r\n-加载时间: " . round(microtime(true) - DOVE_START_TIME, 8) . '(s)';
        $content .= "\r\n-日志备注: " . $remarks ."\r\n";

        return static::write($content, DOVE_RUNTIME_DIR.'log/'.date('Ym'), date('d').'.log');
    }

    /**
     * 写入日志文件
     */
    private static function write($text, $path, $filename)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return file_put_contents($path.'/'.$filename, $text, FILE_APPEND|LOCK_EX);
    }
}