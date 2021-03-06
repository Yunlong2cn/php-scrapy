<?php
namespace yunlong2cn\ps;

use League\Csv\Writer;


class Db
{
    protected $adapter;

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
    }

    public function save($data, $conf = [])
    {
        $out = empty($conf['table']) ? empty($conf['file']) ? '' : $conf['file'] : $conf['table'];

        // 为数据自动加入创建时间和更新时间
        $data['created'] = empty($data['created']) ? time() : $data['created'];
        $data['updated'] = empty($data['updated']) ? time() : $data['updated'];

        if(!empty($data['unique_key'])) {// 如果设置了唯一字段检查
            $filter = [
                $data['unique_key'] => $data[$data['unique_key']]
            ];
            unset($data['unique_key']);
            if($res = $this->adapter->find($filter, $out)) {
                unset($data['created']);
                return $this->adapter->update($filter, $data, $out);
            }
        }


        if($insert = $this->adapter->insert($data, $out)) {
            // $this->adapter->insert([
            //     'url' => $data['url'],
            //     'urlmd5' => $data['urlmd5']
            // ], 'snatch_urls');
            return $insert;
        }
        return false;
    }

    public function find($filter, $collection, $database = NULL) {
        return $this->adapter->find($filter, $collection);
    }

    public function insert($data, $collection, $database = NULL) {
        return $this->adapter->insert($data, $collection, $database);
    }
}