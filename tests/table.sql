CREATE TABLE `student_score`
(
    `id`           bigint(20) unsigned                    NOT NULL AUTO_INCREMENT,
    `student_name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `student_id`   bigint(20) unsigned                    NOT NULL DEFAULT '0',
    `chinese`      tinyint(3)                             NOT NULL DEFAULT '0',
    `english`      tinyint(3)                             NOT NULL DEFAULT '0',
    `math`         double                                 NOT NULL DEFAULT '0',
    `history`      tinyint(3)                             NOT NULL DEFAULT '0',
    `biology`      float                                  NOT NULL DEFAULT '0' COMMENT '生物',
    `create_time`  int(11)                                NOT NULL DEFAULT '0',
    `update_time`  int(11)                                NOT NULL DEFAULT '0',
    `class_id`     bigint(20) unsigned                    NOT NULL DEFAULT '0',
    `is_delete`    tinyint(1)                             NOT NULL DEFAULT '0',
    `sex`          tinyint(1)                             NOT NULL DEFAULT '1' COMMENT '1男2女',
    `age`          tinyint(2)                             NOT NULL DEFAULT '18',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `article`
(
    `id`          bigint(20) unsigned                     NOT NULL AUTO_INCREMENT,
    `user_id`     bigint(20) unsigned                     NOT NULL DEFAULT '0',
    `title`       varchar(50) COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT '',
    `content`     text COLLATE utf8mb4_unicode_ci,
    `create_time` int(11)                                 NOT NULL DEFAULT '0',
    `update_time` int(11)                                 NOT NULL DEFAULT '0',
    `tag`         varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `class_group`
(
    `id`          bigint(20) unsigned                    NOT NULL AUTO_INCREMENT,
    `name`        varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `create_time` timestamp                              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 11
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `film_actor`
(
    `id`       int(11) NOT NULL,
    `film_id`  int(11) NOT NULL,
    `actor_id` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_film_actor_id` (`film_id`, `actor_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `actor`
(
    `id`          int(11) NOT NULL,
    `name`        varchar(45) DEFAULT NULL,
    `update_time` datetime    DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `film`
(
    `id`   int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(10) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_name` (`name`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 4
  DEFAULT CHARSET = utf8;