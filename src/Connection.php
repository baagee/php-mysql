<?php
/**
 * Desc: 获取链接类
 * User: baagee
 * Date: 2019/3/15
 * Time: 下午5:10
 */

namespace BaAGee\MySQL;

use BaAGee\MySQL\Base\SingletonTrait;

/**
 * Class Connection
 * @package BaAGee\MySQL
 */
final class Connection
{
    use SingletonTrait;

    /**
     * @var array 保存slaveId
     */
    protected static $slaveId = [];

    /**
     * @var array mysql配置
     */
    protected static $config = [];
    /**
     * @var string 当前配置名
     */
    protected static $configName = '';

    /**
     * @throws \Exception
     */
    final private static function getDBConfig()
    {
        self::$config = DBConfig::get();
        self::$configName = DBConfig::getCurrentName();
    }

    /**
     * @return int
     */
    public static function getSlaveId()
    {
        if (isset(self::$slaveId[self::$configName])) {
            return self::$slaveId[self::$configName];
        }
        return -1;
    }

    /**
     * 获取mysql连接实例
     * @param bool $isRead 是否读操作
     * @return \PDO
     * @throws \Exception
     */
    final public static function getInstance(bool $isRead = false): \PDO
    {
        // 获取DB配置
        self::getDBConfig();
        // echo "当前使用：" . self::$configName . PHP_EOL;
        return self::getConnection($isRead);
    }

    /**
     * @param array $config
     * @param int   $retryTimes
     * @return \PDO
     */
    private static function getPdoObject(array $config, $retryTimes = 0)
    {
        // echo '连接数据库：' . self::$configName . PHP_EOL;
        if (isset($config['connectTimeout'])) {
            $connect_timeout = intval($config['connectTimeout']) == 0 ? 2 : intval($config['connectTimeout']);
        } else {
            $connect_timeout = 2;
        }
        $options = ($config['options'] ?? []) + [
                \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,//禁止多语句查询
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '" . $config['charset'] . "';",// 设置客户端连接字符集
                \PDO::ATTR_TIMEOUT => $connect_timeout,// 设置超时
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                // \PDO::ATTR_EMULATE_PREPARES       => false, //禁用模拟预处理
            ];
        $dsn = sprintf('mysql:dbname=%s;host=%s;port=%d', $config['database'], $config['host'], $config['port']);
        try {
            return new \PDO($dsn, $config['user'], $config['password'], $options);
        } catch (\PDOException $e) {
            if ($retryTimes < ($config['retryTimes'] ?? 0)) {
                $retryTimes++;
                return self::getPdoObject($config, $retryTimes);
            } else {
                throw $e;
            }
        }
    }

    /**
     * 读写分离时获取从库的配置索引，负载均衡
     * @param array $gravity
     * @return int|mixed
     */
    private static function getGravity(array $gravity)
    {
        $res = 0;
        $total_weight = 0;
        $weights = [];
        foreach ($gravity as $sid => $weight) {
            $total_weight += $weight;
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
     * 关闭连接
     * @param bool $isRead
     * @return bool
     */
    final public static function close(bool $isRead = true)
    {
        if (!isset(self::$config['slave'])) {
            // 没有配置从库 忽略参数 全删除
            unset(self::$_instance[self::$configName]['slave'], self::$_instance[self::$configName]['master']);
        } else {
            // 配置了主从
            if ($isRead) {
                unset(self::$_instance[self::$configName]['slave']);
            } else {
                unset(self::$_instance[self::$configName]['master']);
            }
        }
        return true;
    }

    /**
     * 获取数据库连接
     * @param bool $isRead
     * @return mixed|null|\PDO
     */
    private static function getConnection($isRead = true): \PDO
    {
        if (isset(self::$config['slave'])) {
            // 配置了从库
            if ($isRead) {
                // 读库
                if (!isset(self::$_instance[self::$configName]['slave'])) {
                    //读操作选择slave
                    self::$slaveId[self::$configName] = self::getGravity(array_column(self::$config['slave'], 'weight'));
                    $connection = self::getPdoObject(self::$config['slave'][self::$slaveId[self::$configName]]);
                    self::$_instance[self::$configName]['slave'] = $connection;
                } else {
                    $connection = self::$_instance[self::$configName]['slave'];
                }
            } else {
                // 除了读操作的，选择主库
                if (!isset(self::$_instance[self::$configName]['master'])) {
                    $connection = self::getPdoObject(self::$config);
                    self::$_instance[self::$configName]['master'] = $connection;
                } else {
                    $connection = self::$_instance[self::$configName]['master'];
                }
            }
        } else {
            // 没有配置读写分离从库，读写在一个数据库
            if (!isset(self::$_instance[self::$configName]['master'])) {
                // 主库链接也不存在
                $connection = self::getPdoObject(self::$config);
                self::$_instance[self::$configName]['master'] = self::$_instance[self::$configName]['slave'] = $connection;
            } else {
                // 直接使用主库
                $connection = self::$_instance[self::$configName]['master'];
            }
        }
        return $connection;
    }
}
