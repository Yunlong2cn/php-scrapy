<?php
namespace yunlong2cn\ps;

use phpQuery;
use Flow\JSONPath\JSONPath;

class Field
{
    public static function gets($content, $fields = [], $selector_type = 'csspath')
    {        
        if('jsonpath' == $selector_type) {
            return self::fromJson($content, $fields);
        } else {
            return self::fromHtml($content, $fields);
        }
    }

    public static function fromJson($content, $fields)
    {
        if(empty($fields)) {// 未设置字段，JSONPATH 模式下返回 false
            return false;
        }

        $data = json_decode($content, 1);
        $query = new JSONPath($data);
        
        $returnFields = [];
        $value = NULL;
        foreach ($fields as $field) {
            if(empty($field['selector'])) {
                if(!is_array($value)) {
                    Log::debug('不能将未设置 selector 的字段放在第一位');exit;
                }
            } else {
                $value = $query->find($field['selector'])->data();
            }

            $tempValue = NULL;
            foreach ($value as $k => $v) {

                $tempValue = empty($field['selector']) ? empty($field['value']) ? '' : $field['value'] : $v;

                if(isset($field['required']) && $field['required'] && empty($tempValue)) {
                    Log::debug('必需的字段 '. $field['name'] .' 为空，跳过此数据');
                    return false;
                }

                if(isset($field['save']) && $field['save'] == false) {
                    Log::debug('不保存的字段 name = ' . $field['name']);
                    continue;
                }

                if(is_null($tempValue)) {
                    Log::debug('字段 name = ' . $field['name'] . ' 为 NULL');
                    continue;
                }

                $tempValue = method_exists($tempValue, 'data') ? $tempValue->data() : $tempValue;

                if(isset($field['callback'])) {
                    $callback = Helper::unserialize($field['callback']);
                    $tempValue = $callback($tempValue);
                }

                $tempValue = empty($tempValue) ? empty($field['value']) ? '' : $field['value'] : $tempValue;

                $returnFields[$k][$field['name']] = $tempValue;
            }
            unset($tempValue);
        }
        unset($value);
        
        return $returnFields;
    }

    public static function fromHtml($content, $fields)
    {
        $document = phpQuery::newDocumentHTML($content);
        
        if(empty($fields)) {// 未设置字段，则默认返回 html/text 数据
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
        foreach ($fields as $field) {
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
                    $returnField[$name] = $temp;
                }
            }
        }

        return [$returnField];
    }
}