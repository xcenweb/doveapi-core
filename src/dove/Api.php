<?php

declare(strict_types=1);

namespace dove;

use Exception;
use dove\config;
use dove\tool\Arr;

/**
 * DoveAPI 框架便捷操作Request、Response
 * @package dove
 */
class Api
{
    public $started = false;
    public $config;

    /**
     * request class
     * @package Request
     */
    public $request;

    /**
     * response class
     * @package Response
     */
    public $response;

    function __construct()
    {
        $this->config = Config::get('api', '*');
        $this->request = new Request();
        $this->response = new Response($this->config);
    }

    /**
     * 初始化
     * @param array $set
     */
    public function start($set = [])
    {
        // if repeat is continue
        if (!$this->started) {
            isset($set['origin']) ? header('Access-Control-Allow-Origin:' . $set['origin']) : header('Access-Control-Allow-Origin:' . $this->config['origin']);
            isset($set['method']) ? header('Access-Control-Allow-Methods:' . $set['methods']) : header('Access-Control-Allow-Methods:' . $this->config['methods']);
            if (!empty($set)) {
                set_header(isset($set['header']) ? $this->config['header'] + $set['header'] : []);
                set_ini(isset($set['ini']) ? $this->config['ini'] + $set['ini'] : []);
            }
            $this->started = true;
        }
    }
}


/**
 * 请求
 * @package dove\Api
 */
class Request
{
    public $request;

    /**
     * 获取get
     * $this->request->get('*',[]);
     */
    public function get($name = '*', $def = '')
    {
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            if ($name == '*') return $_GET;
            if ($name == '') return isset(array_keys($_GET)[$name]) ? array_keys($_GET)[$name] : $def;
            return $_GET[$name];
        }
    }

    /**
     * 获取post
     */
    public function post($name = '*', $def = '')
    {
        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            if ($name == '*') return $_POST;
            if ($name == '') return isset(array_keys($_POST)[$name]) ? array_keys($_POST)[$name] : $def;
            return $_POST[$name];
        }
    }

    /**
     * 获取put
     */
    public function put($name = '*', $def = '')
    {
        if ('PUT' == $_SERVER['REQUEST_METHOD']) {
            parse_str(file_get_contents('php://input'), $_PUT);
            if ($name == '*') return $_PUT;
            if ($name == '') return isset(array_keys($_PUT)[$name]) ? array_keys($_PUT)[$name] : $def;
            return $_PUT[$name];
        }
    }

    /**
     * 获取所有的
     * return ["get"=>[],"post"=>[],"put"=>[]]
     */
    public function all()
    {
        return ['get' => $_GET, 'post' => $_POST, 'put' => $this->put('*', [])];
    }
}


/**
 * 响应
 * @package dove\Api
 */
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

    /** 
     * 改变统一返回形式
     * @param string $uni 返回形式
     * @return bool
     */
    public function set_uni($uni = 'json')
    {
        $this->uni = $uni;
        return true;
    }

    /**
     * 覆盖设置模板
     */
    public function set_temps($temps = [])
    {
        $this->temps = $temps;
    }

    /**
     * 统一返回内容
     * @return mixed
     */
    public function uni()
    {
        switch ($this->uni) {
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
                $this->json($this->get_temp(func_num_args(), func_get_args()));
                break;
            case 'mxml':
                $this->xml($this->get_temp(func_num_args(), func_get_args()));
                break;
        }
    }

    /**
     * 输出json,输出后停止运行
     * $this->response->json()
     */
    public function json($arr = [], $encoding = 'utf-8')
    {
        header('Content-type: application/json;charset=' . $encoding);
        die(json_encode($arr, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 输出xml,输出后停止运行
     * $this->response->xml()
     */
    public function xml($arr = [], $startElement = 'response', $version = '1.0', $encoding = 'utf-8')
    {
        // TODO 增加参数
        header("Content-type: text/xml;charset=" . $encoding);
        die(Arr::toxml($arr, $startElement, $version, $encoding));
    }

    /**
     * 输出html，可选择压缩html
     */
    public function html($html, $zip = false)
    {
        if ($zip) {
            $html = str_replace("\r\n", '', $html);
            $html = str_replace("\n", '', $html);
            $html = str_replace("\t", '', $html);
            die(preg_replace([
                "/> *([^ ]*) *</", "/[\s]+/", "/<!--[^!]*-->/", "/\" /", "/ \"/", "'/\*[^*]*\*/'"
            ], [
                ">\\1<", " ", "", "\"", "\"", ""
            ], $html));
        }
        die($html);
    }

    /**
     * 输出空值
     */
    public function void()
    {
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
        if (func_num_args() == 0) throw new Exception('$this->response->mxml() 参数不能为空', 500);
        $this->xml($this->get_temp(func_num_args(), func_get_args()));
    }

    /**
     * 模板输出json,输出后停止运行
     * 
     * 模板示例 true => ['code'=>'int','msg'=>'text','data'=>'array']
     * 使用示例 $this->response->mjson(true,200,'值二',['值三','值四']);
     * 返回示例 {"code":200,"msg":"值二","data":["值三","值四"]}
     *
     */
    public function mjson()
    {
        if (func_num_args() == 0) throw new Exception('$this->response->mjson() 参数不能为空', 500);
        $this->json($this->get_temp(func_num_args(), func_get_args()));
    }

    /**
     * 获取模板
     */
    public function get_temp($arg, $args)
    {
        if (!isset($this->temps[$args[0]])) return [];
        $temp  = $this->temps[$args[0]];
        $tempNum = count($temp);
        $i = 1;
        $array = [];
        foreach ($temp as $name => $def) {
            if ($tempNum < $i) break;
            $array = isset($args[$i]) ? array_merge($array, [$name => $args[$i]]) : array_merge($array, [$name => $def]);
            $i++;
        }
        return $array;
    }
}
