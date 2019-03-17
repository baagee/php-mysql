<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/3/14
 * Time: 下午10:45
 */

namespace BaAGee\MySQL;

use BaAGee\MySQL\Base\ModelAbstract;
use BaAGee\MySQL\Base\ModelInterface;

abstract class Model extends ModelAbstract implements ModelInterface
{
    /**
     * 设置查询条件，可以多次调用 and连接
     * @param array $conditions  条件数组
     *                           [
     *                           'field1'=>['>|<|!=|=',value,'or'],
     *                           'field2'=>['like','%like','and'],
     *                           'field3'=>['[not]in',[1,2,3,4,5],'and']
     *                           'field4'=>['between',[start,end],'or']
     *                           ]
     * @return Model
     */
    final public function where(array $conditions)
    {
        $this->sqlBuilder->buildWhereOrHaving($conditions, true, false);
        return $this;
    }

    /**
     * 多次调用or连接 条件查询
     * @param array $conditions 条件
     * @return Model
     */
    final public function orWhere(array $conditions)
    {
        $this->sqlBuilder->buildWhereOrHaving($conditions, false, false);
        return $this;
    }

    /**
     * Having查询
     * @param array $having 条件
     * @return Model
     */
    final public function having(array $having)
    {
        $this->sqlBuilder->buildWhereOrHaving($having, true, true);
        return $this;
    }

    /**
     * 两个having 之间or连接
     * @param array $having 条件
     * @return Model
     */
    final public function orHaving(array $having)
    {
        $this->sqlBuilder->buildWhereOrHaving($having, false, true);
        return $this;
    }

    /**
     * 查询字段，可以多次调用
     * @param array $fields 要查询的字段
     * @return Model
     */
    final public function selectFields(array $fields)
    {
        $this->sqlBuilder->setSelectFields($fields);
        return $this;
    }

    /**
     * limit 不可多次调用
     * @param int $offset offset
     * @param int $limit  limit
     * @return $this
     */
    final public function limit(int $offset, int $limit = 0)
    {
        $this->sqlBuilder->setLimit($offset, $limit);
        return $this;
    }

    /**
     * 分组 可以多次调用
     * @param string $field 分组字段
     * @return $this
     */
    final public function groupBy(string $field)
    {
        $this->sqlBuilder->setGroupBy($field);
        return $this;
    }

    /**
     * 排序 可以多次调用
     * @param array $orderBy
     *      [
     *      'name'=>'desc',
     *      'age'=>'asc'
     *      ]
     * @return $this
     */
    final public function orderBy(array $orderBy)
    {
        $this->sqlBuilder->setOrderBy($orderBy);
        return $this;
    }

    /**
     * 自增
     * @param string $field 要自增的字段
     * @param int    $step  自增步长
     * @return int
     */
    final public function increment(string $field, int $step = 1)
    {
        return $this->incrementOrDecrement($field, $step, true);
    }

    /**
     * 自增自减
     * @param string $field       字段
     * @param int    $step        步长
     * @param bool   $isIncrement 是否自增
     * @return int
     */
    private function incrementOrDecrement($field, $step = 1, $isIncrement = true)
    {
        $field = trim($field, '`');
        $step  = abs(intval($step));
        list($prepareSql, $prepareData) = $this->sqlBuilder->buildIncrementOrDecrement($field, $step, $isIncrement)->buildUpdate()->get();
        return $this->execute($prepareSql, $prepareData);
    }

    /**
     * 自减
     * @param string $field 要自减的字段
     * @param int    $step  步长
     * @return int
     */
    final public function decrement(string $field, int $step = 1)
    {
        return $this->incrementOrDecrement($field, $step, false);
    }

    /**
     * 添加数据
     * @param array $data 数据
     * @return bool|string false|插入的ID
     */
    final public function insert(array $data)
    {
        if (method_exists($this, '__autoInsert')) {
            $data = array_merge($data, $this->__autoInsert());
        }
        list($prepareSql, $prepareData) = $this->sqlBuilder->setInsertFields(array_keys($data))->buildInsert()->get();
        if ($this->execute($prepareSql, $prepareData)) {
            return $this->getLastInsertId();
        } else {
            return false;
        }
    }

