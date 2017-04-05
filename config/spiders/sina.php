<?php
return [
    'name' => '新浪网',
    'scan_urls' => [
        'https://sapi.sina.cn/ls/getCardDataV3?card=news&page=-1'
    ],
    'domains' => ['sapi.sina.cn', 'cmnt.sina.cn'],
    'list_url_regexes' => [
        [
            'regex' => 'https://sapi.sina.cn/ls/getCardDataV3\?card=news&page=\-1',
            'fields' => [
                [
                    'name' => 'index',
                    'selector' => '$.retData.news.list.*.docID'
                ]
            ],
            'selector_type' => 'jsonpath',
            'return_url' => [
                'http://cmnt.sina.cn/aj/v2/index?index={index}&page=1'
            ]
        ]
    ],
    'content_url_regexes' => [
        [
            'regex' => 'http://newsapi.sina.cns/\?resource=article&newsId=.*?&from=6061195012&urlSign=f3036fbcf1',
            'selector_type' => 'jsonpath',
            'fields' => [
                [
                    'name' => 'title',
                    'selector' => '$.body.title',
                    'required' => true
                ], [
                    'name' => 'summary',
                    'value' => ''
                ], [
                    'name' => 'content',
                    'selector' => '$.body.text'
                ], [
                    'name' => 'author',
                    'selector' => '$.body.author'
                ], [
                    'name' => 'source',
                    'selector' => '$.body.source'
                ], [
                    'name' => 'pubdate',
                    'selector' => '$.body.editTime',
                    'callback' => function($date) {
                        return strtotime($date);
                    }
                ]
            ]
        ], [
            'regex' => 'http://cmnt.sina.cn/aj/v2/index\?index=.*?&page=\d+',
            'selector_type' => 'jsonpath',
            'fields' => [
                [
                    'name' => 'uname',
                    'selector' => '$.data.data.*.main.nick'
                ], [
                    'name' => 'uid',
                    'selector' => '$.data.data.*.main.uid'
                ], [
                    'name' => 'ip',
                    'selector' => '$.data.data.*.main.ip'
                ], [
                    'name' => 'ip_from',
                    'selector' => '$.data.data.*.main.source'
                ], [
                    'name' => 'agree_count',
                    'value' => '0'
                ], [
                    'name' => 'content',
                    'selector' => '$.data.data.*.main.content'
                ], [
                    'name' => 'comment_id',
                    'selector' => '$.data.data.*.main.mid'
                ], [
                    'name' => 'parent_comment_ids',
                    'selector' => '$.data.data.*.main.parent'
                ], [
                    'name' => 'children_comment_ids',
                    'value' => ''
                ], [
                    'name' => 'pubdate',
                    'selector' => '$.data.data.*.main.time',
                    'callback' => function($time) {
                        $unixTime = \yunlong2cn\ps\Helper::str2unix($time);
                        return $unixTime;
                    }
                ], [
                    'name' => 'created',
                    'callback' => function() {
                        return time();
                    }
                ], [
                    'name' => 'updated',
                    'callback' => function() {
                        return time();
                    }
                ]
            ],
            'export' => [
                'table' => 'apps_comments'
            ]
        ]
    ],
    'data' => [
        'media_source' => 'sina',
        'flag' => 0
    ]
];