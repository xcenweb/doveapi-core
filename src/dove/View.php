<?php
namespace dove;

use Exception;
use dove\Config;

/**
 * TODO DoveAPI 框架视图类
 * @package dove
 */
class View {

    /**
     * 模板文件路径
     * @var string
     */
    public $template_path;

    /**
     * 初始化
     */
    public function __construct() {
        $this->template_path = Config::get('view', 'template_path');
    }

    /**
     * 渲染模板
     * @return string
     */
    public function render() {
        
        if (!file_exists($this->template_path)) {
            throw new Exception('模板文件不存在！');
        }

        $output = file_get_contents($this->template_path);

        return $output;
    }

}