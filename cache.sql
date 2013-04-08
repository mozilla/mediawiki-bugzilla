CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/bugzilla_cache (
  `id` integer(40) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL DEFAULT '',
  `fetched_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `data` longtext,
  `expires` integer(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/key_unique ON /*_*/bugzilla_cache (`key`);
