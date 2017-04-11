<?php
namespace yunlong2cn\ps;


use League\Csv\Writer;
use yunlong2cn\ps\adapter\FileAdapter;


class Spider
{
    private $config = [
        'max_depth' => 0
    ];


    // 回调
    public $onStart = NULL;
    public $onListPage = NULL;
    public $onContentPage = NULL;
    public $encodeUrl = NULL;


    private $queueHandle = NULL;
    private $dbHandle = NULL;

    public function __construct($config)
    {
        $this->config = Helper::merge($this->config, $config);

        /**
         * 爬虫类型 type，待修复，目前请使用 multi 形式
         *
         * multi 既生成队列又执行采集
         * queue 只生成队列
         * collect 只采集
         *
         **/
        $this->config['type'] = empty($this->config['type']) ? 'multi' : $this->config['type'];
        
        $queueType = empty($this->config['queue']) ? 'flash' : $this->config['queue'];
        $this->queueHandle = $this->getQueueHandle($queueType);
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function start()
    {
        
        if(empty($this->config['scan_urls'])) {
            Log::error("未配置入口 URL", true);
        }

        
        // 检查入口 URL 是否正确
        foreach ($this->config['scan_urls'] as $url) {
            $url = is_array($url) ? $url['url'] : $url;
            if(!$this->is_scan_page($url)) {
                Log::error("入口 URL = {$url}，不匹配当前已配置域名范围", true);
            }
        }

        if(Helper::isWin()) {// 如果是 windows 系统，则强制显示为日志
            Log::$log_show = true;
        } else {
            Log::$log_show = false;
        }

        foreach ($this->config['scan_urls'] as $url) {
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

        if(!empty($this->config['name'])) {
            Log::info('---------------------');
            Log::info('- 爬虫名称： 【'. $this->config['name'] .'】');
            if('queue' == $this->config['type']) {
                Log::info('当前模式，只生成队列');
            } elseif('collect' == $this->config['type']) {
                Log::info('当前模式，只采集数据');
            } elseif('multi' == $this->config['type']) {
                Log::info('当前模式，生成队列并采集数据');
            }
            Log::info('---------------------');
        } else {
            Log::error('请先设置爬虫名称', true);
        }

        $this->do_collect_page();// 开始采集

        Log::info('恭喜，采集完成');
    }

    private function is_scan_page($url)
    {
        $parseUrl = parse_url($url);

        if(empty($parseUrl['host'])) return false;

        foreach ($this->config['domains'] as $domain) {
            $pattern = '~'. $domain .'~is';
            $pattern = str_replace('*', '.*', $pattern);
            if(preg_match($pattern, $parseUrl['host'])) {
                return true;
            }
        }

        if(in_array($parseUrl['host'], $this->config['domains'])) {
            return true;
        }

        return false;
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
            // 保存采集的 URL 到库中
            if(is_null($this->dbHandle)) {
                $this->dbHandle = $this->getDbHandle([
                    'type' => 'mongo',
                    'uri' => 'mongodb://192.168.3.47:27018/weibo',
                    'table' => 'snatch_urls'
                ]);
            }

            if($this->dbHandle->find(['urlmd5' => md5($url)], 'snatch_urls')) {
                Log::info('++ 当前URL已被采集，跳过...');
                return false;
            }

        }

        // 只将想要的链接添加到任务，可优化为全站采集
        if(!in_array($link['url_type'], ['list_page', 'content_page'])) {
            // Log::info('不在采集范围内， url = ' . $url . ', url_type = ' . $link['url_type']);
            return false;
        }

        Log::debug('添加 入口 地址 = ' . $url . ' url_type = ' . $link['url_type']);


        return $this->queueHandle->push($link);
    }

    // 添加 url 到队列
    public function add_url($url, $option = [], $depth = 0)
    {
        return $this->add_scan_url($url, $option, false, $depth);
    }

    private function is_list_page($url)
    {
        if(isset($this->config['list_url_regexes'])) foreach ($this->config['list_url_regexes'] as $regex) {
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
        if(isset($this->config['content_url_regexes'])) foreach ($this->config['content_url_regexes'] as $regex) {
            if(is_array($regex)) {
                $regexPattern = $regex['regex'];
            } else {
                $regexPattern = $regex;
            }

            if(preg_match("~{$regexPattern}~is", $url)) {
                return $regex;
            }
        }
        return false;
    }

    private function do_collect_page()
    {
        while($this->queueHandle->size()) {
            $this->collect_page();
        }
    }

    private function collect_page()
    {
        if(!$this->queueHandle->size()) {
            return false;
        }
        Log::debug('当前任务数：' . $this->queueHandle->size());
        $task = $this->queueHandle->get();
        $url = $task['url'];
        $request = isset($task['request']) ? $task['request'] : [];

        // 任务取出来以后，看一下是否允许采集
        if('db' == $this->config['export']['type']) {
            // 根据 url 去数据库中查一下，当前页面是否在之前被采集过
            // if($one = (new Query)->from($this->config['export']['table'])->where([
            //     'url' => $url
            // ])->one()) {
            //     $fields['_id'] = $one['_id']->__tostring();
            //     if(!$this->config['is_allow_update']) {
            //         Log::info('跳过任务，不允许更新已采集内容');
            //         return false;
            //     }
            // }
        }
        

        Log::info("Get task，准备下载 url = $url, url_type = {$task['url_type']}");

        $html = Downloader::fetch($task['url'], $request);
        if(!$html) return false;
        

        // 当前正在爬取的网页页面的对象
        $page = [
            'url' => $url,
            'raw' => $html
        ];
        unset($html);// 释放内存


        // 是否在当前页面提取URL并发现待爬取页面
        $is_find_page = true;

        if('collect' == $this->config['type']) $is_find_page = false;
        
        if($is_find_page) {
            if(0 == $this->config['max_depth']) {
                if($urls = Url::gets($page['raw'], $task)) {
                    foreach ($urls as $_urls) {// 由于页面中可能有需要返回的 url 需要组合，因此这里直接使用二维数组进行处理
                        foreach ($_urls as $_url) {
                            if(isset($_url['url'])) $_url = $_url['url'];
                            $this->add_url($_url);
                        }
                    }
                }
            }
        }

        if('list_page' == $task['url_type']) {
            if($this->onListPage) {
                Log::info('触发回调函数 onListPage');
                call_user_func($this->onListPage, $page, $this);
            }
        }


        if('queue' == $this->config['type']) {// 如果只生成队列，这里分析字段部分不需要
            Log::info('跳过字段分析');
        } else {// 只采集或既采集又生成队列
            // 如果是内容页面，分析提取页面中的字段
            if('content_page' == $task['url_type']) {
                $rFields = empty($task['fields']) ? empty($this->config['fields']) ? [] : $this->config['fields'] : $task['fields'];
                $selector_type = empty($task['selector_type']) ? empty($this->config['selector_type']) ? 'csspath' : $this->config['selector_type'] : $task['selector_type'];
                if($fields = Field::gets($page['raw'], $rFields, $selector_type)) {
                    
                    // ===> 准备 config
                    if(empty($task['export'])) {
                        $conf = empty($this->config['export']) ? ['type' => 'log'] : $this->config['export'];
                    } else {
                        $conf = empty($this->config['export']) ? $task['export'] : Helper::merge($this->config['export'], $task['export']);
                    }
                    // <=== config

                    // 处理预保存数据
                    foreach ($fields as $k => $field) {
                        $fields[$k] = Helper::merge($fields[$k], [
                            'url' => $url,
                            'urlmd5' => md5($url) // 用于多表关联，主要是包含 return_url 时的关联
                        ]);

                        if(!empty($this->config['data'])) {
                            Log::info('自动合并当前配置默认数据');
                            $globalData = $this->config['data'];
                            foreach ($globalData as $key => $data) {
                                if(is_string($data) || is_numeric($data)) {
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
                            if(is_null($this->dbHandle)) {
                                $this->dbHandle = $this->getDbHandle($conf);
                            }
                            Log::info('准备入库' . json_encode($conf));
                            if($this->dbHandle->save($fields[$k], $conf)) {
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
    }

    private function getQueueHandle($queue)
    {
        if(strstr($queue, '@')) {
            list($adapter, $uri) = explode('@', $queue);
        } else {
            $adapter = $queue;
        }
        
        $class = 'yunlong2cn\\ps\\queue\\adapter\\' . ucfirst($adapter) . 'Adapter';
        
        if(empty($uri)) {
            $adapter = new $class;
        } else {
            $adapter = new $class($uri);
        }

        return new Queue($adapter);
    }

    private function getDbHandle($conf)
    {
        if('mongo' == $conf['type']) {
            $adapter = new db\adapter\MongoAdapter($conf['uri']);
        } elseif(in_array($conf['type'], ['file', 'log', 'csv'])) {
            $class = 'yunlong2cn\\ps\\db\\adapter\\'. ucfirst($conf['type']) .'Adapter';
            $adapter = new $class;
        } else {
            Log::error('非法的 export type = '. $conf['type'] .' 类型，仅允许范围为 [file, log, csv, mongo]', true);
        }
        return new Db($adapter);
    }
}
