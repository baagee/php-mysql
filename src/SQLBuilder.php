<?php
/**
 * Desc: Sql生成器
 * User: baagee
 * Date: 2019/3/15
 * Time: 下午5:15
 */

namespace BaAGee\MySQL;

use BaAGee\MySQL\Base\SingletonTrait;

final class SQLBuilder
{
    use SingletonTrait;

    private $table = '';

    /**
     * 最后一次预处理sql语句
     * @var string
     */
    private $prepareSql = '';
    /**
     * 最后一次处理sql的数据参数
     * @var array
     */
    private $prepareData = [];

    /**
     * 要查询的条件
     * @var string
     */
    private $whereConditions = '';
    /**
     * having条件
     * @var string
     */
    private $havingConditions = '';
    /**
     * order by field desc/asc
     * @var string
     */
    private $orderBy = '';
    /**
     * group by field
     * @var string
     */
    private $groupBy = '';
    /**
     * limit offset,limit
     * @var string
     */
    private $limit = '';
    /**
     * fields
     * @var string
     */
    private $fields = '';

    /**
     * 加锁类型
     * @var string
     */
    private $lock = '';

    public static function getInstance($table)
    {
        $table = trim($table);
        if (empty(self::$_instance[$table])) {
            $self                    = new self();
            $self->table             = $table;
            self::$_instance[$table] = $self;
        }
        return self::$_instance[$table];
    }

    public function buildSelect()
    {

    }

    public function buildUpdate()
    {

    }

    public function buildDelete()
    {

    }

    public function buildInsert()
    {

    }

    /**
     * @return array
     */
    public function getPrepareData(): array
    {
        return $this->prepareData;
    }

    /**
     * @return string
     */
    public function getPrepareSql(): string
    {
        return $this->prepareSql;
    }

    /**
     * 清除上次执行的
     */
    public function reset()
    {
        $this->havingConditions = '';
        $this->orderBy          = '';
        $this->whereConditions  = '';
        $this->limit            = '';
        $this->groupBy          = '';
        $this->fields           = '';
        $this->prepareSql       = '';
        $this->prepareData      = [];
        $this->lock             = '';
    }
}
