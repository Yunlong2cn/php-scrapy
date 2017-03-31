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
                        Log::debug('替换后 URL = ' . $returnUrls[$index][$k]['url']);
                    }
                }
            }
        }

        return $returnUrls;
    }

    public function fromCssPath($content, $link)
    {
        $document = phpQuery::newDocumentHTML($content);
        $urls = [];
        foreach ($document->find('a') as $arg) {
            $urls[] = pq($arg)->attr('href');
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
            if($url = self::formatUrl($url, $link['url'])) {
                $urls[$k] = $url;
            } else {
                unset($urls[$k]);
            }
        }
        return empty($urls) ? false : [$urls];
    }

    public static function formatUrl($url, $collect_url)
    {
        if('' == $url) return false;

        if(preg_match('~^(javascript:|#|\'|")~is', $url)) return false;

        $parseUrl = parse_url($collect_url);
        if(empty($parseUrl['scheme']) || empty($parseUrl['host'])) return false;
        // 若解析的协议不是 http/https 则移除
        if(!in_array($parseUrl['scheme'], ['http', 'https'])) return false;
        extract($parseUrl);//将数组中的 index 解压为变量输出 scheme, host, path, query, fragment

        $base = $scheme . '://' . $host;
        // $basePath = $base . $path;

        if('//' == substr($url, 0, 2)) {
            $url = str_replace('//', '', $url);
        } elseif('/' == $url[0]) {// 说明是绝对路径
            $url = $host . $url;
        } elseif('.' == $url[0]) {// 说明是相对路径
            $dots = explode('/', $url); // ../../x.html
            $paths = explode('/', $path); // /a/b/c/d
            foreach ($dots as $dot) {
                if('..' == $dot) {
                    $paths = array_pop($paths);
                    $dots = array_shift($dots);
                }
            }
            $url = implode($dots, '/');
            $path = implode($paths, '/');
            $url = $host . $path . $url;
        }
        $url = strstr($url, 'http') ? $url : $scheme . '://' . $url;


        // $parse_url = parse_url($url);
        // if(!empty($parse_url['host'])) {
        //     if(!in_array($parse_url['host'], self::$config['domains'])) {
        //         return false;
        //     }
        // }
        return $url;
    }
}