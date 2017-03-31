<?php
namespace yunlong2cn\ps\db\adapter;

use yunlong2cn\ps\db\DBInterface;

class CsvAdapter implements DBInterface
{
    public static $csvHandle = NULL;

    public function find($filter, $file)
    {
        return false;
    }

    public function update($filter, $data, $file)
    {
        return false;
    }

    public static function insert($data, $file)
    {
        if(self::$csvHandle == NULL) self::$csvHandle = Writer::createFromFileObject(new \SplTempFileObject());
        self::$csvHandle->insertOne($data);

        return self;
    }

    public static function output($file)
    {
        return self::$csvHandle->output($file);
    }
}