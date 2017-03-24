<?php
namespace yunlong2cn\spider;

use Yii;
use phpQuery;
use yii\mongodb\Query;

use Flow\JSONPath\JSONPath;

use League\Csv\Writer;


class Spider
{
    private $name; // 爬虫名称

    private $logShow = FALSE; // 是否显示日志，为 true 时显示调试信息，为 false 时显示爬取面板
    private $logFile = './data/spider.log'; // 日志文件路径
    private $logType; // 显示和记录的日志类型，info 普通，warn 警告，debug 调试，error 错误，默认为所有

    private $inputEncoding = NULL; // 指定输入编码，默认为 NULL 程序会自动识别编码
    private $outputEncoding = NULL; // 指定输出编码

    private $taskNum = 1; // 同时工作的爬虫任务数

    private $multiServer = FALSE; //多服务器处理，需要配合如 redis mongo 来保存采集任务数据，用以多服务器共享数据使用
    private $serverId = 1; // 服务器ID，若开启多服务器处理时，需要保证 serverid 唯一

    private $saveRunningState = FALSE;// 保存爬虫运行状态

    // $proxy = 'http://host:port'; 普通代理
    // $proxy = 'http://user:pass@host:port'; 验证代理
    private $proxy; // 代理服务器，如果爬取的网站根据IP做了反爬虫，可以配置此项

    private $interval = 1000;// 爬虫每个网页的时间间隔，默认 1 秒，单位毫秒
    private $timeout = 5;// 爬取每个网页的超时时间，单位秒

    private $maxTry = 5;// 爬取每个网页失败后的重试次数，如网络不通畅时导致爬取失败，则会重试 5 次
    private $maxDepth = 0;// 爬取网页的深度，超过深度的不再采集，为 0 时表示不限制
    private $maxFields = 0;// 爬取内容网页最大条数，超过将不再采集，为 0 时表示不限制

    private $userAgent; // 爬虫爬取网页所使用的浏览器类型
    private $userAgents; // 爬虫爬取网页时所使用的随机浏览器类型，用于攻破防采集，类型为数组

    private $clientIp; // 爬取网页所使用的伪 IP
    private $clientIps; // 随机伪 IP

    
    // // 导出数据到 csv
    // $export = [
    //     'type' => 'csv',
    //     'file' => 'p2peye.csv'
    // ];
    // // 导出数据到 sql
    // $export = [
    //     'type' => 'sql',
    //     'file' => 'p2peye.sql',
    //     'table' => 'p2peye'
    // ];
    // // 导出数据到 db
    // $export = [
    //     'type' => 'db',
    //     'table' => 'p2peye'
    // ];
    private $export;// 数组类型，爬虫爬取数据导出

    // $domains = ['www.baidu.com', 'baidu.com'];
    private $domains; // 数组类型，定义爬虫爬取哪些域名下的网页，非域名下的 URL 会被忽略，提高爬取效率

    // $scanUrls = ['http://zhidao.baidu.com'];
    private $scanUrls; // 数组类型，定义爬虫的入口链接，爬虫从这些链接开始爬取数据，同时也是监控爬虫要监控的链接

    
    // http://www.51wangdai.com/know/p/1
    // $list_url_regexs = ['http://www.51wangdai.com/know/p/\d+'];
    private $list_url_regexs; // 数组类型，定义列表页 URL 规则


