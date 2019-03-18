CREATE TABLE `student_score` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `student_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `chinese` tinyint(3) NOT NULL DEFAULT '0',
  `english` tinyint(3) NOT NULL DEFAULT '0',
  `math` tinyint(3) NOT NULL DEFAULT '0',
  `history` tinyint(3) NOT NULL DEFAULT '0',
  `biology` tinyint(3) NOT NULL DEFAULT '0' COMMENT '生物',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  `class_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `sex` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1男2女',
  `age` tinyint(2) NOT NULL DEFAULT '18',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `article` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `title` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content` text COLLATE utf8mb4_unicode_ci,
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  `tag` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=566 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci