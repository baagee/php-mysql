<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/7/27
 * Time: 20:39
 */
include __DIR__ . '/../vendor/autoload.php';

use  BaAGee\MySQL\SimpleTable;
use BaAGee\MySQL\Expression;


$config = include __DIR__ . '/config.php';

/*DB测试*/
\BaAGee\MySQL\DBConfig::init($config);


$builder = SimpleTable::getInstance('student_score');

/*插入测试*/
$res = $builder->insert(createStudentScoreRow(), true);
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
var_dump($res);

/*批量插入测试*/
$rows = [];
for ($i = 0; $i < 3; $i++) {
    $rows[] = createStudentScoreRow();
}
$res = $builder->insert($rows, true);
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
var_dump($res);

/*查询测试*/
$res = $builder->fields([
    'id', 'student_name', 'student_id', 'chinese', 'english', 'math', 'biology', 'history', 'class_id', 'age', 'sex'
])->where([
    'history'  => ['>', '60'],
    'class_id' => ['in', [1, 2, 3, 4]],
    'or',
    (new Expression('id % 2 = 0'))
])->where([
    'age' => ['=', 18]
])->orderBy(['id' => 'desc'])->limit(0, 2)->groupBy('student_name')->lockInShareMode()->select(false);
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
// var_dump($res);
// die;

$res = $builder->fields([
    'avg(chinese)', 'class_id', 'min(`age`)', 'max(math)', 'sum(biology)', 'count(student_id)'
])->where(['id' => ['>', mt_rand(300, 590)]])->groupBy('class_id')->orderBy(['class_id' => 'desc'])->limit(0, 7)->select();
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
// var_dump($res);

$res = $builder->fields([
    'student_name', 'math', 'english', 'class_id as cid'
])->where([
    'class_id' => ['between', [1, 5]],
    'sex'      => ['=', 1],
])->orWhere([
    'math'    => ['>', 60, 'or'],
    'english' => ['<', 60]
])->having(['`cid`' => ['>', 3]])->orHaving([
    'cid' => ['<', 2]
])->limit(0, 2)->orderBy(['age' => 'desc', 'student_id' => 'asc'])
    ->groupBy('student_id')->groupBy('math')->lockInShareMode()->select();
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
// var_dump($res);
// die;

/*更新测试*/
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
var_dump($res);

$res = $builder->where(['id' => ['=', mt_rand(300, 590)]])->update(['student_name' => '哈哈哈' . mt_rand(0, 99)]);
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
var_dump($res);

/*删除测试*/
$res = $builder->where(['id' => ['=', mt_rand(300, 590)]])->delete();
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
var_dump($res);

$article = SimpleTable::getInstance('article');
$res     = $article->insert(createArticleRow());
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
var_dump($res);


function createArticleRow()
{
    return [
        'user_id'     => time() + mt_rand(100, 999),
        'title'       => mt_rand(1000, 9999) . 'title',
        'content'     => str_repeat(mt_rand(1000, 9999) . 'content', mt_rand(3, 5)),
        'tag'         => 'tag',
        'create_time' => time()
    ];
}

function createStudentScoreRow()
{
    return [
        'student_name' => createStudentName(),
        'student_id'   => intval(microtime(true) * 1000) + mt_rand(1000000, 9999999),
        'chinese'      => mt_rand(40, 100),
        'english'      => mt_rand(45, 100),
        'math'         => mt_rand(30, 100),
        'biology'      => mt_rand(47, 98),
        'history'      => mt_rand(53, 100),
        'class_id'     => mt_rand(1, 10),
        'age'          => mt_rand(17, 19),
        'sex'          => mt_rand(1, 2),
        'create_time'  => time()
    ];
}

function createStudentName()
{
    $b   = '';
    $num = mt_rand(2, 3);
    for ($i = 0; $i < $num; $i++) {
        $a = chr(mt_rand(0xB0, 0xD0)) . chr(mt_rand(0xA1, 0xF0));
        $b .= iconv('GB2312', 'UTF-8', $a);
    }
    return $b;
}