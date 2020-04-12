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
    public const DEFAULT = 'default';
    /**
     * @var array
     */
    protected static $configMap = [];

    protected static $currentName = self::DEFAULT;

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
     * @param array  $config 配置数据
     * @param string $name   名字
     * @throws \Exception
     */
    final public static function init(array $config, string $name = self::DEFAULT)
    {
        if (self::$isInit === false) {
            self::$isInit = true;
            // 只允许初始化一次 检查配置
            $name = trim($name);
            self::addConfig($config, $name);
            self::$currentName = $name;
        }
    }

    /**
     * 添加配置
     * @param array  $config 配置
     * @param string $name   名字
     * @throws \Exception
     */
    final public static function addConfig(array $config, string $name)
    {
        if (empty($name)) {
            throw new \Exception("addConfig参数name不能为空");
        }
        self::$configMap[$name] = self::checkConfig($config);
    }

    /**
     * 切换到某个配置
     * @param string $name 配置标记名字
     * @return bool
     * @throws \Exception
     */
    final public static function switchTo(string $name)
    {
        $name = trim($name);
        if (empty($name)) {
            return false;
        }
        if (!isset(self::$configMap[$name])) {
            throw new \Exception(__CLASS__ . " 配置信息中不存在 " . $name . '的信息');
        }
        self::$currentName = $name;
        return true;
    }

    /**
     * @throws \Exception
     */
    final public static function createSchemasDir()
    {
        $schemasCachePath = rtrim(self::get('schemasCachePath', ''), '/');
        if (!is_null($schemasCachePath) && !empty($schemasCachePath)) {
            $schemasCachePath .= '/' . self::$currentName;
            if (!is_dir($schemasCachePath)) {
                $res = mkdir($schemasCachePath, 0755, true);
                if ($res == false) {
                    throw new \Exception(sprintf('%s目录创建失败', $schemasCachePath));
                }
            } elseif (!is_writeable($schemasCachePath)) {
                $res = chmod($schemasCachePath, 0755);
                if ($res == false) {
                    throw new \Exception(sprintf('%s目录设置可读写权限失败', $schemasCachePath));
                }
            }
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
     * @param null   $default
     * @return mixed|null
     * @throws \Exception
     */
    final public static function get($name = '', $default = null)
    {
        if (self::$isInit === false) {
            throw new \Exception(__CLASS__ . '没有初始化init');
        }
        $config = self::$configMap[self::$currentName];
        if (empty($name)) {
            // 返回所有配置
            return $config;
        } else {
            if (isset($config[$name])) {
                return $config[$name];
            } else {
                return $default;
            }
        }
    }

    /**
     * 获取当前配置名字
     * @return string
     */
    final public static function getCurrentName()
    {
        return self::$currentName;
    }
}
