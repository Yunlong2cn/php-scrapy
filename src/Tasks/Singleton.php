<?php
namespace yunlong2cn\ps\Tasks;

/**
 * 单例模式运行，数据只保存在当前 php 运行实例内存中
 *
 **/
class Singleton implements TaskInterface
{
    public function set($task)
    {
        
    }

    public function get($tid)
    {
        return 0;
    }
}