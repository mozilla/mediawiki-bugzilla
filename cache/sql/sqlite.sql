CREATE TABLE `bugzilla_cache` (
    `id`         integer primary key AUTOINCREMENT,
    `key`        TEXT NOT NULL DEFAULT '',
    `data`       TEXT,
    `expires`    integer(11) NOT NULL DEFAULT 0
);
CREATE UNIQUE INDEX uniq_bugzilla_cache_key ON `bugzilla_cache` (`key`);

