<?php 
declare(strict_types=1);
namespace dove;

use Exception;
use dove\Log;
use dove\App;
use dove\Route;
use dove\Config;
use dove\Plugin;

class Debug
{
    public static $code; //error code
    public static $info; //error info
    public static $file; //error file
    public static $backtrace; // trace array
    public static $tplDir = DOVE_DIR.'tpl/debug/'; //template dir

    public static function register()
    {
        set_error_handler(['\\dove\\Debug','error']);
        set_exception_handler(['\\dove\\Debug','exception']);
    }
    
    // E_ALL error
    public static function error($level,$info,$file,$line)
    {
        self::$code = 500;
        self::$file = $file;
        $levels = [E_STRICT=>'Strict',E_NOTICE=>'Notice',E_WARNING=>'Warning',E_DEPRECATED=>'Deprecated',E_USER_ERROR=>'User Error',E_USER_NOTICE=>'User Notice',E_USER_WARNING=>'User Warning',E_USER_DEPRECATED=>'User Deprecated'];
        $level = isset($levels[$level])?$levels[$level]:'Unkonw error';
        self::$info = $level.': '.$info;
        self::$backtrace = array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        static::de();
    }
    
    // catch exception
    public static function exception($e)
    {
        self::$code = $e->getCode();
        self::$file = $e->getFile();
        self::$info = $e->getMessage().'在文件['.$e->getFile().'] 第'.$e->getLine().'行';
        self::$backtrace = array_reverse($e->getTrace());
        static::de();
    }

    public static function de()
    {
        $debug = Config::get('dove','debug',false);
        $debug_mode = (Config::get('dove','debug_mode','page')=='page')?true:false;
	    $debug_pe_mode = (Config::get('dove','pe_debug_mode','page')=='page')?true:false;
        // if ajax request,response json text
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])&&strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])=='xmlhttprequest'){
            Log::saveErr(self::$file,self::$info,'(ajax)');
            static::json(($debug)?'json':'pe_json');
        }
        Log::saveErr(self::$file,self::$info,($debug)?'调试模式报错':'生产环境报错');
        ($debug)?($debug_mode)?static::page('page'):static::json('json'):($debug_pe_mode)?static::page('pe_page'):static::json('pe_json');
    }

    // output html page
    public static function page($tpl)
    {
        $stack = '';
	    $line = 1;
	    foreach(self::$backtrace as $key => $val){
		    $stack.= "<kbd>{$line}.</kbd> is ";
	    	if(isset($val['class'])) $stack.='<u>'.$val['class'].'</u>'.$val['type'];
		    if(isset($val['function'])) $stack.= ($val['function']=='{closure}') ? '<font color="red"><b>{closure}</b></font>' : '<mark>'.$val['function'].'()</mark>';
		    if(isset($val['file']) && isset($val['line'])) $stack.='在文件 [<u>'.$val['file'].']</u> <b>第'.$val['line'].'行</b>';
	    	$stack.= "\r\n";
		    $line++;
	    }
	    $array = [
	        'domain'=>Route::domain(),
	        'err_code'=>self::$code,
	        'err_info'=>self::$info,
	        'err_file'=>self::$file,
	        'call_stack'=>str_replace('\\','/',$stack),
	        'get_array_list' => static::array_list($_GET),
	        'post_array_list' => static::array_list($_POST),
	        'version'=>DOVE_VERSION,
	        'exitTime'=>round(microtime(true)-DOVE_START_TIME,8),
	    ];
	    if (Plugin::exists('Compiling')) {
	        if(empty(App::$file)){
	            $uncf_content = '[File Not Found]';
	        }else{
	            $uncf_content = file_exists(App::$file)?htmlspecialchars(file_get_contents(App::$file)):'[File Not Found]';
	        }
	        if(empty(App::$cachePath))
	        {
	            $cf_content = '[File Not Found]';
	        }else{
	            file_exists(App::$cachePath)?htmlspecialchars(file_get_contents(App::$cachePath)):'[File Not Found]';
	        }
	        $array['mistake_file'] = '<div class="mdui-row"><div class="mdui-col-xs-12 mdui-col-sm-6"><div class="mdui-typo"><h3> 未编译文件 </h3><small>'.str_replace(ROOT_DIR,'',App::$file).'</small></div><pre><code>'.$uncf_content.'</code></pre></div><div class="mdui-col-xs-12 mdui-col-sm-6"><div class="mdui-typo"><h3> 编译后文件 </h3><small>'.str_replace(ROOT_DIR,'',App::$cachePath).'</small></div><pre><code>'.$cf_content.'</code></pre></div></div>';
	    } else {
	        if(empty(App::$file))
	        {
	            $content = '[File Not Found]';
	        }else{
	            $content = file_exists(App::$file)?htmlspecialchars(file_get_contents(App::$file)):'[File Not Found]';
	        }
	        $array['mistake_file'] = '<div class="mdui-typo"><h3> 发生错误的文件 </h3><small>'.str_replace(ROOT_DIR,'',App::$file).'</small></div><pre><code>'.$content.'</code></pre>';
	    }
        $value = [];
        $string= [];
        foreach($array as $val=>$str){
            $value[] = '{$'.$val.'}';
            $string[]= $str;
        }
        ob_clean();
        die(str_replace($value,$string,file_get_contents(self::$tplDir.$tpl.'.tpl')));
    }

    // output json text
    public static function json($tpl)
    {
        $stack = [];
	   	$line = 1;
	    foreach(self::$backtrace as $key => $val){
	   		if(isset($val['class'])) $stack['#'.$line]['do']=$val['class'].$val['type'];
		   	if(isset($val['function'])) $stack['#'.$line]['do']= ($val['function']=='{closure}') ? '{closure}' : $val['function'].'()';
		   	if(isset($val['file']) && isset($val['line'])) $stack['#'.$line]['in']=$val['file'].':'.$val['line'];
		    $line++;
	    }
	    $code = self::$code;
	   	$info = self::$info;
	    $file = self::$file;
	    
        $array = require self::$tplDir.$tpl.'.php';
        ob_clean();
        header('Content-type: application/json;charset=utf-8');
        die(json_encode($array,JSON_UNESCAPED_UNICODE));
    }
    
    public static function array_list($array){
        $return = '';
        foreach($array as $k=>$v){
            if($v=='') $v = '<font color="red">NULL</font>';
            $return.= "<b>$k</b> = $v<br>";
        }
        return empty($return)?'<font color="red">--Empty--</font>':$return;
    }
}