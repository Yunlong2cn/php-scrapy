<?php
namespace yunlong2cn\spider;

use Yii;


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
    // $listUrlPatterns = ['http://www.51wangdai.com/know/p/\d+'];
    private $listUrlPatterns; // 数组类型，定义列表页 URL 规则


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



    public function start()
    {
        
    }

}
