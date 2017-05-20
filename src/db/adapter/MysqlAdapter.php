<?php
namespace yunlong2cn\ps\db\adapter;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

use MongoDB\Client;

use yunlong2cn\ps\Log;

use yunlong2cn\ps\db\DBInterface;

class MysqlAdapter implements DBInterface
{
    protected $db = NULL;
    protected $client;

    // $dsn = 'mysql:host=localhost;dbname=hh_main'
    public function __construct($dsn, $user, $password)
    {
        $connection = new \Nette\Database\Connection($dsn, $user, $password);
        $cacheStorage = new \Nette\Caching\Storages\MemoryStorage;
        $structure = new \Nette\Database\Structure($connection, $cacheStorage);
        $this->client = new \Nette\Database\Context($connection, $structure);
    }

    public function getDb($database)
    {
        return false;
    }

    public function find($filter, $collection, $database = NULL)
    {
        return false;
    }

    public function insert($data, $table)
    {
        return $this->client->table($table)->insert($data);
    }

    public function update($filter, $data, $collection, $database = NULL)
    {
        return false;
    }
}











