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
            if (!is_null(self::$saveCallback) && is_array(self::$saveCallback)) {
                try {
                    foreach (self::getAllFullSql() as $itemSql) {
                        $params = self::$saveCallback['params'];
                        $params['sqlInfo'] = $itemSql;
                        call_user_func(self::$saveCallback['callable'], $params);
                        unset($params);
                    }
                } catch (\Throwable $e) {
                    // 捕获所有抛出的错误 保证不会影响到后面的register_shutdown_function
                    // TODO something
                }
            }
        });
    }

    /**
     * 记录sql执行信息
     * @param string $prepareSql    预处理sql
     * @param array  $prepareData   预处理data
     * @param float  $startTime     开始时间点
     * @param float  $connectedTime 获得链接时间点
     * @param float  $endTime       结束时间点
     * @param bool   $success       是否执行成功
     * @param string $errMsg        错误信息
     */
    public static function record(string $prepareSql, $startTime, $connectedTime, $endTime, bool $success,
                                  array $prepareData = [], $errMsg = '')
    {
        $row = [
            'prepareSql'    => $prepareSql,
            'prepareData'   => $prepareData,
            'startTime'     => $startTime,
            'connectedTime' => $connectedTime,
            'endTime'       => $endTime,
            'success'       => $success,
        ];
        if (!$success) {
            $row['errorInfo'] = $errMsg;
        }
        self::$sqlList[] = $row;
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
                if ($field[0] !== ':') {
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
