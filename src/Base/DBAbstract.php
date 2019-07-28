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

/**
 * Class DBAbstract
 * @package BaAGee\MySQL\Base
 */
abstract class DBAbstract
{
    use SingletonTrait;

    /**
     * 获取DB
     * @return DB
     * @throws \Exception
     */
    public static function getInstance(): DB
    {
        if (self::$_instance === null) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    /**
     * 获取数据库连接
     * @param bool $is_read
     * @return \PDO
     * @throws \Exception
     */
    protected static function getConnection($is_read = true): \PDO
    {
        return Connection::getInstance($is_read);
    }

    /**
     * 关闭连接
     * @param bool $isRead
     * @return bool
     */
    protected static function close(bool $isRead)
    {
        return Connection::close($isRead);
    }
}
