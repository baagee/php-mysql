<?php
/**
 * Desc: 简单封装DB类
 * User: baagee
 * Date: 2019/3/17
 * Time: 下午10:05
 */

namespace BaAGee\MySQL;

use BaAGee\MySQL\Base\SimpleTableInterface;
use BaAGee\MySQL\Base\SingletonTrait;
use BaAGee\MySQL\Base\SqlBuilder;

/**
 * Class SimpleTable
 * @method $this hasOne(string $leftColumn, string $rightTableColumn, array $fields = ['*'], array $conditions = [], $callback = null);
 * @method $this hasMany(string $leftColumn, string $rightTableColumn, array $fields = ['*'], array $conditions = [], $callback = null);
 * @package BaAGee\MySQL
 */
final class SimpleTable extends SqlBuilder implements SimpleTableInterface
{
    use SingletonTrait;

    /**
     * @var DB
     */
    protected $_dbInstance = null;
    /**
     * @var array
     */
    protected $_relations = [];

    /**
     * @return DB
     */
    public function getDb()
    {
        return $this->_dbInstance;
    }

    /**
     * 获取表结构
     * @param $tableName
     * @return array
     * @throws \Exception
     */
    private static function getTableSchema($tableName)
    {
        $schemasCachePath = DBConfig::get('schemasCachePath', '');
        if (!is_null($schemasCachePath) && !empty($schemasCachePath)) {
            // 存在缓存目录 判断缓存在不在
            $schemasCachePath .= DIRECTORY_SEPARATOR . DBConfig::getCurrentName();
            if (!is_dir($schemasCachePath)) {
                DBConfig::createSchemasDir();
            }
            $schemaFile = $schemasCachePath . DIRECTORY_SEPARATOR . $tableName . '.php';
            if (!is_file($schemaFile)) {
                //从数据库查询 写入文件缓存
                $schema = self::getSchemaFromDb($tableName);
                register_shutdown_function(function () use ($schema, $schemaFile) {
                    try {
                        file_put_contents($schemaFile, '<?php' . PHP_EOL . "// Create time: " . date('Y-m-d H:i:s')
                            . PHP_EOL . 'return ' . var_export($schema, true) . ';');
                    } catch (\Throwable $e) {
                    }
                });
            } else {
                // 读取缓存
                $schema = require $schemaFile;
            }
        } else {
            // 不存在缓存目录每次从数据库重新查询
            $schema = self::getSchemaFromDb($tableName);
        }
        return $schema;
    }

    /**
     * 从数据库查询表结构
     * @param $tableName
     * @return array
     * @throws \Exception
     */
    private static function getSchemaFromDb($tableName)
    {
        $sql = 'SHOW COLUMNS FROM `' . $tableName . '`';
        $res = DB::getInstance()->query($sql);
        $schema = [];
        $columns = [];
        foreach ($res as $v) {
            if ($v['Key'] === 'PRI') {
                $schema['primaryKey'] = $v['Field'];
            }
            if ($v['Extra'] === 'auto_increment') {
                $schema['autoIncrement'] = $v['Field'];
            }
            if ((strpos($v['Type'], 'int') !== false)) {
                $field_type = self::COLUMN_TYPE_INT;
            } else if (
                strpos($v['Type'], 'decimal') !== false
                || strpos($v['Type'], 'float') !== false
                || strpos($v['Type'], 'double') !== false
            ) {
                $field_type = self::COLUMN_TYPE_FLOAT;
            } else {
                $field_type = self::COLUMN_TYPE_STRING;
            }
            $columns[$v['Field']] = $field_type;
        }
        $schema['columns'] = $columns;
        return $schema;
    }

    /**
     * 获取表结构
     * @return array|mixed
     */
    final public function getSchema()
    {
        return $this->_tableSchema;
    }

