<?php

namespace dove;

/**
 * DoveAPI中文语法编译支持
 * @package dove
 */
class CncodeCompile
{

	/**
	 * 开始编译并输出
	 * @param string $v 中文代码
	 * @return string 编译后代码
	 */
	public static function run($v = '')
	{
		$v = static::namespace($v);
		$v = static::var_dump($v);
		$v = static::echo($v);
		$v = static::value($v);

		return $v;
	}

	/**
	 * namespace 中文化
	 * @param string $v
	 * @return string
	 */
	public static function namespace($v)
	{
		// @note 使用 xx; -> use xx;
		return preg_replace('/使用类名\s(.*)\;/', 'use $1;', $v);
	}

	/**
	 * var_dump 函数中文化
	 * @param string $v
	 * @return string
	 */
	public static function var_dump($v)
	{
		// TODO 输出 xx 类型; -> var_dump(xx);
		return preg_replace('/输出\s(.*)\s的类型(\;|\；)/', 'var_dump($1);', $v);
	}

	/**
	 * 变量
	 * @param string $v
	 * @return string
	 */
	public static function value($v)
	{
		// @note 设置 变量a 的值为 xx;
		preg_match_all('/设置变量\s[a-zA-Z_]\s的值为\s([\s\S]*?)(\;|\；)/', $v, $array);
		$i = 0;
		foreach ($array[0] as $code) {
			// 截取变量名
			$value_name = preg_replace('/设置变量\s([a-zA-Z_])\s(的值为)\s([\s\S]*?)(\;|\；)/', '$1', $code);
			$v = static::is_int($array[1][$i]) ? str_replace($code, '$' . $value_name . ' = ' . $array[1][$i] . ';', $v) : str_replace($code, '$' . $value_name . ' = "' . $array[1][$i] . '";', $v);
			$i++;
		}
		return $v;
	}

	/**
	 * echo
	 * @param string $v
	 * @return string
	 */
	public static function echo($v)
	{
		// @note 输出 xxx; -> Cncode::echo(xxx);
		return static::to_cncode_func($v, '/输出\s([\s\S]*?)(\;|\；)/', 'Cncode::echo(', ');');

		// preg_match_all('/输出\s([\s\S]*?)(\;|\；)/', $v, $array);
		// $i = 0;
		// foreach($array[0] as $code){
		// 	if(isset($array[1][$i])) {
		// 		$v = static::is_int($array[1][$i]) ? str_replace($code, 'Cncode::echo('.$array[1][$i].');',$v) : str_replace($code, 'Cncode::echo("'.$array[1][$i].'");',$v);
		// 	}
		// 	$i++;
		// }
		// return $v;
	}

	/** 
	 * 判断给定字符串是否是一个数值或数组
	 * @param string $str
	 * @return bool
	 */
	public static function is_int($str)
	{
		return (preg_match('/^\d*$/', $str) || preg_match('/^(-?\d+)(.\d+)?$/', $str) || preg_match('/(^\[)[\s\S]*?(\]$)/', $str));
	}

	/**
	 * 替换目标中文代码为追加定义内容，非数值或数组值自动加上引号
	 * @param mixed $old_val 待处理字符串
	 * @param string $rule 处理规则
	 * @param string $t_front 追加前字符串
	 * @param string $t_end 追加后字符串
	 * @return string
	 */
	public static function to_cncode_func($old_val, $rule, $t_front, $t_end)
	{
		preg_match_all($rule, $old_val, $array);
		$i = 0;
		foreach ($array[0] as $code) {
			if (isset($array[1][$i])) {
				if (static::is_int($array[1][$i])) {
					$old_val = str_replace($code, $t_front . $array[1][$i] . $t_end, $old_val);
				} else {
					$old_val = str_replace($code, $t_front . '"' . $array[1][$i] . '"' . $t_end, $old_val);
				}
			}
			$i++;
		}
		return $old_val;
	}
}