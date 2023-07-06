<?php

declare(strict_types=1);

namespace dove;

use dove\Api;
use Exception;
use dove\Route;
use dove\Config;
use dove\CncodeCompile;

/**
 * DoveAPI框架核心逻辑支持
 * @package dove
 * @see Api
 */
class App extends Api
{
    /**
     * 当前被访问路径
     * @var string
     */
    public static $path;

    /**
     * 当前被访问文件
     * @var string
     */
    public static $file;

    /**
     * 当前被访问文件缓存
     * @var string
     */
    public static $cacheName;

    /**
     * 当前被访问缓存路径
     * @var string
     */
    public static $cachePath;

    public function run()
    {
        static::init();
        if (!file_exists(self::$file)) throw new Exception('路由不存在，路径[' . self::$file . ']', 404);
        if (Config::get('api', 'autoload')) $this->start();

        // 中文编译缓存支持
        if (Config::get('dove', 'cncode', false)) {
            // TODO 使 __begin.php、__coda.php 支持中文编译
            if (!file_exists(self::$cachePath)) {
                static::mk_cache();
            } else {
                // 通过修改时间判断源文件是否需要更新，生产环境下强烈建议注释掉该句
                if (filemtime(self::$cachePath) < filemtime(self::$file)) static::up_cache();
            }

            include self::$cachePath;
            return;
        }

        if (file_exists(self::$path . '__begin.php')) require self::$path . '__begin.php';
        include self::$file;
        if (file_exists(self::$path . '__coda.php')) require self::$path . '__coda.php';
        return;
    }

    public static function init()
    {
        $baseUrl  = str_replace(['//', '\\'], '/', Route::baseUrl());
        $baseUrlArr = explode('/', $baseUrl);
        $pathinfo = str_replace('\\', '/', pathinfo($baseUrl));
        $AClist = Config::get('AccessControl', '*', []);

        /**
         * 1.是否是一个禁止外部访问的一级目录
         * 2.是否是起始或结束自动加载文件
         * 3.这个方法只进行多级目录检查，但对性能影响可能会有点大，观察一下
         */
        if (in_array($baseUrlArr[1], $AClist['padlock'], true) || in_array($pathinfo['filename'], ['__begin', '__coda'], true) || in_array(preg_replace("/^\/+?|\/+?$/", '', $baseUrl), $AClist['padlock'], true)) {
            throw new Exception('目录或文件[' . $baseUrlArr[1] . ']已被设置为禁止外部访问或为起始、结束文件!', 403);
        }
        $file = ($pathinfo['dirname'] == '/' || $pathinfo['dirname'] == '.') ? ($pathinfo['basename'] == '') ? '/' . $AClist['default_file'] : '/' . $pathinfo['basename'] . '.php' : $pathinfo['dirname'] . '/' . $pathinfo['basename'] . '.php';
        self::$file = rtrim(DOVE_APP_DIR, '/') . $file;
        self::$path = pathinfo(self::$file)['dirname'] . '/';

        self::$cacheName = base64_encode($file);
        self::$cachePath = DOVE_RUNTIME_DIR . 'cache/' . self::$cacheName . '.php';
        return;
    }

    /**
     * 中文代码编译缓存
     * @return bool
     */
    public static function mk_cache()
    {
        $src_handle = fopen(self::$file, 'r');
        $cache_handle = fopen(self::$cachePath, 'w');
        fwrite($cache_handle, CncodeCompile::run(fread($src_handle, filesize(self::$file))));
        fclose($src_handle);
        fclose($cache_handle);
        return true;
    }

    /**
     * 更新中文代码编译缓存
     * @return bool
     */
    public static function up_cache()
    {
        return static::mk_cache();
    }
}
