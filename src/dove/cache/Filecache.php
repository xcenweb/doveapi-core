<?php
declare(strict_types=1);
namespace dove\cache;
use dove\Config;

class Filecache {

    public $path;
    public $suffix;
    public $compress_level;

    function __construct()
    {
        $c = Config::get('cache','file');
        $this->path = $c['path'];
        $this->suffix = $c['suffix'];
        $this->compress_level = $c['compress_level'];
        return true;
    }

    /**
	 * 设置缓存，数组自动序列化
	 * @param string $key 标记
	 * @param mixed $value 缓存值
	 * @param int $exp 缓存时间，0为永久
	 * @return bool
	 */
    // FIXME 缓存时间无效的问题
    public function set($key, $value = '', $exp = 0){
        $f = $this->path.$key.$this->suffix;
        file_put_contents($f,gzcompress(strval(is_array($value)?serialize($value):$value),$this->compress_level));
        if($exp == 0){
            return touch($f,-28800);
        }
        return touch($f,time()+$exp);
    }

	/**
	 * 读取缓存，数组自动去序列化
	 * @param string $key 标记
	 * @param bool $delp 过期是否返回值
	 * @return mixed
	 */
    public function get($key, $delp = false){
        $f = $this->path.$key.$this->suffix;
        if(!file_exists($f)) return false;
        $data = gzuncompress(file_get_contents($f));
        if(filemtime($f) < time()){
            unlink($f);
            if(!$delp) return false;
        }
        return (preg_match("/^a\:[0-9]\:\{.*\}/",$data)==1)?unserialize($data):$data;
    }

    /**
	 * 数值自增，返回自增后的值
	 * @param string $key 标记
	 * @param int $int 自增数
	 * @return int
	 */ 
    public function inc($key, $int = 1){
        $f = $this->path.$key.$this->suffix;
        if(!file_exists($f)) return false;
        $int = intval(gzuncompress(file_get_contents($f)))+$int;
        file_put_contents($f,gzcompress(strval($int),$this->compress_level));
        return $int;
    }

	/**
	 * 数值自减，返回自减后的值
	 * @param string $key 标记
	 * @param int $int 自减数
	 * @return int
	 */
    public function dec($key, $int = 1){
        $f = $this->path.$key.$this->suffix;
        if(!file_exists($f)) return false;
        $int = intval(gzuncompress(file_get_contents($f)))-$int;
        file_put_contents($f,gzcompress(strval($int),$this->compress_level));
        return $int;
    }

	/**
	 * 删除缓存，可一次性删除多个
	 * @return bool
	 */
    public function del(){
        $keys = func_get_args();
        foreach($keys as $key){
            $f = $this->path.$key.$this->suffix;
            if(!file_exists($f)) continue;
            unlink($f);
        }
        return true;
    }

	/**
	 * 删除所有缓存，包括子目录
	 * @return bool
	 */
    public function clean(){
        $dirs = scandir($this->path);
        foreach ($dirs as $dir) {
            if ($dir != '.' && $dir != '..') {
                $sonDir = $this->path.'/'.$dir;
                if(is_dir($sonDir)){
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
     * 写入缓存,如果该文件已经存在则不改变文件时间写入
     * @param string $tag 标签
     * @param mixed $content 值
     * @param int $time 指定文件时间，默认当前时间戳
     * @return string
     */
    private function write($tag = '',$value = '',$time = 0)
    {
        $file = $this->path.$tag.$this->suffix;
        if($time == 0) $time = time();
        // 压缩内容，数组自动序列化
        $value = gzcompress(strval(is_array($value) ? serialize($value) : $value), $this->compress_level);
        if(file_exists($file)){
            // 缓存文件存在
        }
    }

    /**
     * 读取缓存
     * @param string $tag 标签
     * @param string $key 标记
     * @return string
     */
    private function read($tag = '',$key = '')
    {
        $file = $this->path.$tag.$this->suffix;
        // 读取并解压内容，数组自动去序列化
        $data = gzuncompress(file_get_contents($file));
        // TODO 若值全为数字 => intval()
        return (preg_match("/^a\:[0-9]\:\{.*\}/",$data) == 1) ? unserialize($data) : $data;
    }
}