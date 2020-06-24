# php-mysql php操作mysql的简单实现类
php mysql library

不使用ORM或者ActiveRecord，轻量操作MySQL，php自动实现mysql读写分离数据库的选择。
单例模式封装了PDO，使用预处理防止SQL注入

## 安装
`composer require baagee/php-mysql`

## 简单示例：
### 直接执行SQL语句 
```php
// 引入配置文件
$config = include __DIR__ . '/config.php';

/*DB配置初始化*/
\BaAGee\MySQL\DBConfig::init($config);
// 获取DB实例
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
    var_dump(\BaAGee\MySQL\SqlRecorder::getLastSql());
}
/*查询测试*/
$list = $db->query('select * from student_score where id >? order by id desc limit 2', [mt_rand(10, 100)]);
var_dump($list);
var_dump(\BaAGee\MySQL\SqlRecorder::getLastSql());

// 当一次查询数据量大时可以使用yield 返回生成器
$list = $db->yieldQuery('select * from student_score where id>:id', ['id' => 0]);
var_dump($list);
foreach ($list as $i => $item) {
    var_dump($item);
}
var_dump(\BaAGee\MySQL\SqlRecorder::getLastSql());

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
    var_dump(\BaAGee\MySQL\SqlRecorder::getLastSql());

    $sql        = 'update student_score set english=? where id = ?';
    $updateData = [mt_rand(30, 100), 330];

    // throw new Exception('发生失误');
    $db->execute($sql, $updateData);
    var_dump(\BaAGee\MySQL\SqlRecorder::getLastSql());

    return true;
}

var_dump($db->getLastPrepareSql());
var_dump($db->getLastPrepareData());

echo 'OVER' . PHP_EOL;
```

### 使用简单的Table类
```php
include __DIR__ . '/../vendor/autoload.php';

use  BaAGee\MySQL\SimpleTable;

$config = include __DIR__ . '/config.php';

/*DB测试*/
\BaAGee\MySQL\DBConfig::init($config);

$builder = SimpleTable::getInstance('student_score');

/*插入测试*/
$res = $builder->insert(createStudentScoreRow(), true);
var_dump(\BaAGee\MySQL\SqlRecorder::getLastSql());
var_dump($res);

/*批量插入测试*/
$rows = [];
for ($i = 0; $i < 3; $i++) {
    $rows[] = createStudentScoreRow();
}
$res = $builder->insert($rows, true);
var_dump(\BaAGee\MySQL\DB::getLastSql());
var_dump($res);

/*查询测试 多条件嵌套*/
$res = $builder->fields([
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
])->orderBy(['id' => 'desc'])->limit(0, 2)->groupBy('student_name')->lockInShareMode()->select(false);
var_dump(\BaAGee\MySQL\DB::getLastSql());
// var_dump($res);

$res = $builder->fields([
    'avg(chinese)', 'class_id', 'min(`age`)', 'max(math)', 'sum(biology)', 'count(student_id)'
])->where(['id' => ['>', mt_rand(300, 590)]])->groupBy('class_id')->orderBy(['class_id' => 'desc'])->limit(0, 7)->select();
var_dump(\BaAGee\MySQL\DB::getLastSql());
// var_dump($res);

$res = $builder->fields([
    'student_name', 'math', 'english', '`class_id` as cid'
])->where([
    'class_id' => ['between', [1, 5]],
    'sex'      => ['=', 1],
])->orWhere([
    'math'    => ['>', 60],
    'english' => ['<', 60],
    'or',
    (new Expression('id % 2 = 0'))
])->having(['`cid`' => ['>', 3]])->orHaving([
    'cid'  => ['<', 2],
    // 'or',
    'math' => ['>', 60]
])->limit(0, 2)->orderBy(['age' => 'desc', 'student_id' => 'asc'])
    ->groupBy('student_id')->groupBy('math')->lockInShareMode()->select();
var_dump(\BaAGee\MySQL\DB::getLastSql());
// var_dump($res);
// die;

/*更新测试*/
$res = $builder->where(['id' => ['=', mt_rand(300, 590)]])->update(['student_name' => '哈哈哈' . mt_rand(0, 99)]);
var_dump(\BaAGee\MySQL\DB::getLastSql());
var_dump($res);
// 递增递减
$res=$builder->where([
    'id' => ['=', mt_rand(390, 600)]
])->update([
    // 使用表达式
    'english' => (new Expression('english + 1')),
    'math'    => (new Expression('math - 1')),
]);

/*删除测试*/
$res = $builder->where(['id' => ['=', mt_rand(300, 590)]])->delete();
var_dump(\BaAGee\MySQL\DB::getLastSql());
var_dump($res);

// 查询
$res = $builder->where(['id' => ['=', mt_rand(300, 590)]])->fields(['distinct `age`', 'sex'])->select();
var_dump(\BaAGee\MySQL\DB::getLastSql());
var_dump($res);

$article = SimpleTable::getInstance('article');
$res     = $article->insert(createArticleRow());
var_dump(\BaAGee\MySQL\DB::getLastSql());
var_dump($res);
//
// $article->where(['id' => ['>', 20]]);
// var_dump($article);
// $article = SimpleTable::getInstance('article');
// var_dump($article);

// 关联查询
$studentScoreObj = SimpleTable::getInstance('student_score');
$studentScoreList2 = $studentScoreObj->limit(3)->hasOne('class_id', 'class_group.id', ['name', 'create_time'], [], function (&$v) {
    $v['create_time'] = explode(' ', $v['create_time'])[0];
})->hasMany('student_id', 'article.user_id', ['tag'],
    [
        new \BaAGee\MySQL\Expression('id %2= 0'),
    ])->select();
```

