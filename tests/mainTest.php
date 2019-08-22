<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/7/27
 * Time: 20:39
 */

include __DIR__ . '/../vendor/autoload.php';

use BaAGee\MySQL\SimpleTable;
use BaAGee\MySQL\Expression;
use BaAGee\MySQL\DB;


class mainTest extends \PHPUnit\Framework\TestCase
{
    protected $config = [];

    /**
     * @var SimpleTable
     */
    protected $simpleTable = null;
    /**
     * @var \BaAGee\MySQL\DB
     */
    protected $db = null;

    public function setUp()
    {
        $this->start();
    }

    public function start()
    {
        $this->config = include __DIR__ . '/config.php';
        \BaAGee\MySQL\DBConfig::init($this->config);
        $this->simpleTable = SimpleTable::getInstance('student_score');
        $this->db          = \BaAGee\MySQL\DB::getInstance();
    }

    public function testTableInsert()
    {
        /*插入测试*/
        $res = $this->simpleTable->insert($this->createStudentScoreRow(), true, $this->createStudentScoreRow());
        echo "SQL:" . DB::getLastSql();
        $res = $this->simpleTable->insert($this->createStudentScoreRow(), true);
        echo "SQL:" . DB::getLastSql();
        $this->assertEquals($res > 0, true);
    }

    public function testBatchTableInsert()
    {
        /*批量插入测试*/
        $rows = [];
        for ($i = 0; $i < 3; $i++) {
            $rows[] = $this->createStudentScoreRow();
        }
        $res = $this->simpleTable->insert($rows, true);
        echo "SQL:" . DB::getLastSql();
        $res = $this->simpleTable->insert($rows, false, $this->createStudentScoreRow());
        echo "SQL:" . DB::getLastSql();
        var_dump($res);
        $this->assertNotEmpty('$res');
    }

    public function testTableDelete()
    {
        /*删除测试*/
        $res = $this->simpleTable->where(['id' => ['=', mt_rand(300, 590)]])->delete();
        echo "SQL:" . DB::getLastSql();
        var_dump($res);
        $this->assertNotEmpty('$res');
    }

    public function testTableUpdate()
    {
        /*更新测试*/
        $res = $this->simpleTable->where(['id' => ['=', mt_rand(300, 590)]])->update(['student_name' => '哈哈哈' . mt_rand(0, 99)]);
        echo "SQL:" . DB::getLastSql();
        var_dump($res);

        $res = $this->simpleTable->where([
            'id'   => ['=', mt_rand(390, 600)],
            'or',
            'math' => ['between', [90.2, 99.9]]
        ])->update([
            'english' => (new Expression('english + 1')),
            'math'    => (new Expression('math - 1')),
        ]);
        var_dump('递增递减结果：', $res);
        echo 'SQL:' . DB::getLastSql() . PHP_EOL;
        $this->assertNotEmpty('$res');
    }

    public function testReplace()
    {
        /*批量插入测试*/
        $rows = [];
        for ($i = 0; $i < 3; $i++) {
            $rows[] = $this->createStudentScoreRow();
        }
        $res = $this->simpleTable->replace($rows);
        echo "SQL:" . DB::getLastSql();
        $res = $this->simpleTable->replace($this->createStudentScoreRow());
        echo "SQL:" . DB::getLastSql();
        var_dump($res);
        $this->assertNotEmpty('$res');
    }

    public function test4()
    {
        \BaAGee\MySQL\Connection::close();
        $slaveId = \BaAGee\MySQL\Connection::getSlaveId();
        var_dump($slaveId);
        $this->assertNotEmpty('dsgds');
    }

    public function testConnection()
    {
        // 获取读操作链接
        $link1 = \BaAGee\MySQL\Connection::getInstance(true);
        $link2 = \BaAGee\MySQL\Connection::getInstance(true);
        // 获取写操作链接
        $link3 = \BaAGee\MySQL\Connection::getInstance(false);
        $link4 = \BaAGee\MySQL\Connection::getInstance(false);
        $this->assertEquals($link1, $link2);
        $this->assertEquals($link3, $link4);
    }

