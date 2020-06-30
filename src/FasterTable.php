<?php
/**
 * Desc: 快速进行增删改查的类
 * User: baagee
 * Date: 2020/6/24
 * Time: 上午10:15
 */

namespace BaAGee\MySQL;

use BaAGee\MySQL\Base\SingletonTrait;

/**
 * Class FasterTable
 * @method sum(array $conditions, array $columns, array $groupBy = [], array $orderBy = [], bool $yield = false)
 * @method min(array $conditions, array $columns, array $groupBy = [], array $orderBy = [], bool $yield = false)
 * @method max(array $conditions, array $columns, array $groupBy = [], array $orderBy = [], bool $yield = false)
 * @method avg(array $conditions, array $columns, array $groupBy = [], array $orderBy = [], bool $yield = false)
 * @method count(array $conditions, array $columns, array $groupBy = [], array $orderBy = [], bool $yield = false)
 * @method complex(array $conditions, array $type2columns, array $groupBy = [], array $orderBy = [], bool $yield = false)
 * @method $this hasOne(string $leftColumn, string $rightTableColumn, array $fields = ['*'], array $conditions = [], $callback = null);
 * @method $this hasMany(string $leftColumn, string $rightTableColumn, array $fields = ['*'], array $conditions = [], $callback = null);
 * @package BaAGee\MySQL
 */
class FasterTable
{
    /**
     * @var SimpleTable
     */
    protected $simpleTable = null;
    use SingletonTrait;

    /**
     * @var array 保存表关系
     */
    protected $_relations = [];

    /**
     * 清空数据表关系
     * @return $this
     */
    public function clearRelations()
    {
        $this->_relations = [];
        return $this;
    }

    /**
     * 获取的simpleTable对象
     * @return SimpleTable
     */
    public function getSimpleTable()
    {
        return $this->simpleTable;
    }

    /**
     * 获取实例
     * @param string $tableName
     * @return static|null
     * @throws \Exception
     */
    public static function getInstance(string $tableName)
    {
        if (empty($tableName)) {
            throw new \Exception('表名不能为空');
        }

        if (empty(static::$_instance[$tableName])) {
            $static = new static();
            $static->simpleTable = SimpleTable::getInstance($tableName);
            static::$_instance[$tableName] = $static;
        }
        return static::$_instance[$tableName];
    }

    /**
     * 查找多行数据
     * @param array $columns    查找的字段
     * @param array $conditions 查找条件
     * @param array $orderBy    排序
     * @param int   $offset     offset
     * @param null  $limit      limit
     * @return \Generator
     * @throws \Exception
     */
    public function yieldRows(array $conditions, array $columns = ['*'], array $orderBy = [], int $offset = 0, $limit = null)
    {
        return $this->_findRows($conditions, $columns, $orderBy, $offset, $limit, true);
    }

    /**
     * @param array          $conditions
     * @param array|string[] $columns
     * @param array          $orderBy
     * @param int            $offset
     * @param null           $limit
     * @param bool           $yield
     * @return array|\Generator
     * @throws \Exception
     */
    protected function _findRows(array $conditions, array $columns = ['*'], array $orderBy = [], int $offset = 0, $limit = null, bool $yield = false)
    {
        $offset = intval($offset);
        $limit = intval($limit);
        if ($offset < 0 || $limit < 0) {
            throw new \Exception('offset或者limit不允许小于0');
        }

        $this->simpleTable->fields($columns)->where($conditions);
        if (!empty($orderBy)) {
            $this->simpleTable->orderBy($orderBy);
        }
        if ($limit != 0) {
            // limit>0才会使用offset+limit
            $this->simpleTable->limit($offset, $limit);
        }
        if (!empty($this->_relations)) {
            foreach ($this->_relations as $relation) {
                $method = $relation['method'];
                if (in_array('*', $columns) || in_array($relation['left_column'], $columns)) {
                    $this->simpleTable->$method($relation['left_column'], $relation['right_column'],
                        $relation['fields'], $relation['conditions'], $relation['callback']);
                }
            }
        }
        return $this->simpleTable->select($yield);
    }

    /**
     * 查找多行数据
     * @param array $columns    查找的字段
     * @param array $conditions 查找条件
     * @param array $orderBy    排序
     * @param int   $offset     offset
     * @param null  $limit      limit
     * @return array
     * @throws \Exception
     */
    public function findRows(array $conditions, array $columns = ['*'], array $orderBy = [], int $offset = 0, $limit = null)
    {
        return $this->_findRows($conditions, $columns, $orderBy, $offset, $limit, false);
    }

