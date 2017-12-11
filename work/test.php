<?php

use Beanbun\Beanbun;
use Beanbun\Middleware\Parser;

require_once(__DIR__ . '/../vendor/autoload.php');

$beanbun        = new Beanbun;
$beanbun->name  = '950d';
$beanbun->count = 5;
$beanbun->seed  = 'https://bj.lianjia.com/ershoufang/beiqijia/l2p4/';
$beanbun->max   = 100;

/*
 [
        'name'     => 'url',
        'selector' => ['body > div.content > div.leftContent > ul > li> div.info.clear > div.title > a', 'href'],
        'repeated' => true,
    ],

 */
$beanbun->middleware(new Parser());
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
    print_r($beanbun->data);
};
$beanbun->start();