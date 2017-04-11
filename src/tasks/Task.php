<?php
namespace yunlong2cn\ps\Tasks;

class Task
{
    protected $taskId;
    protected $coroutine;

    protected $sendValue = NULL;
    protected $beforeFirstYeild = TRUE;

    public function __construct($taskId, \Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
    }

    public function getTaskId()
    {
        return $this->taskId;
    }

    public function setSendValue($sendValue)
    {
        $this->sendValue = $sendValue;
    }

    public function run()
    {
        if($this->beforeFirstYeild) {
            $this->beforeFirstYeild = FALSE;
            return $this->coroutine->current();     
        } else {
            $ret = $this->coroutine->send($this->sendValue);
            $this->sendValue = null;
            return $ret;
        }
    }

    public function isFinished()
    {
        return !$this->coroutine->valid();
    }
}