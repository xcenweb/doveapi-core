<?php

declare(strict_types=1);

namespace dove\tool;

use dove\Debug;

/**
 * 数组操作助手类
 * @package dove\tool
 */
class Arr
{
    /**
     * 将一个多维数组拆分成 [键 => 值]
     * @param array $data 待处理数组
     */ 
    public static function divide($array)
    {
        return [array_keys($array), array_values($array)];
    }

    // 输出被打乱的 $array 或输出被打乱的 $array 的第 $count 个值
    // $print 值为true（默认）时输出被打乱的数组，为 false 时输出打乱数组的第 $count 的值
    public static function random($array, $print = true, $count = 0)
    {
        shuffle($array);
        return $print ? $array : $array[$count];
    }

    /**
     * 数组转xml
     * edit from https://github.com/jmarceli/array2xml thank :)
     * @param array $data 将要转成数组的数组
     * @param string $startElement 初始元素标记
     * @param string $version xml版本
     * @param string $encoding xml编码
     * @return xml
     */
    public static function toxml($data, $startElement = 'data', $version='1.0', $encoding='UTF-8')
    {
        $xml = new \XmlWriter();
        $xml->openMemory();
        $xml->startDocument($version, $encoding);
        $xml->startElement($startElement);

        $data = static::toxml_writeAttr($xml, $data);
        static::toxml_writeEl($xml, $data);

        $xml->endElement();
        return $xml->outputMemory(true);
    }
    private static function toxml_writeAttr(\XMLWriter $xml, $data)
    {
        if (is_array($data)) {
            $nonAttributes = [];
            foreach ($data as $key => $val) {
                if ($key[0] == '@') {
                    $xml->writeAttribute(substr($key, 1), $val);
                } else if ($key[0] == '%') {
                    if (is_array($val)) $nonAttributes = $val;
                    else $xml->text($val);
                } elseif ($key[0] == '#') {
                    if (is_array($val)) $nonAttributes = $val;
                    else {
                        $xml->startElement(substr($key, 1));
                        $xml->writeCData($val);
                        $xml->endElement();
                    }
                } else if ($key[0] == "!") {
                    if (is_array($val)) $nonAttributes = $val;
                    else $xml->writeCData($val);
                } else $nonAttributes[$key] = $val;
            }
            return $nonAttributes;
        } else return $data;
    }
    private static function toxml_writeEl(\XMLWriter $xml, $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && !(bool)count(array_filter(array_keys($value), 'is_string'))) {
                foreach ($value as $itemValue) {
                    if (is_array($itemValue)) {
                        $xml->startElement($key);
                        $itemValue = static::toxml_writeAttr($xml, $itemValue);
                        static::toxml_writeEl($xml, $itemValue);
                        $xml->endElement();
                    } else {
                        $itemValue = static::toxml_writeAttr($xml, $itemValue);
                        $xml->writeElement($key, "$itemValue");
                    }
                }
            } else if (is_array($value)) {
                $xml->startElement($key);
                $value = static::toxml_writeAttr($xml, $value);
                static::toxml_writeEl($xml, $value);
                $xml->endElement();
            } else {
                $value = static::toxml_writeAttr($xml, $value);
                $xml->writeElement($key, "$value");
            }
        }
    }
}