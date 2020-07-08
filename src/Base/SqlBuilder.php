<?php
/**
 * Desc: SQL语句生成
 * User: baagee
 * Date: 2019/7/27
 * Time: 20:34
 */

namespace BaAGee\MySQL\Base;

use BaAGee\MySQL\Expression;

abstract class SqlBuilder
{
    protected const COLUMN_TYPE_INT    = 'int';
    protected const COLUMN_TYPE_FLOAT  = 'float';
    protected const COLUMN_TYPE_STRING = 'string';

    /**
     * 表名
     * @var string
     */
    protected $_tableName = '';
    /**
     * 表文件
     * @var array|mixed
     *
     */
    protected $_tableSchema = [];

    /**
     * 最后一次预处理sql语句
     * @var string
     */
    private $__lastPrepareSql = '';
    /**
     * 强制使用索引
     * @var string
     */
    private $__forceIndex = '';
    /**
     * 最后一次处理sql的数据参数
     * @var array
     */
    private $__lastPrepareData = [];
    /**
     * 要查询的条件
     * @var string
     */
    private $__whereConditions = '';
    /**
     * having条件
     * @var string
     */
    private $__havingConditions = '';
    /**
     * order by field desc/asc
     * @var string
     */
    private $__orderBy = '';
    /**
     * group by field
     * @var string
     */
    private $__groupBy = '';
    /**
     * limit offset,limit
     * @var string
     */
    private $__limit = '';
    /**
     * fields
     * @var string
     */
    private $__fields = '';

    /**
     * 加锁类型
     * @var string
     */
    private $__lock = '';

    /**
     * 清除上次执行的
     */
    protected function _clear()
    {
        $this->__havingConditions = '';
        $this->__forceIndex = '';
        $this->__orderBy = '';
        $this->__whereConditions = '';
        $this->__limit = '';
        $this->__groupBy = '';
        $this->__fields = '';
        $this->__lastPrepareSql = '';
        $this->__lastPrepareData = [];
        $this->__lock = '';
    }

    /**
     * 设置查询条件，可以多次调用 and连接
     * @param array $conditions  条件数组
     *                           [
     *                           'field1'=>['>|>=|<=|<|!=|=',value],
     *                           'field2'=>['[not]like','%like'],
     *                           'field3'=>['[not]in',[1,2,3,4,5]]
     *                           'field4'=>['[not]between',[start,end]]
     *                           ]
     * @return static
     * @throws \Exception
     */
    final public function where(array $conditions)
    {
        return $this->whereOrHaving($conditions, true, false);
    }

    /**
     * 多次调用or连接 条件查询
     * @param array $conditions
     * @return static
     * @throws \Exception
     */
    final public function orWhere(array $conditions)
    {
        return $this->whereOrHaving($conditions, false, false);
    }

    /**
     * @param array $having
     * @return static
     * @throws \Exception
     */
    final public function having(array $having)
    {
        return $this->whereOrHaving($having, true, true);
    }

    /**
     * 两个having 之间or连接
     * @param array $having
     * @return SqlBuilder
     * @throws \Exception
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
     * @throws \Exception
     */
    private function whereOrHaving(array $conditions, bool $is_and = true, bool $is_having = false)
    {
        if (empty($conditions)) {
            return $this;
        }
        $conditionString = '(' . $this->buildConditions($conditions, $is_having) . ')';
        if ($is_having) {
            // having
            if (empty($this->__havingConditions)) {
                $this->__havingConditions = ' HAVING ' . $conditionString;
            } else {
                if ($is_and) {
                    $this->__havingConditions .= ' AND ' . $conditionString;
                } else {
                    $this->__havingConditions .= ' OR ' . $conditionString;
                }
            }
        } else {
            // where
            if (empty($this->__whereConditions)) {
                $this->__whereConditions = ' WHERE ' . $conditionString;
            } else {
                if ($is_and) {
                    $this->__whereConditions .= ' AND ' . $conditionString;
                } else {
                    $this->__whereConditions .= ' OR ' . $conditionString;
                }
            }
        }
        return $this;
    }

