<?php

namespace yunlong2cn\ps\demo;

use yunlong2cn\ps\Scheduler;
use yunlong2cn\ps\Work;
use yunlong2cn\ps\Helper;
use yunlong2cn\ps\Config;
use yunlong2cn\ps\Spider;


use MongoDB\Client;
use SuperClosure\Serializer;
use yunlong2cn\ps\callback\Base;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

class Test
{
    public function index($id = 1)
    {
        if(isset($_POST['config'])) {
            $config = $_POST['config'];
            $config = "<?php return $config;";
            file_put_contents('./config/spiders/temp/temp.php', $config);
            $config = require_once('./config/spiders/temp/temp.php');
            $spider = new Spider($config);
            $res = shell_exec('php /data/scrapy crawler temp/temp');
            exit;
        }

        echo "<form method='post'><textarea name='config'></textarea><input type='submit' value='测试'/></form>";
    }

    public function webdriver($arg = 'chrome')
    {
        $host = 'http://'. $arg .'-host:5555/wd/hub';
        
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

    public function fork()
    {
        $fork = new \duncan3dc\Forker\Fork;
        $a = $fork->call(function() {
            for ($i=0; $i < 10; $i++) { 
                echo "a - i = " . $i . PHP_EOL;
                sleep(1);
            }
        });

        $b = $fork->call(function() {
            for ($i=0; $i < 15; $i++) { 
                echo "b - i = " . $i . PHP_EOL;
                sleep(1);
            }
        });

        $c = $fork->call(function() {
            for ($i=0; $i < 10; $i++) { 
                echo "c - i = " . $i . PHP_EOL;
                sleep(1);
            }
        });

        print_r($fork->getPIDs());

        $fork->wait($a);
        echo "a.." . PHP_EOL;
        $fork->wait($b);
        echo "b.." . PHP_EOL;
        $fork->wait($c);
        echo "c.." . PHP_EOL;
    }

    public function spider($name) {
        $config = require_once('./config/spiders/'. $name .'.php');
        $spider = new Spider($config);
        $spider->start();
    }
}





