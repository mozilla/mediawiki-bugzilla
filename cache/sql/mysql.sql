CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/bugzilla_cache (
  `id` integer(40) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL DEFAULT '',
  `data` longtext,
  `expires` integer(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX /*i*/uniq_bugzilla_cache_key (`key`)
) /*$wgDBTableOptions*/;
