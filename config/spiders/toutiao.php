<?php
return [
    'name' => '今日头条',
    'scan_urls' => [
        'http://ic.snssdk.com/2/article/v34/stream/?category=news_hot&count=20'
    ],
    'domains' => ['ic.snssdk.com', 'www.toutiao.com'],
    'queue' => 'redis@tcp://redis-host:6379',
    'is_allow_update' => true,
    'list_url_regexes' => [
        [
            'regex' => 'http://ic.snssdk.com/2/article/v34/stream/\?category=news_hot&count=20',
            'fields' => [
                [
                    'name' => 'location',
                    'selector' => '$.data.*.display_url',
                    'callback' => function($url) {
                        $pattern = '~http://toutiao.com/group/(\d+)/~is';
                        if(!preg_match($pattern, $url, $match)) {
                            return false;
                        }
                        $url = 'http://m.toutiao.com/a' . $match[1] . '/';
                        $response = \yunlong2cn\spider\Spider::request($url, [
                            'allow_redirects' => false,
                            'headers' => [
                                'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1'
                            ]
                        ], false);
                        $location = $response->getHeader('Location');
                        $url = $location[0] . 'info';
                        if(!strstr($url, 'toutiao.com')) return false; // 发现跳转目的路径不是头条内容，则返回失败并跳出
                        return $url;
                    }
                ], [
                    'name' => 'group_id',
                    'selector' => '$.data.*.group_id'
                ], [
                    'name' => 'item_id',
                    'selector' => '$.data.*.item_id'
                ]
            ],
            'selector_type' => 'jsonpath',
            'return_url' => [
                '{location}',
                'http://www.toutiao.com/api/comment/list/?group_id={group_id}&item_id={item_id}&offset=0&count=20'
            ]
        ]
    ],
    'content_url_regexes' => [
        [
            'regex' => 'http://m.toutiao.com/i\d+/info',
            'selector_type' => 'jsonpath',
            'fields' => [
                [
                    'name' => 'title',
                    'selector' => '$.data.title',
                    'required' => true
                ], [
                    'name' => 'summary',
                    'value' => ''
                ], [
                    'name' => 'content',
                    'selector' => '$.data.content'
                ], [
                    'name' => 'author',
                    'selector' => '$.data.media_user.screen_name'
                ], [
                    'name' => 'source',
                    'selector' => '$.data.detail_source'
                ], [
                    'name' => 'pubdate',
                    'selector' => '$.data.publish_time'
                ]
            ]
        ], [
            'regex' => 'http://www.toutiao.com/api/comment/list/\?group_id=\d+&item_id=\d+&offset=\d+&count=20',
            'selector_type' => 'jsonpath',
            'fields' => [
                [
                    'name' => 'uname',
                    'selector' => '$.data.comments.*.user.name'
                ], [
                    'name' => 'uid',
                    'selector' => '$.data.comments.*.user.user_id'
                ], [
                    'name' => 'ip',
                    'value' => ''
                ], [
                    'name' => 'ip_from',
                    'value' => ''
                ], [
                    'name' => 'agree_count',
                    'selector' => '$.data.comments.*.digg_count'
                ], [
                    'name' => 'content',
                    'selector' => '$.data.comments.*.text'
                ], [
                    'name' => 'comment_id',
                    'selector' => '$.data.comments.*.id'
                ], [
                    'name' => 'parent_comment_ids',
                    'value' => ''
                ], [
                    'name' => 'children_comment_ids',
                    'value' => ''
                ], [
                    'name' => 'pubdate',
                    'selector' => '$.data.comments.*.create_time'
                ]
            ],
            'export' => [
                'type' => 'mongo',
                'uri' => 'mongodb://192.168.0.136:17017/vvv',
                'table' => 'apps_comments'
            ]
        ]
    ],
    'data' => [
        'media_source' => 'toutiao',
        'flag' => 0
    ],
    'export' => [
        'type' => 'mongo',
        'uri' => 'mongodb://192.168.0.136:17017/vvv',
        'table' => 'apps_data'
    ]
];