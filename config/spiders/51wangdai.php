<?php
return [
    'name' => '51网贷',
    'scan_urls' => ['http://www.51wangdai.com/know/p/1'],
    'domains' => ['www.51wangdai.com'],
    'is_allow_update' => false,
    'export' => [
        'type' => 'db',
        'table' => 'wangdai_platforms'
    ],
    'list_url_regexes' => ['http://www.51wangdai.com/know/p/\d+'],
    'content_url_regexes' => ['http://www.51wangdai.com/cx\d+.html'],
    'fields' => [
        [
            'name' => 'name',
            'selector' => 'div.cx_xx_lis_c01_left_lis01>a'
        ], [
            'name' => ['site', 'addr', 'limit', ''],
            'selector' => 'div.cx_xx_lis_c01_left_lis02'
        ]
    ]
];