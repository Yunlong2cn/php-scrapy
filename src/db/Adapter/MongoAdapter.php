<?php
namespace yunlong2cn\ps\db\adapter;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

use MongoDB\Client;

use yunlong2cn\ps\Log;

use yunlong2cn\ps\db\DBInterface;

class MongoAdapter implements DBInterface
{
    protected $db = NULL;
    protected $client;

    public function __construct($uri = 'mongodb://127.0.0.1/')
    {
        $this->client = new Client($uri);

        $parse_url = parse_url(trim($uri, '/'));
        if(!empty($parse_url['path'])) {
            $database = trim($parse_url['path'], '/');
            $this->db = $this->client->$database;
        }
    }

    public function getDb($database)
    {
        if(!is_null($database)) {
            $db = $this->client->$database;
        } else {
            $db = $this->db;
        }

        if(is_null($db)) {
            Log::error('file = ' . __FILE__ . ' line number = ' . __LINE__ . ' ++ 未选择数据库，请先配置数据库或传递数据库参数 ++', true);
        }

        return $db;
    }

    public function find($filter, $collection, $database = NULL)
    {
        return $this->getDb($database)->$collection->findOne($filter);
    }

    public function insert($data, $collection, $database = NULL)
    {
        return $this->getDb($database)->$collection->insertOne($data);
    }

    public function update($filter, $data, $collection, $database = NULL)
    {
        return $this->getDb($database)->$collection->updateOne($filter, ['$set' => $data]);
    }
}











