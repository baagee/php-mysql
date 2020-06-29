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
     * @var int 事务计数
     */
    private $transactionCount;
    /**
     * @var bool 是否是事务操作
     */
    private $inTransaction = false;

    /**
     * 查询sql
     * @param string $sql  要查询的sql
     * @param array  $data 查询条件
     * @return array
     * @throws \Exception
     */
    final public function query(string $sql, array $data = [])
    {
        $stmt = $this->runSql(!$this->inTransaction, $sql, $data);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 返回查询数据的生成器
     * @param string   $sql      要查询的sql
     * @param array    $data     参数绑定
     * @param callable $callback 回调函数
     * @return \Generator
     * @throws \Exception
     */
    final public function yieldQuery($sql, array $data = [], callable $callback = null)
    {
        $stmt = $this->runSql(!$this->inTransaction, $sql, $data);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (!is_null($callback) && is_callable($callback)) {
                $row = call_user_func($callback, $row);
            }
            yield $row;
        }
    }

    /**
     * 执行SQL
     * @param bool   $isRead
     * @param string $sql
     * @param array  $data
     * @param int    $retryTimes
     * @return \PDOStatement
     * @throws \Exception
     */
    private function runSql(bool $isRead, string $sql, array $data = [], $retryTimes = 0)
    {
        $sTime = microtime(true);
        $connection = self::getConnection($isRead);
        $cTime = microtime(true);
        try {
            $stmt = $connection->prepare($sql);
            if ($stmt === false) {
                $errorInfo = $connection->errorInfo();
                throw new \PDOException($errorInfo[2], $errorInfo[1]);
            }
            $stmt->execute($data);
            $errorInfo = $stmt->errorInfo();
            if ($errorInfo[0] != '00000') {
                throw new \PDOException($errorInfo[2], $errorInfo[1]);
            }
            $eTime = microtime(true);
            SqlRecorder::record($sql, $sTime, $cTime, $eTime, true, $data);
            return $stmt;
        } catch (\PDOException $e) {
            $errMsg = $e->getMessage();//记录失败原因
            $eTime = microtime(true);
            SqlRecorder::record($sql, $sTime, $cTime, $eTime, false, $data, $errMsg);
            if ($this->isBreak($e)) {
                // 重试三次
                if ($isRead) {
                    //读操作获取对应从库配置
                    $rtc = DBConfig::get('slave')[Connection::getSlaveId()];
                } else {
                    $rtc = DBConfig::get();
                }
                if ($retryTimes < (int)($rtc['retryTimes'] ?? 3)) {
                    self::closeConnection($isRead);
                    $retryTimes++;
                    return $this->runSql($isRead, $sql, $data, $retryTimes);
                }
            }
            throw new \PDOException($errMsg . ' [SQL: ' . $sql . ']', $e->getCode());
        }
    }

    /**
     * 判断是否断开连接
     * @param \Exception $e
     * @return bool
     */
    protected function isBreak(\Exception $e): bool
    {
        $breakMatchStr = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'failed with errno',
        ];
        $error = $e->getMessage();
        foreach ($breakMatchStr as $msg) {
            if (false !== stripos($error, $msg)) {
                return true;
            }
        }
        return false;
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
        $stmt = $this->runSql(false, $sql, $data);
        return $stmt->rowCount();
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
        // 事务操作在主库 is_read=false
        $link = self::getConnection(false);
        //设置是事务操作
        $this->inTransaction = true;
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
     * 获取上次执行的sql信息
     * @return array
     */
    final public static function getLastSql()
    {
        return SqlRecorder::getLastSql();
    }
}
