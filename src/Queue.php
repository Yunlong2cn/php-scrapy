<?php
namespace yunlong2cn\ps;

class Queue
{
    protected $adapter = null;

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
    }

    public function size()
    {
        return $this->adapter->size();
    }

    public function push($task, $head = false, $repeat = false)
    {
        return $this->adapter->push($task, $head, $repeat);
    }

    public function get($head = false)
    {
        return $this->adapter->get($head);
    }
}