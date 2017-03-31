<?php
namespace yunlong2cn\ps;

class Crawler
{

}



class CrawlerProcess()
{
    public $crawlers;

    public function __construct()
    {

    }

    /**
     * 创建一个 Crawler 对象，然后调用 Crawler 的 crawl 方法开启一个爬取任务
     *
     *
     **/
    public function crawl($crawler)
    {
        $crawler = $this->create_crawler($crawler);
        return $this->_crawl($crawler);
    }

    private function create_crawler($crawler)
    {
        $this->crawlers[] = $crawler;
    }
}