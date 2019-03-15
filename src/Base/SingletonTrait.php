<?php
/**
 * Desc: 单例trait
 * User: baagee
 * Date: 2019/1/31
 * Time: 下午6:35
 */

namespace BaAGee\MySQL\Base;
/**
 * Trait SingletonTrait
 * @package Sim
 */
trait SingletonTrait
{
    /**
     * @var null 类实例
     */
    protected static $_instance = null;

    /**
     * 禁止new
     * SingletonTrait constructor.
     */
    final private function __construct()
    {
    }

    /**
     * 禁止克隆
     */
    final private function __clone()
    {
    }

    /**
     * 获取实例的方法
     * @param array $params 构造实例所需参数
     * @return mixed
     */
    abstract public static function getInstance(array $params = []);
}