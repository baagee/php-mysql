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
$link1 = \BaAGee\MySQL\Connection::getInstance();
$link2 = \BaAGee\MySQL\Connection::getInstance();
// 获取写操作链接
$link3 = \BaAGee\MySQL\Connection::getInstance(false);
$link4 = \BaAGee\MySQL\Connection::getInstance(true);
var_dump($link1 === $link2);
var_dump($link3 === $link4);
\BaAGee\MySQL\Connection::close(false);
//此时link1,2,3还能用
var_dump($link1, $link2, $link3, $link4);
//重新获取一个写链接 已经和1，2，3不一样了
$link5 = \BaAGee\MySQL\Connection::getInstance(false);
var_dump($link5 === $link2);

$s = $link2->query('select 1');
//
var_dump($s->fetchAll());
