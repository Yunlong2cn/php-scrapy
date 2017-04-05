<?php
namespace yunlong2cn\ps;

use yunlong2cn\ps\field\adapter\JsonAdapter;
use yunlong2cn\ps\field\adapter\HtmlAdapter;


class Field
{
    /**
     * 解析页面中的字段
     * @param content 页面内容
     * @param fields array 字段，包含 name, selector, callback...
     * @param selector_type string 选择器类型
     * @param selector_area string 选择范围，默认整个页面
     * @return array
     **/
    public static function gets($content, $fields = [], $selector_type = 'csspath', $selector_area = '')
    {        
        if('jsonpath' == $selector_type) {
            $adapter = new JsonAdapter($content, $fields);
        } else {
            $adapter = new HtmlAdapter($content, $fields, $selector_area);
        }

        return $adapter->execute();
    }
}