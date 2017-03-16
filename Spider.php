<?php
namespace yunlong2cn\spider;

use Yii;
use phpQuery;
use yii\mongodb\Query;


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
    public $before_start;

    private $queue = [];

    public function __construct($config)
    {
        self::$config = array_merge(self::$config, $config);
    }

    public function start()
    {
        
        if(empty(self::$config['scan_urls'])) {
            Log::error("未配置入口 URL");exit;
        }

        
        // 检查入口 URL 是否正确
        foreach (self::$config['scan_urls'] as $url) {
            if(!$this->is_scan_page($url)) {
                Log::error("入口 URL = {$url}，不匹配当前已配置域名范围");
                exit;
            }
        }

        if(Help::isWin()) {// 如果是 windows 系统，则强制显示为日志
            Log::$log_show = true;
        } else {
            Log::$log_show = $this->logShow;
        }

        foreach (self::$config['scan_urls'] as $url) {
            $this->add_scan_url($url);// 添加入口URL到队列
        }

        if($this->before_start) {
            call_user_func($this->before_start, $this);
        }

        $this->do_collect_page();// 开始采集

        Log::info('恭喜，采集完成');

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
        $task = $option;
        $task['url'] = $url;
        $task['url_type'] = 'scan_page';
        $task['depth'] = $depth;


        if($this->is_list_page($url)) {
            $task['url_type'] = 'list_page';
        } elseif($this->is_content_page($url)) {
            $task['url_type'] = 'content_page';
        }

        // 只将想要的链接添加到任务，可优化为全站采集
        if(!in_array($task['url_type'], ['list_page', 'content_page'])) {
            return false;
        }


        return Queue::push($task);
    }

    // 添加 url 到队列
    private function add_url($url, $option = [], $depth = 0)
    {
        return $this->add_scan_url($url, $option, false, $depth);
    }

    private function is_list_page($url)
    {
        if(isset(self::$config['list_url_regexes'])) foreach (self::$config['list_url_regexes'] as $regex) {
            if(preg_match("~{$regex}~is", $url)) {
                return true;
            }
        }
        return false;
    }

    private function is_content_page($url)
    {
        if(isset(self::$config['content_url_regexes'])) foreach (self::$config['content_url_regexes'] as $regex) {
            if(preg_match("~{$regex}~is", $url)) {
                return true;
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
        
        // 任务取出来以后，看一下是否允许采集
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

        Log::info("取出任务，准备采集 url = $url, url_type = {$task['url_type']}");

        $html = $this->request($task['url']);
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
                $this->getUrls($page['raw'], $url);
            }
        }


        // 如果是内容页面，分析提取页面中的字段
        if('content_page' == $task['url_type']) {
            $fields = $this->parseField($page['raw']);
            $fields['url'] = $url;
            if(self::$config['export'] && $fields) {
                if($this->save($fields, self::$config['export'])) {
                    Log::info('保存数据成功');
                } else {
                    Log::warn('保存数据失败');
                }
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
     * @param collectUrl string 内容来源 url
     */
    private function getUrls($content, $collectUrl)
    {
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
        $basePath = $base . $path;

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
        $url = $scheme . '://' . $url;

        $parse_url = parse_url($url);
        if(!empty($parse_url['host'])) {
            if(!in_array($parse_url['host'], self::$config['domains'])) {
                return false;
            }
        }
        return $url;
    }

    private function parseField($content)
    {
        $document = phpQuery::newDocumentHTML($content);
        $fields = self::$config['content_fields'];
        $res = [];
        foreach ($fields as $field) {
            if(is_string($field['name'])) {
                $res[$field['name']] = pq($field['selector'])->text();
            } elseif(is_array($field['name'])) {
                foreach ($field['name'] as $k => $name) {
                    $res[$name] = pq($field['selector'])->eq($k)->text();
                }
            }
        }
        return $res;
    }

    private function request($url, $task = array())
    {
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $url);

        $html = $res->getBody();
        $httpCode = $res->getStatusCode();
        if($httpCode != 200) {
            Log::info("statusCode = $httpCode");
            print_r($res);    
        }

        return $html;
    }

}
