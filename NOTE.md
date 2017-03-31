几个关键的模块和类介绍

cmdline: 命令执行模块，主要用于配置的获取，并执行相应的 ScrapyCommand.
ScrapyCommand: 命令对象，用于执行不同的命令。对于 crawl 任务，主要是调用 CrawlerProcess 的 craw 和 start 方法。
CrawlerProcess: 顾名思义，爬取进程，主要用于管理 Crawler 对象，可以控制多个 Crawler 对象来同时进行多个不同的爬取任务，并调用 Crawler 的 crawl 方法。
Crawler: 爬取对象，用来控制爬虫的执行，里面会通过一个执行引擎 engine 对象来控制 spider 从打开到启动等生命周期。
ExecutionEngine: 执行引擎，主要控制整个调度过程，通过 twisted 的 task.LoopingCall 来不断的产生爬取任务。


一、ExecutionEngine: 执行引擎，是 scrapy 的核心模块之一，这驱动了整个爬取的开始、进行、关闭。
1. slot: 使用 Twisted 的主循环 reactor 来不断的调度执行 Engine 的 _next_request 方法
2. downloader: 下载器，主要用于网页的实际下载
3. scraper: 数据抓取器。主要用于从网页中抓取数据的处理。也就是 ItemPipeLine 的处理