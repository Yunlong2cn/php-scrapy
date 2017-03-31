<?php
return [
    'name' => 'wodai',
    'domains' => ['www.wodai.com'],
    'scan_urls' => ['http://www.wodai.com/shuju/0-0-0-20101101-20180208.html'],
    'is_allow_update' => false,
    'list_url_regexes' => ['http://www.wodai.com/shuju/0-0-0-20101101-20180208.html'],
    'content_url_regexes'=>['http://www.wodai.com/\w+/$'],
    'fields' => [
        [
            'name' => 'name',
            'selector' => 'div.bigtitle h1>strong',
            'required' => true
        ], [
            'name' => ['a1', 'b1', 'c1', 'd1', 'e1'],
            'selector' => 'div.border-top-dashed>div.one>span'
        ], [
            'name' => ['a2', 'b2', 'c2', 'd2', 'e2'],
            'selector' => 'div.border-top-dashed>div.two>span'
        ], [
            'name' => ['a3', 'b3', 'c3', 'd3', 'e3'],
            'selector' => 'div.border-top-dashed>div.three>span'
        ],
    ],
    'export' => [
        'type' => 'mongo',
        'uri' => 'mongodb://192.168.0.136:17017/vvv',
        'table' => 'wodai'
    ]
];