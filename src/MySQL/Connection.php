<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/3/14
 * Time: 下午10:46
 */

namespace BaAGee\MySQL\MySQL;

use BaAGee\MySQL\SingletonTrait;

final class Connection
{
    use SingletonTrait;

    /**
     * 获取mysql连接实例
     * @param array $config
     * @return null|\PDO
     */
    public static function getInstance(array $config): \PDO
    {
        $dsn = sprintf('mysql:dbname=%s;host=%s;port=%d', $config['database'], $config['host'], $config['port']);
        $key = md5($dsn);
        if (empty(self::$_instance[$key])) {
            if (isset($config['connect_timeout'])) {
                $connect_timeout = intval($config['connect_timeout']) == 0 ? 2 : intval($config['connect_timeout']);
            } else {
                $connect_timeout = 2;
            }
            $options = [
                \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,//禁止多语句查询
                \PDO::MYSQL_ATTR_INIT_COMMAND     => "SET NAMES '" . $config['charset'] . "';",// 设置客户端连接字符集
                \PDO::ATTR_TIMEOUT                => $connect_timeout// 设置超时
            ];
            $pdo     = new \PDO($dsn, $config['user'], $config['password'], $options);;
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false); //禁用模拟预处理
            self::$_instance[$key] = $pdo;
        }
        return self::$_instance[$key];
    }
}
