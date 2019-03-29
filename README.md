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

$config = include __DIR__ . '/config.php';

/*配置初始化*/
\BaAGee\MySQL\DBConfig::init($config);
// 传入表名
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
     * 根据ID查询一条
     * @param $id
     * @return mixed
     */
    public function getOne($id)
    {
        return $this->simpleTable->where('id=:id')->select(['id' => $id])[0];
    }
}

/*DB初始化*/
\BaAGee\MySQL\DBConfig::init(include __DIR__ . '/config.php');

$article = new ArticleModel();
var_dump($article->getOne(490));

echo 'OVER' . PHP_EOL;
```

### 具体使用见tests目录
