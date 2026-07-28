-- Creates the dedicated testing database so the Pest suite can run against a
-- real Postgres instance without touching the development database.
CREATE DATABASE imgz_testing;
GRANT ALL PRIVILEGES ON DATABASE imgz_testing TO imgz;
