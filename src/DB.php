<?php
/**
 * Desc: MySQL操作基本类
 * User: baagee
 * Date: 2019/3/15
 * Time: 下午5:10
 */

namespace BaAGee\MySQL;

use BaAGee\MySQL\Base\DBAbstract;
use BaAGee\MySQL\Base\DBInterface;

/**
 * Class DB
 * @package BaAGee\MySQL
 */
final class DB extends DBAbstract implements DBInterface
{
    /**
     * @var \PDOStatement
     */
    private $PDOStatement;
    /**
     * @var int 事务计数
     */
    private $transactionCount;
    /**
     * @var bool 是否是事务操作
     */
    private $inTransaction = false;
    /**
     * @var string 预处理SQL
     */
    private $lastPrepareSql = '';
    /**
     * @var array 预处理绑定的数据
     */
    private $lastPrepareData = [];
    /**
     * @var string 执行的完整SQL
     */
    private $fullSql = '';

    /**
     * @return string
     */
    final public function getLastPrepareSql(): string
    {
        return $this->lastPrepareSql;
    }

    /**
     * @return array
     */
    final public function getLastPrepareData(): array
    {
        return $this->lastPrepareData;
    }

    /**
     * 查询sql
     * @param string $sql  要查询的sql
     * @param array  $data 查询条件
     * @return array
     * @throws \Exception
     */
    final public function query(string $sql, array $data = [])
    {
        $this->runSql(self::getConnection(!$this->inTransaction), $sql, $data);
        return $this->PDOStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 返回查询数据的生成器
     * @param string $sql  要查询的sql
     * @param array  $data 参数绑定
     * @return \Generator
     * @throws \Exception
     */
    final public function yieldQuery($sql, array $data = [])
    {
        $this->runSql(self::getConnection(!$this->inTransaction), $sql, $data);
        while ($row = $this->PDOStatement->fetch(\PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    /**
     * 执行SQL
     * @param \PDO   $connection
     * @param string $sql
     * @param array  $data
     */
    private function runSql(\PDO $connection, string $sql, array $data = [])
    {
        $this->fullSql         = '';
        $this->lastPrepareSql  = $sql;
        $this->lastPrepareData = $data;
        $this->PDOStatement    = $connection->prepare($this->lastPrepareSql);
        if ($this->PDOStatement === false) {
            $errorInfo = $connection->errorInfo();
            throw new \PDOException($errorInfo[2] . ' #SQL:' . $this->getLastSql(), $errorInfo[1]);
        }
        $this->PDOStatement->execute($this->lastPrepareData);
        $errorInfo = $this->PDOStatement->errorInfo();
        if ($errorInfo[0] != '00000') {
            throw new \PDOException($errorInfo[2] . ' #SQL:' . $this->getLastSql(), $errorInfo[1]);
        }
    }

    /**
     * 执行原生 insert, update create delete等语句
     * @param string $sql  sql语句
     * @param array  $data 查询条件
     * @return int 影响行数
     * @throws \Exception
     */
    final public function execute(string $sql, array $data = [])
    {
        $this->runSql(self::getConnection(false), $sql, $data);
        return $this->PDOStatement->rowCount();
    }

    /**
     * 获取最后一次插入的ID
     * @return int
     * @throws \Exception
     */
    final public function getLastInsertId()
    {
        return intval(self::getConnection(false)->lastInsertId());
    }

    /**
     * 开启事务
     * @return bool
     * @throws \Exception
     */
    final public function beginTransaction()
    {
        //设置是事务操作
        $this->inTransaction = true;
        // 事务操作在主库 is_read=false
        $link = self::getConnection(false);
        if (!$this->transactionCount++) {
            return $link->beginTransaction();
        }
        $link->exec('SAVEPOINT trans' . $this->transactionCount);
        return $this->transactionCount >= 0;
    }

    /**
     * 提交事务
     * @return bool
     * @throws \Exception
     */
    final public function commit()
    {
        if (!--$this->transactionCount) {
            // 事务操作结束
            $this->inTransaction = false;
            // 事务操作在主库 is_read=false
            return self::getConnection(false)->commit();
        }
        return $this->transactionCount >= 0;
    }

    /**
     * 事务回滚
     * @return bool
     * @throws \Exception
     */
    final public function rollback()
    {
        // 事务操作在主库 is_read=false
        $link = self::getConnection(false);
        if (--$this->transactionCount) {
            $link->exec('ROLLBACK TO trans' . ($this->transactionCount + 1));
            return true;
        }
        $res = $link->rollback();
        // 事务操作结束
        $this->inTransaction = false;
        return $res;
    }

    /**
     * 将预处理的sql占位符替换成真实的值，拼成完整sql
     * @return mixed|string
     */
    private function replaceSqlData()
    {
        $fullSql = $this->lastPrepareSql;
        if (strpos($fullSql, '?') !== false) {
            // 使用？占位符
            $tmp1    = explode('?', $fullSql);
            $fullSql = '';
            $count   = count($tmp1);
            for ($i = 0; $i < $count; $i++) {
                $fullSql .= $tmp1[$i];
                if ($i !== $count - 1) {
                    if (isset($this->lastPrepareData[$i])) {
                        $value = $this->lastPrepareData[$i];
                        $type  = gettype($value);
                        if (!in_array($type, ['integer', 'double'])) {
                            $value = '\'' . $value . '\'';
                        }
                        $fullSql .= $value;
                    } else {
                        $fullSql .= '?';
                    }
                }
            }
        } else {
            // 使用:field占位
            foreach ($this->lastPrepareData as $field => $value) {
                if ($field{0} !== ':') {
                    $field = ':' . $field;
                }
                $type = gettype($value);
                if (!in_array($type, ['integer', 'double'])) {
                    $value = '\'' . $value . '\'';
                }
                $fullSql = str_replace($field, $value, $fullSql);
            }
        }
        return $fullSql;
    }

    /**
     * 事务操作
     * @param callable $func   回调方法 请不要返回false 否则会认为事务失败
     * @param array    $params 回调方法参数
     * @return bool|mixed 失败返回false  成功返回回调方法的返回值
     * @throws \Exception
     */
    final public static function transaction(callable $func, array $params = [])
    {
        $self = self::getInstance();
        try {
            $self->beginTransaction();
            $res = call_user_func_array($func, $params);
            $self->commit();
            return $res;
        } catch (\Throwable $e) {
            $self->rollback();
            return false;
        }
    }

    /**
     * 获取上次执行的sql
     * @return mixed|string
     */
    final public function getLastSql()
    {
        if (empty($this->fullSql)) {
            $this->fullSql = $this->replaceSqlData();
        }
        return $this->fullSql;
    }
}
