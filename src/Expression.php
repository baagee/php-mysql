<?php
/**
 * Desc: 表达式
 * User: baagee
 * Date: 2019/7/30
 * Time: 23:35
 */

namespace BaAGee\MySQL;

/**
 * Class Expression
 * @package BaAGee\MySQL
 */
final class Expression
{
    /**
     * @var string
     */
    private $expression = '';

    /**
     * Expression constructor.
     * @param string $expression
     */
    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    /**
     * 直接输出对象引用时自动调用的
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->expression;
    }
}