    // http://www.51wangdai.com/cx6932.html
    // $contentUrlPatterns = ['http://www.51wangdai.com/cx\d+.html'];
    private $contentUrlPatterns; // 数组类型，定义内容页 URL 的规则

    
    /**
     * field 说明
     * name 表示数据项的名称
     * selector 定义抽取规则，默认使用 xpath，若使用其它类型，需要定义 selector_type
     * selector_type 定义抽取规则的类型 xpath,json,regex 默认使用 xpath
     * required 定义当前数据项是否为必需，默认为 false
     * repeated 定义当前 field 抽取到的内容是否有多项，默认为 false，若为 true 则不论抽取到的数据是否为多项，返回结果为数组
     * children 重复 fields 功能
     * source_type 定义当前数据项的数据源，默认从当前页面中抽取，可选为 attached_url(可以发起一个新的请求，然后从请求返回的数据中抽取内容)/url_context(可以从当前网页的 url 附加数据中抽取)
     * attached_url 定义请求新 URL ，当爬取的网页中某些内容需要异步加载时，就需要使用 attached_url
     */
    
    // demo
    // $fields = [
    //     [
    //         'name' => 'name',
    //         'selector' => '',
    //         'selector_type' => 'xpath',
    //         'required' => true
    //     ],[
    //         'name' => 'site',
    //         'selector' => ''
    //     ],
    // ];
    private $fields; // 数组类型，定义内容页的抽取规则，规则有一个个的 field 组成，一个 field 代表一个数据项



    
    private static $config = [
        'max_depth' => 0
    ];


    // 回调
    public $onStart = NULL;
    public $onListPage = NULL;
    public $onContentPage = NULL;
    public $encodeUrl = NULL;

    private $queue = [];

    private $csvHandle = NULL;

