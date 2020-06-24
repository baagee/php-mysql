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

        if (empty(self::$_instance)) {
            $self = new static();
            $self->simpleTable = SimpleTable::getInstance($tableName);
            self::$_instance = $self;
        }
        return self::$_instance;
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
        return $this->simpleTable->select(true);
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
        return $this->simpleTable->select();
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
        $res = $this->findRows($conditions, [$column], $orderBy, $offset, $limit);
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
        $res = $this->yieldRows($conditions, [$column], $orderBy, $offset, $limit);
        foreach ($res as $re) {
            yield $re[$column] ?? null;
        }
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
     * 返回符合条件的行数
     * @param array  $conditions 条件
     * @param string $column     列
     * @param array  $groupBy    分组
     * @param array  $orderBy    排序
     * @param bool   $yield      是否返回生成器
     * @return array|\Generator
     * @throws \Exception
     */
    public function count(array $conditions, string $column = '1', array $groupBy = [], array $orderBy = [], bool $yield = false)
    {
        $this->simpleTable->where($conditions);
        $fields = [sprintf('count(%s) as _count', $column)];
        if (!empty($groupBy)) {
            foreach ($groupBy as $value) {
                $this->simpleTable->groupBy($value);
                $fields[] = $value;
            }
        }
        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $value) {
                $fields[] = $field;
            }
            $this->simpleTable->orderBy($orderBy);
        }
        $fields = array_values(array_unique($fields));
        return $this->simpleTable->fields($fields)->select($yield);
    }

    /**
     * 求符合条件这些列的和
     * @param array $conditions 条件
     * @param array $columns    列
     * @param array $groupBy    分组
     * @param array $orderBy    排序
     * @param bool  $yield      是否返回生成器
     * @return array|\Generator
     * @throws \Exception
     */
    public function sum(array $conditions, array $columns, array $groupBy = [], array $orderBy = [], bool $yield = false)
    {
        $this->simpleTable->where($conditions);
        $fields = [];
        if (!empty($groupBy)) {
            foreach ($groupBy as $value) {
                $this->simpleTable->groupBy($value);
                $fields[] = $value;
            }
        }
        foreach ($columns as $column) {
            $fields[] = sprintf('sum(`%s`) as %s_sum', $column, $column);
        }
        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $value) {
                $fields[] = $field;
            }
            $this->simpleTable->orderBy($orderBy);
        }
        $fields = array_values(array_unique($fields));
        return $this->simpleTable->fields($fields)->select(false);
    }

    /**
     * 求符合条件这些列的平均值
     * @param array $conditions 条件
     * @param array $columns    列
     * @param array $groupBy    分组
     * @param array $orderBy    排序
     * @param bool  $yield      是否返回生成器
     * @return array|\Generator
     * @throws \Exception
     */
    public function avg(array $conditions, array $columns, array $groupBy = [], array $orderBy = [], $yield = false)
    {
        $this->simpleTable->where($conditions);
        $fields = [];
        if (!empty($groupBy)) {
            foreach ($groupBy as $value) {
                $this->simpleTable->groupBy($value);
                $fields[] = $value;
            }
        }
        foreach ($columns as $column) {
            $fields[] = sprintf('avg(`%s`) as %s_avg', $column, $column);
        }
        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $value) {
                $fields[] = $field;
            }
            $this->simpleTable->orderBy($orderBy);
        }
        $fields = array_values(array_unique($fields));
        return $this->simpleTable->fields($fields)->select($yield);
    }

    /**
     * 求符合条件这些列的最大值
     * @param array $conditions 条件
     * @param array $columns    列
     * @param array $groupBy    分组
     * @param array $orderBy    排序
     * @param bool  $yield      是否返回生成器
     * @return array|\Generator
     * @throws \Exception
     */
    public function max(array $conditions, array $columns, array $groupBy = [], array $orderBy = [], bool $yield = false)
    {
        $this->simpleTable->where($conditions);
        $fields = [];
        if (!empty($groupBy)) {
            foreach ($groupBy as $value) {
                $this->simpleTable->groupBy($value);
                $fields[] = $value;
            }
        }
        foreach ($columns as $column) {
            $fields[] = sprintf('max(`%s`) as %s_max', $column, $column);
        }
        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $value) {
                $fields[] = $field;
            }
            $this->simpleTable->orderBy($orderBy);
        }
        $fields = array_values(array_unique($fields));
        return $this->simpleTable->fields($fields)->select($yield);
    }

    /**
     * 求符合条件这些列的最小值
     * @param array $conditions 条件
     * @param array $columns    列
     * @param array $groupBy    分组
     * @param array $orderBy    排序
     * @param bool  $yield      是否返回生成器
     * @return array|\Generator
     * @throws \Exception
     */
    public function min(array $conditions, array $columns, array $groupBy = [], array $orderBy = [], bool $yield = false)
    {
        $this->simpleTable->where($conditions);
        $fields = [];
        if (!empty($groupBy)) {
            foreach ($groupBy as $value) {
                $this->simpleTable->groupBy($value);
                $fields[] = $value;
            }
        }
        foreach ($columns as $column) {
            $fields[] = sprintf('min(`%s`) as %s_min', $column, $column);
        }
        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $value) {
                $fields[] = $field;
            }
            $this->simpleTable->orderBy($orderBy);
        }
        $fields = array_values(array_unique($fields));
        return $this->simpleTable->fields($fields)->select($yield);
    }
}
