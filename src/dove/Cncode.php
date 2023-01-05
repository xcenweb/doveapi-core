<?php 
namespace dove;

/**
 * DoveAPI中文语法输出输入支持
 * @package dove
 */ 
class Cncode {
	public static function echo($v)
	{
		if(is_array($v)){
			print_r($v);
		} else {
			echo $v;
		}
	}
}