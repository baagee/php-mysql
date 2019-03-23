<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/3/15
 * Time: 上午12:27
 */
include __DIR__ . '/../vendor/autoload.php';

$config = include __DIR__ . '/config.php';

/*DB测试*/
\BaAGee\MySQL\DBConfig::init($config);

$article = \BaAGee\MySQL\SimpleTable::getInstance('article');
// 插入
$res = $article->insert(createArticleRow(), true);
var_dump('last insert id=' . $res);

// 删除数组
$res = $article->where('id=:id')->delete(['id' => 500]);
var_dump('delete res=' . $res);

//更新数据
$res = $article->where('id=:id')->updateFields('content=:content')->update(['content' => 'content', 'id' => 490]);
var_dump('update res=' . $res);

// 查询数据
$res = $article->where('id>:id')->orderBy('id desc')->limitOffset(10)->select(['id' => 500]);
// var_dump('select res=', $res);
var_dump($article->getDb()->getLastSql());

$student = \BaAGee\MySQL\SimpleTable::getInstance('student_score');
$res     = $student->selectFields('id,student_name,age,sex')->orderBy('history desc')->groupBy('english')->where('chinese>:chinese or english<:english')->limitOffset(0, 10)->having('sex=:sex')->lock('for update')->select(['chinese' => 69, 'english' => 60, 'sex' => 1]);
// var_dump($res);

var_dump($student->selectFields('count(id) as c')->select());
$db = $article->getDb();
var_dump($db);
echo 'OVER' . PHP_EOL;


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