    /**
     * 查询字段，可以多次调用
     * @param array $fields 要查询的字段
     * @return static
     */
    final public function fields(array $fields)
    {
        foreach ($fields as $field) {
            $field = trim($field);
            if ($field === '*') {
                $this->__fields = ' *';
                break;
            }
            // if (in_array($field, array_keys($this->_tableSchema['columns']))) {
            if (isset($this->_tableSchema['columns'][$field])) {
                $field = trim($field, '`');
                $this->__fields .= ', `' . trim($field, '`') . '`';
            } else {
                $this->__fields .= ', ' . $field;
            }
        }
        $this->__fields = trim($this->__fields, ',');
        return $this;
    }

    /**
     * limit 不可多次调用
     * @param int $offset offset
     * @param int $limit  limit
     * @return static
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
     * @return static
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
     * @return static
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
     * 批量添加数据
     * @param array $data              要添加的二维数组
     * @param bool  $replace           是否replace
     * @param bool  $ignore            是否ignore
     * @param array $onDuplicateUpdate 唯一键冲突要更新的字段
     * @return array
     */
    final protected function _buildInsertOrReplace(array $data, bool $replace = false, bool $ignore = false, array $onDuplicateUpdate = [])
    {
        $this->__lastPrepareSql = ($replace ? 'REPLACE' : 'INSERT') .
            ((!$replace && $ignore && empty($onDuplicateUpdate)) ? ' IGNORE' : '') .//insert才有可能有IGNORE
            ' INTO `' . $this->_tableName . '` (';
        $fields = [];
        $zz = '';
        $this->__lastPrepareData = [];
        foreach ($data as $i => $item_array) {
            $z = '(';
            foreach ($item_array as $k => $v) {
                // if (in_array($k, array_keys($this->_tableSchema['columns']))) {
                if (isset($this->_tableSchema['columns'][$k])) {
                    if (!in_array($k, $fields)) {
                        $fields[] = $k;
                    }
                    if ($v instanceof Expression) {
                        $z .= sprintf('%s, ', $v);
                    } else {
                        $z .= ':' . $k . '_' . $i . ', ';
                        $this->__lastPrepareData[':' . $k . '_' . $i] = $v;
                    }
                }
            }
            $zz .= rtrim($z, ', ') . '),';
        }
        $fields = sprintf('`%s`', implode('`, `', $fields));
        $this->__lastPrepareSql .= $fields;
        $this->__lastPrepareSql = rtrim($this->__lastPrepareSql, ', ') . ') VALUES ' . rtrim($zz, ',');

        if (!$replace && !empty($onDuplicateUpdate)) {
            //插入 并且设置了$onDuplicateUpdate
            $sub = '';
            foreach ($onDuplicateUpdate as $field => $value) {
                if (isset($this->_tableSchema['columns'][$field])) {
                    if ($value instanceof Expression) {
                        // $value .= sprintf('%s, ', $value);
                        $sub .= sprintf('`%s` = %s, ', $field, $value);
                    } else {
                        $sub .= sprintf('`%s` = :%s_odu, ', $field, $field);
                        $this->__lastPrepareData[':' . $field . '_odu'] = $value;
                    }
                }
            }

            $sub = rtrim($sub, ', ');
            $this->__lastPrepareSql .= ' ON DUPLICATE KEY UPDATE ' . $sub;
        }

        return [$this->__lastPrepareSql, $this->__lastPrepareData];
    }

    /**
     * 删除数据
     * @return array
     */
    final protected function _buildDelete()
    {
        $this->__lastPrepareSql = 'DELETE FROM `' . $this->_tableName . '`';
        if (!empty($this->__whereConditions)) {
            $this->__lastPrepareSql .= $this->__whereConditions;
        }
        //else 全删除
        return [$this->__lastPrepareSql, $this->__lastPrepareData];
    }

