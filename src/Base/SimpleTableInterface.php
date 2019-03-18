<?php
/**
 * Desc: SimpleTable 接口
 * User: baagee
 * Date: 2019/3/18
 * Time: 下午2:11
 */

namespace BaAGee\MySQL\Base;

interface SimpleTableInterface
{
    public function select(array $data);

    public function update(array $data);

    public function delete(array $data);

    public function insert(array $data, bool $replace);
}
