<?php

namespace yunlong2cn\ps\demo;

use yunlong2cn\ps\Log;
use yunlong2cn\ps\Helper;

use Predis\Client as RedisClient;
use MongoDB\Client;

class Tools
{
    public function queue_urls($clear = false)
    {
        $client = new RedisClient('tcp://redis-host:6379');

        if($clear) {
            $keys = $client->hkeys('queue_urls');
            $ret = $client->hdel('queue_urls', $keys);
            echo "清除成功";
        } else {
            $queue_urls = $client->hgetall('queue_urls');
            print_r($queue_urls);
        }
    }

    public function queue($clear = false)
    {
        $client = new RedisClient('tcp://redis-host:6379');
    }


    /**
     * 将写好的配置文件导入数据库
     * @param force boolean 若有重名是否强制入库，若入库则更新已有 spider 慎用
     * @return
     **/
    public function spider($force = false)
    {
        $client = new Client('mongodb://192.168.0.136:17017');
        $spiders = require_once('./config/spiders.php');
        foreach ($spiders['spiders'] as $spider) {
            $data = Helper::serialize($spider);
            $filter = ['name' => $spider['name']];

            if($client->main->sites->findOne($filter)) {
                if($force) {
                    if($client->main->sites->updateOne($filter, $data)) {
                        Log::info("spider for [{$spider['name']}] 配置更新成功");
                    } else {
                        Log::error("spider for [{$spider['name']}] 配置更新失败");
                    }
                } else {
                    Log::info("spider for [{$spider['name']}] 已存在");
                }
            } else {
                if($client->main->sites->insertOne($data)) {
                    Log::info("spider for [{$spider['name']}] 配置新增成功");
                } else {
                    Log::error("spider for [{$spider['name']}] 配置更新失败");
                }
            }
        }

        Log::info('使用 ./scrapy test 启动采集');
    }


}