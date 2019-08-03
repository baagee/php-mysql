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
     * 记录sql
     * @param string $prepareSql
     * @param array  $prepareData
     */
    public static function record(string $prepareSql, array $prepareData = [])
    {
        self::$sqlList[] = [
            'prepareSql'  => $prepareSql,
            'prepareData' => $prepareData,
        ];
    }

    /**
     * 获取最后一条执行的sql
     * @return mixed|string
     */
    public static function getLastSql()
    {
        $end = end(self::$sqlList);
        return self::replaceSqlData($end['prepareSql'], $end['prepareData']);
    }

    /**
     * 返回所有运行sql的生成器
     * @return \Generator
     */
    public static function getAllFullSql()
    {
        foreach (self::$sqlList as $itemSqlData) {
            yield self::replaceSqlData($itemSqlData['prepareSql'], $itemSqlData['prepareData']);
        }
    }

    /**
     * 将预处理的sql占位符替换成真实的值，拼成完整sql
     * @param string $prepareSql
     * @param array  $prepareData
     * @return mixed|string
     */
    private static function replaceSqlData(string $prepareSql, $prepareData = [])
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
