<?php
/**
 * Desc: mysql数据库配置
 * User: baagee
 * Date: 2019/3/15
 * Time: 下午6:47
 */
return [
    'host'           => '47.98.216.117',
    'port'           => 3306,
    'user'           => 'root',
    'password'       => 'gA@fF5aQ=rsfdlh',
    'database'       => 'spiders_data',
    'connectTimeout' => 1,
    'charset'        => 'utf8mb4',
    'slave'          => [
        [
            'host'           => '47.98.216.117',
            'port'           => 3306,
            'user'           => 'root',
            'password'       => 'gA@fF5aQ=rsfdlh',
            'database'       => 'spiders_data',
            'connectTimeout' => 1,
            'weight'         => 3,//数据库从库权重，越大使用的频率越大
        ],
        [
            'host'           => '47.98.216.117',
            'port'           => 3306,
            'user'           => 'root',
            'password'       => 'gA@fF5aQ=rsfdlh',
            'database'       => 'spiders_data',
            'connectTimeout' => 1,
            'weight'         => 5,//数据库从库权重，越大使用的频率越大
        ]
    ]
];
