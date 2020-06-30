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


$filmActorTable = SimpleTable::getInstance('film_actor');


$filmActorGenera = $filmActorTable->hasOne('film_id', 'film.id', ['name'])
    ->hasOne('actor_id', 'actor.id', ['name'])->select(false);
foreach ($filmActorGenera as $fa) {
    var_dump($fa);
}

$filmActorGenera = $filmActorTable->hasOne('film_id', 'film.id', ['name'])
    ->hasOne('actor_id', 'actor.id', ['name'])->select(true);
foreach ($filmActorGenera as $genus) {
    var_dump($genus);
}

$filmActorTable = \BaAGee\MySQL\FasterTable::getInstance('film_actor');
//
\BaAGee\MySQL\DataRelation::setCacheSize(100);
$filmActorTable->hasOne('actor_id', 'actor.id', ['name'])->hasOne('film_id', 'film.id', ['name']);

$filmActorGenera = $filmActorTable->yieldColumn('film_id', []);
foreach ($filmActorGenera as $genus) {
    var_dump($genus);
}
\BaAGee\MySQL\DataRelation::clearCache();

var_dump($filmActorTable->findColumn('film_id', []));
$filmActorGenera = $filmActorTable->yieldRows([], ['*']);
// var_dump($filmActorGenera);
foreach ($filmActorGenera as $fa) {
    var_dump($fa);
}

$filmActorGenera = $filmActorTable->findRows([], ['*']);

foreach ($filmActorGenera as $fa) {
    var_dump($fa);
}

$filmActorGenera = $filmActorTable->findRow([], ['film_id']);
var_dump($filmActorGenera);

foreach (\BaAGee\MySQL\SqlRecorder::getAllFullSql() as $value) {
    var_dump($value['fullSql']);
}
