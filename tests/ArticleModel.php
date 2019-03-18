<?php

/**
 * Desc: 简单示例
 * User: baagee
 * Date: 2019/3/18
 * Time: 下午1:39
 */
include __DIR__ . '/../vendor/autoload.php';

/**
 * Class ArticleModel
 */
class ArticleModel
{
    use \BaAGee\MySQL\Base\SingletonTrait;

    /**
     * @var \BaAGee\MySQL\SimpleTable 加上备注方便代码提示
     */
    protected $table = null;

    /**
     * @return ArticleModel
     * @throws Exception
     */
    public static function getInstance(): ArticleModel
    {
        if (self::$_instance === null) {
            $obj             = new self();
            $obj->table      = \BaAGee\MySQL\SimpleTable::getInstance('article');
            self::$_instance = $obj;
        }
        return self::$_instance;
    }

    /**
     * 根据ID查询一条
     * @param $id
     * @return mixed
     */
    public function getOne($id)
    {
        return $this->table->setWhere('id=:id')->select(['id' => $id])[0];
    }
}

/*DB初始化*/
\BaAGee\MySQL\DB::init(include __DIR__ . '/config.php');

$article = ArticleModel::getInstance();
var_dump($article->getOne(490));

