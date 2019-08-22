<?php
/**
 * Desc: 数据关系
 * User: baagee
 * Date: 2019-08-21
 * Time: 10:19
 */

namespace BaAGee\MySQL;

/**
 * Class OrmRelation
 * @method $this hasOne($leftColumn, $rightTableColumn, $fields = ['*'], $conditions = [], $callback = null);
 * @method $this hasMany($leftColumn, $rightTableColumn, $fields = ['*'], $conditions = [], $callback = null);
 * @package SfLib\Base
 */
final class DataRelation
{
    /**
     * @var array 数据
     */
    protected $data = [];

    /**
     * @var array 关系配置
     */
    protected $relations = [];

    /**
     * @var bool 标记是否将一维数组转化为二维数组
     */
    protected $flag = false;

    /**
     * DataRelation constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $this->prepareData($data);
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this
     * @throws \Exception
     */
    public function __call($name, $arguments): DataRelation
    {
        if (in_array(strtolower($name), ['hasone', 'hasmany'])) {
            list($table, $column) = explode('.', $arguments[1]);
            $this->relations[] = [
                'left_column'    => $arguments[0],
                'right_column'   => $column,
                'relation_table' => $table,
                'fields'         => $arguments[2] ?? ['*'],
                'conditions'     => $arguments[3] ?? [],
                'callback'       => $arguments[4] ?? null,
                'method'         => $name,
            ];
        }
        return $this;
    }

    /**
     * 获取查询的字段
     * @param $relationConfig
     * @return array|mixed
     */
    protected static function getFields($relationConfig): array
    {
        if (!empty($relationConfig['fields'])) {
            if (in_array('*', $relationConfig['fields'])) {
                $fields = ['*'];
            } else {
                if (!in_array($relationConfig['right_column'], $relationConfig['fields'])) {
                    $fields = array_unique(array_merge($relationConfig['fields'], [$relationConfig['right_column']]));
                } else {
                    $fields = $relationConfig['fields'];
                }
            }
        } else {
            $fields = ['*'];
        }
        return $fields;
    }

    /**
     * 查询数据库获取数据
     * @param $relationConfig
     * @return array
     * @throws \Exception
     */
    protected function getDataFromDB($relationConfig): array
    {
        $columnValues = array_unique(array_filter(array_column($this->data, $relationConfig['left_column'])));
        if (!empty($columnValues)) {
            $conditions = array_merge([
                $relationConfig['right_column'] => ['in', $columnValues]
            ], (array)($relationConfig['conditions'] ?? []));
            $tableObj = SimpleTable::getInstance($relationConfig['relation_table']);
            $list     = $tableObj->fields(self::getFields($relationConfig))->where($conditions)->select(false);
        }
        if (empty($list))
            $list = [];
        return $list;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getData(): array
    {
        //循环获取每个关系的数据
        foreach ($this->relations as $itemRelation) {
            $list   = $this->getDataFromDB($itemRelation);
            $method = strtolower($itemRelation['method']);
            $prefix = str_replace('has', '', $method);
            if ($method == 'hasone') {
                if (isset($itemRelation['callback']) && $itemRelation['callback'] instanceof \Closure) {
                    array_walk($list, $itemRelation['callback']);
                }
                $list = array_column($list, null, $itemRelation['right_column']);
            } elseif ($method == 'hasmany') {
                $newList = [];
                foreach ($list as $k => $item) {
                    if (isset($itemRelation['callback']) && $itemRelation['callback'] instanceof \Closure) {
                        $itemRelation['callback']($item, $k);
                    }
                    $newList[$item[$itemRelation['right_column']]][] = $item;
                }
                $list = $newList;
            } else {
                continue;
            }
            $fieldName = $prefix . '_' . $itemRelation['relation_table'];
            foreach ($this->data as &$row) {
                $row[$fieldName] = $list[$row[$itemRelation['left_column']]] ?? [];
            }
            unset($row);
        }
        //清空关系
        $this->relations = [];
        return $this->flag ? $this->data[0] : $this->data;
    }

    /**
     * 添加数据
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $this->prepareData($data);
        return $this;
    }

    /**
     * 一维数组转二维数组
     * @param $data
     * @return array
     */
    protected function prepareData($data): array
    {
        if (count($data) === count($data, COUNT_RECURSIVE)) {
            $data       = [$data];
            $this->flag = true;
        } else {
            $this->flag = false;
        }
        return $data;
    }
}
