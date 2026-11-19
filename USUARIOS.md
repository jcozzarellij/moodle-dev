# Usuarios de prueba

> ⚠️ Credenciales **solo para desarrollo local**. No uses esta plantilla en producción.

Todas las cuentas usan autenticación manual y correo `@example.com` (Moodle no envía correos en esta plantilla).
Los datos salen de [`seed/data.json`](seed/data.json). Si cambias ese archivo, actualiza también esta tabla.

## Administración

| Usuario | Contraseña | Nombre | Rol | Ámbito |
|---|---|---|---|---|
| `admin` | `Admin#2026` | Administrador Usuario | Administrador del sitio | Todo el sitio (configurable en `.env`: `MOODLE_ADMIN_USER` / `MOODLE_ADMIN_PASS`) |
| `gestor` | `Gestor#2026` | Sofía Paredes | Gestor (manager) | Todo el sitio |

## Profesores

| Usuario | Contraseña | Nombre | Rol | Cursos |
|---|---|---|---|---|
| `prof.python` | `Profesor#2026` | Laura Méndez | Profesor (editingteacher) | PY101 |
| `prof.javascript` | `Profesor#2026` | Diego Herrera | Profesor (editingteacher) | JS101 |
| `prof.java` | `Profesor#2026` | Valeria Ortiz | Profesor (editingteacher) | JAVA101 |
| `prof.sql` | `Profesor#2026` | Andrés Castillo | Profesor (editingteacher) | SQL101 |
| `prof.git` | `Profesor#2026` | Camila Rojas | Profesor (editingteacher) | GIT101 |
| `ayudante` | `Profesor#2026` | Mateo Vargas | Profesor sin permiso de edición (teacher) | PY101, JS101, JAVA101, SQL101, GIT101 |

## Estudiantes

Contraseña de todos los estudiantes: **`Estudiante#2026`**

| Usuario | Nombre | Cursos |
|---|---|---|
| `est01` | Ana Torres | PY101, SQL101, GIT101 |
| `est02` | Bruno Salazar | PY101, SQL101 |
| `est03` | Carla Mejía | PY101, GIT101 |
| `est04` | Daniel Ruiz | PY101 |
| `est05` | Elena Guzmán | PY101, JS101, GIT101 |
| `est06` | Felipe Navarro | PY101, JS101 |
| `est07` | Gabriela Paz | PY101, JS101, GIT101 |
| `est08` | Héctor Molina | PY101, JS101 |
| `est09` | Isabel Cordero | PY101, JS101, JAVA101, GIT101 |
| `est10` | Javier León | PY101, JS101, JAVA101 |
| `est11` | Karina Vega | PY101, JS101, JAVA101, GIT101 |
| `est12` | Luis Andrade | PY101, JS101, JAVA101 |
| `est13` | María Cevallos | JS101, JAVA101, SQL101, GIT101 |
| `est14` | Nicolás Ibarra | JS101, JAVA101, SQL101 |
| `est15` | Olga Pineda | JAVA101, SQL101, GIT101 |
| `est16` | Pablo Rivas | JAVA101, SQL101 |
| `est17` | Rosa Delgado | JAVA101, SQL101, GIT101 |
| `est18` | Santiago Fuentes | JAVA101, SQL101 |
| `est19` | Tatiana Aguirre | SQL101, GIT101 |
| `est20` | Víctor Benítez | SQL101 |

## Cursos (categoría «Programación»)

| Código | Curso | Profesor | Estudiantes |
|---|---|---|---|
| `PY101` | Python desde cero | `prof.python` | 12 |
| `JS101` | JavaScript moderno | `prof.javascript` | 10 |
| `JAVA101` | Java y Programación Orientada a Objetos | `prof.java` | 10 |
| `SQL101` | Bases de datos con SQL | `prof.sql` | 10 |
| `GIT101` | Git y GitHub | `prof.git` | 10 |

Cada curso tiene 5 secciones con: página de bienvenida, enlace a documentación oficial, foro con una discusión inicial, páginas de contenido, una tarea (texto en línea + archivos) y un cuestionario de 3 preguntas verdadero/falso.

## Servicios web (API REST)

| Dato | Valor |
|---|---|
| Usuario | `ws_integracion` / `Servicio#2026` (roles: manager + Usuario REST) |
| Servicio externo | Servicio Demo (moodle-dev) (`moodle_dev_demo`) |
| Token | `0123456789abcdef0123456789abcdef` (cámbialo con `MOODLE_WS_TOKEN` en `.env` antes de la primera instalación) |
| Endpoint | `http://localhost:8080/webservice/rest/server.php` |

Funciones habilitadas en el servicio:

- `core_webservice_get_site_info`
- `core_user_get_users`
- `core_user_get_users_by_field`
- `core_user_create_users`
- `core_user_update_users`
- `core_course_get_categories`
- `core_course_get_courses`
- `core_course_get_courses_by_field`
- `core_course_get_contents`
- `core_course_create_courses`
- `core_enrol_get_enrolled_users`
- `core_enrol_get_users_courses`
- `enrol_manual_enrol_users`
- `enrol_manual_unenrol_users`
- `core_role_assign_roles`
- `core_group_get_course_groups`
- `core_group_create_groups`
- `mod_assign_get_assignments`
- `mod_assign_get_submissions`
- `mod_quiz_get_quizzes_by_courses`
- `mod_forum_get_forums_by_courses`
- `mod_forum_get_forum_discussions`
- `gradereport_user_get_grade_items`
- `core_grades_get_gradeitems`
- `core_completion_get_activities_completion_status`
- `core_calendar_get_calendar_events`

Ejemplo:

```bash
curl "http://localhost:8080/webservice/rest/server.php?wstoken=0123456789abcdef0123456789abcdef&wsfunction=core_webservice_get_site_info&moodlewsrestformat=json"
```
