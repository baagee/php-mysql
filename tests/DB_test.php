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
\BaAGee\MySQL\DB::init($config);

$db  = \BaAGee\MySQL\DB::getInstance();
$db1 = \BaAGee\MySQL\DB::getInstance();
var_dump($db === $db1);
/*插入测试*/
$sql = 'INSERT INTO student_score
(id,student_name,student_id,english,chinese,math,history,biology,create_time,class_id,sex,age) values 
(null ,:student_name,:student_id,:english,:chinese,:math,:history,:biology,:create_time,:class_id,:sex,:age)';

for ($i = 0; $i < 5; $i++) {
    $res = $db->execute($sql, createStudentScoreRow());
    var_dump($res);
    var_dump($db1->getLastInsertId());
    var_dump($db->getLastSql());
}
/*查询测试*/
$list = $db->query('select * from student_score where id >? order by id desc limit 2', [mt_rand(10, 100)]);
var_dump($list);
var_dump($db->getLastSql());

/*测试事务1*/
$db->beginTransaction();
try {
    transactionTest($db);
    $db->commit();
    echo '事务成功' . PHP_EOL;
} catch (Exception $e) {
    echo "事务Error:" . $e->getMessage() . PHP_EOL;
    $db->rollback();
}

/*测试事务2*/
$res = \BaAGee\MySQL\DB::transaction('transactionTest', [$db]);
var_dump('测试事务2 结果：' . ($res ? 'ok' : 'error'));

function transactionTest(\BaAGee\MySQL\DB $db)
{
    $sql      = 'INSERT INTO article(id,user_id,title,content,tag,create_time) values 
(null ,:user_id,:title,:content,:tag,:create_time)';
    $userData = createArticleRow();
    $db->execute($sql, $userData);
    var_dump($db->getLastSql());

    $sql        = 'update student_score set english=? where id = ?';
    $updateData = [mt_rand(30, 100), 330];

    // throw new Exception('发生失误');
    $db->execute($sql, $updateData);
    var_dump($db->getLastSql());

    return true;
}

var_dump($db->getLastPrepareSql());
var_dump($db->getLastPrepareData());
var_dump($db->getPDOStatement());

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