<?php

namespace yunlong2cn\ps\demo;

use yunlong2cn\ps\Log;
use yunlong2cn\ps\Helper;
use yunlong2cn\ps\Scheduler;
use yunlong2cn\ps\Work;

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
}