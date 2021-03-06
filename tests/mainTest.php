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
        \BaAGee\MySQL\DBConfig::addConfig($this->config, 'mysql1');
        // 先初始化sqlRecoder
        \BaAGee\MySQL\SqlRecorder::setSaveHandler(function ($params) {
            // var_dump($params);
            $time = ($params['sqlInfo']['endTime'] - $params['sqlInfo']['startTime']) * 1000;
            $cTime = ($params['sqlInfo']['connectedTime'] - $params['sqlInfo']['startTime']) * 1000;
            $log = sprintf("APP_NAME[%s] success[%s] cost[%s]ms connectTime[%s]ms [SQL] %s" . PHP_EOL, $params['appName'], $params['sqlInfo']['success'] ? 'ok' : 'no',
                $time, $cTime, $params['sqlInfo']['fullSql']);
            echo $log;
            // die;
        }, [
            'appName' => 'test'
        ]);

        $this->simpleTable = SimpleTable::getInstance('student_score');
        $this->db = \BaAGee\MySQL\DB::getInstance();
    }

    protected function printSqlInfo($infoArr)
    {
        echo sprintf('SQL cost[%s]ms %s' . PHP_EOL, ($infoArr['endTime'] - $infoArr['startTime']) * 1000, $infoArr['fullSql']);
    }

    public function testTableInsert()
    {
        /*插入测试*/
        $row = $this->createStudentScoreRow();
        $row['id'] = mt_rand(3000, 4000);
        $res = $this->simpleTable->insert($row, true, $this->createStudentScoreRow());
        $this->printSqlInfo(DB::getLastSql());
        $res = $this->simpleTable->insert($this->createStudentScoreRow(), true);
        $this->printSqlInfo(DB::getLastSql());
        $this->assertEquals($res > 0, true);
    }

    public function testBatchTableInsert()
    {
        /*批量插入测试*/
        $rows = [];
        for ($i = 0; $i < 3; $i++) {
            $r = $this->createStudentScoreRow();
            $rows[] = $r;
        }
        $res = $this->simpleTable->insert($rows, true);
        $this->printSqlInfo(DB::getLastSql());

        $rows = [];
        for ($i = 0; $i < 3; $i++) {
            $r = $this->createStudentScoreRow();
            $r['id'] = mt_rand(3000, 4000);
            $rows[] = $r;
        }
        $onDuplicateUpdate = [
            'student_name' => new Expression('VALUES(student_name)'),
            'student_id' => new Expression('VALUES(student_id)'),
            'chinese' => new Expression('VALUES(chinese)'),
            'english' => new Expression('VALUES(english)'),
            'math' => new Expression('VALUES(math)'),
            'biology' => new Expression('VALUES(biology)'),
            'history' => new Expression('VALUES(history)'),
            'class_id' => new Expression('VALUES(class_id)'),
            'age' => new Expression('VALUES(age)'),
            'sex' => new Expression('VALUES(sex)'),
            'create_time' => time(),
            'update_time' => time(),
        ];
        $res = $this->simpleTable->insert($rows, false, $onDuplicateUpdate);
        $this->printSqlInfo(DB::getLastSql());
        var_dump($res);
        $this->assertNotEmpty('$res');
    }

    public function testTableDelete()
    {
        /*删除测试*/
        $res = $this->simpleTable->where(['id' => ['=', mt_rand(300, 590)]])->delete();
        $this->printSqlInfo(DB::getLastSql());
        var_dump($res);
        $this->assertNotEmpty('$res');
    }

    public function testTableUpdate()
    {
        /*更新测试*/
        $res = $this->simpleTable->where(['id' => ['=', mt_rand(300, 590)]])->update(['student_name' => '哈哈哈' . mt_rand(0, 99)]);
        $this->printSqlInfo(DB::getLastSql());
        var_dump($res);

        $res = $this->simpleTable->where([
            'id' => ['=', mt_rand(390, 600)],
            'or',
            'math' => ['between', [90.2, 99.9]]
        ])->update([
            'english' => (new Expression('english + 1')),
            'math' => (new Expression('math - 1')),
        ]);
        var_dump('递增递减结果：', $res);
        $this->printSqlInfo(DB::getLastSql());
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
        $this->printSqlInfo(DB::getLastSql());
        $res = $this->simpleTable->replace($this->createStudentScoreRow());
        $this->printSqlInfo(DB::getLastSql());
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
        \BaAGee\MySQL\Connection::close(true);
        \BaAGee\MySQL\Connection::close(false);
        $this->assertEquals($link1, $link2);
        $this->assertEquals($link3, $link4);
    }

    public function testTableSelect()
    {
        $res = $this->simpleTable->where(['id' => ['=', mt_rand(300, 590)]])->where(['sex' => ['=', 0]])
            ->having(['id' => ['<', 10]])->having(['age' => ['<', 20]])
            ->fields(['distinct `age`', 'sex'])->select(true);
        $this->printSqlInfo(DB::getLastSql());
        var_dump($res);
        /*查询测试 多条件嵌套*/
        $res = $this->simpleTable->fields([
            'student_name', '`student_id`', 'chinese', 'english', 'math', 'biology', 'history', 'class_id', 'age', 'sex'
        ])->where([
            [
                'history' => ['>', '60'],
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
        $this->printSqlInfo(DB::getLastSql());
        // var_dump($res);

        $res = $this->simpleTable->fields([
            'avg(chinese)', 'class_id', 'min(`age`)', 'max(math)', 'sum(biology)', 'count(student_id)'
        ])->forceIndex('student_score_student_id_index')->where(['id' => ['>', mt_rand(300, 590)]])->groupBy('class_id')->orderBy(['class_id' => 'desc'])
            ->limit(0, 7)->lockForUpdate()->select();
        $this->printSqlInfo(DB::getLastSql());
        // var_dump($res);

        $res = $this->simpleTable->fields([
            'student_name', 'math', 'english', '`class_id` as cid', 'age', 'sex'
        ])->where([
            'class_id' => ['between', [1, 5]],
            'sex' => ['=', 1],
        ])->orWhere([
            'english' => ['<', 60],
            'or',
            'student_name' => new Expression('like \'%哈%\''),
            'math' => ['>', 60],
        ])->having(['`cid`' => ['>', 3]])->orHaving([
            'cid' => ['<', 2],
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
        $this->printSqlInfo(DB::getLastSql());
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
        $this->printSqlInfo(DB::getLastSql());

        // 当一次查询数据量大时可以使用yield 返回生成器
        $list = $db->yieldQuery('select * from student_score where id>:id', ['id' => 0]);
        var_dump($list);
        foreach ($list as $i => $item) {
            // var_dump($item);
        }
        $this->printSqlInfo(DB::getLastSql());
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
            $sql = 'INSERT INTO article(id,user_id1,title,content,tag,create_time) values 
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
        $relationObj = new \BaAGee\MySQL\DataRelation($studentScoreList);
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

    public function testDataRelation2()
    {
        $studentScoreList = $this->simpleTable->limit(3)->hasOne('class_id', 'class_group.id', ['name', 'create_time'], [], function (&$v) {
            $v['create_time'] = explode(' ', $v['create_time'])[0];
        })->hasMany('student_id', 'article.user_id', ['tag'], [
            new \BaAGee\MySQL\Expression('id%2=0')
        ])->select();
        var_dump($studentScoreList);
        $studentScoreList = $this->simpleTable->limit(1)->hasOne('class_id', 'class_group.id', ['name', 'create_time'], [], function (&$v) {
            $v['create_time'] = explode(' ', $v['create_time'])[0];
        })->hasMany('student_id', 'article.user_id', ['tag', 'create_time'], [new \BaAGee\MySQL\Expression('id%2=0')],
            function (&$v) {
                $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            })->select()[0];
        var_dump($studentScoreList);
        $this->assertNotEmpty($studentScoreList);
    }

    public function testRelation3()
    {
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
        $this->assertNotEmpty(42356);
    }

    public function testSwitchTo()
    {
        $res = \BaAGee\MySQL\DBConfig::switchTo('mysql1');
        $this->assertEquals($res, true);
    }

    public function testFasterTable()
    {
        /*插入测试*/
        $student = \BaAGee\MySQL\FasterTable::getInstance('student_score');

        $student->insert($this->createStudentScoreRow(), false);
        $student->insert($this->createStudentScoreRow(), true, ['english' => 100]);
        $rows = [];
        for ($i = 0; $i <= 2; $i++) {
            $rows[] = $this->createStudentScoreRow();
        }
        $student->insert($rows, false);
        $student->replace($this->createStudentScoreRow());
        $rows = [];
        for ($i = 0; $i <= 2; $i++) {
            $rows[] = $this->createStudentScoreRow();
        }
        $student->replace($rows);

        /*删除测试*/
        $student->delete(['id' => ['=', mt_rand(2700, 2999)]]);

        /*修改测试*/
        $student->update(['student_name' => $this->createStudentName()], ['id' => ['=', mt_rand(3000, 3300)]]);
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
        $res = $student->count(['is_delete' => ['=', 0]], ['1'], ['class_id', 'sex'], ['class_id' => 'asc', 'sex' => 'desc']);
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

        $res = $student->complex(['is_delete' => ['=', 0]], [
            'sum' => ['chinese', 'math', 'history', 'biology', 'age'],
            'min' => ['chinese', 'math', 'history', 'biology', 'age'],
            'max' => ['chinese', 'math', 'history', 'biology', 'age'],
            'avg' => ['chinese', 'math', 'history', 'biology', 'age'],
            'count' => '1'
        ], ['class_id', 'sex'], ['class_id' => 'asc', 'sex' => 'desc']);
        var_dump($res);


        $this->assertNotEmpty('sdfgd');
    }

    public function testGetAllFullSql()
    {
        $allSql = \BaAGee\MySQL\SqlRecorder::getAllFullSql();
        foreach ($allSql as $index => $sql) {
            $this->printSqlInfo($sql);
            // echo sprintf('SQL[%d] cost[%s] %s' . PHP_EOL, $index, ($sql['endTime'] - $sql['startTime']) * 1000, $sql['fullSql']);
        }
        $this->assertNotEmpty('sdfgd');
    }

    public function testWhereS()
    {
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
        $lastSql = \BaAGee\MySQL\SqlRecorder::getLastSql()['fullSql'];
        print_r($lastSql . PHP_EOL);
        $this->assertNotEmpty($lastSql);

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
        $lastSql = \BaAGee\MySQL\SqlRecorder::getLastSql()['fullSql'];
        print_r($lastSql . PHP_EOL);
        $this->assertNotEmpty($lastSql);
    }


    protected function createArticleRow()
    {
        return [
            'user_id' => time() + mt_rand(100, 999),
            'title' => mt_rand(1000, 9999) . 'title',
            'content' => str_repeat(mt_rand(1000, 9999) . 'content', mt_rand(3, 5)),
            'tag' => 'tag',
            'create_time' => time()
        ];
    }

    protected function transactionTest(\BaAGee\MySQL\DB $db)
    {
        $sql = 'INSERT INTO article(id,user_id,title,content,tag,create_time) values 
(null ,:user_id,:title,:content,:tag,:create_time)';
        $userData = $this->createArticleRow();
        $db->execute($sql, $userData);
        $this->printSqlInfo(DB::getLastSql());

        $sql = 'update student_score set english=? where id = ?';
        $updateData = [mt_rand(30, 100), 313];

        // throw new Exception('发生失误');
        $db->execute($sql, $updateData);
        $this->printSqlInfo(DB::getLastSql());
        return true;
    }

    protected function createStudentScoreRow()
    {
        return [
            'student_name' => $this->createStudentName(),
            'student_id' => intval(microtime(true) * 1000) + mt_rand(1000000, 9999999),
            'chinese' => mt_rand(40, 100),
            'english' => mt_rand(45, 100),
            'math' => new Expression(mt_rand(30, 100)),
            'biology' => mt_rand(47, 98),
            'history' => mt_rand(53, 100),
            'class_id' => mt_rand(1, 10),
            'age' => mt_rand(17, 19),
            'sex' => mt_rand(1, 2),
            'create_time' => time()
        ];
    }

    protected function createStudentName()
    {
        $b = '';
        $num = mt_rand(2, 3);
        for ($i = 0; $i < $num; $i++) {
            $a = chr(mt_rand(0xB0, 0xD0)) . chr(mt_rand(0xA1, 0xF0));
            $b .= iconv('GB2312', 'UTF-8', $a);
        }
        return $b;
    }

}

