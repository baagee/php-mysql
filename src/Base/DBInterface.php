<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/3/15
 * Time: 下午5:11
 */

namespace BaAGee\MySQL\Base;
interface DBInterface
{
    public function query(string $sql, array $params = []);

    public function execute(string $sql, array $params = []);

    public function getLastInsertId();

    public static function getLastSql();

    public function beginTransaction();

    public function commit();

    public function rollback();
}
