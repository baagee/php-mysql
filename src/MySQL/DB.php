<?php
/**
 * Desc: MySQL  DB类
 * User: baagee
 * Date: 2019/3/14
 * Time: 下午10:46
 */

namespace BaAGee\MySQL\MySQL;

use BaAGee\MySQL\SingletonTrait;

/**
 * Class DB
 * @package BaAGee\MySQL\MySQL
 */
final class DB
{
    use SingletonTrait;

    /**
     * @var array 保存数据库链接
     */
    protected static $link = [
        'master' => null,
        'slave'  => null,
    ];
    /**
     * @var DB
     */
    private static $self = null;
    /**
     * @var \PDOStatement
     */
    private $PDOStatement;
    /**
     * @var
     */
    private $_transactionCount;
    /**
     * @var bool 是否是事务操作
     */
    private $_inTransaction = false;
    /**
     * @var string
     */
    private $_lastPrepareSql = '';
    /**
     * @var array
     */
    private $_lastPrepareData = [];
    /**
     * @var string
     */
    private $_fullSql = '';
    /**
     * @var array
     */
    private static $configArray = [];

    /**
     * @var bool
     */
    private static $isInit = false;

    /**
     * @param array $config
     */
    public static function init(array $config)
    {
        if (self::$isInit === false) {
            self::$configArray = $config;
            self::$isInit      = true;
        }
    }

