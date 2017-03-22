<?php
namespace yunlong2cn\spider;


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
}