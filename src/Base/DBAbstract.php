<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/3/15
 * Time: 下午5:13
 */

namespace BaAGee\MySQL\Base;

use BaAGee\MySQL\Connection;
use BaAGee\MySQL\DB;

/**
 * Class DBAbstract
 * @package BaAGee\MySQL\Base
 */
abstract class DBAbstract
{
    use SingletonTrait;

    /**
     * @var array mysql配置
     */
    protected static $config = [];
    /**
     * @var bool 是否初始化
     */
    protected static $isInit = false;

    /**
     * @var array 保存数据库链接
     */
    protected static $connection = [
        'master' => null,
        'slave'  => null,
    ];

    /**
     * 初始化配置
     * @param array $config
     * @throws \Exception
     */
    public static function init(array $config)
    {
        if (static::$isInit === false) {
            static::$config = static::checkConfig($config);
            static::$isInit = true;
        }
    }

    /**
     * 获取DB
     * @return DB
     * @throws \Exception
     */
    public static function getInstance(): DB
    {
        if (self::$isInit === false) {
            $dbClass = DB::class;
            throw new \Exception($dbClass . '没有初始化' . $dbClass . '::init');
        }
        if (self::$_instance === null) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    /**
     * 检查mysql配置文件
     * @param array $config
     * @param bool  $slave
     * @return array
     * @throws \Exception
     */
    private static function checkConfig(array $config, bool $slave = false)
    {
        $msg = 'DB';
        if ($slave) {
            $msg .= ' Slave';
        }
        if (empty($config)) {
            throw new \Exception($msg . '配置不能为空');
        }
        if (empty($config['host'])) {
            throw new \Exception($msg . '配置host不能为空');
        }
        if (empty($config['port'])) {
            throw new \Exception($msg . '配置port不能为空');
        }
        if (empty($config['user'])) {
            throw new \Exception($msg . '配置user不能为空');
        }
        if (empty($config['password'])) {
            throw new \Exception($msg . '配置password不能为空');
        }
        if (empty($config['charset'])) {
            $config['charset'] = 'utf8';
        }
        if (empty($config['connectTimeout'])) {
            $config['connectTimeout'] = 1;//1秒
        }
        if (empty($config['database'])) {
            throw new \Exception($msg . '配置database不能为空');
        }
        if ($slave) {
            // 默认负载均衡1 每个slave平等
            if (empty($config['weight'])) {
                $config['weight'] = 1;
            }
        } else {
            if (!empty($config['slave'])) {
                $config['slave'] = self::checkConfig($config['slave'], true);
            }
        }
        return $config;
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
    protected static function getConnection($is_read = true): \PDO
    {
        if (isset(static::$config['slave']) && !empty(static::$config['slave'])) {
            // 配置了从库
            if ($is_read) {
                // 读库
                if (static::$connection['slave'] == null) {
                    //读操作选择slave
                    $sid                         = self::getGravity(array_column(static::$config['slave'], 'weight'));
                    $connection                  = Connection::getInstance(static::$config['slave'][$sid]);
                    static::$connection['slave'] = $connection;
                } else {
                    $connection = static::$connection['slave'];
                }
            } else {
                // 除了读操作的，选择主库
                if (static::$connection['master'] == null) {
                    $connection                   = Connection::getInstance(static::$config);
                    static::$connection['master'] = $connection;
                } else {
                    $connection = static::$connection['master'];
                }
            }
        } else {
            // 没有配置读写分离从库，读写在一个数据库
            if (static::$connection['master'] == null) {
                // 主库链接也不存在
                $connection                   = Connection::getInstance(static::$config);
                static::$connection['master'] = static::$connection['slave'] = $connection;
            } else {
                // 直接使用主库
                $connection = static::$connection['master'];
            }
        }
        return $connection;
    }
}
