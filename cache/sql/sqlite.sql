CREATE TABLE `bugzilla_cache` (
    `id`         integer primary key AUTOINCREMENT,
    `key`        TEXT NOT NULL DEFAULT '',
    `data`       TEXT,
    `expires`    integer(11) NOT NULL DEFAULT 0
);