<?php
/**
 * Desc: Model接口
 * User: baagee
 * Date: 2019/3/15
 * Time: 上午12:05
 */

namespace BaAGee\MySQL\Base;

interface ModelInterface
{
    public function where(array $conditions);

    public function orWhere(array $conditions);

    public function having(array $conditions);

    public function orHaving(array $conditions);

    public function fields(array $fields);

    public function limit(int $offset, int $limit);

    public function groupBy(string $field);

    public function orderBy(array $orderBy);

    public function increment(string $field, int $step);

    public function decrement(string $field, int $step);

    public function insert(array $data);

    public function batchInsert(array $data);

    public function delete();

    public function update(array $data);

    public function select();

    public function count(string $field);

    public function sum(string $field);

    public function avg(string $field);

    public function max(string $field);

    public function min(string $field);

    public function lockForUpdate();

    public function lockInShareMode();

    public function getLastInsertId();

    public function getLastSQL();
}