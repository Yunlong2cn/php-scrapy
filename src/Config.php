<?php
namespace yunlong2cn\ps;

class Config
{
    public static $config;

    public static function get($nodeName = '')
    {
        if(empty(self::$config)) {
            self::$config = require_once('./scrapy.cfg');
        }

        if(empty($nodeName)) return self::$config;

        return self::$config[$nodeName];
    }
}