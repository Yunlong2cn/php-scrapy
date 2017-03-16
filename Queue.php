<?php
namespace yunlong2cn\spider;

class Queue
{
    public static $queue = [];
    public static $queueUrls = [];
    public static $useRedis = false;

    public static function size()
    {
        if(self::$useRedis) {
            return 0;
        }

        return count(self::$queue);
    }

    /**
     * 添加 task 到队列
     * @param task array
     * @param repeat bool 是否允许重复
     * @param head 是否入队列左侧，默认 false
     * @return boolean
     */
    public static function push($task, $head = false, $repeat = false)
    {
        
        $url = $task['url'];

        if(self::$useRedis) {
            return false;
        }

        
        $key = md5($url);
        if(!array_key_exists($key, self::$queueUrls)) {
            Log::debug('新增任务， URL = ' . $url);
            self::$queueUrls[$key] = time();
            
            if($head) {
                array_unshift(self::$queue, $task);// 插入到数组的前面
            } else {
                array_push(self::$queue, $task);// 插入到数组的末尾
            }
            
            return true;
        }
        return false;
    }

    /**
     * 在内存中取出一个队列
     * @param pos_end 是否在队尾取，默认在队前取，遵循先进先出原则
     * @return queue
     */
    public static function get($pos_end = false)
    {
        if(self::$useRedis) {
            return false;
        }

        if($pos_end) {
            return array_pop(self::$queue);
        } else {
            return array_shift(self::$queue);
        }
    }
}