<?php  // Configuración de Moodle generada para el entorno Docker de desarrollo.
// Todos los valores se leen de variables de entorno (definidas en .env / docker-compose.yml).

unset($CFG);
global $CFG;
$CFG = new stdClass();

$env = function (string $name, $default = '') {
    $value = getenv($name);
    return ($value === false || $value === '') ? $default : $value;
};

$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = $env('MOODLE_DB_HOST', 'db');
$CFG->dbname    = $env('POSTGRES_DB', 'moodle');
$CFG->dbuser    = $env('POSTGRES_USER', 'moodle');
$CFG->dbpass    = $env('POSTGRES_PASSWORD', 'moodle');
$CFG->prefix    = 'mdl_';
$CFG->dboptions = [
    'dbpersist' => 0,
    'dbport'    => (int) $env('MOODLE_DB_PORT', 5432),
    'dbsocket'  => '',
];

// Debe coincidir EXACTAMENTE con la URL que usas en el navegador (incluido el puerto).
$CFG->wwwroot   = rtrim($env('MOODLE_WWWROOT', 'http://localhost:8080'), '/');
$CFG->dataroot  = '/var/www/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 02777;

// Detrás de un proxy HTTPS (ngrok, traefik...): MOODLE_SSLPROXY=1 y wwwroot con https://.
if ($env('MOODLE_SSLPROXY', '0') === '1') {
    $CFG->sslproxy = true;
}

// Correo: desactivado salvo que se configure SMTP en .env.
if ($env('MOODLE_SMTP_HOST') !== '') {
    $CFG->smtphosts  = $env('MOODLE_SMTP_HOST');
    $CFG->smtpuser   = $env('MOODLE_SMTP_USER');
    $CFG->smtppass   = $env('MOODLE_SMTP_PASS');
    $CFG->smtpsecure = $env('MOODLE_SMTP_SECURE');
    $CFG->noreplyaddress = $env('MOODLE_NOREPLY_ADDRESS', 'noreply@example.com');
} else {
    $CFG->noemailever = true;
}

// Modo desarrollo: depuración DEVELOPER visible y cachés de JS / plantillas / tema desactivadas.
if ($env('MOODLE_DEBUG', '1') === '1') {
    @error_reporting(E_ALL);
    @ini_set('display_errors', '1');
    $CFG->debug = E_ALL;          // DEBUG_DEVELOPER
    $CFG->debugdisplay = 1;
    $CFG->cachejs = false;
    $CFG->cachetemplates = false;
    $CFG->langstringcache = false;
    $CFG->themedesignermode = true;
}

$CFG->preventexecpath = true;

require_once(__DIR__ . '/lib/setup.php');
// No hay etiqueta de cierre PHP a propósito.
