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
    var_dump($db->getLastSql());
}
/*查询测试*/
$list = $db->query('select * from student_score where id >? order by id desc limit 2', [mt_rand(10, 100)]);
var_dump($list);
var_dump($db->getLastSql());

// 当一次查询数据量大时可以使用yield 返回生成器
$list = $db->yieldQuery('select * from student_score where id>:id', ['id' => 0]);
var_dump($list);
foreach ($list as $i => $item) {
    var_dump($item);
}
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
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
// var_dump($res);

$res = $builder->fields([
    'avg(chinese)', 'class_id', 'min(`age`)', 'max(math)', 'sum(biology)', 'count(student_id)'
])->where(['id' => ['>', mt_rand(300, 590)]])->groupBy('class_id')->orderBy(['class_id' => 'desc'])->limit(0, 7)->select();
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
// var_dump($res);

$res = $builder->fields([
    'student_name', 'math', 'english', '`class_id` as cid'
])->where([
    'class_id' => ['between', [1, 5]],
    'sex'      => ['=', 1],
])->orWhere([
    'math'    => ['>', 60],
    'english' => ['<', 60],
    // 'or',
])->having(['`cid`' => ['>', 3]])->orHaving([
    'cid'  => ['<', 2],
    // 'or',
    'math' => ['>', 60]
])->limit(0, 2)->orderBy(['age' => 'desc', 'student_id' => 'asc'])
    ->groupBy('student_id')->groupBy('math')->lockInShareMode()->select();
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
// var_dump($res);
// die;

/*更新测试*/
$res = $builder->where(['id' => ['=', mt_rand(300, 590)]])->update(['student_name' => '哈哈哈' . mt_rand(0, 99)]);
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
var_dump($res);

/*删除测试*/
$res = $builder->where(['id' => ['=', mt_rand(300, 590)]])->delete();
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
var_dump($res);

// 查询
$res = $builder->where(['id' => ['=', mt_rand(300, 590)]])->fields(['distinct `age`', 'sex'])->select();
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
var_dump($res);

$article = SimpleTable::getInstance('article');
$res     = $article->insert(createArticleRow());
var_dump(\BaAGee\MySQL\DB::getInstance()->getLastSql());
var_dump($res);
//
// $article->where(['id' => ['>', 20]]);
// var_dump($article);
// $article = SimpleTable::getInstance('article');
// var_dump($article);
```

### 稍微封装成Model
```php
include __DIR__ . '/../vendor/autoload.php';

/**
 * Class BaseModel
 */
abstract class BaseModel
{
    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var \BaAGee\MySQL\SimpleTable
     */
    protected $simpleTable = null;

    public function __construct()
    {
        $this->simpleTable = \BaAGee\MySQL\SimpleTable::getInstance($this->table);
    }
}

/**
 * Class ArticleModel
 */
class ArticleModel extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'article';

    /**
     * @param $id
     * @return array|Generator
     * @throws Exception
     */
    public function getOne($id)
    {
        return $this->simpleTable->where([
                'id' => ['=', $id]
            ])->select()[0] ?? [];
    }
}

/*DB初始化*/
\BaAGee\MySQL\DBConfig::init(include __DIR__ . '/config.php');

$article = new ArticleModel();
var_dump($article->getOne(490));

echo 'OVER' . PHP_EOL;
```

### 具体使用见tests目录
