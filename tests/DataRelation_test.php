<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/7/27
 * Time: 20:39
 */
$st = microtime(true);
include __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/config.php';

use  BaAGee\MySQL\SimpleTable;

\BaAGee\MySQL\DBConfig::init($config);

$studentScoreObj  = SimpleTable::getInstance('student_score');
$studentScoreList = $studentScoreObj->limit(3)->select();

$relationObj = new \BaAGee\MySQL\DataRelation();
$relationObj->setData($studentScoreList);
$relationObj->hasOne('class_id', 'class_group.id', ['name', 'create_time'], [], function (&$v) {
    $v['create_time'] = explode(' ', $v['create_time'])[0];
})->hasMany('student_id', 'article.user_id', ['tag'],
    [
        new \BaAGee\MySQL\Expression('id %2= 0'),
    ]);
$studentScoreList = $relationObj->getData();
foreach ($studentScoreList as $item) {
    var_dump($item);
}
echo (microtime(true) - $st) * 1000;