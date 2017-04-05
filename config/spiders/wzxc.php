<?php
return [
    'name' => '我在现场',
    'scan_urls' => [
        'http://api.zhongguowangshi.com/Scene_1_1_2/apiSceneMain.ashx?action=20000&myUserId=0&pageNo=1'
    ],
    'domains' => ['api.zhongguowangshi.com'],
    'queue' => 'redis@tcp://redis-host:6379',
    'is_allow_update' => true,
    'list_url_regexes' => [
        [
            'regex' => 'http://api.zhongguowangshi.com/Scene_1_1_2/apiSceneMain.ashx\?action=20000&myUserId=0&pageNo=',
            'fields' => [
                [
                    'name' => 'id',
                    'selector' => '$.data.*.id',
                ]
            ],
            'selector_type' => 'jsonpath',
            'return_url' => 'http://api.zhongguowangshi.com/Scene_1_1_2/apiSceneMain.ashx?action=20004&myUserId=0&pageNo=1&nsId={id}'
        ]
    ],
    'content_url_regexes' => [
        [
            'regex' => 'http://api.zhongguowangshi.com/Scene_1_1_2/apiSceneMain.ashx\?action=20004&myUserId=0&pageNo=1&nsId=\d+',
            'selector_type' => 'jsonpath',
            'fields' => [
                [
                    'name' => 'title',
                    'selector' => '$.data.0.nsBigTitle',
                    'required' => true
                ], [
                    'name' => 'summary',
                    'selector' => '$.data.0.nsIntro'
                ], [
                    'name' => 'content',
                    'selector' => '$.data.0.data'
                ], [
                    'name' => 'author',
                    'selector' => '$.data.0.userName',
                    'callback' => function($name) {
                        return str_replace('创建人：', '', $name);
                    }
                ], [
                    'name' => 'source',
                    'value' => ''
                ], [
                    'name' => 'pubdate',
                    'selector' => '$.data.0.releaseDate',
                    'callback' => function($time) {
                        return strtotime($time);
                    }
                ]
            ]
        ]
    ],
    'data' => [
        'media_source' => 'wzxc',
        'flag' => 0
    ]
];