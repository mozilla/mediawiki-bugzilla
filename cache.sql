CREATE TABLE IF NOT EXISTS `bugzilla_cache` (
  `id` char(40) NOT NULL DEFAULT '',
  `fetched_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `data` longtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB, DEFAULT CHARSET=binary;
