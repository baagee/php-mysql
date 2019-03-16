<?php
/**
 * Desc: Model抽象类
 * User: baagee
 * Date: 2019/3/15
 * Time: 上午12:06
 */

namespace BaAGee\MySQL\Base;

use BaAGee\MySQL\DB;
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
     * @return \BaAGee\MySQL\Model
     * @throws \Exception
     */
    final public static function getInstance()
    {
        if (!isset(self::$_instance[static::class])) {
            $obj = new static();
            if (empty($obj->table)) {
                throw new \Exception('Model table 不能为空');
                // if (empty($table)) {
                // } else {
                //     $obj->table = $table;
                // }
            }
            $obj->dbObject   = DB::getInstance();
            $columns         = self::getTableFields($obj->dbObject->query('DESC `' . $obj->table . '`'));
            $obj->sqlBuilder = SQLBuilder::getInstance($obj->table, $columns);
            unset($columns);
            self::$_instance[static::class] = $obj;
        }
        return self::$_instance[static::class];
    }

    private static function getTableFields($descTable)
    {
        $columns = [];
        foreach ($descTable as $v) {
            if ((strpos($v['Type'], 'int') !== false)) {
                $field_type = 'int';
            } else if (strpos($v['Type'], 'decimal') !== false) {
                $field_type = 'float';
            } else {
                $field_type = 'string';
            }
            $columns[$v['Field']] = $field_type;
        }
        return $columns;
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
