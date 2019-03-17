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

    /**
     * @var string 表名
     */
    private $table = '';

    /**
     * @var string 预处理sql语句
     */
    private $prepareSql = '';
    /**
     * @var array 处理sql的预处理数据参数
     */
    private $prepareData = [];

    /**
     * @var string where条件
     */
    private $whereConditions = '';
    /**
     * @var string having条件
     */
    private $havingConditions = '';

    /**
     * @var string order by field desc/asc
     */
    private $orderBy = '';

    /**
     * @var string group by field
     */
    private $groupBy = '';

    /**
     * @var string limit offset,limit
     */
    private $limit = '';

    /**
     * @var string 要查询的字段
     */
    private $selectFields = '';

    /**
     * @var array 表字段及其类型 [field1=>int,field2=>string]
     */
    private $tableFields = [];

    /**
     * @var string 加锁类型 lock in share mode / lock for update
     */
    private $lock = '';

    /**
     * @var string 更新的字段eg: name='harry'
     */
    private $updateFields = '';

    /**
     * @var string 插入的字段eg: name,age,sex
     */
    private $insertFields = '';

    /**
     * @var string 插入字段的值eg: ('name',12,'man'),('name',12,'man')
     */
    private $insertValues = '';


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

    public function setWhereConditions(string $whereConditions, $isAnd = true)
    {
        if (empty($this->whereConditions)) {
            $this->whereConditions = ' WHERE ' . $whereConditions;
        } else {
            if ($isAnd) {
                $this->whereConditions .= ' AND ' . $whereConditions;
            } else {
                $this->whereConditions .= ' OR ' . $whereConditions;
            }
        }
        return $this;
    }

    public function setHavingConditions(string $havingConditions, $isAnd = true)
    {
        if (empty($this->havingConditions)) {
            $this->havingConditions = ' HAVING ' . $havingConditions;
        } else {
            if ($isAnd) {
                $this->havingConditions .= ' AND ' . $havingConditions;
            } else {
                $this->havingConditions .= ' OR ' . $havingConditions;
            }
        }
        return $this;
    }

    /**
     * 设置更新的字段及其值
     * @param string $data
     * @return $this
     */
    public function setUpdateFields(string $data)
    {
        if (!empty($this->updateFields)) {
            $this->updateFields .= ', ' . $data;
        } else {
            $this->updateFields = ' ' . $data;
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

    /**
     * 生成了个update语句
     * @return $this
     */
    public function buildUpdate()
    {
        $this->prepareSql = 'UPDATE `' . $this->table . '` SET ' . $this->updateFields;
        if (!empty($this->whereConditions)) {
            $this->prepareSql .= $this->whereConditions;
        }
        return $this;
    }

    /**
     * 生成insert语句
     * @return $this
     */
    public function buildInsert()
    {
        $this->prepareSql = 'INSERT INTO `' . $this->table . '` ' . $this->insertFields . ' VALUES ' . $this->insertValues;
        return $this;
    }


    /**
     * 删除语句
     * @return $this
     */
    public function buildDelete()
    {
        $this->prepareSql = 'DELETE FROM `' . $this->table . '`';
        if (!empty($this->whereConditions)) {
            $this->prepareSql .= $this->whereConditions;
        }
        return $this;
    }

    /**
     * 设置插入的字段
     * @param string $insertFields eg: name/name,age,sex
     * @return $this
     */
    public function setInsertFields(string $insertFields)
    {
        if (!empty($this->insertFields)) {
            $this->insertFields = rtrim($this->insertFields, ')');
            $this->insertFields .= ', ' . $insertFields;
        } else {
            $this->insertFields = '(' . $insertFields;
        }
        $this->insertFields .= ')';
        return $this;
    }

    /**
     * 设置要插入的值
     * @param string $insertValues eg:('name','age','sex')/('name','age','sex'),('name','age','sex')
     * @return $this
     */
    public function setInsertValues(string $insertValues)
    {
        if (!empty($this->insertValues)) {
            $this->insertValues .= ', ' . $insertValues;
        } else {
            $this->insertValues = $insertValues;
        }
        return $this;
    }

    /**
     * 设置查询锁
     * @param string $lock
     * @return $this
     */
    public function setLock(string $lock)
    {
        $this->lock = $lock;
        return $this;
    }

    /**
     * 设置orderBy
     * @param array $orderBy
     * @return $this
     */
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

    /**
     * 设置查询的字段
     * @param string|array $fields eg:[name,age]/'name,age'/'*'
     * @return $this
     */
    public function setSelectFields($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $field) {
                if ($field === '*') {
                    $this->selectFields = ' *';
                    break;
                }
                if (in_array($field, array_keys($this->tableFields))) {
                    $this->selectFields .= ', `' . trim($field, '`') . '`';
                }
            }
        } else {
            if ($fields === '*') {
                $this->selectFields = '*';
            } else {
                $this->selectFields .= ', ' . $fields;
            }
        }
        $this->selectFields = trim($this->selectFields, ',');
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
        $this->lock             = '';
        $this->insertFields     = '';
        $this->insertValues     = '';
        $this->updateFields     = '';
        $this->prepareSql       = '';
        $this->prepareData      = [];
    }
}
