<?php

declare(strict_types=1);

namespace dove\cache;

use dove\Config;

/**
 * 文件缓存类
 * @package dove\cache
 */
class Filecache
{

    /**
     * 缓存文件根目录
     * @var string
     */
    public $path;

    /**
     * 缓存文件后缀名
     * @var string
     */
    public $suffix;

    /**
     * 压缩等级
     * @var int
     */
    public $compress_level;

    function __construct()
    {
        $config = Config::get('cache', 'file');
        $this->path = $config['path'];
        $this->suffix = $config['suffix'];
        $this->compress_level = $config['compress_level'];
        return true;
    }

    /**
     * 数值自增，返回自增后的值
     * @param string $key 标记
     * @param int $int 自增数
     * @return int|bool 自增后的值
     */
    public function inc($key, $plus = 1)
    {
        $data = $this->get($key, true);
        if ($data) {
            $time = $data['time'];
            $exp = $data['exp'];
            $int = intval($data['value']) + $plus;
            return ($this->set($key, $int, $exp, $time)) ? $int : false;
        }
        return false;
    }

    /**
     * 数值自减，返回自减后的值
     * @param string $key 标记
     * @param int $int 自减数
     * @return int|bool 自减后的值
     */
    public function dec($key, $dec = 1)
    {
        $data = $this->get($key, true);
        if ($data) {
            $time = $data['time'];
            $exp = $data['exp'];
            $int = intval($data['value']) - $dec;
            return ($this->set($key, $int, $exp, $time)) ? $int : false;
        }
        return false;
    }

    /**
     * 删除缓存，可一次性删除多个
     * @return bool
     */
    public function del()
    {
        $keys = func_get_args();
        foreach ($keys as $key) {
            $file = $this->path . $key . $this->suffix;
            if (!file_exists($file)) continue;
            unlink($file);
        }
        return true;
    }

    /**
     * 删除所有缓存，包括子目录
     * @return bool
     */
    public function clean()
    {
        $dirs = scandir($this->path);
        foreach ($dirs as $dir) {
            if ($dir != '.' && $dir != '..') {
                $sonDir = $this->path . '/' . $dir;
                if (is_dir($sonDir)) {
                    $this->clean($sonDir);
                    @rmdir($sonDir);
                } else {
                    @unlink($sonDir);
                }
            }
        }
        return true;
    }

    /**
     * 写入缓存
     * @param string $key 标签
     * @param mixed $value 值
     * @param int $exp 缓存有效期，0为永久
     * @param int $time 缓存文件创建时间戳
     * @return bool
     */
    public function set($key = '', $value = '', $exp = 0, $time = null)
    {
        // 缓存文件路径
        $file = $this->path . $key . $this->suffix;
        return file_put_contents($file, gzcompress(serialize([
            'time'  => (!$time) ? time() : $time,  // 创建时的时间戳
            'exp'   => $exp,    // 有效期
            'value' => $value,  // 内容
        ]), $this->compress_level));
    }

    /**
     * 读取缓存
     * @param string $key 标签
     * @param bool $is_source 是否输出原始内容
     * @return bool
     */
    public function get($key = '', $is_source = false)
    {
        $file = $this->path . $key . $this->suffix;
        if (file_exists($file)) {
            $data = unserialize(gzuncompress(file_get_contents($file)));
            if (intval($data['time'] + $data['exp']) > time() || $data['exp'] == 0) {
                return (!$is_source) ? $data['value'] : $data;
            }
            unlink($file);
        }
        return false;
    }
}