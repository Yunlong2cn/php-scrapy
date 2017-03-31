<?php
namespace yunlong2cn\ps\queue;

interface QInterface
{
    // 获取队列大小
    public function size();
    
    /**
     * 添加 task 到队列
     * @param task array
     * @param repeat bool 是否允许重复
     * @param head 是否入队列左侧，默认 false
     * @return boolean
     */
    public function push($task, $head = false, $repeat = false);
    
    /**
     * 在队列中取出一个任务
     * @param $head boolean 是否在前面取出，默认遵循先进先出原则
     * @return $task
     **/
    public function get($head = true);
}