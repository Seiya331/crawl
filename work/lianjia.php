<?php
/**
 * Created by PhpStorm.
 * User: zgj
 * Date: 2017/12/9 下午11:05
 *
 */

use Beanbun\Beanbun;
use Beanbun\Lib\Db;
use Beanbun\Middleware\Parser;

require_once(__DIR__ . '/../vendor/autoload.php');

// 数据库配置
Db::$config['lianjia'] = [
    'server'        => '127.0.0.1',
    'port'          => '3306',
    'username'      => 'root',
    'password'      => '123',
    'database_name' => 'house',
    'charset'       => 'utf8',
];

function getProxies($beanbun)
{
    $client           = new \GuzzleHttp\Client();
    $beanbun->proxies = [];
    $pattern          =
        '/<tr><td>(.+)<\/td><td>(\d+)<\/td>(.+)(HTTP|HTTPS)<\/td><td><div class=\"delay (fast_color|mid_color)(.+)<\/tr>/isU';

    for ($i = 1; $i < 5; $i++) {
        $res  = $client->get("http://www.mimiip.com/gngao/$i");
        $html = str_replace(['  ', "\r", "\n"], '', $res->getBody());
        preg_match_all($pattern, $html, $match);
        foreach ($match[1] as $k => $v) {
            $proxy = strtolower($match[4][$k]) . "://{$v}:{$match[2][$k]}";
            echo "get proxy $proxy ";
            try {
                $client->get('http://www.baidu.com', [
                    'proxy'   => $proxy,
                    'timeout' => 1,
                ]);
                $beanbun->proxies[] = $proxy;
                echo "success.\n";
            } catch (\Exception $e) {
                echo "error.\n";
            }
        }
    }
}

$beanbun           = new Beanbun;
$beanbun->name     = 'lianjia';
$beanbun->count    = 5;
$beanbun->interval = 4;
// 使用远程 redis 队列
$beanbun->setQueue('redis', [
    'host' => '127.0.0.1',
    'port' => '6379',
]);
$beanbun->seed = 'https://bj.lianjia.com/ershoufang/beiqijia/l2p4/';
$beanbun->max  = 100;

$beanbun->logFile = __DIR__ . '/zhihu_user_access.log';
if (false && $argv[1] == 'start') {
    getProxies($beanbun);
}

$beanbun->startWorker = function ($beanbun) {
    // 每隔半小时，更新一下代理池
    Beanbun::timer(1800, 'getProxies', $beanbun);
};

$beanbun->beforeDownloadPage = function ($beanbun) {
    // 在爬取前设置请求的 headers
    $beanbun->options['headers'] = [
        'Connection'      => 'keep-alive',
        'Cache-Control'   => 'max-age=0',
        'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
        'Accept'          => 'application/json, text/plain, */*',
        'Accept-Encoding' => 'gzip, deflate, sdch, br',
    ];

    if (isset($beanbun->proxies) && count($beanbun->proxies)) {
        $beanbun->options['proxy'] = $beanbun->proxies[array_rand($beanbun->proxies)];
    }
};
$parser                      = new Parser;
$beanbun->middleware($parser);

$beanbun->fields = [
    [
        'name'     => 'url',
        'selector' => ['body > div.content > div.leftContent > ul > li> div.info.clear > div.title > a', 'href'],
        'repeated' => true,
    ],
    //
    [
        'name'     => 'page-data',
        'selector' => [
            'body > div.content > div.leftContent > div.contentBottom.clear > div.page-box.fr > div',
            'page-data'
        ],
        'repeated' => true,
    ],
    [
        'name'     => 'page-url',
        'selector' => [
            'body > div.content > div.leftContent > div.contentBottom.clear > div.page-box.fr > div',
            'page-url'
        ],
        'repeated' => true,
    ],


];


$beanbun->afterDownloadPage = function ($beanbun) {
    $data = $beanbun->data;
    var_dump($data);
    exit;
    // 如果没有数据或报错，那可能是被屏蔽了。就把地址才重新加回队列
    if (empty($data)) {
        $beanbun->queue()->add($beanbun->url);
        $beanbun->error();
    }
    /*$beanbun->fields = [
        'name' => 'url',
        'selector' => ['#lessResultIds ul li.clear a.img','href'],
        'repeated' => true,
    ];*/

    $data = $beanbun->parser->getData([
        'name'     => 'url',
        'selector' => ['#lessResultIds ul li.clear a.img', 'href'],
        'repeated' => true,
    ]);
    print_r($data);
    exit();

    // 如果本次爬取的不是最后一页，就把下一页加入队列
    if ($data['paging']['is_end'] == false) {
        $beanbun->queue()->add($data['paging']['next']);
    }

    $insert = [];
    $date   = date('Y-m-d H:i:s');

    if (count($insert)) {
        echo $insert;
        //Db::instance('house')->insert('zhihu_user', $insert);
    }
    // 把刚刚爬取的地址标记为已经爬取
    $beanbun->queue()->queued($beanbun->queue);
};
// 不需要框架来发现新的网址，
$beanbun->discoverUrl = function () { };
$beanbun->start();