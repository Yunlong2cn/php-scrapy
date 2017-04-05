<?php

namespace yunlong2cn\ps\demo;

use yunlong2cn\ps\Scheduler;
use yunlong2cn\ps\Work;
use yunlong2cn\ps\Helper;


use MongoDB\Client;
use SuperClosure\Serializer;
use yunlong2cn\ps\callback\Base;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

class Test
{
    public function index($id = 1)
    {
        $client = new Client('mongodb://192.168.0.136:17017');
        $sites = $client->main->sites->find();

        $scheduler = new Scheduler;

        foreach ($sites as $site) {
            $site = json_decode(json_encode($site), 1);
            $scheduler->newTask(Work::execute($site));
        }
        
        $scheduler->run();
    }

    public function webdriver($arg = 'chrome')
    {
        $host = 'http://'. $arg .'-host:4444/wd/hub';
        // $host = 'http://192.168.0.200:4445/wd/hub';
        
        if('firefox' == $arg) {
            // Launch Firefox:
            $driver = RemoteWebDriver::create($host, DesiredCapabilities::firefox());
        } elseif ('chrome' == $arg) {
            // Launch Chrome:
            $driver = RemoteWebDriver::create($host, DesiredCapabilities::chrome());
        } else {
            exit('错误的浏览器驱动');
        }      

        $driver->get('https://www.baidu.com/');
        $doc = $driver->getTitle();
        print_r($doc);


    }
}





