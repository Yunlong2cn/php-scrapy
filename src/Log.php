<?php
namespace yunlong2cn\ps;


class Log
{
    
    public static $log_show = false;

    public static function info($message)
    {
        self::out($message, 'info');
    }

    public static function debug($message)
    {
        self::out($message, 'debug');
    }

    public static function error($message, $stop = false)
    {
        self::out($message, 'error');
        if($stop) exit;
    }

    public static function warn($message)
    {
        self::out($message, 'warn');
    }

    private function out($message, $type = 'info', $file = '')
    {
        $out = '['. strtoupper($type) .'] ' . date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
        if(empty($file)) {
            echo $out;
        } else {
            file_put_contents($file, $out, FILE_APPEND);
        }
    }
}