### 快捷方法
```php
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
$r = createStudentScoreRow();
$r['id'] = mt_rand(3000, 3100);
// 组建冲突支持自动更新
$student->insert($r, true, [
    'english' => new \BaAGee\MySQL\Expression('Values(english)'),
    'math' => new \BaAGee\MySQL\Expression('Values(math)'),
    'age' => new \BaAGee\MySQL\Expression('Values(age)'),
    'update_time' => time(),
]);
var_dump(SqlRecorder::getLastSql());
// die;
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

echo ((microtime(true) - $st) * 1000) . PHP_EOL;
```

### 支持切换数据库配置
```php
include __DIR__ . '/../vendor/autoload.php';

use BaAGee\MySQL\SimpleTable;
use BaAGee\MySQL\Expression;


$config = include __DIR__ . '/config.php';

/*DB测试*/
\BaAGee\MySQL\DBConfig::init($config);//初始化默认配置
\BaAGee\MySQL\DBConfig::addConfig($config, 'test1');//加入新配置
\BaAGee\MySQL\DBConfig::addConfig($config, 'test2');// 加入新配置
$names = [\BaAGee\MySQL\DBConfig::getCurrentName(), 'test1', 'test2'];// 当前所有配置名
foreach ($names as $name) {
    \BaAGee\MySQL\DBConfig::switchTo($name);//切换到其中一个配置
    echo '切换到：' . $name . PHP_EOL;

    $builder = SimpleTable::getInstance('student_score');

    /*插入测试*/
    $res = $builder->insert(createStudentScoreRow(), true);
    var_dump(\BaAGee\MySQL\DB::getLastSql());
    var_dump($res);

    /*批量插入测试*/
    $rows = [];
    for ($i = 0; $i < 3; $i++) {
        $rows[] = createStudentScoreRow();
    }
    $res = $builder->insert($rows, true);
    var_dump(\BaAGee\MySQL\DB::getLastSql());
    var_dump($res);

    /*查询测试*/
    $res = $builder->fields([
        'id', 'student_name', 'student_id', 'chinese', 'english', 'math', 'biology', 'history', 'class_id', 'age', 'sex'
    ])->where([
        'history' => ['>', '60'],
        'class_id' => ['in', [1, 2, 3, 4]],
        'or',
        (new Expression('id % 2 = 0'))
    ])->where([
        'age' => ['=', 18]
    ])->orderBy(['id' => 'desc'])->limit(0, 2)->groupBy('student_name')->lockInShareMode()->select(false);
    var_dump(\BaAGee\MySQL\SqlRecorder::getLastSql());
    // var_dump($res);
    // die;

    // 强制使用索引
    $res = $res = $builder->forceIndex('student_score_student_id_index', 'student_score_student_name_index')->where(
        ['student_id' => ['=', 1565246274451]]
    )->select();
    var_dump($res);
    var_dump(\BaAGee\MySQL\SqlRecorder::getLastSql());
    // die;
}
```
### 具体使用见tests目录
