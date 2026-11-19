<?php
// Carga los datos demo definidos en seed/data.json.
// Se ejecuta automáticamente tras la primera instalación (ver docker/bin/entrypoint.sh).
// Es idempotente: si ya se cargaron, no hace nada (usa --force para reintentar lo que falte).
//
// Uso manual: docker compose exec -u www-data moodle php /opt/moodle-dev/seed/seed.php [--force]

define('CLI_SCRIPT', true);

require('/var/www/html/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/questionlib.php');

// Menos ruido en la salida: el seed no necesita depuración nivel DEVELOPER.
$CFG->debug = DEBUG_MINIMAL;
$CFG->debugdeveloper = false;

const SEED_COMPONENT = 'moodledevtemplate';

$force = in_array('--force', $argv ?? [], true);
if (get_config(SEED_COMPONENT, 'seeded') && !$force) {
    cli_writeln('[seed] Datos demo ya cargados: nada que hacer.');
    exit(0);
}

$data = json_decode(file_get_contents(__DIR__ . '/data.json'), true, 512, JSON_THROW_ON_ERROR);
$out = function (string $msg): void {
    cli_writeln('[seed] ' . $msg);
};

\core\session\manager::set_user(get_admin());
global $USER;
$gen = phpunit_util::get_data_generator();
$syscontext = context_system::instance();

// ---------------------------------------------------------------------------
// 1. Configuración del sitio
// ---------------------------------------------------------------------------
foreach (($data['site_config'] ?? []) as $name => $value) {
    set_config($name, $value);
}
$lang = getenv('MOODLE_LANG') ?: 'es';
if (get_string_manager()->translation_exists($lang, false)) {
    set_config('lang', $lang);
    $DB->set_field('user', 'lang', $lang, ['id' => get_admin()->id]);
} else {
    $out("AVISO: el idioma '$lang' no está instalado; se mantiene el idioma actual.");
    $lang = $CFG->lang;
}
if ($tz = getenv('MOODLE_TIMEZONE')) {
    set_config('timezone', $tz);
}
$out('Configuración del sitio aplicada (idioma: ' . $lang . ').');

// ---------------------------------------------------------------------------
// 2. Usuarios
// ---------------------------------------------------------------------------
$roleids = $DB->get_records_menu('role', null, '', 'shortname, id');

$getorcreateuser = function (array $u) use ($DB, $CFG, $gen, $lang) {
    $existing = $DB->get_record('user', ['username' => $u['username'], 'mnethostid' => $CFG->mnet_localhost_id]);
    if ($existing) {
        return $existing;
    }
    return $gen->create_user([
        'username'    => $u['username'],
        'password'    => $u['password'],
        'firstname'   => $u['firstname'],
        'lastname'    => $u['lastname'],
        'email'       => $u['email'],
        'auth'        => 'manual',
        'lang'        => $lang,
        'city'        => $u['city'] ?? 'Quito',
        'country'     => $u['country'] ?? 'EC',
        'description' => $u['description'] ?? '',
    ]);
};

$users = [];
foreach ($data['users'] as $u) {
    $user = $getorcreateuser($u);
    $users[$u['username']] = $user;
    if (!empty($u['system_role'])) {
        role_assign($roleids[$u['system_role']], $user->id, $syscontext->id);
    }
}
$out(count($users) . ' usuarios listos.');

// ---------------------------------------------------------------------------
// 3. Categoría, cursos, secciones, matrículas y actividades
// ---------------------------------------------------------------------------
$cat = $DB->get_record('course_categories', ['idnumber' => $data['category']['idnumber']]);
if (!$cat) {
    $cat = $gen->create_category([
        'name'        => $data['category']['name'],
        'idnumber'    => $data['category']['idnumber'],
        'description' => $data['category']['description'] ?? '',
    ]);
}

$qgen = $gen->get_plugin_generator('core_question');
$forumgen = $gen->get_plugin_generator('mod_forum');