    /**
     * 更新数据
     * @param array $data 要更新的数据
     * @return array
     */
    final protected function _buildUpdate(array $data)
    {
        $this->__lastPrepareSql = 'UPDATE `' . $this->_tableName . '` SET ';
        foreach ($data as $field => $value) {
            if (isset($this->_tableSchema['columns'][$field])) {
                if ($value instanceof Expression) {
                    $this->__lastPrepareSql .= sprintf('`' . $field . '` = %s, ', $value);
                } else {
                    $this->__lastPrepareSql .= '`' . $field . '` = :' . $field . ', ';
                    $this->__lastPrepareData[':' . $field] = $value;
                }
            }
        }
        $this->__lastPrepareSql = rtrim($this->__lastPrepareSql, ', ');
        if (!empty($this->__whereConditions)) {
            $this->__lastPrepareSql .= $this->__whereConditions;
        }
        //else 全更新
        return [$this->__lastPrepareSql, $this->__lastPrepareData];
    }

    /**
     * 查询数据
     * @return array 结果集二维数组
     */
    final protected function _buildSelect()
    {
        $this->__lastPrepareSql = 'SELECT ';
        if (empty($this->__fields)) {
            $this->__lastPrepareSql .= '*';
        } else {
            $this->__lastPrepareSql .= $this->__fields;
        }
        $this->__lastPrepareSql .= (' FROM `' . $this->_tableName . '`' . $this->__forceIndex . $this->__whereConditions . $this->__groupBy .
            $this->__havingConditions . $this->__orderBy . $this->__limit . $this->__lock);
        return [$this->__lastPrepareSql, $this->__lastPrepareData];
    }

    /**
     * 获取随机ID
     * @param int $len 长度
     * @return string
     */
    private static function uniqId($len)
    {
        $string = 'qazwsxedcrfvtgbyhnujmikolp0129384756';
        $count = strlen($string) - 1;
        $return = '';
        for ($i = 0; $i < $len; $i++) {
            $return .= $string[mt_rand(0, $count)];
        }
        return $return;
    }

