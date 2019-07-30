<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/7/27
 * Time: 20:39
 */

// include __DIR__ . '/../vendor/autoload.php';

use  BaAGee\MySQL\SimpleTable;


class sqlBuilderTest extends \PHPUnit\Framework\TestCase
{
    protected $config = [];

    protected $simpleTable = null;
    protected $db          = null;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->config = include __DIR__ . '/config.php';
    }

    public function start()
    {
        \BaAGee\MySQL\DBConfig::init($this->config);
        $this->simpleTable = SimpleTable::getInstance('student_score');
        $this->db          = \BaAGee\MySQL\DB::getInstance();
    }

    public function testTableInsert()
    {
        $this->start();
        /*插入测试*/
        $res = $this->simpleTable->insert($this->createStudentScoreRow(), true);
        var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
        var_dump($res);
        $this->assertNotEmpty('4544');
    }

    public function testBatchTableInsert()
    {
        $this->start();
        /*批量插入测试*/
        $rows = [];
        for ($i = 0; $i < 3; $i++) {
            $rows[] = $this->createStudentScoreRow();
        }
        $res = $this->simpleTable->batchInsert($rows, true);
        var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
        var_dump($res);
        $this->assertNotEmpty('$res');
    }

    public function testTableDelete()
    {
        $this->start();
        /*删除测试*/
        $res = $this->simpleTable->where(['id' => ['=', mt_rand(300, 590)]])->delete();
        var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
        var_dump($res);
        $this->assertNotEmpty('$res');
    }

    public function testTableUpdate()
    {
        $this->start();
        /*更新测试*/
        $res = $this->simpleTable->where(['id' => ['=', mt_rand(300, 590)]])->decrement('math', 1);
        var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
        var_dump($res);
        $res = $this->simpleTable->where(['id' => ['=', mt_rand(300, 590)]])->increment('math', 1);
        var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
        var_dump($res);

        $res = $this->simpleTable->where(['id' => ['=', mt_rand(300, 590)]])->update(['student_name' => '哈哈哈' . mt_rand(0, 99)]);
        var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
        var_dump($res);
        $this->assertNotEmpty('$res');
    }

    public function testTableSelect()
    {
        $this->start();
        $res = $this->simpleTable->fields(['*'])->where(['id' => ['=', mt_rand(300, 590)]])->where(['sex' => ['=', 0]])
            ->having(['id' => ['<', 10]])->having(['age' => ['<', 20]])
            ->fields(['distinct `age`', 'sex'])->select(true);
        var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
        var_dump($res);
        /*查询测试 多条件嵌套*/
        $res = $this->simpleTable->fields([
            'student_name', '`student_id`', 'chinese', 'english', 'math', 'biology', 'history', 'class_id', 'age', 'sex'
        ])->where([
            [
                'history'  => ['>', '60'],
                'or',
                'class_id' => ['in', [1, 2, 3, 4]]
            ],
            'or',
            [
                'sex' => ['=', 0],
                'age' => ['<', 19],
                'or',
                [
                    'sex' => ['=', 0],
                    'age' => ['<', 19],
                    'or',
                    [
                        'sex' => ['=', 0],
                        'age' => ['<', 19],
                        'or',
                        [
                            'sex' => ['=', 0],
                            'age' => ['<', 19]
                        ]
                    ]
                ]
            ]
        ])->orWhere([
            'age' => ['=', 18]
        ])->orderBy(['id' => 'desc'])->orderBy(['age' => 'desc'])->limit(0, 2)->groupBy('student_name')->lockInShareMode()->select(false);
        var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
        // var_dump($res);

        $res = $this->simpleTable->fields([
            'avg(chinese)', 'class_id', 'min(`age`)', 'max(math)', 'sum(biology)', 'count(student_id)'
        ])->where(['id' => ['>', mt_rand(300, 590)]])->groupBy('class_id')->orderBy(['class_id' => 'desc'])->limit(0, 7)->select();
        var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
        // var_dump($res);

        $res = $this->simpleTable->fields([
            'student_name', 'math', 'english', '`class_id` as cid', 'age', 'sex'
        ])->where([
            'class_id' => ['between', [1, 5]],
            'sex'      => ['=', 1],
        ])->orWhere([
            'math'    => ['>', 60],
            'english' => ['<', 60],
            // 'or',
        ])->having(['`cid`' => ['>', 3]])->orHaving([
            'cid'  => ['<', 2],
            'or',
            [
                'sex' => ['=', 0],
                'age' => ['<', 19],
                'or',
                [
                    'sex' => ['=', 0],
                    'age' => ['<', 19]
                ]
            ],
            'math' => ['>', 60]
        ])->limit(0, 2)->orderBy(['age' => 'desc', 'student_id' => 'asc'])
            ->groupBy('student_id')->groupBy('math')->lockInShareMode()->select();
        var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
        $this->assertNotEmpty('$res');
    }

    public function test2()
    {
        $this->start();
        $db = \BaAGee\MySQL\DB::getInstance();
        $this->assertEquals($db, $this->db);
        /*插入测试*/
        $sql = 'INSERT INTO student_score
(id,student_name,student_id,english,chinese,math,history,biology,create_time,class_id,sex,age) values 
(null ,:student_name,:student_id,:english,:chinese,:math,:history,:biology,:create_time,:class_id,:sex,:age)';

        for ($i = 0; $i < 5; $i++) {
            $res = $db->execute($sql, $this->createStudentScoreRow());
            // var_dump($res);
            var_dump($this->db->getLastInsertId());
            var_dump($db->getLastSql());
        }
        /*查询测试*/
        $list = $db->query('select * from student_score where id >? order by id desc limit 2', [mt_rand(10, 100)]);
        // var_dump($list);
        var_dump($db->getLastSql());

        // 当一次查询数据量大时可以使用yield 返回生成器
        $list = $db->yieldQuery('select * from student_score where id>:id', ['id' => 0]);
        var_dump($list);
        foreach ($list as $i => $item) {
            // var_dump($item);
        }
        var_dump($db->getLastSql());
        echo 'OVER' . PHP_EOL;
        $this->assertNotEmpty('ooi');
    }

    public function testTransaction()
    {
        $db = \BaAGee\MySQL\DB::getInstance();
        /*测试事务1*/
        $db->beginTransaction();
        try {
            $this->transactionTest($db);
            $db->commit();
            echo '事务成功' . PHP_EOL;
        } catch (Exception $e) {
            echo "事务Error:" . $e->getMessage() . PHP_EOL;
            $db->rollback();
        }

        /*测试事务2*/
        $res = \BaAGee\MySQL\DB::transaction(function () use ($db) {
            $this->transactionTest($db);
        }, [$db]);
        var_dump('测试事务2 结果：' . ($res ? 'ok' : 'error'));

        $res = \BaAGee\MySQL\DB::transaction(function () use ($db) {
            $this->transactionTest($db);
            throw new Exception();
        }, [$db]);

        var_dump($db->getLastPrepareSql());
        var_dump($db->getLastPrepareData());
        $this->assertNotEmpty('ooi');
    }

    protected function createArticleRow()
    {
        return [
            'user_id'     => time() + mt_rand(100, 999),
            'title'       => mt_rand(1000, 9999) . 'title',
            'content'     => str_repeat(mt_rand(1000, 9999) . 'content', mt_rand(3, 5)),
            'tag'         => 'tag',
            'create_time' => time()
        ];
    }

    protected function transactionTest(\BaAGee\MySQL\DB $db)
    {
        $sql      = 'INSERT INTO article(id,user_id,title,content,tag,create_time) values 
(null ,:user_id,:title,:content,:tag,:create_time)';
        $userData = $this->createArticleRow();
        $db->execute($sql, $userData);
        var_dump($db->getLastSql());

        $sql        = 'update student_score set english=? where id = ?';
        $updateData = [mt_rand(30, 100), 330];

        // throw new Exception('发生失误');
        $db->execute($sql, $updateData);
        var_dump($db->getLastSql());

        return true;
    }

    protected function createStudentScoreRow()
    {
        return [
            'student_name' => $this->createStudentName(),
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

    protected function createStudentName()
    {
        $b   = '';
        $num = mt_rand(2, 3);
        for ($i = 0; $i < $num; $i++) {
            $a = chr(mt_rand(0xB0, 0xD0)) . chr(mt_rand(0xA1, 0xF0));
            $b .= iconv('GB2312', 'UTF-8', $a);
        }
        return $b;
    }

}

