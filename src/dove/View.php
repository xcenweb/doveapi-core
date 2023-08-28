<?php
namespace dove;

use Exception;
use dove\Config;

/**
 * DoveAPI 框架视图类
 * @package dove
 */
class View {

    /**
     * 模板文件路径
     * @var string
     */
    protected $template;

    public function __construct() {
        // Config::get('view', '*', []);
    }

    /**
     * 输出模板
     */
    public function render() {
        
        if (!file_exists($this->template)) {
            throw new Exception("模板文件不存在！");
        }

        $output = file_get_contents($this->template);

        return $output;
    }

}