<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/3/26
 * Time: 下午5:02
 */
include __DIR__ . '/../vendor/autoload.php';

$config = include __DIR__ . '/config.php';

/*DB配置初始化*/
\BaAGee\MySQL\DBConfig::init($config);

// 获取读操作链接
$link1=\BaAGee\MySQL\Connection::getInstance(true);
$link2=\BaAGee\MySQL\Connection::getInstance(true);
// 获取写操作链接
$link3=\BaAGee\MySQL\Connection::getInstance(false);
$link4=\BaAGee\MySQL\Connection::getInstance(false);
var_dump($link1===$link2);
var_dump($link3===$link4);
var_dump($link1,$link3);
