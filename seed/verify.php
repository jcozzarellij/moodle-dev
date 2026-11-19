<?php
// Verifica que los datos demo de seed/data.json existen en la instancia.
// Uso: docker compose exec -T -u www-data moodle php /opt/moodle-dev/seed/verify.php
// Sale con código 1 si algo falta (lo usa el workflow de CI).

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/clilib.php');

$data = json_decode(file_get_contents(__DIR__ . '/data.json'), true, 512, JSON_THROW_ON_ERROR);
$errors = [];
$check = function (bool $ok, string $msg) use (&$errors) {
    cli_writeln(($ok ? '  OK    ' : '  FALLA ') . $msg);
    if (!$ok) {
        $errors[] = $msg;
    }
};

cli_writeln('Moodle ' . $CFG->release);
$check((bool) get_config('moodledevtemplate', 'seeded'), 'Marca de datos demo cargados');
$check($CFG->lang === (getenv('MOODLE_LANG') ?: 'es'), "Idioma por defecto del sitio = {$CFG->lang}");

$admin = get_admin();
$check($admin && $admin->username === (getenv('MOODLE_ADMIN_USER') ?: 'admin'), 'Usuario administrador');

foreach (array_merge($data['users'], [$data['webservice']['user']]) as $u) {
    $user = $DB->get_record('user', ['username' => $u['username'], 'deleted' => 0]);
    $check($user && validate_internal_user_password($user, $u['password']), "Usuario {$u['username']} (contraseña válida)");
}

$expectedmods = ['page', 'url', 'forum', 'assign', 'quiz'];
foreach ($data['courses'] as $c) {
    $course = $DB->get_record('course', ['shortname' => $c['shortname']]);
    $check((bool) $course, "Curso {$c['shortname']}");
    if (!$course) {
        continue;
    }
    $ctx = context_course::instance($course->id);
    $students = count_role_users($DB->get_field('role', 'id', ['shortname' => 'student']), $ctx);
    $check($students === count($c['students']), "{$c['shortname']}: $students estudiantes matriculados");
    $check(is_enrolled($ctx, $DB->get_record('user', ['username' => $c['teacher']])), "{$c['shortname']}: profesor {$c['teacher']}");
    $modinfo = get_fast_modinfo($course);
    foreach ($expectedmods as $m) {
        $check(count($modinfo->get_instances_of($m)) > 0, "{$c['shortname']}: actividad $m");
    }
    foreach ($modinfo->get_instances_of('quiz') as $cm) {
        $sum = (float) $DB->get_field('quiz', 'sumgrades', ['id' => $cm->instance]);
        $check($sum > 0, "{$c['shortname']}: cuestionario con preguntas (sumgrades=$sum)");
    }
}

$check((bool) $CFG->enablewebservices, 'Servicios web activados');
$check(str_contains((string) $CFG->webserviceprotocols, 'rest'), 'Protocolo REST activado');
$check($DB->record_exists('external_tokens', []), 'Token de servicio web creado');

if ($errors) {
    cli_writeln("\n" . count($errors) . ' comprobación(es) fallida(s).');
    exit(1);
}
cli_writeln("\nTodo correcto.");
