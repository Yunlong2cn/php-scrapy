<?php
namespace yunlong2cn\ps;


use phpQuery;
use Flow\JSONPath\JSONPath;

class Url
{
    public static function gets($content, $link)
    {
        $selector_type = isset($link['selector_type']) ? strtolower($link['selector_type']) : 'csspath';

        if('jsonpath' == $selector_type) {
            return self::fromJsonPath($content, $link);
        } elseif('csspath' == $selector_type) {
            return self::fromCssPath($content, $link);
        }

        return false;
    }

    /**
     * JSONPATH 用于解析字段并生成 URL
     * @param $content string 被解析内容
     * @param $link array [fields, return_urls, ...]
     **/
    public function fromJsonPath($content, $link)
    {
        if(!isset($link['fields'])) {
            Log::error('启用 JSONPath 时，同时需要设置 fields 字段');
            return false;
        }
        if(!isset($link['return_url'])) return false;
        if(empty($link['return_url'])) return false;
        $data = json_decode($content, 1);
        $query = new JSONPath($data);
        
        $fields = [];
        foreach ($link['fields'] as $field) {
            if(empty($field['selector'])) continue;
            $value = $query->find($field['selector'])->data();

            if(is_array($value) && isset($field['callback'])) {
                $callback = $field['callback'];
                $value = array_map(function($d) use ($callback, $value) {
                    $callback = Helper::unserialize($callback);
                    $res = $callback($d);
                    Log::info('res = ' . $res);
                    return $res;
                }, $value);
            }

            $fields[$field['name']] = $value;
        }
        
        $returnUrls = [];
        
        if(is_string($link['return_url'])) {
            $return_url[] = $link['return_url'];
        } else {
            $return_url = $link['return_url'];
        }
        
        foreach ($return_url as $index => $returnUrl) {
            $returnUrlTpl = is_array($returnUrl) ? $returnUrl['url'] : $returnUrl;
            foreach ($fields as $key => $field) {
                foreach ($field as $k => $f) {
                    if(!strstr($returnUrlTpl, '{'. $key .'}')) continue;
                    $before = empty($returnUrls[$index][$k]['url']) ? $returnUrlTpl : $returnUrls[$index][$k]['url'];
                    if(!is_array($f)) {
                        $returnUrls[$index][$k]['url'] = str_replace("{{$key}}", $f, $before);
                        // Log::debug('替换后 URL = ' . $returnUrls[$index][$k]['url']);
                    }
                }
            }
        }

        return $returnUrls;
    }

    public function fromCssPath($content, $link)
    {
        $document = phpQuery::newDocumentHTML($content);
        if(isset($link['selector'])) {
            $selector = is_string($link['selector']) ? [$link['selector']] : $link['selector'];
            $selector_content = '';
            foreach ($selector as $value) {
                $selector_content .= $document->find($value)->html();
            }
            $document = phpQuery::newDocumentHTML($selector_content);
        }
        $urls = [];
        foreach ($document->find('a') as $arg) {
            $href = pq($arg)->attr('href');

            $urls[] = $href;
        }
        
        // 处理并优化 url
        // 1. 去除重复 url
        $urls = array_unique($urls);
        foreach ($urls as $k => $url) {
            $url = trim($url);
            if(empty($url)) {
                continue;
            }

            // 2.优化链接地址
            if($url = Helper::formatUrl($url, $link['url'])) {
                $urls[$k] = $url;
            } else {
                unset($urls[$k]);
            }
        }


        return empty($urls) ? false : [$urls];
    }
}