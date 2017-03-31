<?php
return [
    'name' => '凤凰网',
    'scan_urls' => [
        'http://api.iclient.ifeng.com/ClientNews?id=SYLB10,SYDT10,SYRECOMMEND'
    ],
    'domains' => ['api.iclient.ifeng.com', 'comment.ifeng.com'],
    'queue' => 'redis@tcp://redis-host:6379',
    'is_allow_update' => false,
    'list_url_regexes' => [
        [
            'regex' => 'http://api.iclient.ifeng.com/ClientNews\?id=',
            'fields' => [
                [
                    'name' => 'documentId',
                    'selector' => '$.0.item.*.documentId',
                ]
            ],
            'selector_type' => 'jsonpath',
            'return_url' => 'http://api.iclient.ifeng.com/ipadtestdoc?aid={documentId}'
        ]
    ],
    'content_url_regexes' => [
        [
            'regex' => 'http://api.iclient.ifeng.com/ipadtestdoc\?aid=cmpp_\d+',
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
                ], [
                    'name' => 'docUrl',
                    'selector' => '$.body.commentsUrl',
                    'save' => false,
                    'callback' => function($url) {
                        return urlencode($url);
                    }
                ]
            ],
            'return_url' => 'http://comment.ifeng.com/get.php?callback=newCommentListCallBack&orderby=&docUrl={docUrl}&format=json&job=1&p=1&pageSize=10'
        ], [
            'regex' => 'http://comment.ifeng.com/get.php\?callback=newCommentListCallBack&orderby=&docUrl=.*&format=json&job=1&p=1&pageSize=10',
            'selector_type' => 'jsonpath',
            'fields' => [
                [
                    'name' => 'uname',
                    'selector' => '$.comments.*.uname'
                ], [
                    'name' => 'uid',
                    'selector' => '$.comments.*.user_id'
                ], [
                    'name' => 'ip',
                    'selector' => '$.comments.*.client_ip'
                ], [
                    'name' => 'ip_from',
                    'selector' => '$.comments.*.ip_from'
                ], [
                    'name' => 'agree_count',
                    'value' => '0'
                ], [
                    'name' => 'content',
                    'selector' => '$.comments.*.comment_contents'
                ], [
                    'name' => 'comment_id',
                    'selector' => '$.comments.*.comment_id'
                ], [
                    'name' => 'parent_comment_ids',
                    'selector' => '$.comments.*.parent'
                ], [
                    'name' => 'children_comment_ids',
                    'value' => ''
                ], [
                    'name' => 'pubdate',
                    'selector' => '$.comments.*.create_time'
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
                'type' => 'mongo',
                'uri' => 'mongodb://192.168.0.136:17017/vvv',
                'table' => 'apps_comments'
            ]
        ]
    ],
    'data' => [
        'media_source' => 'ifeng',
        'flag' => 0
    ],
    'export' => [
        'type' => 'mongo',
        'uri' => 'mongodb://192.168.0.136:17017/vvv',
        'table' => 'apps_data'
    ]
];