<?php
/**
 * Desc: 简单封装DB类
 * User: baagee
 * Date: 2019/3/17
 * Time: 下午10:05
 */

namespace BaAGee\MySQL;

use BaAGee\MySQL\Base\SimpleTableInterface;
use BaAGee\MySQL\Base\SingletonTrait;

/**
 * Class SimpleTable
 * @package BaAGee\MySQL
 */
final class SimpleTable implements SimpleTableInterface
{
    use SingletonTrait;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var DB
     */
    protected $db = null;

    /**
     * @var string
     */
    private $where = '';

    /**
     * @var string
     */
    private $having = '';

    /**
     * @var string
     */
    private $orderBy = '';

    /**
     * @var string
     */
    private $groupBy = '';

    /**
     * @var int
     */
    private $limitOffset = '';

    /**
     * @var string
     */
    private $lock = '';

    /**
     * @var string
     */
    private $selectFields = '';

    /**
     * @var string
     */
    private $updateFields = '';

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
     * @param string $table
     * @return SimpleTable
     * @throws \Exception
     */
    final public static function getInstance($table): SimpleTable
    {
        $table = trim($table);
        if (empty(self::$_instance[$table])) {
            $obj                     = new self();
            $obj->table              = $table;
            $obj->db                 = DB::getInstance();
            self::$_instance[$table] = $obj;
        }
        return self::$_instance[$table];
    }

    /**
     * 插入数据insert into 或者replace into 返回插入的ID
     * @param array $data
     * @param bool  $replace
     * @return int|null
     */
    final public function insert(array $data, bool $replace = false)
    {
        $columns = $holder = [];
        foreach ($data as $column => $value) {
            $columns[] = sprintf('`%s`', $column);
            $holder[]  = ':' . $column;
        }
        $columns = implode(', ', $columns);
        $holder  = sprintf('(%s)', implode(',', $holder));
        $sql     = sprintf('%s INTO `%s`(%s) VALUES %s', $replace ? 'REPLACE' : 'INSERT', $this->table, $columns, $holder);
        $res     = $this->db->execute($sql, $data);
        $this->clear();
        if ($res == 1) {
            return $this->db->getLastInsertId();
        } else {
            return null;
        }
    }

    /**
     * 删除数据
     * @param array $data
     * @return int
     */
    final public function delete(array $data = [])
    {
        $sql = trim(sprintf('DELETE FROM `%s` %s', $this->table, $this->where));
        $res = $this->db->execute($sql, $data);
        $this->clear();
        return $res;
    }

    /**
     * 更新数据
     * @param array $data
     * @return int
     */
    final public function update(array $data = [])
    {
        $sql = sprintf('UPDATE `%s` SET %s %s', $this->table, $this->updateFields, $this->where);
        $res = $this->db->execute($sql, $data);
        $this->clear();
        return $res;
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
     * @param array $data
     * @return array
     */
    final public function select(array $data = [])
    {
        $sql = trim(sprintf('SELECT %s FROM `%s` %s %s %s %s %s %s',
            !empty($this->selectFields) ? $this->selectFields : '*', $this->table, $this->where, $this->groupBy,
            $this->having, $this->orderBy, $this->limitOffset, $this->lock));
        $res = $this->db->query($sql, $data);
        $this->clear();
        return $res;
    }

    /*
     * 清除私有属性
     */
    private function clear()
    {
        foreach ($this as $field => &$item) {
            if (!in_array($field, ['table', 'db'])) {
                $item = '';
            }
        }
    }
}
