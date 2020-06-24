<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/7/27
 * Time: 20:39
 */
ini_set('display_errors', 1);
$st = microtime(true);
include __DIR__ . '/../vendor/autoload.php';

use BaAGee\MySQL\SqlRecorder;

$uniqId = function ($len) {
    $string = 'qazwsxedcrfvtgbyhnujmikolp0129384756';
    $count = strlen($string) - 1;
    $return = '';
    for ($i = 0; $i < $len; $i++) {
        $return .= $string{mt_rand(0, $count)};
    }
    return $return;
};

$config = include __DIR__ . '/config.php';

/*DB测试*/
\BaAGee\MySQL\DBConfig::init($config);

// 先初始化sqlRecoder
SqlRecorder::setSaveHandler(function ($params) {
    $time = ($params['sqlInfo']['endTime'] - $params['sqlInfo']['startTime']) * 1000;
    $cTime = ($params['sqlInfo']['connectedTime'] - $params['sqlInfo']['startTime']) * 1000;
    $log = sprintf("success[%s] cost[%s]ms connectTime[%s]ms [SQL] %s" . PHP_EOL, $params['sqlInfo']['success'] ? 'ok' : 'no',
        $time, $cTime, $params['sqlInfo']['fullSql']);
    echo $log;
    // die;
});

/*插入测试*/
$student = \BaAGee\MySQL\FasterTable::getInstance('student_score');

$student->insert(createStudentScoreRow(), false);
$student->insert(createStudentScoreRow(), true, ['english' => 100]);
$rows = [];
for ($i = 0; $i <= 2; $i++) {
    $rows[] = createStudentScoreRow();
}
$student->insert($rows, false);
$student->replace(createStudentScoreRow());
$rows = [];
for ($i = 0; $i <= 2; $i++) {
    $rows[] = createStudentScoreRow();
}
$student->replace($rows);

/*删除测试*/
$student->delete(['id' => ['=', mt_rand(2700, 2999)]]);

/*修改测试*/
$student->update(['student_name' => createStudentName()], ['id' => ['=', mt_rand(3000, 3300)]]);
$student->increment('english', ['id' => ['=', mt_rand(3000, 3300)]]);
$student->increment('english', ['id' => ['=', mt_rand(3000, 3300)]], 2);
$student->decrement('english', ['id' => ['=', mt_rand(3000, 3300)]]);
$student->decrement('english', ['id' => ['=', mt_rand(3000, 3300)]], 2);
/*查询测试*/
$res = $student->findRows(['id' => ['=', mt_rand(2900, 3300)]]);
var_dump($res);
$res = $student->findRow(['id' => ['=', mt_rand(2900, 3300)]]);
var_dump($res);
$res = $student->findColumn('student_name', ['id' => ['=', mt_rand(2900, 3300)]]);
var_dump($res);
$res = $student->findValue('student_name', ['id' => ['=', mt_rand(2900, 3300)]]);
var_dump($res);
$res = $student->yieldRows(['chinese' => ['=', mt_rand(90, 99)]]);
foreach ($res as $re) {
    var_dump($re);
}

$res = $student->yieldColumn('student_name', ['chinese' => ['=', mt_rand(90, 99)]]);
foreach ($res as $re) {
    var_dump($re);
}


$res = $student->exists(['id' => ['=', mt_rand(3000, 3100)]]);
var_dump($res);

$res = $student->findRows(['english' => ['=', mt_rand(90, 100)]], ['*'], ['id' => 'desc'], 10, 10);
var_dump($res);

/*聚合查询测试*/
$res = $student->count(['is_delete' => ['=', 0]], '1', ['class_id', 'sex'], ['class_id' => 'asc', 'sex' => 'desc']);
var_dump($res);
$res = $student->sum(['is_delete' => ['=', 0]], ['english', 'math', 'history'], ['class_id', 'sex'], ['class_id' => 'asc', 'sex' => 'desc']);
var_dump($res);
$res = $student->avg(['is_delete' => ['=', 0]], ['english', 'history'], ['class_id', 'sex'], ['class_id' => 'asc', 'sex' => 'desc']);
// var_dump($res);
$res = $student->min(['is_delete' => ['=', 0]], ['english', 'biology'], ['class_id', 'sex'], ['class_id' => 'asc', 'sex' => 'desc']);
foreach ($res as $re) {
    var_dump($re);
}
// var_dump($res);
$res = $student->max(['is_delete' => ['=', 0]], ['english', 'math'], ['class_id', 'sex'], ['class_id' => 'asc', 'sex' => 'desc']);
// var_dump($res);

function createStudentScoreRow()
{
    return [
        'student_name' => createStudentName(),
        'student_id' => intval(microtime(true) * 1000) + mt_rand(1000000, 9999999),
        'chinese' => mt_rand(40, 100),
        'english' => mt_rand(45, 100),
        'math' => mt_rand(30, 100),
        'biology' => mt_rand(47, 98),
        'history' => mt_rand(53, 100),
        'class_id' => mt_rand(1, 10),
        'age' => mt_rand(17, 19),
        'sex' => mt_rand(1, 2),
        'create_time' => time()
    ];
}

function createStudentName()
{
    $b = '';
    $num = mt_rand(2, 3);
    for ($i = 0; $i < $num; $i++) {
        $a = chr(mt_rand(0xB0, 0xD0)) . chr(mt_rand(0xA1, 0xF0));
        $b .= iconv('GB2312', 'UTF-8', $a);
    }
    return $b;
}

echo ((microtime(true) - $st) * 1000) . PHP_EOL;