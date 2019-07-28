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
use BaAGee\MySQL\Base\SqlBuilder;

/**
 * Class SimpleTable
 * @package BaAGee\MySQL
 */
final class SimpleTable extends SqlBuilder implements SimpleTableInterface
{
    use SingletonTrait;

    /**
     * @var DB
     */
    protected $_dbInstance = null;

    /**
     * 获取表结构
     * @param $tableName
     * @return array
     * @throws \Exception
     */
    private static function getTableSchema($tableName)
    {
        $sql     = 'DESC `' . $tableName . '`';
        $res     = DB::getInstance()->query($sql);
        $schema  = [];
        $columns = [];
        foreach ($res as $v) {
            if ($v['Key'] === 'PRI') {
                $schema['primaryKey'] = $v['Field'];
            }
            if ($v['Extra'] === 'auto_increment') {
                $schema['autoIncrement'] = $v['Field'];
            }
            if ((strpos($v['Type'], 'int') !== false)) {
                $field_type = 'int';
            } else if (strpos($v['Type'], 'decimal') !== false) {
                $field_type = 'float';
            } else {
                $field_type = 'string';
            }
            $columns[$v['Field']] = $field_type;
        }
        $schema['columns'] = $columns;
        return $schema;
    }

    /**
     * 获取操作一个表的简单Table类
     * @param $tableName
     * @return $this
     * @throws \Exception
     */
    final public static function getInstance(string $tableName)
    {
        $tableName = trim($tableName);
        if (empty($tableName)) {
            throw new \Exception('表名不能为空');
        }
        if (empty(self::$_instance[$tableName])) {
            $obj                         = new static();
            $obj->_tableName             = $tableName;
            $obj->_tableSchema           = self::getTableSchema($tableName);
            $obj->_dbInstance            = DB::getInstance();
            self::$_instance[$tableName] = $obj;
        }
        return self::$_instance[$tableName];
    }

    /**
     * 插入数据insert into 或者replace into 返回插入的ID
     * @param array $data
     * @param bool  $replace
     * @return int|null
     * @throws \Exception
     */
    final public function insert(array $data, bool $replace = false)
    {
        $res = $this->_buildInsert($data, $replace);
        $res = $this->_dbInstance->execute($res['sql'], $res['data']);
        $this->_clear();
        if ($res == 1) {
            return $this->_dbInstance->getLastInsertId();
        } else {
            return null;
        }
    }

    /**
     * 批量插入
     * @param array $rows
     * @param bool  $replace
     * @return int|null
     * @throws \Exception
     */
    final public function batchInsert(array $rows, bool $replace = false)
    {
        $res = $this->_buildBatchInsert($rows, $replace);
        $res = $this->_dbInstance->execute($res['sql'], $res['data']);
        $this->_clear();
        if ($res > 0) {
            return $res;
        } else {
            return null;
        }
    }

    /**
     * 删除数据
     * @return int
     * @throws \Exception
     */
    final public function delete()
    {
        $res = $this->_buildDelete();
        $res = $this->_dbInstance->execute($res['sql'], $res['data']);
        $this->_clear();
        return $res;
    }

    /**
     * 更新数据
     * @param array $data
     * @return int
     * @throws \Exception
     */
    final public function update(array $data = [])
    {
        $res = $this->_buildUpdate($data);
        $res = $this->_dbInstance->execute($res['sql'], $res['data']);
        $this->_clear();
        return $res;
    }

    /**
     * @param bool $generator
     * @return array|\Generator
     * @throws \Exception
     */
    final public function select(bool $generator = false)
    {
        $res = $this->_buildSelect();

        if ($generator) {
            $res = $this->_dbInstance->yieldQuery($res['sql'], $res['data']);
        } else {
            $res = $this->_dbInstance->query($res['sql'], $res['data']);
        }
        $this->_clear();
        return $res;
    }

    /**
     * 自增
     * @param string $field
     * @param int    $step
     * @return array|int
     * @throws \Exception
     */
    final public function increment(string $field, $step = 1)
    {
        $res = $this->_buildIncrement($field, $step);
        $res = $this->_dbInstance->execute($res['sql'], $res['data']);
        $this->_clear();
        return $res;
    }

    /**
     * 自减
     * @param string $field
     * @param int    $step
     * @return array|int
     * @throws \Exception
     */
    final public function decrement(string $field, $step = 1)
    {
        $res = $this->_buildDecrement($field, $step);
        $res = $this->_dbInstance->execute($res['sql'], $res['data']);
        $this->_clear();
        return $res;
    }
}
