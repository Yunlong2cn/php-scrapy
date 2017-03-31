<?php
namespace yunlong2cn\ps;

class Work
{
    public function test()
    {
        $spider = new Spider([
            'name' => 'Lance',
            'scan_urls' => ['http://lance.moe/page/1'],
            'domains' => ['lance.moe'],
            'list_url_regexes' => ['http://lance.moe/page/\d+'],
            'content_url_regexes' => ['http://lance.moe/post-\d+.html'],
            'queue' => 'redis@tcp://redis-host:6379',
            'fields' => [
                [
                    'name' => 'title',
                    'selector' => '.article-title'
                ]
            ],
            // 'export' => [
            //     'type' => 'file',
            //     'file' => '/data/lance.txt'
            // ],
            'export' => [
                'type' => 'mongo',
                'uri' => 'mongodb://192.168.0.136:17017/vvv',
                'table' => 'lance'
            ],
            'data' => ['unique_key' => 'urlmd5']
        ]);
        $spider->start();
        yield;
    }

    public static function execute($config)
    {
        $spider = new Spider($config);
        $spider->start();
        yield;
    }
}