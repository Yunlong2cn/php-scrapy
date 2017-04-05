<?php
return [
    'name' => '360',
    'scan_urls' => [
        'http://api.app.btime.com/news/list?protocol=2&cid=7389193781085e10178780f6bbb3c79e'
    ],
    'domains' => ['api.app.btime.com'],
    'is_allow_update' => false,
    'list_url_regexes' => [
        [
            'regex' => 'http://api.app.btime.com/news/list\?protocol=2&cid=',
            'fields' => [
                [
                    'name' => 'gid',
                    'selector' => '$.data.*.gid',
                ]
            ],
            'selector_type' => 'jsonpath',
            'return_url' => 'http://api.btime.com/trans?m=btime&fmt=json&url=&os_type=&protocol=2&gid={gid}'
        ]
    ],
    'content_url_regexes' => [
        [
            'regex' => 'http://api.btime.com/trans\?m=btime&fmt=json&url=&os_type=&protocol=2&gid=',
            'selector_type' => 'jsonpath',
            'fields' => [
                [
                    'name' => 'title',
                    'selector' => '$.data.title',
                    'required' => true
                ], [
                    'name' => 'summary',
                    'selector' => '$.data.summary'
                ], [
                    'name' => 'content',
                    'selector' => '$.data.content'
                ], [
                    'name' => 'author',
                    'value' => ''
                ], [
                    'name' => 'source',
                    'selector' => '$.data.source'
                ], [
                    'name' => 'pubdate',
                    'selector' => '$.data.time'
                ], [
                    'name' => 'prefix-url',
                    'selector' => '$.data.url',
                    'save' => false,
                    'callback' => function($url) {
                        return str_replace('%', '%25', urlencode($url));
                    }
                ]
            ],
            'return_url' => 'http://gcs.so.com/comment/lists?url={prefix-url}&num=20&client_id=25&page=1'
        ], [
            'regex' => 'http://gcs.so.com/comment/lists\?url=.*?&num=20&client_id=25&page=\d+',
            'selector_type' => 'jsonpath',
            'fields' => [
                [
                    'name' => 'uname',
                    'selector' => '$.data.comments.*.user_info',
                    'callback' => function($info) {
                        $user = json_decode($info, 1);
                        return isset($user['nick_name']) ? $user['nick_name'] : '';
                    }
                ], [
                    'name' => 'uid',
                    'selector' => '$.data.comments.*.uid'
                ], [
                    'name' => 'ip',
                    'selector' => '$.data.comments.*.ip'
                ], [
                    'name' => 'ip_from',
                    'value' => ''
                ], [
                    'name' => 'agree_count',
                    'value' => '0'
                ], [
                    'name' => 'content',
                    'selector' => '$.data.comments.*.message'
                ], [
                    'name' => 'comment_id',
                    'selector' => '$.data.comments.*.id'
                ], [
                    'name' => 'parent_comment_ids',
                    'selector' => '$.data.comments.*.reply_to'
                ], [
                    'name' => 'children_comment_ids',
                    'selector' => '$.data.comments.*.sub_comment'
                ], [
                    'name' => 'pubdate',
                    'selector' => '$.data.comments.*.pdate',
                    'callback' => function($date) {
                        return strtotime($date);
                    }
                ]
            ],
            'export' => [
                'table' => 'apps_comments'
            ]
        ]
    ],
    'data' => [
        'media_source' => '360',
        'flag' => 0
    ]
];