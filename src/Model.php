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

class Model extends ModelAbstract implements ModelInterface
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
     * @throws SimException
     */
    final public function where(array $conditions)
    {
        return $this->whereOrHaving($conditions, true, false);
    }

    /**
     * 多次调用or连接 条件查询
     * @param array $conditions 条件
     * @return Model
     * @throws SimException
     */
    final public function orWhere(array $conditions)
    {
        return $this->whereOrHaving($conditions, false, false);
    }

    /**
     * Having查询
     * @param array $having 条件
     * @return Model
     * @throws SimException
     */
    final public function having(array $having)
    {
        return $this->whereOrHaving($having, true, true);
    }

    /**
     * 两个having 之间or连接
     * @param array $having 条件
     * @return Model
     * @throws SimException
     */
    final public function orHaving(array $having)
    {
        return $this->whereOrHaving($having, false, true);
    }

    /**
     * @param array $conditions
     * @param bool  $is_and
     * @param bool  $is_having
     * @return $this
     * @throws SimException
     */
    private function whereOrHaving($conditions, $is_and = true, $is_having = false)
    {
        if (empty($conditions)) {
            return $this;
        }
        $conditionString = $this->buildConditions($conditions, $is_having);
        if ($is_having) {
            // having
            if (empty($this->__havingConditions)) {
                $this->__havingConditions = ' HAVING' . $conditionString;
            } else {
                if ($is_and) {
                    $this->__havingConditions .= 'AND' . $conditionString;
                } else {
                    $this->__havingConditions .= 'OR' . $conditionString;
                }
            }
        } else {
            // where
            if (empty($this->__whereConditions)) {
                $this->__whereConditions = ' WHERE' . $conditionString;
            } else {
                if ($is_and) {
                    $this->__whereConditions .= 'AND' . $conditionString;
                } else {
                    $this->__whereConditions .= 'OR' . $conditionString;
                }
            }
        }
        return $this;
    }

    /**
     * 查询字段，可以多次调用
     * @param array $fields 要查询的字段
     * @return $this
     */
    final public function fields(array $fields)
    {
        foreach ($fields as $field) {
            if ($field === '*') {
                $this->__fields = ' *';
                break;
            }
            if (in_array($field, array_keys($this->__tableSchema['columns']))) {
                $this->__fields .= ', `' . trim($field, '`') . '`';
            }
        }
        $this->__fields = trim($this->__fields, ',');
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
        $this->__limit = ' LIMIT ';
        if ($limit == 0) {
            $this->__limit .= intval($offset);
        } else {
            $this->__limit .= intval($offset) . ', ' . intval($limit);
        }
        return $this;
    }

    /**
     * 分组 可以多次调用
     * @param string $field 分组字段
     * @return $this
     */
    final public function groupBy(string $field)
    {
        if (!empty($this->__groupBy)) {
            $this->__groupBy .= ', `' . trim($field, '`') . '`';
        } else {
            $this->__groupBy = ' GROUP BY `' . trim($field, '`') . '`';
        }
        return $this;
    }

    /**
     * 排序 可以多次调用
     * @param array $order_by
     *      [
     *      'name'=>'desc',
     *      'age'=>'asc'
     *      ]
     * @return $this
     */
    final public function orderBy(array $order_by)
    {
        if (!empty($this->__orderBy)) {
            $str = '';
        } else {
            $str = ' ORDER BY ';
        }
        foreach ($order_by as $field => $order) {
            $str .= '`' . trim($field, '`') . '` ' . strtoupper($order) . ', ';
        }
        $orderByString = rtrim($str, ', ');
        if (!empty($this->__orderBy)) {
            $this->__orderBy .= ', ' . $orderByString;
        } else {
            $this->__orderBy = $orderByString;
        }
        return $this;
    }

    /**
     * 自增
     * @param string $field 要自增的字段
     * @param int    $step  自增步长
     * @return int
     */
    final public function increment($field, $step = 1)
    {
        return $this->incrementOrDecrement($field, $step, true);
    }

    /**
     * 自增自减
     * @param string $field        字段
     * @param int    $step         步长
     * @param bool   $is_increment 是否自增
     * @return int
     */
    private function incrementOrDecrement($field, $step = 1, $is_increment = true)
    {
        $field                  = trim($field, '`');
        $step                   = abs(intval($step));
        $this->__lastPrepareSql = 'UPDATE `' . $this->_table . '` SET `' . $field . '` = `' . $field . '`';
        if ($is_increment) {
            $this->__lastPrepareSql .= '+' . intval($step);
        } else {
            $this->__lastPrepareSql .= '-' . intval($step);
        }
        if (!empty($this->__whereConditions)) {
            $this->__lastPrepareSql .= $this->__whereConditions;
        }
        return $this->__execute();
    }

    /**
     * 自减
     * @param string $field 要自减的字段
     * @param int    $step  步长
     * @return int
     */
    final public function decrement($field, $step = 1)
    {
        return $this->incrementOrDecrement($field, $step, false);
    }

    /**
     * 执行
     * @return int
     */
    private function __execute()
    {
        $res = $this->dbObject->execute($this->__lastPrepareSql, $this->__lastPrepareData);
        $this->__clear();
        return $res;
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
        $this->__lastPrepareSql  = 'INSERT INTO `' . $this->_table . '` (';
        $fields                  = $placeholder = '';
        $this->__lastPrepareData = [];
        foreach ($data as $k => $v) {
            if (in_array($k, array_keys($this->__tableSchema['columns']))) {
                $fields                            .= '`' . $k . '`, ';
                $placeholder                       .= ':' . $k . ', ';
                $this->__lastPrepareData[':' . $k] = $v;
            }
        }
        $fields                 = rtrim($fields, ', ');
        $placeholder            = rtrim($placeholder, ', ');
        $this->__lastPrepareSql .= $fields . ') VALUES(' . $placeholder . ')';
        if ($this->__execute()) {
            return $this->dbObject->lastInsertId();
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
        $this->__lastPrepareSql  = 'INSERT INTO `' . $this->_table . '` (';
        $fields                  = [];
        $zz                      = '';
        $this->__lastPrepareData = [];
        foreach ($data as $i => $item_array) {
            $z = '(';
            if (method_exists($this, '__autoInsert')) {
                $item_array = array_merge($item_array, $this->__autoInsert());
            }
            foreach ($item_array as $k => $v) {
                if (in_array($k, array_keys($this->__tableSchema['columns']))) {
                    if (!in_array($k, $fields)) {
                        $fields[] = $k;
                    }
                    $z                                            .= ':' . $k . '_' . $i . ', ';
                    $this->__lastPrepareData[':' . $k . '_' . $i] = $v;
                }
            }
            $zz .= rtrim($z, ', ') . '),';
        }
        $fields                 = implode(', ', $fields);
        $this->__lastPrepareSql .= $fields;
        $this->__lastPrepareSql = rtrim($this->__lastPrepareSql, ', ') . ') VALUES ' . rtrim($zz, ',');
        return $this->__execute();
    }

    /**
     * 删除数据
     * @return int 返回影响行数
     */
    final public function delete()
    {
        $this->__lastPrepareSql = 'DELETE FROM `' . $this->_table . '`';
        if (!empty($this->__whereConditions)) {
            $this->__lastPrepareSql .= $this->__whereConditions;
        }
        //else 全删除
        return $this->__execute();
    }

    /**
     * 更新数据
     * @param array $data 要更新的数据
     * @return int 返回影响行数
     */
    final public function update(array $data)
    {
        $this->__lastPrepareSql = 'UPDATE `' . $this->_table . '` SET ';
        if (method_exists($this, '__autoUpdate')) {
            $data = array_merge($data, $this->__autoUpdate());
        }
        foreach ($data as $field => $value) {
            if (in_array($field, array_keys($this->__tableSchema['columns']))) {
                $this->__lastPrepareSql                .= '`' . $field . '` = :' . $field . ', ';
                $this->__lastPrepareData[':' . $field] = $value;
            }
        }
        $this->__lastPrepareSql = rtrim($this->__lastPrepareSql, ', ');
        if (!empty($this->__whereConditions)) {
            $this->__lastPrepareSql .= $this->__whereConditions;
        }
        //else 全更新
        return $this->__execute();
    }

    /**
     * 查询数据
     * @return array 结果集二维数组
     */
    final public function select()
    {
        $this->__lastPrepareSql = 'SELECT ';
        if (empty($this->__fields)) {
            $this->__lastPrepareSql .= '*';
        } else {
            $this->__lastPrepareSql .= $this->__fields;
        }
        $this->__lastPrepareSql .= (' FROM `' . $this->_table . '`' . $this->__whereConditions . $this->__groupBy .
            $this->__havingConditions . $this->__orderBy . $this->__limit . $this->__lock);
        return $this->__query();
    }

    /**
     * 查询
     * @return array
     */
    private function __query()
    {
        $res = $this->dbObject->query($this->__lastPrepareSql, $this->__lastPrepareData);
        $this->__clear();
        return $res;
    }

    /**
     * sum|count|avg|min|max 查询
     * @param string $function sum|count|avg|min|max
     * @param string $field    字段
     * @return mixed
     */
    private function sumOrCountOrAvgOrMinOrMax($function, $field)
    {
        $field = trim(trim($field), '`');
        if ($field !== '*') {
            $field = '`' . trim($field, '`') . '`';
        }
        $this->__lastPrepareSql = 'SELECT ' . strtoupper($function) . '(' . $field . ') AS _' . $function . ' FROM `' . $this->_table . '` ';
        if (!empty($this->__whereConditions)) {
            $this->__lastPrepareSql .= $this->__whereConditions;
        }
        //else 查询全部
        $res = $this->__query();
        return $res[0]['_' . $function];
    }

    /**
     * 查询数目
     * @param string $field 字段
     * @return mixed
     */
    final public function count($field = '*')
    {
        return $this->sumOrCountOrAvgOrMinOrMax('count', $field);
    }

    /**
     * 查询字段和
     * @param string $field 字段
     * @return mixed
     */
    final public function sum($field)
    {
        return $this->sumOrCountOrAvgOrMinOrMax('sum', $field);
    }

    /**
     * 求平均数
     * @param string $field 求平均数的字段
     * @return mixed
     */
    final public function avg($field)
    {
        return $this->sumOrCountOrAvgOrMinOrMax('avg', $field);
    }

    /**
     * 查询字段最大值
     * @param string $field 字段
     * @return mixed
     */
    final public function max($field)
    {
        return $this->sumOrCountOrAvgOrMinOrMax('max', $field);
    }

    /**
     * 查询字段最小值
     * @param string $field 字段
     * @return mixed
     */
    final public function min($field)
    {
        return $this->sumOrCountOrAvgOrMinOrMax('min', $field);
    }

    /**
     * 处理条件
     * @param array $conditions
     *                      [
     *                      'field1'=>['>|<|!=|=',value,'or'],
     *                      'field2'=>['like','%like','and'],
     *                      'field3'=>['[not]in',[1,2,3,4,5],'and']
     *                      'field4'=>['between',[start,end],'or']
     *                      ]
     * @param bool  $having 是否为having查询
     * @return string
     * @throws SimException
     */
    private function buildConditions(array $conditions, $having = false)
    {
        if (!is_array($conditions)) {
            throw new \Exception('conditions not be array');
        }
        $conditionString = ' (';
        foreach ($conditions as $k => $v) {
            if (in_array($k, array_keys($this->__tableSchema['columns']))) {
                if ($having) {
                    $z_k = '_h_' . $k . '_' . uniqid();
                } else {
                    $z_k = '_w_' . $k . '_' . uniqid();
                }
                $w  = strtoupper(trim($v[0]));
                $vv = $v[1];
                if (!isset($v[2])) {
                    // 默认and
                    $op = 'AND';
                } else {
                    $op = strtoupper($v[2]);
                }
                if (strpos($w, 'BETWEEN') !== false) {
                    // between
                    $conditionString .= ' `' . $k . '` BETWEEN :' . $z_k . '_min AND :' . $z_k . '_max ' . $op;
                    if ($this->__tableSchema['columns'][$k] === 'int') {
                        $vv[0] = intval($vv[0]);
                        $vv[1] = intval($vv[1]);
                    } elseif ($this->__tableSchema['columns'][$k] === 'float') {
                        $vv[0] = floatval($vv[0]);
                        $vv[1] = floatval($vv[1]);
                    } else {
                        $vv[0] = strval($vv[0]);
                        $vv[1] = strval($vv[1]);
                    }
                    $this->__lastPrepareData[':' . $z_k . '_min'] = $vv[0];
                    $this->__lastPrepareData[':' . $z_k . '_max'] = $vv[1];
                } else if (strpos($w, 'IN') !== false) {
                    // in 不能用参数绑定，预处理
                    $ppp = '';
                    $vv  = array_unique($vv);
                    foreach ($vv as $var) {
                        if ($this->__tableSchema['columns'][$k] === 'int') {
                            $ppp .= intval($var) . ',';
                        } else {
                            $ppp .= '\'' . strval($var) . '\',';
                        }
                    }
                    $conditionString .= ' `' . $k . '` in (' . rtrim($ppp, ',') . ') ' . $op;
                } else {
                    // > < != = like %112233% intval => 0
                    $conditionString .= ' `' . $k . '` ' . $w . ' :' . $z_k . ' ' . $op;
                    if ($this->__tableSchema['columns'][$k] === 'int') {
                        $vv = $w === 'LIKE' ? strval($vv) : intval($vv);
                    } elseif ($this->__tableSchema['columns'][$k] === 'float') {
                        $vv = floatval($vv);
                    } else {
                        $vv = strval($vv);
                    }
                    $this->__lastPrepareData[':' . $z_k] = $vv;
                }
            }
        }
        return rtrim(rtrim($conditionString, 'OR'), 'AND') . ') ';
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
        $this->__lock = ' FOR UPDATE';
        return $this;
    }

    /**
     * +共享锁
     * @return $this
     */
    final public function lockInShareMode()
    {
        $this->__lock = ' LOCK IN SHARE MODE';
        return $this;
    }
}