    /**
     * 获取一行数据
     * @param array $columns    查询的列
     * @param array $conditions 查找条件
     * @param array $orderBy    排序
     * @return array|mixed
     * @throws \Exception
     */
    public function findRow(array $conditions, array $columns = ['*'], array $orderBy = [])
    {
        return $this->findRows($conditions, $columns, $orderBy, 0, 1)[0] ?? [];
    }

    /**
     * 保存数据 支持批量 多维数组
     * @param array $data                    保存的数据数组
     * @param bool  $ignore                  唯一键 冲突是否忽略
     * @param array $onDuplicateUpdateFields 唯一键冲突时更新的数据
     * @return int|null
     * @throws \Exception
     */
    public function insert(array $data, bool $ignore = false, array $onDuplicateUpdateFields = [])
    {
        if (empty($data)) {
            throw new \Exception('要插入的数据不能为空');
        }
        return $this->simpleTable->insert($data, $ignore, $onDuplicateUpdateFields);
    }

    /**
     * 保存数据 支持批量 多维数组
     * @param array $data 保存的数据数组
     * @return int|null
     * @throws \Exception
     */
    public function replace(array $data)
    {
        if (empty($data)) {
            throw new \Exception('要插入的数据不能为空');
        }
        return $this->simpleTable->replace($data);
    }

    /**
     * 删除数据
     * @param array $conditions 条件
     * @return int
     * @throws \Exception
     */
    public function delete(array $conditions)
    {
        return $this->simpleTable->where($conditions)->delete();
    }

    /**
     * 更新数据
     * @param array $newRows    新的行
     * @param array $conditions 条件
     * @return int
     * @throws \Exception
     */
    public function update(array $newRows, array $conditions)
    {
        if (empty($newRows)) {
            throw new \Exception('新数据不能为空');
        }
        // if (empty($conditions)) {
        //     throw new \Exception('更新条件不能为空');
        // }
        return $this->simpleTable->where($conditions)->update($newRows);
    }

    /**
     * 字段自增
     * @param string $column     列
     * @param array  $conditions 条件
     * @param int    $step       步长
     * @throws \Exception
     */
    public function increment(string $column, array $conditions, int $step = 1)
    {
        $this->simpleTable->where($conditions)->update([$column => new Expression(sprintf('%s + %d', $column, $step))]);
    }

    /**
     * 字段自减
     * @param string $column     列
     * @param array  $conditions 条件
     * @param int    $step       步长
     * @throws \Exception
     */
    public function decrement(string $column, array $conditions, int $step = 1)
    {
        $this->simpleTable->where($conditions)->update([$column => new Expression(sprintf('%s - %d', $column, $step))]);
    }

    /**
     * 查询一列
     * @param string   $column     列名
     * @param array    $conditions 条件
     * @param array    $orderBy    排序
     * @param int      $offset     offset
     * @param int|null $limit      limit
     * @return array
     * @throws \Exception
     */
    public function findColumn(string $column, array $conditions, array $orderBy = [], int $offset = 0, int $limit = null)
    {
        $relations = $this->_relations;
        $this->_relations = [];
        $res = $this->findRows($conditions, [$column], $orderBy, $offset, $limit);
        $this->_relations = $relations;
        return array_column($res, $column);
    }


    /**
     * 查询某一列 生成器
     * @param string   $column     列名
     * @param array    $conditions 条件
     * @param array    $orderBy    排序
     * @param int      $offset     offset
     * @param int|null $limit      limit
     * @return \Generator
     * @throws \Exception
     */
    public function yieldColumn(string $column, array $conditions, array $orderBy = [], int $offset = 0, int $limit = null)
    {
        $relations = $this->_relations;
        $this->_relations = [];
        $res = $this->yieldRows($conditions, [$column], $orderBy, $offset, $limit);
        foreach ($res as $re) {
            yield $re[$column] ?? null;
        }
        $this->_relations = $relations;
    }

    /**
     * 判断是否存在
     * @param array $conditions 条件
     * @return bool
     * @throws \Exception
     */
    public function exists(array $conditions)
    {
        $res = $this->simpleTable->where($conditions)->limit(0, 1)->fields(['1'])->select();
        return !empty($res);
    }

    /**
     * 查找一个单元格的值
     * @param string $column     单元格列名
     * @param array  $conditions 条件
     * @param array  $orderBy    排序
     * @return mixed|null
     * @throws \Exception
     */
    public function findValue(string $column, array $conditions, array $orderBy = [])
    {
        $res = $this->findColumn($column, $conditions, $orderBy, 0, 1);
        return $res[0] ?? null;
    }

