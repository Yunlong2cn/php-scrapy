<?php
namespace yunlong2cn\ps;

class Config
{
    public static function get($nodeName = '')
    {
        $config = require_once('./scrapy.cfg');

        if(empty($nodeName)) return $config;

        return $config[$nodeName];
    }
}