    public function __construct($config)
    {
        self::$config = Helper::merge(self::$config, $config);
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function start()
    {
        
        if(empty(self::$config['scan_urls'])) {
            Log::error("未配置入口 URL");exit;
        }

        
        // 检查入口 URL 是否正确
        foreach (self::$config['scan_urls'] as $url) {
            $url = is_array($url) ? $url['url'] : $url;
            if(!$this->is_scan_page($url)) {
                Log::error("入口 URL = {$url}，不匹配当前已配置域名范围");
                exit;
            }
        }

        if(Helper::isWin()) {// 如果是 windows 系统，则强制显示为日志
            Log::$log_show = true;
        } else {
            Log::$log_show = $this->logShow;
        }

        foreach (self::$config['scan_urls'] as $url) {
            $option = [];
            if(is_array($url)) {
                $option = $url;
                $url = $url['url'];
                unset($option['url']);
            }
            $this->add_scan_url($url, $option);// 添加入口URL到队列
        }

        if($this->onStart) {
            call_user_func($this->onStart, $this);
        }

        if(!empty(self::$config['name'])) {
            Log::info('---------------------');
            Log::info('- 爬虫名称： 【'. self::$config['name'] .'】');
            Log::info('---------------------');
        } else {
            Log::error('请先设置爬虫名称');exit;
        }
        
        usleep(1000);

        $this->do_collect_page();// 开始采集

        if($this->csvHandle) $this->csvHandle->output($conf['file']);

        Log::info('恭喜，采集完成');
        usleep(1000);

    }

    private function is_scan_page($url)
    {
        $parseUrl = parse_url($url);
        if(empty($parseUrl['host']) || !in_array($parseUrl['host'], self::$config['domains'])) {
            return false;
        }
        return true;
    }

    /**
     * 添加入口 URL
     * @param url string
     * @param option array
     * @param repeat boolean 是否允许重复
     * @return boolean
     */
    private function add_scan_url($url, $option = [], $repeat = false, $depth = 0)
    {
        if(empty(trim($url))) return false;

        Log::debug('准备添加 入口 地址 = ' . $url);

        $link = $option;
        $link['url'] = $url;
        $link['url_type'] = 'scan_page';
        $link['depth'] = $depth;


        if($regex = $this->is_list_page($url)) {
            if(is_array($regex)) { // 如果是数组，则需要根据配置设置 $link
                $link = Helper::merge($regex, $link);
            }
            $link['url_type'] = 'list_page';
        } elseif($regex = $this->is_content_page($url)) {
            if(is_array($regex)) { // 如果是数组，则需要根据配置设置 $link
                $link = Helper::merge($regex, $link);
            }
            $link['url_type'] = 'content_page';
        }

        // 只将想要的链接添加到任务，可优化为全站采集
        if(!in_array($link['url_type'], ['list_page', 'content_page'])) {
            Log::info('不在采集范围内， url = ' . $url . ', url_type = ' . $link['url_type']);
            return false;
        }


        return Queue::push($link);
    }

    // 添加 url 到队列
    public function add_url($url, $option = [], $depth = 0)
    {
        return $this->add_scan_url($url, $option, false, $depth);
    }

    private function is_list_page($url)
    {
        if(isset(self::$config['list_url_regexes'])) foreach (self::$config['list_url_regexes'] as $regex) {
            if(is_array($regex)) {
                $regexPattern = $regex['regex'];
            } else {
                $regexPattern = $regex;
            }
            if(preg_match("~{$regexPattern}~is", $url, $match)) {
                return $regex;
            }
        }
        return false;
    }

    private function is_content_page($url)
    {
        if(isset(self::$config['content_url_regexes'])) foreach (self::$config['content_url_regexes'] as $regex) {
            if(is_array($regex)) {
                $regexPattern = $regex['regex'];
            } else {
                $regexPattern = $regex;
            }

            // Log::info('@# 准备匹配规则 ' . $regexPattern);
            // Log::info('@# 待匹配 URL = ' . $url);
            if(preg_match("~{$regexPattern}~is", $url)) {
                // Log::info('@# 匹配成功');
                return $regex;
            }
        }
        return false;
    }

    private function do_collect_page()
    {
        while(Queue::size()) {
            $this->collect_page();
        }
    }

    private function queue_size()
    {
        return count($this->queue);
    }

    private function collect_page()
    {
        if(!Queue::size()) {
            return false;
        }
        Log::debug('当前任务数：' . Queue::size());
        $task = Queue::get();
        $url = $task['url'];
        $request = isset($task['request']) ? $task['request'] : [];

        // 任务取出来以后，看一下是否允许采集
        if('db' == self::$config['export']['type']) {
            // 根据 url 去数据库中查一下，当前页面是否在之前被采集过
            if($one = (new Query)->from(self::$config['export']['table'])->where([
                'url' => $url
            ])->one()) {
                $fields['_id'] = $one['_id']->__tostring();
                if(!self::$config['is_allow_update']) {
                    Log::info('跳过任务，不允许更新已采集内容');
                    return false;
                }
            }
        }
        

        Log::info("Get task，准备采集 url = $url, url_type = {$task['url_type']}");

        $html = $this->request($task['url'], $request);
        if(!$html) return false;
        

        // 当前正在爬取的网页页面的对象
        $page = [
            'url' => $url,
            'raw' => $html
        ];
        unset($html);// 释放内存


        // 是否在当前页面提取URL并发现待爬取页面
        $is_find_page = true;
        
        if($is_find_page) {
            if(0 == self::$config['max_depth']) {
                $this->getUrls($page['raw'], $task);
            }
        }

        if('list_page' == $task['url_type']) {
            if($this->onListPage) {
                Log::info('触发回调函数 onListPage');
                call_user_func($this->onListPage, $page, $this);
            }
        }


        // 如果是内容页面，分析提取页面中的字段
        if('content_page' == $task['url_type']) {
            if($fields = $this->parseField($page['raw'], $task)) {
                
                $conf = isset($task['export']) ? $task['export'] : self::$config['export'];

                // 处理预保存数据
                foreach ($fields as $k => $field) {
                    $fields[$k] = Helper::merge($fields[$k], [
                        'url' => $url,
                        'urlmd5' => md5($url) // 用于多表关联，主要是包含 return_url 时的关联
                    ]);

                    if(!empty(self::$config['data'])) {
                        Log::info('自动合并当前配置默认数据');
                        $globalData = self::$config['data'];
                        foreach ($globalData as $key => $data) {
                            if(is_string($data)) {
                                $globalData[$key] = $data;
                            } else {
                                $globalData[$key] = call_user_func($data);
                            }
                        }
                        $fields[$k] = Helper::merge($fields[$k], $globalData);
                    }

                    if(isset($task['data'])) {// 如果配置了默认数据，则自动合并
                        Log::info('自动合并当前任务默认数据');
                        $taskData = $task['data'];
                        foreach ($taskData as $key => $data) {
                            if(is_string($data)) {
                                $taskData[$key] = $data;
                            } else {
                                $taskData[$key] = call_user_func($data);
                            }
                        }

                        $fields[$k] = Helper::merge($fields[$k], $taskData);
                    }

                    if($conf && $fields[$k]) {
                        if($this->save($fields[$k], $conf)) {
                            Log::info('保存数据成功');
                        } else {
                            Log::warn('保存数据失败');
                        }
                    }

                }
                unset($fields);
            }

        }
    }

    /**
     * 保存数据
     * @param $data array 要保存的数据
     * @param $conf array 配置数据保存的位置

     * $conf = ['type' => 'db', 'table' => 'platforms']
     * $conf = ['type' => 'csv', 'file' => 'platforms.csv']

     * @return unknow_type
     */
    private function save($data, $conf = [])
    {
        if('db' == $conf['type']) {
            return Yii::$app->mongodb->getCollection($conf['table'])->save($data);
        } elseif('csv' == $conf['type']) {
            if($this->csvHandle === NULL) $this->csvHandle = Writer::createFromFileObject(new \SplTempFileObject());
            return $this->csvHandle->insertOne($data);
        } else {
            Log::info('++++++++++ 分析结果 ++++++++++');
            Log::info('+');
            Log::info(serialize($data));
        }

        return false;
    }

    /**
     * 批量保存数据
     * @param $data array 要保存的数据
     * @param $conf array 配置数据保存的位置

     * $conf = ['type' => 'db', 'table' => 'platforms']
     * $conf = ['type' => 'csv', 'file' => 'platforms.csv']

     * @return unknow_type
     */
    private function batchInsert($data, $conf = [])
    {
        if('db' == $conf['type']) {
            return Yii::$app->mongodb->getCollection($conf['table'])->batchInsert($data);
        }

        return false;
    }

    /**
     * 根据 URL 检查数据是否已经采集过了
     * @param $url string
     * @return boolean/_id
     */
    private function exists($url)
    {
        
        return false;
    }

    /**
     * 在内容中获取链接 URL
     * @param content string 内容
     * @param link array
     */
    private function getUrls($content, $link)
    {
        $collectUrl = $link['url'];
        $selector_type = isset($link['selector_type']) ? strtolower($link['selector_type']) : 'css';

        if('jsonpath' == $selector_type) {
            if(!isset($link['fields'])) {
                Log::error('启用 JSONPath 时，同时需要设置 fields 字段');
                return false;
            }
            if(!isset($link['return_url'])) return false;
            if(empty($link['return_url'])) return false;
            $data = json_decode($content, 1);
            $query = new JSONPath($data);
            
            $fields = [];
            foreach ($link['fields'] as $field) {
                if(empty($field['selector'])) continue;
                $value = $query->find($field['selector'])->data();

                if(is_array($value) && isset($field['callback'])) {
                    $callback = $field['callback'];
                    $value = array_map(function($d) use ($callback, $value) {
                        $res = $callback($d);
                        Log::info('res = ' . $res);
                        return $res;
                    }, $value);
                }

                $fields[$field['name']] = $value;
            }
            
            $returnUrls = [];
            
            if(is_string($link['return_url'])) {
                $return_url[] = $link['return_url'];
            } else {
                $return_url = $link['return_url'];
            }
            
            foreach ($return_url as $index => $returnUrl) {
                $returnUrlTpl = is_array($returnUrl) ? $returnUrl['url'] : $returnUrl;
                foreach ($fields as $key => $field) {
                    foreach ($field as $k => $f) {
                        if(!strstr($returnUrlTpl, '{'. $key .'}')) continue;
                        Log::debug('准备替换字段：' . $key . ' 为 ' . $f);
                        $before = empty($returnUrls[$index][$k]['url']) ? $returnUrlTpl : $returnUrls[$index][$k]['url'];
                        Log::debug('替换前URL = ' . $before);
                        if(!is_array($f)) {
                            $returnUrls[$index][$k]['url'] = str_replace("{{$key}}", $f, $before);
                            Log::debug('替换后URL = ' . $returnUrls[$index][$k]['url']);
                        }
                    }
                }
            }

            

            // 添加 url 到队列
            foreach ($returnUrls as $returnUrl) {
                foreach ($returnUrl as $url) {
                    $this->add_url($url['url'], [
                        'request' => [
                            'header' => [
                                'Referer' => $collectUrl
                            ]
                        ],
                        'data' => [
                            'doc_urlmd5' => md5($collectUrl)
                        ]
                    ]);
                }
            }

            return true;
        }

        $document = phpQuery::newDocumentHTML($content);
        $urls = [];
        foreach (pq('a') as $arg) {
            $urls[] = pq($arg)->attr('href');
        }
        
        // 处理并优化 url
        // 1. 去除重复 url
        $urls = array_unique($urls);
        foreach ($urls as $k => $url) {
            $url = trim($url);
            if(empty($url)) {
                continue;
            }

            // 2.优化链接地址
            if($url = $this->formatUrl($url, $collectUrl)) {
                $urls[$k] = $url;
            } else {
                unset($urls[$k]);
            }
        }
        if(empty($urls)) return false;

        // 把分析到的 url 放入队列
        foreach ($urls as $url) {
            $this->add_url($url, [
                'header' => [
                    'Referer' => $collectUrl
                ]
            ]);
        }
        return $urls;
    }

    private function formatUrl($url, $collectUrl)
    {
        if('' == $url) return false;

        if(preg_match('~^(javascript:|#|\'|")~is', $url)) return false;

        $parseUrl = parse_url($collectUrl);
        if(empty($parseUrl['scheme']) || empty($parseUrl['host'])) return false;
        // 若解析的协议不是 http/https 则移除
        if(!in_array($parseUrl['scheme'], ['http', 'https'])) return false;
        extract($parseUrl);//将数组中的 index 解压为变量输出 scheme, host, path, query, fragment

        $base = $scheme . '://' . $host;
        // $basePath = $base . $path;

        if('//' == substr($url, 0, 2)) {
            $url = str_replace('//', '', $url);
        } elseif('/' == $url[0]) {// 说明是绝对路径
            $url = $host . $url;
        } elseif('.' == $url[0]) {// 说明是相对路径
            $dots = explode('/', $url); // ../../x.html
            $paths = explode('/', $path); // /a/b/c/d
            foreach ($dots as $dot) {
                if('..' == $dot) {
                    $paths = array_pop($paths);
                    $dots = array_shift($dots);
                }
            }
            $url = implode($dots, '/');
            $path = implode($paths, '/');
            $url = $host . $path . $url;
        }
        $url = strstr($url, 'http') ? $url : $scheme . '://' . $url;

        Log::info('重组后 URL = ' . $url);

        $parse_url = parse_url($url);
        if(!empty($parse_url['host'])) {
            if(!in_array($parse_url['host'], self::$config['domains'])) {
                Log::info('URL 不在域名范围内');
                return false;
            }
        }
        return $url;
    }

    private function parseField($content, $option = [])
    {
        
        if(empty($option['fields']) && empty(self::$config['fields'])) {
            Log::info('保存内容到 数据库');
            $document = phpQuery::newDocumentHTML($content);
            $html = $document->html();
            $title = $document->find('title')->text();
            $text = $document->find('body')->text();

            return [[
                'title' => $title,
                'text' => $text,
                'html' => $html
            ]];
        }

        if(isset($option['selector_type'])) {
            if('jsonpath' == $option['selector_type']) {
                $data = json_decode($content, 1);
                $query = new JSONPath($data);
                
                $fields = [];
                $value = NULL;
                foreach ($option['fields'] as $field) {
                    if(empty($field['selector'])) {
                        if(!is_array($value)) {
                            Log::debug('不能将未设置 selector 的字段放在第一位');exit;
                        }
                    } else {
                        $value = $query->find($field['selector'])->data();
                    }

                    $tempValue = NULL;
                    foreach ($value as $k => $v) {

                        $tempValue = empty($field['selector']) ? empty($field['value']) ? '' : $field['value'] : $v;

                        if(isset($field['required']) && $field['required'] && empty($tempValue)) {
                            Log::debug('必需的字段 '. $field['name'] .' 为空，跳过此数据');
                            return false;
                        }

                        if(isset($field['save']) && $field['save'] == false) {
                            Log::debug('不保存的字段 name = ' . $field['name']);
                            continue;
                        }

                        if(is_null($tempValue)) {
                            Log::debug('字段 name = ' . $field['name'] . ' 为 NULL');
                            continue;
                        }

                        $tempValue = method_exists($tempValue, 'data') ? $tempValue->data() : $tempValue;

                        if(isset($field['callback'])) {
                            $callback = $field['callback'];
                            $tempValue = $callback($tempValue);
                        }

                        $tempValue = empty($tempValue) ? empty($field['value']) ? '' : $field['value'] : $tempValue;

                        $fields[$k][$field['name']] = $tempValue;
                    }
                    unset($tempValue);
                }
                unset($value);
                
                return $fields;
            }
        }

        $document = phpQuery::newDocumentHTML($content);
        $fields = self::$config['fields'];
        $res = [];
        foreach ($fields as $field) {
            if(is_string($field['name'])) {
                $temp = pq($field['selector'])->text();
                if(!empty($field['callback'])) {
                    $temp = call_user_func($field['callback'], $temp);
                }
                if(!empty($field['required']) && empty($temp)) return false;
                $res[$field['name']] = $temp;
            } elseif(is_array($field['name'])) {
                foreach ($field['name'] as $k => $name) {
                    $temp = pq($field['selector'])->eq($k)->text();
                    if(!empty($field['callback'])) {
                        $temp = call_user_func($field['callback'], $temp);
                    }
                    if(!empty($field['required']) && empty($temp)) return false;
                    $res[$name] = $temp;
                }
            }
        }
        return [$res];
    }

    public static function request($url, $args = [], $body = true)
    {
        $client = new \GuzzleHttp\Client();
        $method = isset($args['method']) ? strtoupper($args['method']) : 'GET';
        Log::debug("method = $method");
        $option = [
            'allow_redirects' => [
                'max' => 10
            ]
        ];
        if(isset($args['data'])) $option['form_params'] = $args['data'];

        $option = Helper::merge($option, $args);
        
        try {
            $response = $client->request($method, $url, $option);
            // $response->getParams()->set('redirect.max', 100);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            Log::info('网络请求异常， URL = ' . $url);
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            Log::info('GuzzleHttp\Exception\ClientException');
            return false;
        } catch(\GuzzleHttp\Exception\TooManyRedirectsException $e) {
            Log::debug($e->getMessage());
            print_r($e->getRequest());
            Log::warn('重定向次数太多');
            return false;
        }

        
        $httpCode = $response->getStatusCode();
        if($httpCode != 200) {
            Log::info("statusCode = $httpCode");
        }

        $return = $body ? $response->getBody() : $response;

        // echo($return);

        return $return;
    }

}
