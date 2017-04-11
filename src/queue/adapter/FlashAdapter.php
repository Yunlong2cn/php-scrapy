<?php
namespace yunlong2cn\ps\queue\adapter;

use yunlong2cn\ps\queue\QInterface;

class FlashAdapter implements QInterface
{
    protected $queue = [];
    protected $queueUrls = [];

    public function size()
    {
        return count($this->queue);
    }

    public function push($task, $head = false, $repeat = false)
    {
        $url = $task['url'];

        $key = md5($url);
        if(!array_key_exists($key, $this->queueUrls)) {
            $this->queueUrls[$key] = time();
            
            if($head) {
                array_unshift($this->queue, $task);// 插入到数组的前面
            } else {
                array_push($this->queue, $task);// 插入到数组的末尾
            }
            
            return true;
        }
        return false;
    }

    public function get($head = true)
    {
        if($head) {
            return array_shift($this->queue);
        } else {
            return array_pop($this->queue);
        }
    }
}