    /**
     * @param string $field
     * @param        $condition
     * @param string $op
     * @param bool   $having
     * @return string
     * @throws \Exception
     */
    private function createSingleCondition(string $field, $condition, string $op = 'AND', bool $having = false)
    {
        $conditionString = '';
        $k = trim($field, '`');
        if ($having == true || ($having == false && isset($this->_tableSchema['columns'][$k]))) {
            if ($condition instanceof Expression) {
                $conditionString .= sprintf(' `%s` %s %s', $k, $condition, $op);
            } elseif (is_array($condition)) {
                if ($having) {
                    $z_k = '_h_' . $k . '_' . self::uniqId(7);
                } else {
                    $z_k = '_w_' . $k . '_' . self::uniqId(7);
                }
                $w = strtoupper(trim($condition[0]));
                $vv = $condition[1];
                if (strpos($w, 'BETWEEN') !== false) {
                    // between
                    $conditionString .= ' `' . $k . '` ' . $w . ' :' . $z_k . '_min AND :' . $z_k . '_max ' . $op;
                    // $conditionString .= ' (`' . $k . '` BETWEEN :' . $z_k . '_min AND :' . $z_k . '_max) ' . $op;
                    if (($this->_tableSchema['columns'][$k] ?? '') === self::COLUMN_TYPE_INT) {
                        $vv[0] = intval($vv[0]);
                        $vv[1] = intval($vv[1]);
                    } elseif (($this->_tableSchema['columns'][$k] ?? '') === self::COLUMN_TYPE_FLOAT) {
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
                    $vv = array_unique($vv);
                    foreach ($vv as $var) {
                        if (($this->_tableSchema['columns'][$k] ?? '') === self::COLUMN_TYPE_INT) {
                            $ppp .= intval($var) . ',';
                        } else {
                            $ppp .= '\'' . strval($var) . '\',';
                        }
                    }
                    $conditionString .= ' `' . $k . '` ' . $w . ' (' . rtrim($ppp, ',') . ') ' . $op;
                    // $conditionString .= ' (`' . $k . '` in (' . rtrim($ppp, ',') . ')) ' . $op;
                } else {
                    // > < != = like %112233% intval => 0
                    $conditionString .= ' `' . $k . '` ' . $w . ' :' . $z_k . ' ' . $op;
                    // $conditionString .= ' (`' . $k . '` ' . $w . ' :' . $z_k . ') ' . $op;
                    if (($this->_tableSchema['columns'][$k] ?? '') === self::COLUMN_TYPE_INT) {
                        $vv = $w === 'LIKE' ? strval($vv) : intval($vv);
                    } elseif (($this->_tableSchema['columns'][$k] ?? '') === self::COLUMN_TYPE_FLOAT) {
                        $vv = floatval($vv);
                    } else {
                        $vv = strval($vv);
                    }
                    $this->__lastPrepareData[':' . $z_k] = $vv;
                }
            } else {
                throw new \Exception('条件格式不合法');
            }
        }
        return $conditionString;
    }

    /**
     * @param array $conditions
     * @param bool  $having
     * @return string
     * @throws \Exception
     */
    private function buildConditions(array $conditions, $having = false)
    {
        $keys = array_keys($conditions);
        $conStr = '';
        foreach ($keys as $index => $key) {
            if (is_string($conditions[$key]) && in_array(strtolower($conditions[$key]), ['or', 'and'])) {
                continue;
            }
            $nextKey = $keys[$index + 1] ?? null;
            if ($nextKey === null) {
                $op = '';
            } else {
                if (is_string($conditions[$nextKey]) && in_array(strtolower($conditions[$nextKey]), ['or', 'and'])) {
                    $op = strtoupper($conditions[$nextKey]);
                } else {
                    $op = 'AND';
                }
            }

            if (is_integer($key) && is_array($conditions[$key])) {
                $str = trim($this->buildConditions($conditions[$key], $having), 'AND OR');
                $conStr .= ' (' . $str . ') ' . $op;
            } elseif (is_integer($key) && $conditions[$key] instanceof Expression) {
                $conStr .= sprintf(' (%s) %s', $conditions[$key], $op);
            } elseif (is_string($key)) {
                // $conStr .= sprintf(' (%s %s %s) %s', $key, $conditions[$key][0], $conditions[$key][1], $op);
                $conStr .= $this->createSingleCondition($key, $conditions[$key], $op, $having);
            }
        }

        return trim($conStr, 'AND OR');
    }

    /**
     * where in查询
     * @param string $field  字段
     * @param array  $values 值数组
     * @param bool   $and    是否and
     * @return $this
     * @throws \Exception
     */
    public function whereIn(string $field, array $values, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }
        if (empty($values) || !is_array($values)) {
            throw new \Exception('values不合法');
        }
        $condition = [$field => ['in', $values]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    /**
     * where not in查询
     * @param string $field  字段
     * @param array  $values 值数组
     * @param bool   $and    是否and
     * @return $this
     * @throws \Exception
     */
    public function whereNotIn(string $field, array $values, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }
        if (empty($values) || !is_array($values)) {
            throw new \Exception('values不合法');
        }
        $condition = [$field => ['not in', $values]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    /**
     * where between查询
     * @param string           $field 字段
     * @param string|float|int $start 开始
     * @param string|float|int $end   结束
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function whereBetween(string $field, $start, $end, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }
        $condition = [$field => ['between', [$start, $end]]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    /**
     * where not between查询
     * @param string           $field 字段
     * @param string|float|int $start 开始
     * @param string|float|int $end   结束
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function whereNotBetween(string $field, $start, $end, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['not between', [$start, $end]]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    /**
     * where like查询
     * @param string $field 字段
     * @param string $like  "abc%" or %"abc" or "%abc%"
     * @param bool   $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function whereLike(string $field, string $like, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }
        if (empty($like)) {
            throw new \Exception('like不能为空');
        }
        $condition = [$field => ['like', $like]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    /**
     * where not like查询
     * @param string $field 字段
     * @param string $like  "abc%" or %"abc" or "%abc%"
     * @param bool   $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function whereNotLike(string $field, string $like, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }
        if (empty($like)) {
            throw new \Exception('like不能为空');
        }
        $condition = [$field => ['not like', $like]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    /**
     * where = 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function whereEqual(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['=', $value]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    /**
     * where != 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function whereNotEqual(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['!=', $value]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    /**
     * where > 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function whereGt(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['>', $value]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    /**
     * where < 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function whereLt(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['<', $value]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    /**
     * where >= 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function whereGte(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['>=', $value]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    /**
     * where <= 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function whereLte(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['<=', $value]];
        if ($and) {
            return $this->where($condition);
        } else {
            return $this->orWhere($condition);
        }
    }

    // HAVING

    /**
     * having in查询
     * @param string $field  字段
     * @param array  $values 值数组
     * @param bool   $and    是否and
     * @return $this
     * @throws \Exception
     */
    public function havingIn(string $field, array $values, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }
        if (empty($values) || !is_array($values)) {
            throw new \Exception('values不合法');
        }
        $condition = [$field => ['in', $values]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * having not in查询
     * @param string $field  字段
     * @param array  $values 值数组
     * @param bool   $and    是否and
     * @return $this
     * @throws \Exception
     */
    public function havingNotIn(string $field, array $values, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }
        if (empty($values) || !is_array($values)) {
            throw new \Exception('values不合法');
        }
        $condition = [$field => ['not in', $values]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * having between查询
     * @param string           $field 字段
     * @param string|float|int $start 开始
     * @param string|float|int $end   结束
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function havingBetween(string $field, $start, $end, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['between', [$start, $end]]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * having not between查询
     * @param string           $field 字段
     * @param string|float|int $start 开始
     * @param string|float|int $end   结束
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function havingNotBetween(string $field, $start, $end, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['not between', [$start, $end]]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * having like查询
     * @param string $field 字段
     * @param string $like  "abc%" or %"abc" or "%abc%"
     * @param bool   $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function havingLike(string $field, string $like, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }
        if (empty($like)) {
            throw new \Exception('like不能为空');
        }
        $condition = [$field => ['like', $like]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * having not like查询
     * @param string $field 字段
     * @param string $like  "abc%" or %"abc" or "%abc%"
     * @param bool   $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function havingNotLike(string $field, string $like, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }
        if (empty($like)) {
            throw new \Exception('like不能为空');
        }
        $condition = [$field => ['not like', $like]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * having = 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function havingEqual(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['=', $value]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * having != 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function havingNotEqual(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['!=', $value]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * having > 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function havingGt(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['>', $value]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * having < 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function havingLt(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['<', $value]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * having >= 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function havingGte(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['>=', $value]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * having <= 查询
     * @param string           $field 字段
     * @param string|int|float $value 值
     * @param bool             $and   是否and
     * @return $this
     * @throws \Exception
     */
    public function havingLte(string $field, $value, bool $and = true)
    {
        if (empty($field)) {
            throw new \Exception('field不能为空');
        }

        $condition = [$field => ['<=', $value]];
        if ($and) {
            return $this->having($condition);
        } else {
            return $this->orHaving($condition);
        }
    }

    /**
     * 强制使用索引
     * @param mixed ...$indexName
     * @return $this
     */
    final public function forceIndex(...$indexName)
    {
        if (!empty($indexName)) {
            $this->__forceIndex = " FORCE INDEX (" . implode(", ", $indexName) . ")";
        }
        return $this;
    }

    /**
     * +排它锁
     * @return static
     */
    final public function lockForUpdate()
    {
        $this->__lock = ' FOR UPDATE';
        return $this;
    }

    /**
     * +共享锁
     * @return static
     */
    final public function lockInShareMode()
    {
        $this->__lock = ' LOCK IN SHARE MODE';
        return $this;
    }
}
