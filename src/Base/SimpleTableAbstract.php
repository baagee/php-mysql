<?php
/**
 * Desc: SimpleTableAbstract
 * User: baagee
 * Date: 2019/3/18
 * Time: 下午4:11
 */

namespace BaAGee\MySQL\Base;

use BaAGee\MySQL\DB;
use BaAGee\MySQL\SimpleTable;

/**
 * Class SimpleTableAbstract
 * @package BaAGee\MySQL\Base
 */
abstract class SimpleTableAbstract
{
    use SingletonTrait;

    /**
     * @var string
     */
    protected $tableName = '';
    /**
     * @var DB
     */
    protected $db = null;

    /**
     * @var string
     */
    protected $where = '';

    /**
     * @var string
     */
    protected $having = '';

    /**
     * @var string
     */
    protected $orderBy = '';

    /**
     * @var string
     */
    protected $groupBy = '';

    /**
     * @var int
     */
    protected $limitOffset = '';

    /**
     * @var string
     */
    protected $lock = '';

    /**
     * @var string
     */
    protected $selectFields = '';

    /**
     * @var string
     */
    protected $updateFields = '';

    /**
     * 设置更新的字段 eg: name=:name, age=:age
     * @param string $updateFields
     * @return $this
     */
    final public function setUpdateFields(string $updateFields)
    {
        $this->updateFields = $updateFields;
        return $this;
    }

    /**
     * 设置查询的字段 eg:count(*)/name,age,sex/*
     * @param string $selectFields
     * @return $this
     */
    final public function setSelectFields(string $selectFields)
    {
        $this->selectFields = $selectFields;
        return $this;
    }

    /**
     * 设置having条件 eg:age>:age and sex=:sex
     * @param string $having
     * @return $this
     */
    final public function setHaving(string $having)
    {
        $this->having = 'HAVING ' . $having;
        return $this;
    }

    /**
     * 设置排序字段 eg:score desc/asc
     * @param string $orderBy
     * @return $this
     */
    final public function setOrderBy(string $orderBy)
    {
        $this->orderBy = 'ORDER BY ' . $orderBy;
        return $this;
    }

    /**
     * 设置分组字段 eg:category,sex
     * @param string $groupBy
     * @return $this
     */
    final public function setGroupBy(string $groupBy)
    {
        $this->groupBy = 'GROUP BY ' . $groupBy;
        return $this;
    }

    /**
     * 设置where条件 eg:sex=:sex or age>:age
     * @param string $where
     * @return $this
     */
    final public function setWhere(string $where)
    {
        $this->where = 'WHERE ' . $where;
        return $this;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return $this
     */
    final public function setLimitOffset(int $limit, int $offset = 0)
    {
        if ($offset == 0) {
            $this->limitOffset = 'LIMIT ' . $limit;
        } else {
            $this->limitOffset = sprintf('LIMIT %d, %d', $offset, $limit);
        }
        return $this;
    }


    /**
     * 设置查询锁 eg:IN SHARE MODE/FOR UPDATE
     * @param string $lock
     * @return $this
     */
    final public function setLock(string $lock)
    {
        $this->lock = $lock;
        return $this;
    }

    /**
     * 获取操作一个表的简单Table类
     * @param string $tableName
     * @return SimpleTable
     * @throws \Exception
     */
    final public static function getInstance($tableName)
    {
        $tableName = trim($tableName);
        if (empty(self::$_instance[$tableName])) {
            $obj                         = new static();
            $obj->tableName              = $tableName;
            $obj->db                     = DB::getInstance();
            self::$_instance[$tableName] = $obj;
        }
        return self::$_instance[$tableName];
    }

    /**
     * 获取DB对象
     * @return DB
     */
    final public function getDb(): DB
    {
        return $this->db;
    }

    /**
     * 清除私有属性
     */
    protected function clear()
    {
        foreach ($this as $field => &$item) {
            if (!in_array($field, ['tableName', 'db'])) {
                $item = '';
            }
        }
    }
}
