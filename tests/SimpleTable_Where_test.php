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

$student = $builder = SimpleTable::getInstance('student_score');

$resList = $student->whereEqual('sex', 1)
    ->whereGt('chinese', '60')
    ->whereIn('class_id', [1, 2, 3, 4, 5, 6, 7])
    ->whereNotIn('age', [16, 17], false)
    ->whereBetween('math', 60, 99)
    ->whereNotBetween('history', 0, 60, false)
    ->whereGte('english', 60)
    ->whereLt('biology', 90, false)
    ->whereLte('id', 3000)
    ->whereLike('student_name', '槽%', false)
    ->whereNotLike('student_name', '%骆%')
    ->whereNotEqual('is_delete', 1)
    //having
    ->havingEqual('sex', 1)
    ->havingGt('chinese', '70', false)
    ->havingIn('class_id', [1, 2, 3, 4])
    ->havingNotIn('age', [16, 17, 18], false)
    ->havingBetween('math', 60, 90)
    ->havingNotBetween('history', 20, 60, false)
    ->havingGte('english', 70, false)
    ->havingLt('biology', 90)
    ->havingLte('id', 2000)
    ->havingLike('student_name', '槽%', false)
    ->havingNotLike('student_name', '%骆%')
    ->havingNotEqual('is_delete', 1)
    ->select();

// var_dump($resList);
print_r(\BaAGee\MySQL\SqlRecorder::getLastSql()['fullSql'] . PHP_EOL);

$resList = $student->whereEqual('sex', 1, false)
    ->whereGt('chinese', '60', false)
    ->whereIn('class_id', [1, 2, 3, 4, 5, 6, 7], false)
    ->whereNotIn('age', [16, 17], true)
    ->whereBetween('math', 60, 99, false)
    ->whereNotBetween('history', 0, 60, true)
    ->whereGte('english', 60, false)
    ->whereLt('biology', 90, true)
    ->whereLte('id', 3000, false)
    ->whereLike('student_name', '槽%', true)
    ->whereNotLike('student_name', '%骆%', false)
    ->whereNotEqual('is_delete', 1, false)
    //having
    ->havingEqual('sex', 1, false)
    ->havingGt('chinese', '70', true)
    ->havingIn('class_id', [1, 2, 3, 4], false)
    ->havingNotIn('age', [16, 17, 18], true)
    ->havingBetween('math', 60, 90, false)
    ->havingNotBetween('history', 20, 60, true)
    ->havingGte('english', 70, true)
    ->havingLt('biology', 90, false)
    ->havingLte('id', 2000, false)
    ->havingLike('student_name', '槽%', true)
    ->havingNotLike('student_name', '%骆%', false)
    ->havingNotEqual('is_delete', 1, false)
    ->select();

// var_dump($resList);
print_r(\BaAGee\MySQL\SqlRecorder::getLastSql()['fullSql'] . PHP_EOL);


echo (microtime(true) - $st) * 1000;