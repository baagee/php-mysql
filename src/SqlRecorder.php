<?php
/**
 * Desc: Sql记录者
 * User: baagee
 * Date: 2019/8/3
 * Time: 20:42
 */

namespace BaAGee\MySQL;

/**
 * Class SqlRecorder
 * @package BaAGee\MySQL
 */
final class SqlRecorder
{
    /**
     * @var array
     */
    protected static $sqlList = [];
    /**
     * @var null
     */
    protected static $saveCallback = null;

    /**
     * 设置sql日志保存方式
     * @param       $callable
     * @param array $params
     */
    public static function setSaveHandler($callable, array $params = [])
    {
        self::$saveCallback = compact('callable', 'params');
        register_shutdown_function(function () {
            foreach (self::getAllFullSql() as $itemSql) {
                $params            = self::$saveCallback['params'];
                $params['sqlInfo'] = $itemSql;
                call_user_func(self::$saveCallback['callable'], $params);
                unset($params);
            }
        });
    }

    /**
     * @param string $prepareSql
     * @param array  $prepareData
     * @param int    $startTime
     * @param int    $endTime
     */
    public static function record(string $prepareSql, array $prepareData = [], $startTime = 0, $endTime = 0)
    {
        self::$sqlList[] = [
            'prepareSql'  => $prepareSql,
            'prepareData' => $prepareData,
            'startTime'   => $startTime,
            'endTime'     => $endTime,
        ];
    }

    /**
     * 获取最后一条执行的sql信息
     * @return array
     */
    public static function getLastSql()
    {
        $end            = self::$sqlList[count(self::$sqlList) - 1];
        $end['fullSql'] = self::replaceSqlPlaceholder($end['prepareSql'], $end['prepareData']);
        return $end;
    }

    /**
     * 返回所有运行sql的生成器
     * @return \Generator
     */
    public static function getAllFullSql()
    {
        foreach (self::$sqlList as $itemSqlData) {
            $itemSqlData['fullSql'] = self::replaceSqlPlaceholder($itemSqlData['prepareSql'], $itemSqlData['prepareData']);
            yield $itemSqlData;
        }
    }

    /**
     * 将预处理的sql占位符替换成真实的值，拼成完整sql
     * @param string $prepareSql
     * @param array  $prepareData
     * @return mixed|string
     */
    private static function replaceSqlPlaceholder(string $prepareSql, $prepareData = [])
    {
        $fullSql = $prepareSql;
        if (strpos($fullSql, '?') !== false) {
            // 使用？占位符
            $tmp1    = explode('?', $fullSql);
            $fullSql = '';
            $count   = count($tmp1);
            for ($i = 0; $i < $count; $i++) {
                $fullSql .= $tmp1[$i];
                if ($i !== $count - 1) {
                    if (isset($prepareData[$i])) {
                        $value = $prepareData[$i];
                        $type  = gettype($value);
                        if (!in_array($type, ['integer', 'double'])) {
                            $value = '\'' . $value . '\'';
                        }
                        $fullSql .= $value;
                    } else {
                        $fullSql .= '?';
                    }
                }
            }
        } else {
            // 使用:field占位
            foreach ($prepareData as $field => $value) {
                if ($field{0} !== ':') {
                    $field = ':' . $field;
                }
                $type = gettype($value);
                if (!in_array($type, ['integer', 'double'])) {
                    $value = '\'' . $value . '\'';
                }
                $fullSql = str_replace($field, $value, $fullSql);
            }
        }
        return $fullSql;
    }
}
