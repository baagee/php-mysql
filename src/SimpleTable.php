<?php
/**
 * Desc: 简单封装DB类
 * User: baagee
 * Date: 2019/3/17
 * Time: 下午10:05
 */

namespace BaAGee\MySQL;

use BaAGee\MySQL\Base\SimpleTableAbstract;
use BaAGee\MySQL\Base\SimpleTableInterface;

/**
 * Class SimpleTable
 * @package BaAGee\MySQL
 */
final class SimpleTable extends SimpleTableAbstract implements SimpleTableInterface
{
    /**
     * 插入数据insert into 或者replace into 返回插入的ID
     * @param array $data
     * @param bool  $replace
     * @return int|null
     * @throws \Exception
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
        $sql     = trim(sprintf('%s INTO `%s`(%s) VALUES %s', $replace ? 'REPLACE' : 'INSERT', $this->tableName, $columns, $holder));
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
     * @throws \Exception
     */
    final public function delete(array $data = [])
    {
        $sql = trim(sprintf('DELETE FROM `%s` %s', $this->tableName, $this->where));
        $res = $this->db->execute($sql, $data);
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
        $sql = trim(sprintf('UPDATE `%s` SET %s %s', $this->tableName, $this->updateFields, $this->where));
        $res = $this->db->execute($sql, $data);
        $this->clear();
        return $res;
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    final public function select(array $data = [])
    {
        $sql = trim(sprintf('SELECT %s FROM `%s` %s %s %s %s %s %s',
            !empty($this->selectFields) ? $this->selectFields : '*', $this->tableName, $this->where, $this->groupBy,
            $this->having, $this->orderBy, $this->limitOffset, $this->lock));
        if ($this->selectYield) {
            $res = $this->db->yieldQuery($sql, $data);
        } else {
            $res = $this->db->query($sql, $data);
        }
        $this->clear();
        return $res;
    }
}
