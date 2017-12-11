<?php
/**
 * Created by PhpStorm.
 * User: zgj
 * Date: 2017/12/9 下午11:04
 *
 */
require_once(__DIR__ . '/../vendor/autoload.php');

use Beanbun\Beanbun;
$beanbun = new Beanbun;
$beanbun->seed = [
    'http://www.950d.com/',
    'http://www.950d.com/list-1.html',
    'http://www.950d.com/list-2.html',
];
$beanbun->afterDownloadPage = function($beanbun) {
    file_put_contents(__DIR__ . '/' . md5($beanbun->url), $beanbun->page);
};
$beanbun->start();