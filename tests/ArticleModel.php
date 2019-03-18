<?php

/**
 * Desc: 简单示例
 * User: baagee
 * Date: 2019/3/18
 * Time: 下午1:39
 */
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
        return $this->simpleTable->setWhere('id=:id')->select(['id' => $id])[0];
    }
}

/*DB初始化*/
\BaAGee\MySQL\DBConfig::init(include __DIR__ . '/config.php');

$article = new ArticleModel();
var_dump($article->getOne(490));

echo 'OVER' . PHP_EOL;