foreach ($data['courses'] as $c) {
    if ($DB->record_exists('course', ['shortname' => $c['shortname']])) {
        $out("Curso {$c['shortname']} ya existe: se omite.");
        continue;
    }

    $course = $gen->create_course([
        'fullname'         => $c['fullname'],
        'shortname'        => $c['shortname'],
        'idnumber'         => $c['idnumber'] ?? '',
        'category'         => $cat->id,
        'summary'          => $c['summary'] ?? '',
        'summaryformat'    => FORMAT_HTML,
        'format'           => 'topics',
        'numsections'      => count($c['sections']),
        'enablecompletion' => 1,
        'startdate'        => usergetmidnight(time() - 7 * DAYSECS),
    ], ['createsections' => true]);

    // Matrículas.
    $teacher = $users[$c['teacher']];
    $gen->enrol_user($teacher->id, $course->id, 'editingteacher');
    if (!empty($data['teaching_assistant'])) {
        $gen->enrol_user($users[$data['teaching_assistant']]->id, $course->id, 'teacher');
    }
    foreach ($c['students'] as $s) {
        $gen->enrol_user($users[$s]->id, $course->id, 'student');
    }

    // Secciones y actividades.
    foreach ($c['sections'] as $i => $s) {
        $sectionnum = $i + 1;
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $sectionnum], '*', MUST_EXIST);
        course_update_section($course, $section, [
            'name'          => $s['name'],
            'summary'       => $s['summary'] ?? '',
            'summaryformat' => FORMAT_HTML,
        ]);

        foreach ($s['activities'] as $a) {
            $base = [
                'course'      => $course->id,
                'section'     => $sectionnum,
                'name'        => $a['name'],
                'intro'       => $a['intro'] ?? '',
                'introformat' => FORMAT_HTML,
                'completion'  => COMPLETION_TRACKING_MANUAL,
            ];
            switch ($a['type']) {
                case 'page':
                    $gen->create_module('page', $base + ['content' => $a['content'], 'contentformat' => FORMAT_HTML]);
                    break;

                case 'url':
                    $gen->create_module('url', $base + ['externalurl' => $a['externalurl']]);
                    break;

                case 'forum':
                    $forum = $gen->create_module('forum', $base + ['type' => 'general']);
                    if (!empty($a['discussion'])) {
                        $forumgen->create_discussion([
                            'course'  => $course->id,
                            'forum'   => $forum->id,
                            'userid'  => $teacher->id,
                            'name'    => $a['discussion']['subject'],
                            'message' => $a['discussion']['message'],
                        ]);
                    }
                    break;

                case 'assign':
                    $gen->create_module('assign', $base + [
                        'assignsubmission_onlinetext_enabled' => 1,
                        'assignsubmission_file_enabled'       => 1,
                        'assignsubmission_file_maxfiles'      => 5,
                        'assignsubmission_file_maxsizebytes'  => 0,
                        'submissiondrafts'                    => 0,
                        'grade'                               => 100,
                        'allowsubmissionsfromdate'            => time(),
                        'duedate'                             => time() + ((int) ($a['duedays'] ?? 14)) * DAYSECS,
                    ]);
                    break;

                case 'quiz':
                    $quiz = $gen->create_module('quiz', $base + ['grade' => 10, 'sumgrades' => 0]);
                    $qcat = $qgen->create_question_category([
                        'contextid' => context_course::instance($course->id)->id,
                        'name'      => $a['name'],
                    ]);
                    foreach ($a['questions'] as $n => $q) {
                        $feedback = $q['feedback'] ?? '';
                        // API estándar del banco de preguntas (el generador de pruebas requiere PHPUnit).
                        $question = (object) [
                            'category'  => $qcat->id,
                            'qtype'     => 'truefalse',
                            'createdby' => $USER->id,
                            'idnumber'  => null,
                        ];
                        $form = (object) [
                            'category'        => $qcat->id,
                            'name'            => 'Pregunta ' . ($n + 1),
                            'questiontext'    => ['text' => '<p>' . $q['text'] . '</p>', 'format' => FORMAT_HTML],
                            'generalfeedback' => ['text' => '<p>' . $feedback . '</p>', 'format' => FORMAT_HTML],
                            'defaultmark'     => 1,
                            'penalty'         => 1,
                            'correctanswer'   => $q['answer'] ? '1' : '0',
                            'feedbacktrue'    => ['text' => $q['answer'] ? '¡Correcto!' : 'Incorrecto.', 'format' => FORMAT_HTML],
                            'feedbackfalse'   => ['text' => $q['answer'] ? 'Incorrecto.' : '¡Correcto!', 'format' => FORMAT_HTML],
                            'showstandardinstruction' => 0,
                            'status'          => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY,
                        ];
                        $question = question_bank::get_qtype('truefalse')->save_question($question, $form);
                        quiz_add_quiz_question($question->id, $quiz, 0, 1);
                    }
                    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
                    break;

                default:
                    $out("AVISO: tipo de actividad no soportado '{$a['type']}' en {$c['shortname']}.");
            }
        }
    }
    $out("Curso {$c['shortname']} creado: " . count($c['students']) . ' estudiantes, ' . count($c['sections']) . ' secciones.');
}

