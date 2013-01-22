CREATE TABLE IF NOT EXISTS `bugzilla_cache` (
  `id` integer(40) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL DEFAULT '',
  `fetched_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `data` longtext,
  `expires` integer(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB, DEFAULT CHARSET=binary;

CREATE UNIQUE INDEX `uniq_bugzilla_cache_key` ON `bugzilla_cache` (`key`);