    public function testTableSelect()
    {
        $res = $this->simpleTable->where(['id' => ['=', mt_rand(300, 590)]])->where(['sex' => ['=', 0]])
            ->having(['id' => ['<', 10]])->having(['age' => ['<', 20]])
            ->fields(['distinct `age`', 'sex'])->select(true);
        echo "SQL:" . DB::getLastSql();
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
                    'age' => ['<', '19'],
                    'or',
                    [
                        'sex' => ['=', 0],
                        'age' => ['<', 19],
                        'or',
                        [
                            'sex' => ['=', 0],
                            'age' => ['<', 19]
                        ],
                        (new Expression('id % 2 = 0'))
                    ],
                    'or',
                    (new Expression('id % 2 = 0'))
                ]
            ]
        ])->orWhere([
            'age' => ['=', 18]
        ])->orderBy(['id' => 'desc'])->orderBy(['age' => 'desc'])->limit(0, 2)->groupBy('student_name')->lockInShareMode()->select(false);
        echo "SQL:" . DB::getLastSql();
        // var_dump($res);

        $res = $this->simpleTable->fields([
            'avg(chinese)', 'class_id', 'min(`age`)', 'max(math)', 'sum(biology)', 'count(student_id)'
        ])->where(['id' => ['>', mt_rand(300, 590)]])->groupBy('class_id')->orderBy(['class_id' => 'desc'])
            ->limit(0, 7)->lockForUpdate()->select();
        echo "SQL:" . DB::getLastSql();
        // var_dump($res);

        $res = $this->simpleTable->fields([
            'student_name', 'math', 'english', '`class_id` as cid', 'age', 'sex'
        ])->where([
            'class_id' => ['between', [1, 5]],
            'sex'      => ['=', 1],
        ])->orWhere([
            'math'         => ['>', 60],
            'english'      => ['<', 60],
            'or',
            'student_name' => new Expression('like \'%哈%\'')
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
        ])->limit(2)->orderBy(['age' => 'desc', 'student_id' => 'asc'])
            ->groupBy('student_id')->groupBy('math')->lockInShareMode()->select();
        echo "SQL:" . DB::getLastSql();
        $this->simpleTable->where(['id' => ['<', 400]])->limit(1)->select();
        $this->simpleTable->fields(['*'])->where(['id' => ['<', 400]])->limit(1)->select();
        $this->assertNotEmpty('$res');
    }

    public function test2()
    {
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
            var_dump(\BaAGee\MySQL\SqlRecorder::getLastSql());
        }
        /*查询测试*/
        $list = $db->query('select * from student_score where id >? order by id desc limit 2', [mt_rand(10, 100)]);
        // var_dump($list);
        echo "SQL:" . DB::getLastSql();

        // 当一次查询数据量大时可以使用yield 返回生成器
        $list = $db->yieldQuery('select * from student_score where id>:id', ['id' => 0]);
        var_dump($list);
        foreach ($list as $i => $item) {
            // var_dump($item);
        }
        echo "SQL:" . DB::getLastSql();
        $this->assertNotEmpty('ooi');
    }

    public function test1()
    {
        try {
            SimpleTable::getInstance('');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), '表名不能为空');
        }
    }

    public function test3()
    {
        try {
            $sql      = 'INSERT INTO article(id,user_id1,title,content,tag,create_time) values 
(null ,:user_id,:title,:content,:tag,:create_time)';
            $userData = $this->createArticleRow();
            $this->db->execute($sql, $userData);
        } catch (Exception $e) {

        }
        $this->assertNotEmpty('fdsgs');
    }

    public function testTransaction()
    {
        $db = \BaAGee\MySQL\DB::getInstance();
        /*测试事务1*/
        $db->beginTransaction();
        try {
            $this->transactionTest($db);

            $db->beginTransaction();
            try {
                $this->transactionTest($db);
                $db->commit();
                echo '内部事务成功' . PHP_EOL;
            } catch (Exception $e) {
                $db->rollback();
                echo "内部事务Error:" . $e->getMessage() . PHP_EOL;
            }
            $db->commit();
            echo '事务成功' . PHP_EOL;
        } catch (Exception $e) {
            echo "事务Error:" . $e->getMessage() . PHP_EOL;
            $db->rollback();
        }

        /*测试事务2*/
        $res = \BaAGee\MySQL\DB::transaction(function () use ($db) {
            $db->beginTransaction();
            try {
                $this->transactionTest($db);
                throw new Exception('内部事务2故意失败');
                $db->commit();
                echo '内部事务成功' . PHP_EOL;
            } catch (Exception $e) {
                $db->rollback();
                echo "内部事务Error:" . $e->getMessage() . PHP_EOL;
            }
            return $this->transactionTest($db);
        }, [$db]);
        var_dump('测试事务2 结果：' . ($res ? 'ok' : 'error'));

        $res = \BaAGee\MySQL\DB::transaction(function () use ($db) {
            throw new Exception();
            return $this->transactionTest($db);
        }, [$db]);
        var_dump('测试事务3 结果：' . ($res ? 'ok' : 'error'));

        $this->assertNotEmpty('ooi');
    }

    public function testDataRelation()
    {
        $studentScoreList = $this->simpleTable->limit(3)->select();
        $relationObj      = new \BaAGee\MySQL\DataRelation($studentScoreList);
        $relationObj->hasOne('class_id', 'class_group.id', ['name', 'create_time'], [], function (&$v) {
            $v['create_time'] = explode(' ', $v['create_time'])[0];
        })->hasMany('student_id', 'article.user_id', ['tag'], [new \BaAGee\MySQL\Expression('id%2=0')]);
        $studentScoreList = $relationObj->getData();

        $studentScoreList = $this->simpleTable->limit(1)->select()[0];
        $relationObj->setData($studentScoreList);
        $relationObj->hasOne('class_id', 'class_group.id', ['name', 'create_time'], [], function (&$v) {
            $v['create_time'] = explode(' ', $v['create_time'])[0];
        })->hasMany('student_id', 'article.user_id', ['tag', 'create_time'], [new \BaAGee\MySQL\Expression('id%2=0')],
            function (&$v) {
                $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            });
        $studentScoreList = $relationObj->getData();
        var_dump($studentScoreList);
        $this->assertNotEmpty($studentScoreList);
    }

    public function testGetAllFullSql()
    {
        $allSql = \BaAGee\MySQL\SqlRecorder::getAllFullSql();
        foreach ($allSql as $index => $sql) {
            echo sprintf('SQL[%d] %s' . PHP_EOL, $index, $sql);
        }
        $this->assertNotEmpty('sdfgd');
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
        echo "SQL:" . DB::getLastSql();

        $sql        = 'update student_score set english=? where id = ?';
        $updateData = [mt_rand(30, 100), 313];

        // throw new Exception('发生失误');
        $db->execute($sql, $updateData);
        echo "SQL:" . DB::getLastSql();
        return true;
    }

    protected function createStudentScoreRow()
    {
        return [
            'student_name' => $this->createStudentName(),
            'student_id'   => intval(microtime(true) * 1000) + mt_rand(1000000, 9999999),
            'chinese'      => mt_rand(40, 100),
            'english'      => mt_rand(45, 100),
            'math'         => new Expression(mt_rand(30, 100)),
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