    /**
     * 单种统计类型
     * @param string $name      统计类型
     * @param array  $arguments 参数
     * @return array|\Generator
     * @throws \Exception
     */
    protected function singleTypeCount($name, $arguments)
    {
        $conditions = $arguments[0] ?? [];
        $columns = $arguments[1] ?? [];
        if (empty($columns)) {
            throw new \Exception('列不能为空');
        }
        $schemas = $this->simpleTable->getSchema()['columns'] ?? [];
        $groupBy = $arguments[2] ?? [];
        $orderBy = $arguments[3] ?? [];
        $yield = $arguments[4] ?? false;
        $this->simpleTable->where($conditions);
        $fields = [];
        if (!empty($groupBy)) {
            foreach ($groupBy as $value) {
                $this->simpleTable->groupBy($value);
                $fields[] = $value;
            }
        }
        foreach ($columns as $column) {
            if (isset($schemas[$column])) {
                $fields[] = sprintf('%s(`%s`) as %s_%s', strtoupper($name), $column, $column, $name);
            } else {
                $fields[] = sprintf('%s(%s) as %s_%s', strtoupper($name), $column, $column, $name);
            }
        }
        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $value) {
                $fields[] = $field;
            }
            $this->simpleTable->orderBy($orderBy);
        }
        return $this->simpleTable->fields(array_values(array_unique($fields)))->select($yield);
    }

    /**
     * 多种数据统计方式
     * @param string $name
     * @param array  $arguments
     * @return array|\Generator
     * @throws \Exception
     */
    protected function multiTypeCount($name, $arguments)
    {
        if ($name !== 'complex') {
            throw new \Exception('调用来源不合法');
        }
        $conditions = $arguments[0] ?? [];
        $type2columns = $arguments[1] ?? [];
        if (empty($type2columns)) {
            throw new \Exception('统计方式不能为空');
        }
        $schemas = $this->simpleTable->getSchema()['columns'] ?? [];
        $groupBy = $arguments[2] ?? [];
        $orderBy = $arguments[3] ?? [];
        $yield = $arguments[4] ?? false;

        $this->simpleTable->where($conditions);
        $fields = [];
        if (!empty($groupBy)) {
            foreach ($groupBy as $value) {
                $this->simpleTable->groupBy($value);
                $fields[] = $value;
            }
        }
        foreach ($type2columns as $type => $columns) {
            if (is_array($columns)) {
                foreach ($columns as $column) {
                    if (isset($schemas[$column])) {
                        $fields[] = sprintf('%s(`%s`) as %s_%s', strtoupper($type), $column, $column, $type);
                    } else {
                        $fields[] = sprintf('%s(%s) as %s_%s', strtoupper($type), $column, $column, $type);
                    }
                }
            } elseif (isset($schemas[$columns])) {
                $fields[] = sprintf('%s(`%s`) as %s_%s', strtoupper($type), $columns, $columns, $type);
            } elseif (is_string($columns)) {
                $fields[] = sprintf('%s(%s) as %s_%s', strtoupper($type), $columns, $columns, $type);
            }
        }
        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $value) {
                $fields[] = $field;
            }
            $this->simpleTable->orderBy($orderBy);
        }
        return $this->simpleTable->fields(array_values(array_unique($fields)))->select($yield);
    }

    /**
     * @param $method
     * @param $arguments
     * @return $this
     * @throws \Exception
     */
    protected function relations($method, $arguments): self
    {
        if (empty($method)) {
            throw new \Exception('method不能为空');
        }
        if (in_array($method, ['hasone', 'hasmany'])) {
            $this->_relations[] = [
                'left_column' => $arguments[0],
                'right_column' => $arguments[1],
                'fields' => $arguments[2] ?? ['*'],
                'conditions' => $arguments[3] ?? [],
                'callback' => $arguments[4] ?? null,
                'method' => $method,
            ];
        }
        return $this;
    }

    /**
     * @param string $name
     * @param array  $arguments
     * @return array|\Generator
     * @throws \Exception
     */
    public function __call(string $name, $arguments)
    {
        $name = strtolower($name);
        if (in_array($name, ['max', 'min', 'avg', 'sum', 'count'])) {
            return $this->singleTypeCount($name, $arguments);
        } elseif ($name == 'complex') {
            //多种统计分析
            return $this->multiTypeCount($name, $arguments);
        } elseif (in_array($name, ['hasone', 'hasmany'])) {
            return $this->relations($name, $arguments);
        }
    }
}
