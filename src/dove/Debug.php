<?php

declare(strict_types=1);

namespace dove;

use dove\Log;
use dove\App;
use dove\Route;
use dove\Config;

/**
 * DoveAPI 框架调试模块
 * @package dove
 */
class Debug
{
	public static $code; //error code
	public static $info; //error info
	public static $file; //error file
	public static $backtrace; // trace array

	/**
	 * 模块注册
	 */
	public static function register()
	{
		set_error_handler(['\\dove\\Debug', 'error']);
		set_exception_handler(['\\dove\\Debug', 'exception']);
	}

	/**
	 * E_ALL抛错 (set_error_handler)
	 */
	public static function error($level, $info, $file, $line)
	{
		self::$code = 500;
		self::$file = $file;
		$levels = [E_STRICT => 'Strict', E_NOTICE => 'Notice', E_WARNING => 'Warning', E_DEPRECATED => 'Deprecated', E_USER_ERROR => 'User Error', E_USER_NOTICE => 'User Notice', E_USER_WARNING => 'User Warning', E_USER_DEPRECATED => 'User Deprecated'];
		$level = isset($levels[$level]) ? $levels[$level] : 'Unkonw error';
		self::$info = $level . ': ' . $info;
		self::$backtrace = array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
		static::exit();
	}

	/**
	 * 抓取exception并抛错 (set_exception_handler)
	 */
	public static function exception($e)
	{
		self::$code = $e->getCode();
		self::$file = $e->getFile();
		// self::$info = $e->getMessage(). ', in file [' .$e->getFile(). '], Line' .$e->getLine();
		self::$info = sprintf('%s, in file [%s], Line %d', $e->getMessage(), $e->getFile(), $e->getLine());
		self::$backtrace = array_reverse($e->getTrace());
		static::exit();
	}

	/**
	 * 抛错
	 * Fixed 2023.8.27 可读性差的问题
	 */
	public static function exit()
	{
		$debug = Config::get('dove', 'debug', false); // 是否开发环境
		$debug_mode = Config::get('debug', 'debug_mode', 'page'); // 开发环境下
		$debug_pe_mode = Config::get('debug', 'pe_debug_mode', 'json'); // 生产环境下

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			// 如果是一个ajax请求，直接返回json
			Log::debug(self::$file, self::$info, '【ajax】' . ($debug) ? '调试模式报错' : '生产环境报错');
			static::json(Config::get('debug', ($debug) ? 'debug_mode_json_path' : 'pe_debug_mode_json_path'));
		}

