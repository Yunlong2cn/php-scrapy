<?php
namespace yunlong2cn\ps\db\adapter;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

use yunlong2cn\ps\db\DBInterface;

class FileAdapter implements DBInterface
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
        $adapter = new Local(dirname($file), FILE_APPEND);
        $filesystem = new Filesystem($adapter);
        $filesystem->put(basename($file), date('Y-m-d H:i:s') . PHP_EOL);
        foreach ($data as $k => $v) {
            $filesystem->put(basename($file), $k . ' = ' . $v . PHP_EOL);
        }
        $filesystem->put(basename($file), '+++++++++++' . PHP_EOL . PHP_EOL);
        
        return true;
    }
}