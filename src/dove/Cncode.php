<?php 
namespace dove;
// DoveAPI中文语法输出输入支持
// TODO echo preg_replace('/输出 (.*)\;/','Cncode::echo($1)',$v);
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