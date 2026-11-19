<?php
// Estado de la base de datos: exit 0 = Moodle instalado, 2 = BD vacía, 1 = BD inaccesible.
$conn = @pg_connect(sprintf(
    "host=%s port=%d dbname=%s user=%s password=%s connect_timeout=5",
    getenv('MOODLE_DB_HOST') ?: 'db',
    (int) (getenv('MOODLE_DB_PORT') ?: 5432),
    getenv('POSTGRES_DB') ?: 'moodle',
    getenv('POSTGRES_USER') ?: 'moodle',
    getenv('POSTGRES_PASSWORD') ?: 'moodle'
));
if (!$conn) {
    exit(1);
}
$res = pg_query($conn, "SELECT to_regclass('public.mdl_config') IS NOT NULL AS installed");
$row = pg_fetch_assoc($res);
exit($row['installed'] === 't' ? 0 : 2);