// ---------------------------------------------------------------------------
// 4. Servicios web (REST) + usuario de integración + token fijo
// ---------------------------------------------------------------------------
$ws = $data['webservice'];
set_config('enablewebservices', 1);
$protocols = array_filter(explode(',', (string) get_config('core', 'webserviceprotocols')));
if (!in_array('rest', $protocols, true)) {
    $protocols[] = 'rest';
    set_config('webserviceprotocols', implode(',', $protocols));
}

$wsuser = $getorcreateuser($ws['user']);

// Rol de sistema mínimo que permite usar REST; los permisos de negocio vienen del rol manager.
$wsrole = $DB->get_record('role', ['shortname' => 'wsrest']);
$wsroleid = $wsrole ? $wsrole->id : create_role('Usuario REST', 'wsrest', 'Permite usar el protocolo REST de servicios web.');
set_role_contextlevels($wsroleid, [CONTEXT_SYSTEM]);
assign_capability('webservice/rest:use', CAP_ALLOW, $wsroleid, $syscontext->id, true);
role_assign($wsroleid, $wsuser->id, $syscontext->id);
role_assign($roleids['manager'], $wsuser->id, $syscontext->id);

$service = $DB->get_record('external_services', ['shortname' => $ws['service_shortname']]);
if (!$service) {
    $service = (object) [
        'name'            => $ws['service_name'],
        'shortname'       => $ws['service_shortname'],
        'enabled'         => 1,
        'restrictedusers' => 1,
        'downloadfiles'   => 1,
        'uploadfiles'     => 1,
        'timecreated'     => time(),
        'timemodified'    => time(),
    ];
    $service->id = $DB->insert_record('external_services', $service);
}
foreach ($ws['functions'] as $fn) {
    if (!$DB->record_exists('external_functions', ['name' => $fn])) {
        $out("AVISO: la función de servicio web '$fn' no existe en esta versión.");
        continue;
    }
    if (!$DB->record_exists('external_services_functions', ['externalserviceid' => $service->id, 'functionname' => $fn])) {
        $DB->insert_record('external_services_functions', ['externalserviceid' => $service->id, 'functionname' => $fn]);
    }
}
if (!$DB->record_exists('external_services_users', ['externalserviceid' => $service->id, 'userid' => $wsuser->id])) {
    $DB->insert_record('external_services_users', [
        'externalserviceid' => $service->id,
        'userid'            => $wsuser->id,
        'timecreated'       => time(),
    ]);
}

$token = strtolower(trim((string) getenv('MOODLE_WS_TOKEN'))) ?: $ws['default_token'];
if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
    $out('AVISO: MOODLE_WS_TOKEN no es un hex de 32 caracteres; se usa el token por defecto.');
    $token = $ws['default_token'];
}
if (!$DB->record_exists('external_tokens', ['token' => $token])) {
    $DB->insert_record('external_tokens', [
        'token'             => $token,
        'privatetoken'      => random_string(64),
        'tokentype'         => 0, // EXTERNAL_TOKEN_PERMANENT
        'userid'            => $wsuser->id,
        'externalserviceid' => $service->id,
        'contextid'         => $syscontext->id,
        'creatorid'         => get_admin()->id,
        'timecreated'       => time(),
        'name'              => 'Token demo (moodle-dev)',
    ]);
}
$out("Servicios web REST activos. Usuario: {$ws['user']['username']} | Token: $token");

// ---------------------------------------------------------------------------
set_config('seeded', time(), SEED_COMPONENT);
purge_all_caches();
$out('Datos demo cargados correctamente.');
