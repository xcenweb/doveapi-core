<?php 
declare(strict_types=1);
namespace dove;

use Exception;
use dove\config;
use dove\tool\ArrToxml;

class Api
{
    public $started = false;
    public $config;

    public $request;
    public $response;

    // autoload
    function __construct()
    {
        $this->config = Config::get('api','*');

        // create two tool class: $this->request->xx();
        $this->request = new Request();
        $this->response = new Response($this->config);
    }

    // $this->start([...]); start
    public function start($set=[])
    {
        // if repeat is continue
        if(!$this->started){
            isset($set['origin'])?header('Access-Control-Allow-Origin:'.$set['origin']):header('Access-Control-Allow-Origin:'.$this->config['origin']);
            isset($set['method'])?header('Access-Control-Allow-Methods:'.$set['methods']):header('Access-Control-Allow-Methods:'.$this->config['methods']);
            if(!empty($set)){
                $h = isset($set['header'])?$this->config['header']+$set['header']:[];
                set_header($h);
                set_ini(isset($set['ini'])?$this->config['ini']+$set['ini']:[]);
            }
            $this->started = true;
        }
    }
}

// get post、get..
class Request
{
    public $request;

    // 获取get
    // $this->request->get('*',[]);
    public function get($name='*',$def='')
    {
        
    }
    
    // 获取post
    public function post($name='*',$def='')
    {
        
    }
    
    // 获取put
    public function put($name='*',$def='')
    {
        
    }
    
    // 获取所有的
    // return ["get"=>[],"post"=>[],"put"=>[]]
    public function all()
    {
        
    }
}

// api返回
class Response
{
    public $response;
    public $temps;
    public $uni;

    public function __construct($config)
    {
        $this->temps = $config['response_temps'];
        $this->uni = $config['response_uni'];
    }
	
	// set uni back content
	public function set_uni($uni = 'void')
	{
		$this->uni = $uni;
	}
	
    // uni back content
    public function uni()
    {
        switch($this->uni){
            case 'json':
                $this->json(func_get_arg(0));
                break;
            case 'xml':
                $this->xml(func_get_arg(0));
                break;
            case 'void':
                $this->void();
                break;
            case 'mjson':
                $this->json($this->get_temp(func_num_args(),func_get_args()));
                break;
            case 'mxml':
                $this->xml($this->get_temp(func_num_args(),func_get_args()));
                break;
        }
    }

    // 输出json,输出后停止运行
    // $this->response->json()
    public function json($arr=[])
    {
        ob_clean();
        header('Content-type: application/json;charset=utf-8');
        die(json_encode($arr,JSON_UNESCAPED_UNICODE));
    }
    
    // 输出xml,输出后停止运行
    // $this->response->xml()
    public function xml($arr=[])
    {
        ob_clean();
        header("Content-type: text/xml;charset=utf-8");
        die(ArrToxml::build($arr,'response'));
    }
	
	// 输出html，可选择压缩html
    public function html($html,$zip=false)
    {
        ob_clean();
        
        die($html);
    }

    public function void()
    {
        ob_clean();
        header('HTTP/1.1 204 No Content');
        exit;
    }

    /**
     * 模板输出xml。输出后停止运行
     * 
     * 模板示例 true => ['code'=>'int','msg'=>'text','data'=>'array']
     * 使用示例 $this->response->mxml(true,200,'值二',['值三','值四']);
     * 返回示例 <?xml version="1.0" encoding="UTF-8"?><response><code>200</code><msg>text</msg><data>array</data></response>
     *
     */
    public function mxml()
    {
        if(func_num_args()==0) throw new Exception('$this->response->mxml() 参数不能为空',500);
        $this->xml($this->get_temp(func_num_args(),func_get_args()));
    }

    /**
     * 模板输出json。输出后停止运行
     * 
     * 模板示例 true => ['code'=>'int','msg'=>'text','data'=>'array']
     * 使用示例 $this->response->mjson(true,200,'值二',['值三','值四']);
     * 返回示例 {"code":200,"msg":"值二","data":["值三","值四"]}
     *
     */
    public function mjson()
    {
        if(func_num_args()==0) throw new Exception('$this->response->mjson() 参数不能为空',500);
        $this->json($this->get_temp(func_num_args(),func_get_args()));
    }

    // get a template
    public function get_temp($arg,$args)
    {
        if(!isset($this->temps[$args[0]])) return [];
        $temp  = $this->temps[$args[0]];
        $tempNum = count($temp);
        $i = 1;
        $array = [];
        foreach($temp as $name=>$def){
            if($tempNum < $i) break;
            $array = isset($args[$i])?array_merge($array,[$name=>$args[$i]]):array_merge($array,[$name=>$def]);
            $i++;
        }
        return $array;
    }
}