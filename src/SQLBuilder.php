<?php
/**
 * Desc: Sql生成器
 * User: baagee
 * Date: 2019/3/15
 * Time: 下午5:15
 */

namespace BaAGee\MySQL;

use BaAGee\MySQL\Base\SingletonTrait;

final class SQLBuilder
{
    use SingletonTrait;

    private $table = '';

    /**
     * 最后一次预处理sql语句
     * @var string
     */
    private $prepareSql = '';
    /**
     * 最后一次处理sql的数据参数
     * @var array
     */
    private $prepareData = [];

    /**
     * 要查询的条件
     * @var string
     */
    private $whereConditions = '';
    /**
     * having条件
     * @var string
     */
    private $havingConditions = '';
    /**
     * order by field desc/asc
     * @var string
     */
    private $orderBy = '';
    /**
     * group by field
     * @var string
     */
    private $groupBy = '';
    /**
     * limit offset,limit
     * @var string
     */
    private $limit = '';
    /**
     * fields
     * @var string
     */
    private $selectFields = '';

    private $tableFields = [];

    /**
     * 加锁类型
     * @var string
     */
    private $lock = '';

    public static function getInstance(string $table, array $columns)
    {
        $table = trim($table);
        if (empty(self::$_instance[$table])) {
            $self                    = new self();
            $self->table             = $table;
            $self->tableFields       = $columns;
            self::$_instance[$table] = $self;
        }
        return self::$_instance[$table];
    }

    public function buildSelect()
    {
        $this->prepareSql = 'SELECT ';
        if (empty($this->selectFields)) {
            $this->prepareSql .= '*';
        } else {
            $this->prepareSql .= $this->selectFields;
        }
        $this->prepareSql .= (' FROM `' . $this->table . '`' . $this->whereConditions . $this->groupBy .
            $this->havingConditions . $this->orderBy . $this->limit . $this->lock);
        return $this;
    }

    public function buildUpdate($data)
    {
        $this->prepareSql = 'UPDATE `' . $this->table . '` SET ';
        foreach ($data as $field => $value) {
            if (in_array($field, array_keys($this->tableFields))) {
                $this->prepareSql                .= '`' . $field . '` = :' . $field . ', ';
                $this->prepareData[':' . $field] = $value;
            }
        }
        $this->prepareSql = rtrim($this->prepareSql, ', ');
        if (!empty($this->whereConditions)) {
            $this->prepareSql .= $this->whereConditions;
        }
        return $this;
    }

    public function buildIncrementOrDecrement(string $field, int $step, bool $isIncrement = true)
    {
        $this->prepareSql = 'UPDATE `' . $this->table . '` SET `' . $field . '` = `' . $field . '`';
        if ($isIncrement) {
            $this->prepareSql .= '+' . $step;
        } else {
            $this->prepareSql .= '-' . $step;
        }
        if (!empty($this->whereConditions)) {
            $this->prepareSql .= $this->whereConditions;
        }
        return $this;
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
     * @throws \Exception
     */
    private function buildConditions(array $conditions, $having = false)
    {
        if (!is_array($conditions)) {
            throw new \Exception('搜索条件不是数组');
        }
        $conditionString = ' (';
        foreach ($conditions as $k => $v) {
            if (in_array($k, array_keys($this->tableFields))) {
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
                    if ($this->tableFields[$k] === 'int') {
                        $vv[0] = intval($vv[0]);
                        $vv[1] = intval($vv[1]);
                    } elseif ($this->tableFields[$k] === 'float') {
                        $vv[0] = floatval($vv[0]);
                        $vv[1] = floatval($vv[1]);
                    } else {
                        $vv[0] = strval($vv[0]);
                        $vv[1] = strval($vv[1]);
                    }
                    $this->prepareData[':' . $z_k . '_min'] = $vv[0];
                    $this->prepareData[':' . $z_k . '_max'] = $vv[1];
                } else if (strpos($w, 'IN') !== false) {
                    // in 不能用参数绑定，预处理
                    $ppp = '';
                    $vv  = array_unique($vv);
                    foreach ($vv as $var) {
                        if ($this->tableFields[$k] === 'int') {
                            $ppp .= intval($var) . ',';
                        } else {
                            $ppp .= '\'' . strval($var) . '\',';
                        }
                    }
                    $conditionString .= ' `' . $k . '` in (' . rtrim($ppp, ',') . ') ' . $op;
                } else {
                    // > < != = like %112233% intval => 0
                    $conditionString .= ' `' . $k . '` ' . $w . ' :' . $z_k . ' ' . $op;
                    if ($this->tableFields[$k] === 'int') {
                        $vv = $w === 'LIKE' ? strval($vv) : intval($vv);
                    } elseif ($this->tableFields[$k] === 'float') {
                        $vv = floatval($vv);
                    } else {
                        $vv = strval($vv);
                    }
                    $this->prepareData[':' . $z_k] = $vv;
                }
            }
        }
        return rtrim(rtrim($conditionString, 'OR'), 'AND') . ') ';
    }

