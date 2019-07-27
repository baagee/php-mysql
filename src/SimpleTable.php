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
final class SimpleTable extends SqlBuilder implements SimpleTableInterface
{
    use SingletonTrait;

    /**
     * @var DB
     */
    protected $db = null;

    /**
     * 获取操作一个表的简单Table类
     * @param $tableName
     * @return $this
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
     * 插入数据insert into 或者replace into 返回插入的ID
     * @param array $data
     * @param bool  $replace
     * @return int|null
     * @throws \Exception
     */
    final public function insert(array $data, bool $replace = false)
    {
        $res = $this->buildInsert($data, $replace);
        $res = $this->db->execute($res['sql'], $res['data']);
        $this->clear();
        var_dump($this);
        if ($res == 1) {
            return $this->db->getLastInsertId();
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
        $res = $this->buildBatchInsert($rows, $replace);
        $res = $this->db->execute($res['sql'], $res['data']);
        $this->clear();
        if ($res > 0) {
            return $res;
        } else {
            return null;
        }
    }

    /**
     * 删除数据
     * @param array $data
     * @return int
     * @throws \Exception
     */
    final public function delete()
    {
        $res = $this->buildDelete();
        $res = $this->db->execute($res['sql'], $res['data']);
        $this->clear();
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
        $res = $this->buildUpdate($data);
        $res = $this->db->execute($res['sql'], $res['data']);
        $this->clear();
        return $res;
    }

    /**
     * @param bool $generator
     * @return array|\Generator
     * @throws \Exception
     */
    final public function select($generator = false)
    {
        $res = $this->buildSelect();
        var_dump($res);
        die;
        if ($generator) {
            $res = $this->db->yieldQuery($res['sql'], $res['data']);
        } else {
            $res = $this->db->query($res['sql'], $res['data']);
        }
        $this->clear();
        return $res;
    }
}
