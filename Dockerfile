# syntax=docker/dockerfile:1
# Imagen de Moodle para desarrollo local.
# Base oficial de Moodle HQ (PHP + Apache con todas las extensiones que pide Moodle).
ARG PHP_VERSION=8.3
FROM moodlehq/moodle-php-apache:${PHP_VERSION}

# Versión exacta de Moodle (tag de git). Moodle 4.5 admite PHP 8.1 - 8.3.
ARG MOODLE_VERSION=v4.5.16
# Rama de paquetes de idioma y lista de idiomas a incluir (separados por espacios).
ARG MOODLE_LANG_BRANCH=4.5
ARG MOODLE_LANGS="es"

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip curl ca-certificates \
 && rm -rf /var/lib/apt/lists/* \
 && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

# Código de Moodle dentro de la imagen (no en un volumen): actualizar = cambiar MOODLE_VERSION y reconstruir.
RUN cd / && rm -rf /var/www/html \
 && git clone --depth 1 --branch "${MOODLE_VERSION}" https://github.com/moodle/moodle.git /var/www/html \
 && rm -rf /var/www/html/.git

# Paquetes de idioma descargados en el build (no se depende de internet al arrancar).
RUN mkdir -p /opt/moodle-dev/lang \
 && for l in ${MOODLE_LANGS}; do \
      curl -fsSL -o "/tmp/${l}.zip" "https://download.moodle.org/download.php/direct/langpack/${MOODLE_LANG_BRANCH}/${l}.zip" \
      && unzip -q "/tmp/${l}.zip" -d /opt/moodle-dev/lang \
      && rm "/tmp/${l}.zip"; \
    done

COPY docker/config.php /var/www/html/config.php
COPY docker/bin/ /opt/moodle-dev/bin/
COPY seed/ /opt/moodle-dev/seed/
RUN sed -i 's/\r$//' /opt/moodle-dev/bin/*.sh && chmod +x /opt/moodle-dev/bin/*.sh \
 && echo "${MOODLE_VERSION}" > /opt/moodle-dev/MOODLE_VERSION

ENTRYPOINT ["/opt/moodle-dev/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