		Log::debug(self::$file, self::$info, ($debug) ? '调试模式报错' : '生产环境报错');
		if ($debug) {
			// 开发环境
    		if ($debug_mode == 'page') {
        		static::page(Config::get('debug', 'debug_mode_page_path'));
    		} else {
        		static::json(Config::get('debug', 'debug_mode_json_path'));
    		}
		} else {
			// 生产环境
    		if ($debug_pe_mode == 'page') {
        		static::page(Config::get('debug', 'pe_debug_mode_page_path'));
    		} else {
        		static::json(Config::get('debug', 'pe_debug_mode_json_path'));
    		}
		}
	}

	/**
	 * 输出标准html界面，加入模板变量
	 */
	public static function page($tpl)
	{
		$stack = '';
		$line = 1;
		foreach (self::$backtrace as $key => $val) {
			$stack .= sprintf("<kbd>%d.</kbd> is ", $line);
			if (isset($val['classs'])) {
				$stack .= sprintf('<u>%s</u>%s', $val['class'], $val['type']);
			}
			if (isset($val['function'])) {
				$stack .= ($val['function'] == '{closure}') ? '<font color="red"><b>{closure}</b></font>' : '<mark>' . $val['function'] . '()</mark>';
			}
			if (isset($val['file']) && isset($val['line'])) {
				$stack .= sprintf('在文件 [<u>%s</u>] 第<b>%d</b>行', $val['file'], $val['line']);
			}
			$stack .= "\r\n";
			$line++;
		}

		$array = [
			'domain' => Route::domain(),

			'err_code' => self::$code,
			'err_info' => self::$info,
			'err_file' => self::$file,

			'call_stack' => str_replace('\\', '/', $stack),
			'get_array_list' => static::array_list($_GET),
			'post_array_list' => static::array_list($_POST),
			'cookie_array_list' => static::array_list($_COOKIE),
			'server_array_list' => static::array_list($_SERVER),

			'version' => DOVE_VERSION,
			'exitTime' => round(microtime(true) - DOVE_START_TIME, 8),
		];

		/** 
		 * TODO 中文语法报错支持 再研究一下
		 *   if(empty(App::$file)){
		 *       $uncf_content = '[File Not Found]';
		 *   }else{
		 *       $uncf_content = file_exists(App::$file)?htmlspecialchars(file_get_contents(App::$file)):'[File Not Found]';
		 *   }
		 *   if(empty(App::$cachePath))
		 *   {
		 *       $cf_content = '[File Not Found]';
		 *   }else{
		 *       file_exists(App::$cachePath)?htmlspecialchars(file_get_contents(App::$cachePath)):'[File Not Found]';
		 *   }
		 *   $array['mistake_file'] = '<div class="mdui-row"><div class="mdui-col-xs-12 mdui-col-sm-6"><div class="mdui-typo"><h3> 未编译文件 </h3><small>'.str_replace(ROOT_DIR,'',App::$file).'</small></div><pre><code>'.$uncf_content.'</code></pre></div><div class="mdui-col-xs-12 mdui-col-sm-6"><div class="mdui-typo"><h3> 编译后文件 </h3><small>'.str_replace(ROOT_DIR,'',App::$cachePath).'</small></div><pre><code>'.$cf_content.'</code></pre></div></div>';
		 */
		
		// 错误定位
		if (empty(App::$file) || !file_exists(App::$file)) {
			// 目标文件不存在，不定位
			$array['mistake_file'] = '';
		} else {
			// 目标文件存在，定位错误
			$content = htmlspecialchars(file_get_contents(App::$file));
			$array['mistake_file'] = '<div class="mdui-typo">
									      <h3> 发生错误的文件 </h3>
										  <small>/' . str_replace(ROOT_DIR, '', App::$file) . '</small>
									  </div>
									  <pre><code>' . $content . '</code></pre>';
		}
		
		// 模板变量替换
		$value = [];
		$string = [];
		foreach ($array as $val => $str) {
			$value[] = '{$' . $val . '}';
			$string[] = $str;
		}

		ob_clean();

		header('Content-type: text/html;charset=utf-8');
		die(str_replace($value, $string, file_get_contents($tpl)));
	}

	/**
	 * 输出标准的json格式界面，赋值方法内部php变量
	 */
	public static function json($tpl)
	{
		$stack = [];
		$line = 1;
		foreach (self::$backtrace as $key => $val) {
			if (isset($val['class'])) {
				$stack['#' . $line]['do'] = $val['class'] . $val['type'];
			}
			if (isset($val['function'])) {
				$stack['#' . $line]['do'] = ($val['function'] == '{closure}') ? '{closure}' : $val['function'] . '()';
			}
			if (isset($val['file']) && isset($val['line'])) {
				$stack['#' . $line]['in'] = $val['file'] . ':' . $val['line'];
			}
			$line++;
		}

		$code = self::$code;
		$info = self::$info;
		$file = self::$file;
		$array = require $tpl;

		ob_clean();

		header('Content-type: application/json;charset=utf-8');
		die(json_encode($array, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * 解析数组
	 * @param array $array
	 * @return string
	 */
	public static function array_list($array = [])
	{
		$return = '';
		foreach ($array as $k => $v) {

			if ($v == '') $v = '<font color="red">NULL</font>';

			if (is_numeric($v)) $v = '<font color="blue">' . $v . '</font>';

			if ($v == 'false') $v = '<font color="red">' . $v . '</font>';
			if ($v == 'true') $v = '<font color="green">' . $v . '</font>';

			$return .= "<b>$k</b> = $v<br>";
		}
		return empty($return) ? '<font color="red">--Empty--</font>' : $return;
	}
}