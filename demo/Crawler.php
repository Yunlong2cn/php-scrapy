<?php

namespace yunlong2cn\ps\demo;

use yunlong2cn\ps\Log;
use yunlong2cn\ps\Helper;
use yunlong2cn\ps\Scheduler;
use yunlong2cn\ps\Work;
use yunlong2cn\ps\Spider;
use yunlong2cn\ps\Config;

use MongoDB\Client;

class Crawler
{
    /**
     * 通过配置文件执行采集
     * @param spider 为空，则执行所有爬虫进行采集
     *
     **/
    public function index($spider = '')
    {
        $globalConfig = Config::get();
        $config = require(ROOT . '/config/spiders/'. $spider .'.php');
        $config = Helper::merge($globalConfig, Helper::serialize($config));
        $doSpider = new Spider($config);
        $doSpider->start();
    }

    public function multi()
    {
        $spiders = ['360', 'ifeng', 'sina', 'toutiao', 'wzxc'];
        $configs = [];
        $globalConfig = Config::get();


        foreach ($spiders as $key => $spider) {
            $config = require('./config/spiders/app_news/'. $spider .'.php');
            $config = Helper::merge($globalConfig, Helper::serialize($config));
            $configs[$key] = $config;
        }

        $fork = new \duncan3dc\Forker\Fork;

        $threads = [];
        while (true) {
            foreach ($spiders as $key => $spider) {
                $pids = $fork->getPIDs();
                if(empty($threads[$spider]) || !in_array($threads[$spider], $pids)) {
                    $doSpider = new Spider($configs[$key]);
                    $threads[$spider] = $fork->call(function() use ($doSpider){
                        $doSpider->start();
                    });
                    Log::info('启动新进程 spider = '. $spider .', pid = ' . $threads[$spider]);
                }
            }
            $fork->wait();
            sleep(3);
        }
    }

    // 单进程采集
    public function start()
    {
        $root = ROOT;
        
        $spiders = ['360', 'ifeng', 'sina', 'toutiao', 'wzxc'];
        foreach ($spiders as $spider) {
            $cmd = "/usr/bin/lockf -s -t 0 $root/lockfiles/$spider.lock $root/scrapy crawler app_news/$spider >> $root/logs/$spider.log &";
            echo $cmd . PHP_EOL;
            system($cmd);
        }
    }
}