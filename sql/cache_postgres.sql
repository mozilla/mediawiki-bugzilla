CREATE LANGUAGE plpgsql;

CREATE TABLE bugzilla_cache (
    id          SERIAL PRIMARY KEY,
    key         VARCHAR(255) UNIQUE NOT NULL DEFAULT '',
    fetched_at  TIMESTAMPTZ NULL DEFAULT NULL,
    data        TEXT,
    expires     INTEGER NOT NULL DEFAULT 0
);

CREATE OR REPLACE FUNCTION update_fetched_at()
RETURNS TRIGGER AS '
  BEGIN
    NEW.fetched_at = now(); 
    RETURN NEW;
  END;
' LANGUAGE 'plpgsql';

CREATE TRIGGER tg_update BEFORE UPDATE ON ts FOR EACH ROW EXECUTE PROCEDURE update_fetched_at();