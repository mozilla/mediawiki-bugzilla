CREATE TABLE bugzilla_cache (
    id          SERIAL PRIMARY KEY,
    key         VARCHAR(255) UNIQUE NOT NULL DEFAULT '',
    data        TEXT,
    expires     INTEGER NOT NULL DEFAULT 0
);