    /**
     * 获取操作一个表的简单Table类
     * @param $tableName
     * @return $this
     * @throws \Exception
     */
    final public static function getInstance(string $tableName)
    {
        $tableName = trim($tableName);
        if (empty($tableName)) {
            throw new \Exception('表名不能为空');
        }
        if (empty(self::$_instance[$tableName])) {
            $obj = new static();
            $obj->_tableName = $tableName;
            $obj->_dbInstance = DB::getInstance();
            self::$_instance[$tableName] = $obj;
        } else {
            $obj = self::$_instance[$tableName];
            // 清空上次的缓存字段
            $obj->_clear();
        }
        $obj->_tableSchema = self::getTableSchema($tableName);
        return $obj;
    }

    /**
     * 插入数据insert into 返回插入的ID
     * @param array $data
     * @param bool  $ignore
     * @param array $onDuplicateUpdateFields
     * @return int|null
     * @throws \Exception
     */
    final public function insert(array $data, bool $ignore = false, array $onDuplicateUpdateFields = [])
    {
        if (count($data) === count($data, COUNT_RECURSIVE)) {
            $data = [$data];
        }
        // 批量插入
        list($sql, $prepareData) = $this->_buildInsertOrReplace($data, false, $ignore, $onDuplicateUpdateFields);
        $res = $this->_dbInstance->execute($sql, $prepareData);
        $this->_clear();
        if ($res >= 1) {
            return $this->_dbInstance->getLastInsertId();
        } else {
            return null;
        }
    }

    /**
     * @param array $data
     * @return int|null
     * @throws \Exception
     */
    final public function replace(array $data)
    {
        if (count($data) === count($data, COUNT_RECURSIVE)) {
            $data = [$data];
        }
        // 批量插入
        list($sql, $prepareData) = $this->_buildInsertOrReplace($data, true, false, []);
        $res = $this->_dbInstance->execute($sql, $prepareData);
        $this->_clear();
        if ($res >= 1) {
            return $this->_dbInstance->getLastInsertId();
        } else {
            return null;
        }
    }

    /**
     * 删除数据
     * @return int
     * @throws \Exception
     */
    final public function delete()
    {
        list($sql, $prepareData) = $this->_buildDelete();
        $res = $this->_dbInstance->execute($sql, $prepareData);
        $this->_clear();
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
        list($sql, $prepareData) = $this->_buildUpdate($data);
        $res = $this->_dbInstance->execute($sql, $prepareData);
        $this->_clear();
        return $res;
    }

    /**
     * 查询数据
     * yield查询如果之前有设置hasOne,hasMany每次迭代都会查一次数据库所以虽然能降低内存但是请求数据库次数多，总体耗时长，也不建议使用
     * @param bool $generator
     * @return array|\Generator
     * @throws \Exception
     */
    final public function select(bool $generator = false)
    {
        list($sql, $prepareData) = $this->_buildSelect();
        if ($generator) {
            if (!empty($this->_relations)) {
                $relations = $this->_relations;
                $callback = function ($row) use ($relations) {
                    $dataRelation = new DataRelation();
                    $dataRelation->setData($row)->setRelations($relations);
                    $row = $dataRelation->getData();
                    return $row;
                };
            } else {
                $callback = null;
            }
            $res = $this->_dbInstance->yieldQuery($sql, $prepareData, $callback);
        } else {
            $res = $this->_dbInstance->query($sql, $prepareData);
        }
        $this->_clear();
        if ($generator === false && !empty($res) && is_array($res) && !empty($this->_relations)) {
            $dataRelation = new DataRelation();
            $dataRelation->setData($res)->setRelations($this->_relations);
            $res = $dataRelation->getData();
        }
        $this->_relations = [];
        return $res;
    }

    /**
     * @param $name
     * @param $arguments
     * @return SimpleTable
     */
    public function __call($name, $arguments): self
    {
        $method = strtolower($name);
        if (in_array($method, ['hasone', 'hasmany'])) {
            list($table, $column) = explode('.', $arguments[1]);
            $this->_relations[] = [
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
}
