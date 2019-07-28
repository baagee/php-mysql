<?php
/**
 * Desc: 数据库配置
 * User: baagee
 * Date: 2019/3/15
 * Time: 下午6:47
 */
return [
    'host'           => '',
    'port'           => 3306,
    'user'           => 'root',
    'password'       => '',
    'database'       => 'spiders_data',
    'connectTimeout' => 1,
    'charset'        => 'utf8mb4',
    'slave'          => [
        [
            'host'           => '',
            'port'           => 3306,
            'user'           => 'root',
            'password'       => '',
            'database'       => 'spiders_data',
            'connectTimeout' => 1,
            'weight'         => 3,//数据库从库权重，越大使用的频率越大
        ],
        [
            'host'           => '',
            'port'           => 3306,
            'user'           => 'root',
            'password'       => '',
            'database'       => 'spiders_data',
            'connectTimeout' => 1,
            'weight'         => 5,//数据库从库权重，越大使用的频率越大
        ]
    ]
];
