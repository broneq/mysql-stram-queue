-- Sample tables for sql-stream-queue

CREATE TABLE IF NOT EXISTS `ssq_stream` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_name` varchar(64) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  `planned_execution` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `ssq_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `consumer_group` varchar(64) NOT NULL,
  `status` enum('NEW','PENDING','ACK','') NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;