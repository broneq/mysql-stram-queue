-- Sample tables for sql-stream-queue

CREATE TABLE IF NOT EXISTS `ssq_stream`
(
  `id`                int(11)     NOT NULL AUTO_INCREMENT,
  `stream_name`       varchar(64) NOT NULL,
  `message`           text        NOT NULL,
  `created_at`        datetime    NOT NULL,
  `planned_execution` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stream_name` (`stream_name`),
  KEY `planned_execution` (`planned_execution`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

CREATE TABLE IF NOT EXISTS `ssq_status`
(
  `id`                int(11)                      NOT NULL AUTO_INCREMENT,
  `stream_id`         int(11)                      NOT NULL,
  `consumer_group_id` int(11)                      NOT NULL,
  `status`            enum ('NEW','PENDING','ACK') NOT NULL,
  `created_at`        datetime                     NOT NULL,
  `updated_at`        datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stream_id` (`stream_id`),
  KEY `consumer_group_id` (`consumer_group_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

CREATE TABLE IF NOT EXISTS `ssq_consumer_group`
(
  `id`        int(11)     NOT NULL AUTO_INCREMENT,
  `stream_id` int(11)     NOT NULL,
  `name`      varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `consumerGroup` (`stream_id`, `name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

