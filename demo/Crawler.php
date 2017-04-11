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
    public function index($spider = '', $type = 'multi')
    {
        if(empty($spider)) {
            $spiders = require('./config/spiders.php');
        } else {
            $spiders = [];
            $_spiders = explode(',', $spider);
            foreach ($_spiders as $spider) {
                $spiders[] = require('./config/spiders/'. $spider .'.php');
            }
        }


        $scheduler = new Scheduler;

        foreach ($spiders as $spider) {
            $spider = Helper::merge($spider, ['type' => $type]);
            $scheduler->newTask(Work::execute(Helper::serialize($spider)));
        }
        
        $scheduler->run();

    }

    public function multi()
    {
        $spiders = ['360', 'ifeng', 'sina', 'toutiao', 'wzxc'];
        $configs = [];
        $globalConfig = Config::get();
        $doSpiders = [];
        foreach ($spiders as $spider) {
            $config = require('./config/spiders/app_news/'. $spider .'.php');
            // file_put_contents('config.txt', print_r($config, 1), FILE_APPEND);
            $config = Helper::merge($globalConfig, Helper::serialize($config));
            $configs[] = $config;
            $doSpiders[] = new Spider($config);
        }
        $fork = new \duncan3dc\Forker\Fork;

        $forkPIDs = [];
        while (true) {
            foreach ($configs as $config) {
                Log::info('准备采集 spider = ' . $config['name']);
                $spider = new Spider($config);
                $fork->call(function() use ($spider) {
                    $spider->start();
                });
            }
            $fork->wait();
            Log::info('休息一会，继续执行');
            sleep(3);
        }

        
        while (true) {
            foreach ($forkPIDs as $pid) {
                $fork->wait($pid);
            }
        }
    }
}