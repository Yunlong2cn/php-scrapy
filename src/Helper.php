<?php
namespace yunlong2cn\ps;

use SuperClosure\Serializer;

class Helper
{
    public static function isWin()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
    }

    public static function merge($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if ($v instanceof UnsetArrayValue) {
                    unset($res[$k]);
                } elseif ($v instanceof ReplaceArrayValue) {
                    $res[$k] = $v->value;
                } elseif (is_int($k)) {
                    if (isset($res[$k])) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = self::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    public static function str2unix($time)
    {
        $unixTime = 0;
        if(strstr($time, '刚')) return time();
        if(preg_match('~(\d+)\s?秒~is', $time, $match)) {
            $unixTime = time() - $match[1];
            Log::debug($time . ' 转换为 UNIX 后 = ' . date('Y-m-d H:i:s', $unixTime));
        } elseif(preg_match('~(\d+)\s?分~is', $time, $match)) {
            $unixTime = time() - $match[1] * 60;
            Log::debug($time . ' 转换为 UNIX 后 = ' . date('Y-m-d H:i:s', $unixTime));
        } elseif(is_numeric($time)) {
            $unixTime = $time;
        } else {
            $unixTime = strtotime($time);
        }
        return $unixTime;
    }

    public static function unicode_decode($str)
    {
        $str = rawurldecode($str);
        preg_match_all("/(?:%u.{4})|&#x.{4};|&#\d+;|.+/U", $str, $r);
        $ar = $r[0];
        foreach($ar as $k=>$v) {
            if(substr($v,0,2) == "%u"){
                $ar[$k] = iconv("UCS-2BE","UTF-8",pack("H4", substr($v, -4)));
            }
            elseif(substr($v,0,3) == "&#x"){
                $ar[$k] = iconv("UCS-2BE","UTF-8",pack("H4", substr($v, 3, -1)));
            }
            elseif(substr($v,0,2) == "&#") {
                $ar[$k] = iconv("UCS-2BE","UTF-8",pack("n", substr($v, 2, -1)));
            }
        }
        return join("", $ar);
    }

    /**
     * 处理数组中包含回调函数的参数
     * @param $config array 待处理数组
     * @param $serialize boolean 是否返回序列化后的内容，默认只处理数组中回调函数部分
     *
     * @return array | serialize
     **/
    public static function serialize($config, $serialize = false)
    {
        $serializer = new Serializer;

        foreach ($config as $key => $value) {
            if(is_array($value)) {
                $config[$key] = self::serialize($value);
            } elseif(is_callable($value)) {
                $config[$key] = $serializer->serialize($value);
            } else {
                $config[$key] = $value;
            }
        }

        return $serialize ? serialize($config) : $config;
    }

    public static function unserialize($data, $serialize = false)
    {
        return (new Serializer)->unserialize($data);
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
                    array_pop($paths);
                    array_shift($dots);
                }
            }
            $url = implode($dots, '/');
            $path = implode($paths, '/');
            $url = $host . $path . $url;
        } elseif(!strstr($url, 'http')) {
            $paths = explode('/', $path);// /index/detail.php
            
            // 因为 path 一定是 /xxx/x.php
            array_shift($paths);
            array_pop($paths);
            
            $path = implode($paths, '/');
            $url = $host . "/$path/" . $url;
        }
        $url = strstr($url, 'http') ? $url : $scheme . '://' . $url;
        
        return $url;
    }
}