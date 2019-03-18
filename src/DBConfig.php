<?php
/**
 * Desc: 数据库配置类
 * User: baagee
 * Date: 2019/3/19
 * Time: 上午12:07
 */

namespace BaAGee\MySQL;

/**
 * Class DBConfig
 * @package BaAGee\MySQL
 */
final class DBConfig
{
    /**
     * @var array
     */
    protected static $config = [];

    /**
     * @var bool
     */
    protected static $isInit = false;

    final private function __clone()
    {
    }

    /**
     * DBConfig constructor.
     */
    final private function __construct()
    {
    }

    /**
     * 配置初始化
     * @param array $config
     * @throws \Exception
     */
    final public static function init(array $config)
    {
        if (self::$isInit === false) {
            // 只允许初始化一次 检查配置
            self::$config = self::checkConfig($config);
            self::$isInit = true;
        }
    }

    /**
     * @param      $config
     * @param bool $slave
     * @return mixed
     * @throws \Exception
     */
    final private static function checkMySQLConfig($config, $slave = false)
    {
        $msg = 'DB';
        if ($slave) {
            $msg .= ' Slave';
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
        }
        return $config;
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
        if (empty($config)) {
            throw new \Exception('DB配置不能为空');
        }
        if ($slave) {
            foreach ($config as &$item) {
                // 依次检查每个slave配置
                $item = self::checkMySQLConfig($item, true);
            }
        } else {
            // 检查master每一项
            $config = self::checkMySQLConfig($config, false);
            if (!empty($config['slave'])) {
                // 配置了从库
                $config['slave'] = self::checkConfig($config['slave'], true);
            }
        }
        return $config;
    }

    /**
     * 获取配置
     * @param string $name
     * @return array|mixed|null
     * @throws \Exception
     */
    final public static function get($name = '')
    {
        if (self::$isInit === false) {
            throw new \Exception(__CLASS__ . '没有初始化init');
        }
        if (empty($name)) {
            // 返回所有配置
            return self::$config;
        } else {
            if (isset(self::$config[$name])) {
                return self::$config[$name];
            } else {
                return null;
            }
        }
    }
}