    public function buildWhereOrHaving(array $conditions, bool $isAnd = true, bool $isHaving = false)
    {
        if (empty($conditions)) {
            return $this;
        }
        $conditionString = $this->buildConditions($conditions, $isHaving);
        if ($isHaving) {
            // having
            if (empty($this->havingConditions)) {
                $this->havingConditions = ' HAVING' . $conditionString;
            } else {
                if ($isAnd) {
                    $this->havingConditions .= 'AND' . $conditionString;
                } else {
                    $this->havingConditions .= 'OR' . $conditionString;
                }
            }
        } else {
            // where
            if (empty($this->whereConditions)) {
                $this->whereConditions = ' WHERE' . $conditionString;
            } else {
                if ($isAnd) {
                    $this->whereConditions .= 'AND' . $conditionString;
                } else {
                    $this->whereConditions .= 'OR' . $conditionString;
                }
            }
        }
        return $this;
    }

    public function setLock(string $lock)
    {
        $this->lock = $lock;
        return $this;
    }

    public function setSumOrCountOrAvgOrMinOrMaxField($field, $function)
    {
        $field = trim(trim($field), '`');
        if ($field !== '*') {
            $field = '`' . trim($field, '`') . '`';
        }
        $this->selectFields = strtoupper($function) . '(' . $field . ') AS _' . $function;
        return $this;
    }

    public function buildDelete()
    {
        $this->prepareSql = 'DELETE FROM `' . $this->table . '`';
        if (!empty($this->whereConditions)) {
            $this->prepareSql .= $this->whereConditions;
        }
        return $this;
    }

    public function setOrderBy(array $orderBy)
    {
        if (!empty($this->orderBy)) {
            $str = '';
        } else {
            $str = ' ORDER BY ';
        }
        foreach ($orderBy as $field => $order) {
            $str .= '`' . trim($field, '`') . '` ' . strtoupper($order) . ', ';
        }
        $orderByString = rtrim($str, ', ');
        if (!empty($this->orderBy)) {
            $this->orderBy .= ', ' . $orderByString;
        } else {
            $this->orderBy = $orderByString;
        }
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function setGroupBy(string $field)
    {
        if (!empty($this->groupBy)) {
            $this->groupBy .= ', `' . trim($field, '`') . '`';
        } else {
            $this->groupBy = ' GROUP BY `' . trim($field, '`') . '`';
        }
        return $this;
    }

    public function setLimit(int $offset, int $limit = 0)
    {
        $this->limit = ' LIMIT ';
        if ($limit == 0) {
            $this->limit .= intval($offset);
        } else {
            $this->limit .= intval($offset) . ', ' . intval($limit);
        }
        return $this;
    }

    public function setSelectFields(array $fields)
    {
        foreach ($fields as $field) {
            if ($field === '*') {
                $this->selectFields = ' *';
                break;
            }
            if (in_array($field, array_keys($this->tableFields))) {
                $this->selectFields .= ', `' . trim($field, '`') . '`';
            }
        }
        $this->selectFields = trim($this->selectFields, ',');
        return $this;
    }

    public function buildInsert(array $data)
    {
        $this->prepareSql  = 'INSERT INTO `' . $this->table . '` (';
        $fields            = $placeholder = '';
        $this->prepareData = [];
        foreach ($data as $k => $v) {
            if (in_array($k, array_keys($this->tableFields))) {
                $fields                      .= '`' . $k . '`, ';
                $placeholder                 .= ':' . $k . ', ';
                $this->prepareData[':' . $k] = $v;
            }
        }
        $fields           = rtrim($fields, ', ');
        $placeholder      = rtrim($placeholder, ', ');
        $this->prepareSql .= $fields . ') VALUES(' . $placeholder . ')';
        return $this;
    }

    public function buildBatchInsert(array $data)
    {
        $this->prepareSql  = 'INSERT INTO `' . $this->table . '` (';
        $fields            = [];
        $zz                = '';
        $this->prepareData = [];
        foreach ($data as $i => $item_array) {
            $z = '(';
            foreach ($item_array as $k => $v) {
                if (in_array($k, array_keys($this->tableFields))) {
                    $a = '`' . $k . '`';
                    if (!in_array($a, $fields)) {
                        $fields[] = $a;
                    }
                    $z                                      .= ':' . $k . '_' . $i . ', ';
                    $this->prepareData[':' . $k . '_' . $i] = $v;
                }
            }
            $zz .= rtrim($z, ', ') . '),';
        }
        $fields           = implode(', ', $fields);
        $this->prepareSql .= $fields;
        $this->prepareSql = rtrim($this->prepareSql, ', ') . ') VALUES ' . rtrim($zz, ',');
        return $this;
    }

    public function get(): array
    {
        $res = [
            $this->prepareSql,
            $this->prepareData,
        ];
        $this->reset();
        return $res;
    }

    /**
     * @return array
     */
    public function getPrepareData(): array
    {
        return $this->prepareData;
    }

    /**
     * @return string
     */
    public function getPrepareSql(): string
    {
        return $this->prepareSql;
    }

    /**
     * 清除上次执行的
     */
    public function reset()
    {
        $this->havingConditions = '';
        $this->orderBy          = '';
        $this->whereConditions  = '';
        $this->limit            = '';
        $this->groupBy          = '';
        $this->selectFields     = '';
        $this->prepareSql       = '';
        $this->prepareData      = [];
        $this->lock             = '';
    }
}
