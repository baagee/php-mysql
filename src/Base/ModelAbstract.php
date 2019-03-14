<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/3/15
 * Time: 上午12:06
 */

namespace BaAGee\MySQL\Base;

use BaAGee\MySQL\MySQL\DB;

/**
 * Class ModelAbstract
 * @package BaAGee\MySQL\Base
 */
abstract class ModelAbstract
{
    /**
     * @var bool
     */
    protected static $isInit = false;

    /**
     * @var array
     */
    protected static $config = [
        'MySQL'          => [],
        'schemaBasePath' => ''
    ];

    /**
     * Model初始化
     * @param array $config
     * @throws \Exception
     */
    public static function init(array $config)
    {
        if (static::$isInit === false) {
            static::$config = self::checkConfig($config);
            DB::init(self::$config['MySQL']);
            static::$isInit = true;
        }
    }

    /**
     * 检查配置文件
     * @param array $config
     * @return array
     * @throws \Exception
     */
    private static function checkConfig(array $config)
    {
        if (empty($config['MySQL'])) {
            throw new \Exception('配置MySQL不能为空');
        } else {
            // 检查每一项
            $config['MySQL'] = self::checkMySQLConfig($config['MySQL']);
        }
        if (empty($config['schemaBasePath'])) {
            throw new \Exception('配置schemaBasePath不能为空');
        } else {
            $config['schemaBasePath'] = $config['schemaBasePath'] . DIRECTORY_SEPARATOR . $config['MySQL']['database'];
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
    private static function checkMySQLConfig(array $config, bool $slave = false)
    {
        if (empty($config['host'])) {
            throw new \Exception('配置MySQL.host不能为空');
        }
        if (empty($config['port'])) {
            throw new \Exception('配置MySQL.port不能为空');
        }
        if (empty($config['user'])) {
            throw new \Exception('配置MySQL.user不能为空');
        }
        if (empty($config['password'])) {
            throw new \Exception('配置MySQL.password不能为空');
        }
        if (empty($config['charset'])) {
            $config['charset'] = 'utf8';
        }
        if (empty($config['connectTimeout'])) {
            $config['connectTimeout'] = 1;//1秒
        }
        if (empty($config['database'])) {
            throw new \Exception('配置MySQL.database不能为空');
        }
        if ($slave) {
            // 默认负载均衡1 每个slave平等
            if (empty($config['weight'])) {
                $config['weight'] = 1;
            }
        } else {
            if (!empty($config['slave'])) {
                $config['slave'] = self::checkMySQLConfig($config['slave'], true);
            }
        }
        return $config;
    }
}
