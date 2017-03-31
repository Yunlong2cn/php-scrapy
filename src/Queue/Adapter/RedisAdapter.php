<?php
namespace yunlong2cn\ps\queue\adapter;

use yunlong2cn\ps\queue\QInterface;

use Predis\Client;

class RedisAdapter implements QInterface
{
    protected $client = null;

    protected $snatched_urls = [];

    public function __construct($uri = 'tcp://127.0.0.1:6379')
    {
        $this->client = new Client($uri);
    }

    public function size()
    {
        return $this->client->llen('queue');
    }

    public function push($task, $head = false, $repeat = false)
    {
        $key = md5($task['url']);

        if($this->client->hexists('queue_urls', $key) && $repeat == false) {
            return false;
        }

        $this->client->hset('queue_urls', $key, $task['url']);

        return $head ? $this->client->lpush('queue', serialize($task)) : $this->client->rpush('queue', serialize($task));
    }

    public function get($head = true)
    {
        $ret = $head ? $this->client->lpop('queue') : $this->client->rpop('queue');
        return unserialize($ret);
    }
}