    /**
     * 批量添加数据
     * @param array $data 要添加的二维数组
     * @return int 返回插入的行数
     */
    final public function batchInsert(array $data)
    {
        array_walk($data, function (&$item) {
            if (method_exists($this, '__autoInsert')) {
                $item = array_merge($item, $this->__autoInsert());
            }
        });
        list($prepareSql, $prepareData) = $this->sqlBuilder->buildBatchInsert($data)->get();
        if ($this->execute($prepareSql, $prepareData)) {
            return $this->getLastInsertId();
        } else {
            return false;
        }
    }

    /**
     * 删除数据
     * @return int 返回影响行数
     */
    final public function delete()
    {
        list($prepareSql, $prepareData) = $this->sqlBuilder->buildDelete()->get();
        return $this->execute($prepareSql, $prepareData);
    }

    /**
     * 更新数据
     * @param array $data 要更新的数据
     * @return int 返回影响行数
     */
    final public function update(array $data)
    {
        if (method_exists($this, '__autoUpdate')) {
            $data = array_merge($data, $this->__autoUpdate());
        }
        list($prepareSql, $prepareData) = $this->sqlBuilder->setUpdateFields($data)->buildUpdate()->get();
        return $this->execute($prepareSql, $prepareData);
    }

    /**
     * 查询数据
     * @return array 结果集二维数组
     */
    final public function select()
    {
        list($prepareSql, $prepareData) = $this->sqlBuilder->buildSelect()->get();
        return $this->query($prepareSql, $prepareData);
    }

    /**
     * sum|count|avg|min|max
     * @param string $function sum|count|avg|min|max
     * @param string $field    字段
     * @return mixed
     */
    private function sumOrCountOrAvgOrMinOrMax($function, $field)
    {
        $this->sqlBuilder->setSumOrCountOrAvgOrMinOrMaxField($field, $function);
        $res = $this->select();
        return $res[0]['_' . $function];
    }

    /**
     * 查询数目
     * @param string $field 字段
     * @return mixed
     */
    final public function count(string $field = '*')
    {
        return $this->sumOrCountOrAvgOrMinOrMax(__FUNCTION__, $field);
    }

    /**
     * 查询字段和
     * @param string $field 字段
     * @return mixed
     */
    final public function sum(string $field)
    {
        return $this->sumOrCountOrAvgOrMinOrMax(__FUNCTION__, $field);
    }

    /**
     * 求平均数
     * @param string $field 求平均数的字段
     * @return mixed
     */
    final public function avg(string $field)
    {
        return $this->sumOrCountOrAvgOrMinOrMax(__FUNCTION__, $field);
    }

    /**
     * 查询字段最大值
     * @param string $field 字段
     * @return mixed
     */
    final public function max(string $field)
    {
        return $this->sumOrCountOrAvgOrMinOrMax(__FUNCTION__, $field);
    }

    /**
     * 查询字段最小值
     * @param string $field 字段
     * @return mixed
     */
    final public function min(string $field)
    {
        return $this->sumOrCountOrAvgOrMinOrMax(__FUNCTION__, $field);
    }

    /**
     * @return mixed
     */
    final public function getLastSql()
    {
        return $this->dbObject->getLastSql();
    }

    /**
     * @param string $sql
     * @param array  $data
     * @return array
     */
    final public function query(string $sql, array $data = [])
    {
        return $this->dbObject->query($sql, $data);
    }

    /**
     * @param string $sql
     * @param array  $data
     * @return int
     */
    final public function execute(string $sql, array $data = [])
    {
        return $this->dbObject->execute($sql, $data);
    }

    /**
     * @return mixed
     */
    final public function getLastInsertId()
    {
        return $this->dbObject->getLastInsertId();
    }

    /**
     * +排它锁
     * @return $this
     */
    final public function lockForUpdate()
    {
        $this->sqlBuilder->setLock(' FOR UPDATE');
        return $this;
    }

    /**
     * +共享锁
     * @return $this
     */
    final public function lockInShareMode()
    {
        $this->sqlBuilder->setLock(' LOCK IN SHARE MODE');
        return $this;
    }
}
