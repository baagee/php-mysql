<?php
/**
 * Desc: mysql数据库配置
 * User: baagee
 * Date: 2019/3/15
 * Time: 下午6:47
 */

return [
    'host'             => '127.0.0.1',
    'port'             => 5200,
    'user'             => 'aaa',
    'password'         => '1q2w3e@sf',
    'database'         => 'sss',
    'connectTimeout'   => 1,
    'charset'          => 'utf8mb4',
    'retryTimes'       => 1,//重试次数
    'schemasCachePath' => __DIR__ . '/schemas',
    'options'          => [
        \PDO::ATTR_PERSISTENT => true,
    ],
    'slave'            => [
        [
            'host'             => '127.0.0.1',
            'port'             => 5200,
            'user'             => 'aaa',
            'password'         => '1q2w3e@sf',
            'database'         => 'sss',
            'connectTimeout' => 1,
            'weight'         => 3,//数据库从库权重，越大使用的频率越大
        ],
        [
            'host'             => '127.0.0.1',
            'port'             => 5200,
            'user'             => 'aaa',
            'password'         => '1q2w3e@sf',
            'database'         => 'sss',
            'connectTimeout' => 1,
            'weight'         => 5,//数据库从库权重，越大使用的频率越大
        ]
    ]
];
