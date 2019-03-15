<?php
/**
 * Desc: Model抽象类
 * User: baagee
 * Date: 2019/3/15
 * Time: 上午12:06
 */

namespace BaAGee\MySQL\Base;

use BaAGee\MySQL\DB;
use BaAGee\MySQL\Model;
use BaAGee\MySQL\SQLBuilder;

/**
 * Class ModelAbstract
 * @package BaAGee\MySQL\Base
 */
abstract class ModelAbstract
{
    use SingletonTrait;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var DB
     */
    protected $dbObject = null;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var SQLBuilder
     */
    protected $sqlBuilder = null;

    /**
     * @param string $table
     * @return Model
     * @throws \Exception
     */
    final public static function getInstance($table = ''): Model
    {
        // todo 如何做
        if (!isset(self::$_instance[$table])) {
            $obj = new static();
            if (empty($obj->table)) {
                if (empty($table)) {
                    throw new \Exception('Model table 不能为空');
                } else {
                    $obj->table = $table;
                }
            }
            $obj->dbObject   = DB::getInstance();
            $obj->sqlBuilder = SQLBuilder::getInstance($obj->table);
            // todo 获取fields
            self::$_instance[static::class] = $obj;
        }
        return self::$_instance[static::class];
    }


    /**
     * 自动插入的字段
     * @return array
     */
    protected function __autoUpdate()
    {
        return [];
    }

    /**
     * 自动更新的字段
     * @return array
     */
    protected function __autoInsert()
    {
        return [];
    }
}
