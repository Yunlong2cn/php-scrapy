<?php
namespace yunlong2cn\ps\Tasks;


interface TaskInterface
{

    public function set($task);

    public function get($taskId);
}