    /**
     * 获取DB实例
     * @return DB
     * @throws \Exception
     */
    public static function getInstance(): DB
    {
        if (self::$isInit === false) {
            throw new \Exception('DB没有初始化 DB::init');
        }
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 读写分离时获取从库的配置索引，负载均衡
     * @param array $gravity
     * @return int|mixed
     */
    private static function getGravity(array $gravity)
    {
        $res          = 0;
        $total_weight = 0;
        $weights      = [];
        foreach ($gravity as $sid => $weight) {
            $total_weight           += $weight;
            $weights[$total_weight] = $sid;
        }
        $rand_weight = mt_rand(1, $total_weight);
        foreach ($weights as $weight => $sid) {
            if ($rand_weight <= $weight) {
                $res = $sid;
                break;
            }
        }
        return $res;
    }

    /**
     * 获取数据库连接
     * @param bool $is_read
     * @return mixed|null|\PDO
     */
    private static function getLink($is_read = true)
    {
        if (isset(self::$configArray['slave']) && !empty(self::$configArray['slave'])) {
            // 配置了从库
            if ($is_read) {
                // 读库
                if (self::$link['slave'] == null) {
                    //读操作选择slave
                    $sid                 = self::getGravity(array_column(self::$configArray['slave'], 'weight'));
                    $link                = Connection::getInstance(self::$configArray['slave'][$sid]);
                    self::$link['slave'] = $link;
                } else {
                    $link = self::$link['slave'];
                }
            } else {
                // 除了读操作的，选择主库
                if (self::$link['master'] == null) {
                    $link                 = Connection::getInstance(self::$configArray);
                    self::$link['master'] = $link;
                } else {
                    $link = self::$link['master'];
                }
            }
        } else {
            // 没有配置读写分离从库，读写在一个数据库
            if (self::$link['master'] == null) {
                // 主库链接也不存在
                $link                 = Connection::getInstance(self::$configArray);
                self::$link['master'] = self::$link['slave'] = $link;
            } else {
                // 直接使用主库
                $link = self::$link['master'];
            }
        }
        return $link;
    }

    /**
     * 查询sql
     * @param string $sql  要查询的sql
     * @param array  $data 查询条件
     * @return array
     */
    final public function query($sql, $data = [])
    {
        $this->_lastPrepareSql  = $sql;
        $this->_lastPrepareData = $data;
        if ($this->_inTransaction) {
            //事务查询操作在主库
            $link = self::getLink(false);
        } else {
            $link = self::getLink(true);
        }
        $this->recordFullSql();
        $this->PDOStatement = $link->prepare($this->_lastPrepareSql);
        if ($this->PDOStatement === false) {
            $errorInfo = $link->errorInfo();
            throw new \PDOException($errorInfo[2] . ' #SQL:' . $this->_fullSql, $errorInfo[1]);
        }
        $this->PDOStatement->execute($this->_lastPrepareData);
        $this->sqlBugInfo($link);
        return $this->PDOStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 执行原生 insert, update create delete等语句
     * @param string $sql  sql语句
     * @param array  $data 查询条件
     * @return int 影响行数
     */
    final public function execute($sql, $data = [])
    {
        $this->_lastPrepareSql  = $sql;
        $this->_lastPrepareData = $data;
        $link                   = self::getLink(false);
        $this->recordFullSql();
        $this->PDOStatement = $link->prepare($this->_lastPrepareSql);
        if ($this->PDOStatement === false) {
            $errorInfo = $link->errorInfo();
            throw new \PDOException($errorInfo[2] . ' #SQL:' . $this->_fullSql, $errorInfo[1]);
        }
        $this->PDOStatement->execute($this->_lastPrepareData);
        $this->sqlBugInfo($link);
        return $this->PDOStatement->rowCount();
    }

    /**
     * 获取最后一次插入的ID
     * @return int
     */
    final public function lastInsertId()
    {
        return intval(self::getLink(false)->lastInsertId());
    }

    /**
     * 开启事务
     * @return bool
     */
    final public function beginTransaction()
    {
        //设置是事务操作
        $this->_inTransaction = true;
        // 事务操作在主库 is_read=false
        $link = self::getLink(false);
        if (!$this->_transactionCount++) {
            return $link->beginTransaction();
        }
        $link->exec('SAVEPOINT trans' . $this->_transactionCount);
        return $this->_transactionCount >= 0;
    }

    /**
     * 提交事务
     * @return bool
     */
    final public function commit()
    {
        if (!--$this->_transactionCount) {
            // 事务操作结束
            $this->_inTransaction = false;
            // 事务操作在主库 is_read=false
            return self::getLink(false)->commit();
        }
        return $this->_transactionCount >= 0;
    }

    /**
     * 事务回滚
     * @return bool
     */
    final public function rollback()
    {
        // 事务操作在主库 is_read=false
        $link = self::getLink(false);
        if (--$this->_transactionCount) {
            $link->exec('ROLLBACK TO trans' . ($this->_transactionCount + 1));
            return true;
        }
        $res = $link->rollback();
        // 事务操作结束
        $this->_inTransaction = false;
        return $res;
    }

    /**
     * 获取完整的sql语句 将预处理语句中的占位符替换成对应值
     * @return mixed|string
     */
    private function recordFullSql()
    {
        $this->_fullSql = $this->replaceSqlData();
        return $this->_fullSql;
    }

    /**
     * 将预处理的sql占位符替换成真实的值，拼成完整sql
     * @return mixed|string
     */
    private function replaceSqlData()
    {
        // 开发模式每条sql都会到这里处理，生产模式只有sql出错时才会到这里处理
        $full_sql = $this->_lastPrepareSql;
        foreach ($this->_lastPrepareData as $field => $value) {
            if ($field{0} !== ':') {
                $field = ':' . $field;
            }
            $type = gettype($value);
            switch ($type) {
                case 'integer':
                case 'double':
                    $full_sql = str_replace($field, $value, $full_sql);
                    break;
                default:
                    $full_sql = str_replace($field, '\'' . $value . '\'', $full_sql);
            }
        }
        return $full_sql;
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
        if (empty($this->_fullSql)) {
            $this->_fullSql = $this->replaceSqlData();
        }
        return $this->_fullSql;
    }

    /**
     * 处理检查sql异常
     * @param $link
     */
    private function sqlBugInfo($link)
    {
        if ($this->PDOStatement === false) {
            $errorInfo = $link->errorInfo();
        } else {
            $errorInfo = $this->PDOStatement->errorInfo();
        }
        if ($errorInfo[0] != '00000') {
            throw new \PDOException($errorInfo[2] . ' #SQL:' . $this->_fullSql, $errorInfo[1]);
        }
    }
}
