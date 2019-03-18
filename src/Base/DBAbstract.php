<?php
/**
 * Desc: DB抽象类
 * User: baagee
 * Date: 2019/3/15
 * Time: 下午5:13
 */

namespace BaAGee\MySQL\Base;

use BaAGee\MySQL\Connection;
use BaAGee\MySQL\DB;
use BaAGee\MySQL\DBConfig;

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
     * @var array 保存数据库链接
     */
    protected static $connection = [
        'master' => null,
        'slave'  => null,
    ];

    /**
     * 获取DB
     * @return DB
     * @throws \Exception
     */
    public static function getInstance(): DB
    {
        if (self::$_instance === null) {
            self::$_instance = new static();
            self::$config    = DBConfig::get();
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
