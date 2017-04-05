<?php
namespace yunlong2cn\ps\field\adapter;

use phpQuery;
use yunlong2cn\ps\field\FieldInterface;
use yunlong2cn\ps\Log;
use yunlong2cn\ps\Helper;

class HtmlAdapter implements FieldInterface
{
    protected $content;
    protected $fields;

    public function __construct($content, $fields)
    {
        $this->content = $content;
        $this->fields = $fields;
    }

    public function execute()
    {
        $document = phpQuery::newDocumentHTML($this->content);
        
        if(empty($this->fields)) {// 未设置字段，则默认返回 html/text 数据
            $html = $document->html();
            $title = $document->find('title')->text();
            $text = $document->find('body')->html();
            $text = preg_replace([
                '~<script.*?>.*?</script>~is',
                '~<style.*?>.*?</style>~is'
            ], ['', ''], $text);
            $text = strip_tags($text);

            return [[
                'title' => $title,
                'text' => $text,
                'html' => $html
            ]];
        }

        $returnField = [];
        foreach ($this->fields as $field) {
            $temp = '';
            if(is_string($field['name'])) {
                if(isset($field['selector'])) $temp = pq($field['selector'])->text();
                if(!empty($field['callback'])) {
                    if(isset($field['selector'])) {
                        $temp = call_user_func(Helper::unserialize($field['callback']), $temp);
                    } else {
                        $temp = call_user_func(Helper::unserialize($field['callback']));
                    }
                }
                if(!empty($field['required']) && empty($temp)) return false;
                if(isset($field['save']) && $field['save'] == false) {
                    Log::debug('不保存的字段 name = ' . $field['name']);
                    continue;
                }
                $returnField[$field['name']] = $temp;
            } elseif(is_array($field['name'])) {
                foreach ($field['name'] as $k => $name) {
                    if(isset($field['selector'])) $temp = pq($field['selector'])->eq($k)->text();
                    if(!empty($field['callback'])) {
                        if(isset($field['selector'])) {
                            $temp = call_user_func(Helper::unserialize($field['callback']), $temp);
                        } else {
                            $temp = call_user_func(Helper::unserialize($field['callback']));
                        }
                    }
                    if(!empty($field['required']) && empty($temp)) return false;
                    if(isset($field['save']) && $field['save'] == false) {
                        Log::debug('不保存的字段 name = ' . $field['name']);
                        continue;
                    }
                    $returnField[$name] = $temp;
                }
            }
        }

        return [$returnField];
    }
}