<?php
namespace yunlong2cn\ps\db\adapter;

use yunlong2cn\ps\Log;

use yunlong2cn\ps\db\DBInterface;

class LogAdapter implements DBInterface
{
    public function find($filter, $file)
    {
        return false;
    }

    public function update($filter, $data, $file)
    {
        return false;
    }

    public function insert($data, $file)
    {
        Log::info('++++++++++++++');
        Log::info(serialize($data));
        Log::info('+');
        
        return true;
    }
}