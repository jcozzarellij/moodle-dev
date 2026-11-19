# moodle-dev-template

Plantilla para levantar en minutos un **Moodle 4.5 LTS + PostgreSQL 17** local, **ya instalado y con datos de prueba**, para desarrollar y probar plugins, integraciones y servicios web.

- ✅ Instalación automática al primer arranque (sin asistente web)
- ✅ Usuarios demo: admin, gestor, 6 profesores y 20 estudiantes → [USUARIOS.md](USUARIOS.md)
- ✅ 5 cursos de programación con secciones, foros, tareas y cuestionarios
- ✅ API REST activada con usuario y token listos
- ✅ Interfaz en español, modo depuración para desarrolladores
- ✅ Datos persistentes en volúmenes, scripts de backup/restore y reset
- ✅ Varias instancias en paralelo cambiando solo el `.env`

> ⚠️ Solo para **desarrollo y pruebas**. Las contraseñas son públicas y la configuración no es segura para producción.

---

## Índice

1. [Requisitos](#requisitos)
2. [Inicio rápido](#inicio-rápido)
3. [Qué incluye](#qué-incluye)
4. [Configuración (.env)](#configuración-env)
5. [Persistencia de datos](#persistencia-de-datos)
6. [Scripts de ayuda](#scripts-de-ayuda)
7. [Desarrollar plugins](#desarrollar-plugins)
8. [Servicios web / API REST](#servicios-web--api-rest)
9. [Varias instancias a la vez](#varias-instancias-a-la-vez)
10. [Backup y restore](#backup-y-restore)
11. [Personalizar los datos demo](#personalizar-los-datos-demo)
12. [Actualizar Moodle](#actualizar-moodle)
13. [Comandos útiles de Moodle CLI](#comandos-útiles-de-moodle-cli)
14. [Solución de problemas](#solución-de-problemas)
15. [Estructura del proyecto](#estructura-del-proyecto)
16. [Cómo funciona por dentro](#cómo-funciona-por-dentro)

---

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/macOS) o Docker Engine + Compose v2 (Linux)
- ~3 GB libres de disco, 4 GB de RAM asignados a Docker
- Conexión a internet **solo para construir la imagen** (descarga Moodle y el paquete de idioma)

## Inicio rápido

```bash
# 1. Usa esta plantilla en GitHub ("Use this template") o clónala
git clone https://github.com/jcozzarellij/moodle-dev.git
cd moodle-dev

# 2. Crea tu configuración
cp .env.example .env          # PowerShell: Copy-Item .env.example .env

# 3. Levanta todo
docker compose up -d --build

# 4. Sigue la instalación (~2-3 min la primera vez)
docker compose logs -f moodle
```

Cuando veas `[moodle-dev] Moodle listo en http://localhost:8080`, abre **http://localhost:8080** y entra con:

| Usuario | Contraseña |
|---|---|
| `admin` | `Admin#2026` |

El resto de cuentas está en **[USUARIOS.md](USUARIOS.md)**.

## Qué incluye

| Componente | Detalle |
|---|---|
| Moodle | 4.5.16 (LTS, tag `v4.5.16`), código dentro de la imagen |
| PHP | 8.3 sobre la imagen oficial `moodlehq/moodle-php-apache` |
| Base de datos | PostgreSQL 17 |
| Idioma | Español (`es`) por defecto; inglés disponible |
| Cron | Se ejecuta dentro del contenedor de Moodle cada minuto |
| Correo | Desactivado (`noemailever`). Se puede activar SMTP en `.env` |
| Depuración | Nivel DEVELOPER, mensajes visibles, sin caché de JS/plantillas/tema |

**Datos demo** (definidos en [`seed/data.json`](seed/data.json)):

- Categoría **Programación** con 5 cursos: *Python desde cero* (PY101), *JavaScript moderno* (JS101), *Java y POO* (JAVA101), *Bases de datos con SQL* (SQL101) y *Git y GitHub* (GIT101).
- Cada curso: 5 secciones temáticas con página, URL, foro (con discusión inicial), tarea con entrega en línea/archivos y cuestionario de 3 preguntas.
- 1 profesor titular por curso, 1 profesor sin edición en todos, 8-12 estudiantes por curso, 1 gestor del sitio y 1 usuario de servicios web.

> **Soporte de Moodle 4.5:** el soporte general terminó el 6-oct-2025 y el de **seguridad termina el 4-oct-2027**. Para probar contra la LTS más reciente (5.3) habrá que adaptar la imagen (5.x usa PHP 8.4 y la carpeta `public/`).

## Configuración (.env)

Todas las opciones están comentadas en [`.env.example`](.env.example). Las más usadas:

| Variable | Por defecto | Descripción |
|---|---|---|
| `COMPOSE_PROJECT_NAME` | `moodle45` | Nombre de la instancia (prefijo de contenedores y volúmenes) |
| `MOODLE_PORT` | `8080` | Puerto en tu máquina |
| `MOODLE_WWWROOT` | `http://localhost:<PORT>` | URL pública; debe coincidir con la del navegador |
| `MOODLE_SSLPROXY` | `0` | `1` si accedes por HTTPS detrás de un proxy (ngrok, traefik…) |
| `MOODLE_VERSION` | `v4.5.16` | Tag de git de Moodle (requiere `--build`) |
| `MOODLE_LANGS` | `es` | Paquetes de idioma incluidos en la imagen (`"es pt_br"`) |
| `MOODLE_ADMIN_USER` / `MOODLE_ADMIN_PASS` | `admin` / `Admin#2026` | Admin (solo en la primera instalación) |
| `MOODLE_LANG` / `MOODLE_TIMEZONE` | `es` / `America/Guayaquil` | Idioma y zona horaria por defecto |
| `MOODLE_WS_TOKEN` | *(token demo)* | Token fijo de 32 caracteres hex para la API |
| `MOODLE_DEBUG` | `1` | `1` = depuración DEVELOPER; `0` = modo normal |
| `PHP_UPLOAD_MAX_FILESIZE` / `PHP_POST_MAX_SIZE` | `128M` | Tamaño máximo de subida |
| `PHP_MEMORY_LIMIT` | `512M` | Memoria de PHP |
| `MOODLE_SMTP_HOST`… | *(vacío)* | Si se define, Moodle envía correo por ese SMTP |

- Cambios de **PHP, depuración, URL o SMTP**: `docker compose up -d` (recrea el contenedor; los datos se mantienen).
- Cambios de **versión de Moodle/PHP o idiomas**: `docker compose up -d --build`.
- Las variables de **sitio, admin, token** solo se aplican en la **primera instalación**. Para volver a aplicarlas usa `scripts/reset`.

> Las contraseñas con `#` van entre comillas en `.env` (`MOODLE_ADMIN_PASS="Admin#2026"`).

## Persistencia de datos

Los datos viven en **volúmenes de Docker con nombre**, uno por instancia:

| Volumen | Contenido |
|---|---|
| `<proyecto>_pgdata` | Base de datos PostgreSQL |
| `<proyecto>_moodledata` | Archivos subidos, caché, idiomas (`moodledata`) |

Estas operaciones **NO borran datos**: `docker compose stop`, `restart`, `down`, `up -d --build`, reiniciar Docker o el equipo.

Solo se borran con `scripts/reset` (pide confirmación) o con `docker compose down -v`.

El **código de Moodle** está dentro de la imagen (no en un volumen), así que actualizar es limpio. Tus plugins viven en tu disco (`./plugins`).

## Scripts de ayuda

En `scripts/` hay una versión **bash** (`.sh`, Linux/macOS/Git Bash) y **PowerShell** (`.ps1`, Windows) de cada script. Ejecútalos desde la raíz del proyecto.

| Script | Qué hace | Equivalente docker compose |
|---|---|---|
| `reset` | Borra BD + moodledata y reinstala con datos demo | `docker compose down -v && docker compose up -d --build` |
| `logs [moodle\|db\|cron]` | Logs en vivo (o último log de cron) | `docker compose logs -f moodle` |
| `shell [moodle\|db]` | Terminal en el contenedor (o `psql`) | `docker compose exec moodle bash` |
| `purge-caches` | Purga cachés de Moodle | `docker compose exec -u www-data moodle php admin/cli/purge_caches.php` |
| `upgrade` | Instala/actualiza plugins y núcleo | `docker compose exec -u www-data moodle php admin/cli/upgrade.php --non-interactive` |
| `backup [nombre]` | Guarda BD + moodledata en `backups/<nombre>.tar.gz` | — |
| `restore <archivo>` | Restaura un backup (reemplaza los datos actuales) | — |
| `verify` | Comprueba que los datos demo existen | `docker compose exec -u www-data moodle php /opt/moodle-dev/seed/verify.php` |

```bash
# Linux / macOS / Git Bash
bash scripts/backup.sh antes-de-probar
bash scripts/restore.sh backups/antes-de-probar.tar.gz
```

```powershell
# Windows PowerShell
.\scripts\backup.ps1 antes-de-probar
.\scripts\restore.ps1 backups\antes-de-probar.tar.gz
```

> Si PowerShell bloquea los scripts: `Set-ExecutionPolicy -Scope CurrentUser RemoteSigned` o ejecuta `powershell -ExecutionPolicy Bypass -File .\scripts\reset.ps1`.
>
> En **Git Bash** usa los scripts `.sh` (ya ajustan `MSYS_NO_PATHCONV`). Si ejecutas a mano un `docker compose exec ... php /opt/...` en Git Bash, antepón `MSYS_NO_PATHCONV=1` para que no convierta la ruta.

## Desarrollar plugins

1. Pon el código del plugin en `plugins/`, una carpeta por plugin:

   ```
   plugins/
   └── local_miplugin/
       ├── version.php
       └── lang/en/local_miplugin.php
   ```

2. Copia el override de ejemplo y monta el plugin en su ruta de Moodle:

   ```bash
   cp docker-compose.override.example.yml docker-compose.override.yml
   ```

   ```yaml
   services:
     moodle:
       volumes:
         - ./plugins/local_miplugin:/var/www/html/local/miplugin
   ```

   (El archivo de ejemplo lista las rutas de cada tipo de plugin: `mod`, `block`, `auth`, `enrol`, `theme`, `admin/tool`…)

3. Recrea el contenedor. Al arrancar se ejecuta `upgrade.php`, que **instala el plugin automáticamente**:

   ```bash
   docker compose up -d
   ```

4. Mientras desarrollas, los cambios de PHP se ven al recargar. Si subes `$plugin->version` (cambios de BD, capacidades, tareas…), ejecuta `scripts/upgrade`. Si cambias cadenas de idioma o plantillas cacheadas, `scripts/purge-caches`.

Consejo: el código del plugin puede ser su propio repositorio git clonado dentro de `plugins/` (la carpeta está en `.gitignore`).

## Servicios web / API REST

Ya vienen activados: servicios web, protocolo REST, el servicio **Servicio Demo (moodle-dev)** con un conjunto de funciones habituales, el usuario `ws_integracion` y un token fijo.

```bash
curl "http://localhost:8080/webservice/rest/server.php?wstoken=0123456789abcdef0123456789abcdef&wsfunction=core_course_get_courses_by_field&moodlewsrestformat=json&field=shortname&value=PY101"
```

- Funciones incluidas y credenciales: [USUARIOS.md](USUARIOS.md#servicios-web-api-rest).
- Para añadir funciones: *Administración del sitio → Servidor → Servicios web → Servicios externos*, o edita `webservice.functions` en `seed/data.json` y haz `reset`.
- Para conectar desde **otro contenedor** usa `http://host.docker.internal:8080`; para exponer Moodle a internet (webhooks, LTI) usa un túnel HTTPS y configura `MOODLE_WWWROOT=https://...` y `MOODLE_SSLPROXY=1`.

## Varias instancias a la vez

Copia la carpeta del proyecto (o usa otro `.env`) y cambia el nombre y el puerto:

```dotenv
COMPOSE_PROJECT_NAME=moodle45-cliente-a
MOODLE_PORT=8081
```

Cada instancia tiene sus contenedores y volúmenes propios. La imagen se comparte, así que la segunda instancia arranca sin volver a descargar Moodle.

Ver todas: `docker ps --filter name=moodle` · Volúmenes: `docker volume ls`.

## Backup y restore

Ideal antes de una prueba destructiva (instalar un plugin dudoso, probar una migración…):

```bash
bash scripts/backup.sh limpio            # -> backups/limpio.tar.gz
# ... pruebas ...
bash scripts/restore.sh backups/limpio.tar.gz
```

El archivo contiene `db.dump` (`pg_dump` formato custom), `moodledata.tar.gz` (sin cachés ni temporales) y la versión de Moodle. Restaura siempre sobre **la misma versión de Moodle** con la que se creó el backup.

## Personalizar los datos demo

Edita [`seed/data.json`](seed/data.json):

- `users`: usuarios (`system_role: "manager"` para roles de sitio)
- `courses`: cursos, profesor, estudiantes y secciones con actividades (`page`, `url`, `forum`, `assign`, `quiz` con preguntas verdadero/falso)
- `webservice`: usuario, servicio y funciones de la API

La carpeta `seed/` está montada en el contenedor, así que no hace falta reconstruir: ejecuta `scripts/reset` para reinstalar con los nuevos datos. Mantén [USUARIOS.md](USUARIOS.md) sincronizado; `scripts/verify` (y el CI) comprueban que lo que hay en `data.json` existe.

## Actualizar Moodle

Para un nuevo parche 4.5.x:

1. Cambia `MOODLE_VERSION` en `.env` (por ejemplo `v4.5.17`) y `ARG MOODLE_VERSION` en el `Dockerfile` (valor por defecto).
2. `bash scripts/backup.sh antes-de-actualizar`
3. `docker compose up -d --build`: al arrancar se ejecuta `upgrade.php` automáticamente.

Tags disponibles: <https://github.com/moodle/moodle/tags>.

## Comandos útiles de Moodle CLI

Ejecútalos como `www-data` dentro del contenedor:

```bash
docker compose exec -u www-data moodle php admin/cli/cfg.php --name=debug                 # leer un ajuste
docker compose exec -u www-data moodle php admin/cli/cfg.php --name=theme --set=classic   # cambiar un ajuste
docker compose exec -u www-data moodle php admin/cli/cron.php                             # cron manual
docker compose exec -u www-data moodle php admin/cli/scheduled_task.php --list
docker compose exec -u www-data moodle php admin/cli/maintenance.php --enable
docker compose exec -u www-data moodle php admin/cli/reset_password.php
docker compose exec -u www-data moodle php admin/cli/uninstall_plugins.php --plugins=local_miplugin --run
```

## Solución de problemas

| Problema | Solución |
|---|---|
| El puerto 8080 está ocupado | Cambia `MOODLE_PORT` en `.env` y `docker compose up -d` |
| Moodle redirige a otra URL / "Incorrect access detected" | `MOODLE_WWWROOT` debe coincidir exactamente con la URL del navegador (incluido el puerto) |
| La primera vez tarda | Es normal: la instalación + datos demo tarda 1-3 min. Mira `docker compose logs -f moodle` |
| El contenedor se reinicia en bucle | `docker compose logs moodle` muestra el error (BD, permisos, seed). Corrige y `scripts/reset` si la instalación quedó a medias |
| Cambié `.env` (admin, token, sitio) y no se aplica | Solo se usan en la primera instalación: `scripts/reset` |
| Las páginas van lentas | `MOODLE_DEBUG=1` activa *theme designer mode* (recompila CSS). Pon `MOODLE_DEBUG=0` si no tocas temas |
| "Could not open input file: C:/Program Files/Git/opt/..." | Git Bash convierte rutas: usa `MSYS_NO_PATHCONV=1` o los scripts `.sh` |
| Acentos raros en PowerShell | Ejecuta `chcp 65001` o usa Windows Terminal / PowerShell 7 |

## Estructura del proyecto

```
moodle-dev-template/
├── .env.example                         # Configuración (copiar a .env)
├── docker-compose.yml                   # Servicios moodle + db
├── docker-compose.override.example.yml  # Montaje de plugins (copiar a docker-compose.override.yml)
├── Dockerfile                           # Imagen: moodle-php-apache + Moodle + idioma
├── docker/
│   ├── config.php                       # config.php de Moodle (lee variables de entorno)
│   └── bin/
│       ├── entrypoint.sh                # Instala / actualiza / seed / cron / apache
│       └── db-status.php                # ¿BD vacía o instalada?
├── seed/
│   ├── data.json                        # Datos demo (editable)
│   ├── seed.php                         # Carga los datos demo (idempotente)
│   └── verify.php                       # Comprueba los datos demo
├── scripts/                             # reset, logs, shell, purge-caches, upgrade, backup, restore, verify (.sh y .ps1)
├── plugins/                             # Tus plugins (ignorados por git)
├── backups/                             # Backups generados (ignorados por git)
├── .github/workflows/ci.yml             # CI: build + instalación + verificación + backup/restore
├── USUARIOS.md                          # Usuarios, cursos y token de prueba
└── LICENSE                              # MIT (Moodle es GPLv3 y se descarga en el build)
```

## Cómo funciona por dentro

1. **Build** (`Dockerfile`): parte de `moodlehq/moodle-php-apache:8.3`, clona Moodle en el tag `MOODLE_VERSION`, descarga los paquetes de idioma y copia `config.php` y los scripts.
2. **Arranque** (`docker/bin/entrypoint.sh`):
   - escribe la configuración de PHP desde `.env`;
   - espera a PostgreSQL;
   - **BD vacía** → `admin/cli/install_database.php` + idioma + `seed/seed.php`;
   - **BD existente** → `admin/cli/upgrade.php --non-interactive` (no hace nada si no hay cambios; instala plugins nuevos);
   - lanza el cron en segundo plano y arranca Apache.
3. **`config.php`** lee todo de variables de entorno, así que cambiar el `.env` y recrear el contenedor basta para cambiar URL, depuración o SMTP.

## Licencia

[MIT](LICENSE). Moodle™ es una marca de Moodle Pty Ltd y se distribuye bajo GPLv3; este repositorio no incluye su código, que se descarga del repositorio oficial al construir la imagen.
