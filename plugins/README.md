# plugins/

Coloca aquí el código de los plugins que estás desarrollando (una carpeta por plugin, p. ej. `local_miplugin/`).

Luego móntalos en Moodle con `docker-compose.override.yml` (copia `docker-compose.override.example.yml`) y ejecuta `scripts/upgrade`. Detalles en el README principal, sección **Desarrollar plugins**.

El contenido de esta carpeta está ignorado por git (salvo este README): cada plugin suele tener su propio repositorio.
