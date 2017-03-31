<?php
namespace yunlong2cn\ps\db;

interface DBInterface
{
    // 查一条数据
    public function find($filter, $table);
    
    // 插入一条数据
    public function insert($data, $table);
    
    // 更新一条数据
    public function update($filter, $data, $table);
}