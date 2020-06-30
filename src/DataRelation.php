<?php
/**
 * Desc: 数据关系
 * User: baagee
 * Date: 2019-08-21
 * Time: 10:19
 */

namespace BaAGee\MySQL;

/**
 * Class DataRelation
 * @method $this hasOne($leftColumn, $rightTableColumn, $fields = ['*'], $conditions = [], $callback = null);
 * @method $this hasMany($leftColumn, $rightTableColumn, $fields = ['*'], $conditions = [], $callback = null);
 * @package BaAGee\MySQL
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
     * @var array 缓存的数据
     */
    protected static $cacheData = [];

    /**
     * @var int 缓存池大小
     */
    protected static $cacheSize = 10000;

    /**
     * DataRelation constructor.
     * @param array $data 可以是单行记录的关联数组也可以是多行记录的索引数组
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->data = $this->prepareData($data);
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this
     * @throws \Exception
     */
    public function __call($name, $arguments): DataRelation
    {
        $method = strtolower($name);
        if (in_array($method, ['hasone', 'hasmany'])) {
            list($table, $column) = explode('.', $arguments[1]);
            $this->relations[] = [
                'left_column' => $arguments[0],
                'right_column' => $column,
                'relation_table' => $table,
                'fields' => $arguments[2] ?? ['*'],
                'conditions' => $arguments[3] ?? [],
                'callback' => $arguments[4] ?? null,
                'method' => $method,
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
    protected function getDataFromRelation($relationConfig): array
    {
        $columnValues = array_unique(array_filter(array_column($this->data, $relationConfig['left_column'])));
        if (!empty($columnValues)) {
            $conditions = array_merge([
                $relationConfig['right_column'] => ['in', $columnValues]
            ], (array)($relationConfig['conditions'] ?? []));
            $fields = self::getFields($relationConfig);
            sort($fields);
            $key = md5(sprintf('%s:%s:%s', $relationConfig['relation_table'], serialize($fields), serialize($conditions)));
            if (isset(self::$cacheData[$key])) {
                $list = self::$cacheData[$key];
            } else {
                $tableObj = SimpleTable::getInstance($relationConfig['relation_table']);
                $list = $tableObj->fields($fields)->where($conditions)->select(false);
                self::$cacheData[$key] = $list;
                if (count(self::$cacheData, COUNT_RECURSIVE) > self::$cacheSize) {
                    array_shift(self::$cacheData);
                }
            }
        }
        if (empty($list))
            $list = [];
        return $list;
    }

    /**
     * 设置缓存池大小
     * @param int $size default 10000
     */
    public static function setCacheSize(int $size)
    {
        if ($size > 0) {
            self::$cacheSize = $size;
        }
    }

    /**
     * 清空缓存的数据
     */
    public static function clearCache()
    {
        self::$cacheData = [];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getData(): array
    {
        //循环获取每个关系的数据
        foreach ($this->relations as $itemRelation) {
            $list = $this->getDataFromRelation($itemRelation);
            $prefix = str_replace('has', '', $itemRelation['method']);
            $hasCallback = isset($itemRelation['callback']) && ($itemRelation['callback'] instanceof \Closure);
            if ($itemRelation['method'] == 'hasone') {
                if ($hasCallback) {
                    array_walk($list, $itemRelation['callback']);
                }
                $list = array_column($list, null, $itemRelation['right_column']);
            } elseif ($itemRelation['method'] == 'hasmany') {
                $newList = [];
                foreach ($list as $k => $item) {
                    if ($hasCallback) {
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
    public function setData(array $data): DataRelation
    {
        $this->data = $this->prepareData($data);
        return $this;
    }

    /**
     * @param array $relations
     * @return DataRelation
     */
    public function setRelations(array $relations): DataRelation
    {
        $this->relations = $relations;
        return $this;
    }

    /**
     * 是否是关联数组
     * @param $array
     * @return bool
     */
    protected function isAssoc($array)
    {
        if (is_array($array)) {
            $keys = array_keys($array);
            return $keys != array_keys($keys);
        }
        return false;
    }

    /**
     * 一维数组转二维数组
     * @param $data
     * @return array
     */
    protected function prepareData($data): array
    {
        if ($this->isAssoc($data)) {
            //关联数组
            $data = [$data];
            $this->flag = true;
        } else {
            //索引数组
            $this->flag = false;
        }

        return $data;
    }
}
