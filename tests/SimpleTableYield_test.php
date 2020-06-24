<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/7/27
 * Time: 20:39
 */
$st = microtime(true);
include __DIR__ . '/../vendor/autoload.php';

use  BaAGee\MySQL\SimpleTable;


$config = include __DIR__ . '/config.php';

/*DB测试*/
\BaAGee\MySQL\DBConfig::init($config);


$filmTable = SimpleTable::getInstance('film');
$actorTable = SimpleTable::getInstance('actor');
$filmActorTable = SimpleTable::getInstance('film_actor');


$filmActorGenera = $filmActorTable->select(true);
foreach ($filmActorGenera as $fa) {
    var_dump($fa);
    $film = $filmTable->where(['id' => ['=', $fa['film_id']]])->select();
    var_dump($film);
    $actor = $actorTable->where(['id' => ['=', $fa['actor_id']]])->select();
    var_dump($actor);
}


$filmTable = \BaAGee\MySQL\FasterTable::getInstance('film');
$actorTable = \BaAGee\MySQL\FasterTable::getInstance('actor');
$filmActorTable = \BaAGee\MySQL\FasterTable::getInstance('film_actor');


$filmActorGenera = $filmActorTable->yieldRows([], ['*']);
// var_dump($filmActorGenera);
foreach ($filmActorGenera as $fa) {
    var_dump($fa);
    $film = $filmTable->findRow(['id' => ['=', $fa['film_id']]]);
    var_dump($film);
    $actor = $actorTable->findRow(['id' => ['=', $fa['actor_id']]]);
    var_dump($actor);
}
var_dump(\BaAGee\MySQL\SqlRecorder::getLastSql());

