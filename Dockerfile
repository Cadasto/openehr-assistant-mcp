ARG PHP_VERSION=8.4

#
# Caddy Builder
#
FROM caddy:2-builder AS caddy-builder
RUN xcaddy build --with github.com/baldinof/caddy-supervisor

#
# Application base
#
FROM php:${PHP_VERSION}-fpm-alpine AS base
# Install extensions and tools
RUN set -eux \
    && apk add --no-cache \
      curl libcurl \
      nss-tools \
      zstd \
      gzip \
    && curl --etag-compare etag.txt --etag-save etag.txt --remote-name https://curl.se/ca/cacert.pem \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# Add production PHP INI overlays (keep extension configs clean in docker/php)
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/
COPY docker/php/zz-overwrites.ini /usr/local/etc/php/conf.d/
COPY docker/php-fpm.d/docker.conf /usr/local/etc/php-fpm.d/
ENV APP_ENV=production
# Defining XDG Base Directories
ENV XDG_DATA_HOME=/data/.local/share
ENV XDG_STATE_HOME=/data/.local/state
ENV XDG_CONFIG_HOME=/data/.config
ENV XDG_CACHE_HOME=/data/.cache
ENV APP_CACHE_DIR=$XDG_CACHE_HOME/app
RUN mkdir -m 0775 -p "$XDG_CONFIG_HOME" "$XDG_CACHE_HOME" "$XDG_DATA_HOME" "$XDG_STATE_HOME" "$APP_CACHE_DIR" \
    && chown -R www-data:www-data  "$XDG_CONFIG_HOME" "$XDG_CACHE_HOME" "$XDG_DATA_HOME" "$XDG_STATE_HOME" "$APP_CACHE_DIR"
# Source code location
WORKDIR /app
# Add caddy http server
COPY docker/Caddyfile /etc/caddy/
COPY --from=caddy-builder /usr/bin/caddy /usr/bin/caddy
CMD ["caddy", "--config", "/etc/caddy/Caddyfile", "run"]
EXPOSE 8343 443 443/udp

HEALTHCHECK --interval=60s --timeout=5s --retries=5 --start-period=60s --start-interval=3s \
    CMD curl --user-agent "HealthCheck: Docker/1.0" --fail http://localhost/health/caddy || exit 1


FROM base AS development
# Development-specific settings and tools only
ENV APP_ENV=development
COPY docker/php/zz-development.ini /usr/local/etc/php/conf.d/
# Install PHP extensions and minimal OS tools in a single layer
COPY --chmod=0755 --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN set -eux \
    && mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && adduser -S -u 1000 -G www-data local-user \
    && apk add --no-cache \
      git \
    && install-php-extensions \
      xdebug \
      @composer \
    && mkdir -m 0775 -p "$XDG_CONFIG_HOME/composer" "$XDG_CACHE_HOME/composer" \
    && chown -R local-user:www-data "$XDG_CONFIG_HOME/composer" "$XDG_CACHE_HOME/composer"
USER www-data

#
# PHP Dependencies builder (production deps only)
#
FROM development AS vendor-builder
USER root
COPY composer.json ./composer.json
COPY public ./public
COPY src ./src
RUN --mount=type=cache,target=$XDG_CONFIG_HOME \
    --mount=type=cache,target=$XDG_CACHE_HOME \
    composer install --no-interaction --no-progress --no-ansi --no-scripts --no-dev --classmap-authoritative --optimize-autoloader


#
# Application (production)
#
FROM base AS production
COPY --from=vendor-builder /app/public ./public
COPY --from=vendor-builder /app/src ./src
COPY --from=vendor-builder /app/vendor ./vendor
COPY resources ./resources
