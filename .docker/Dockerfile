ARG PHP_VERSION=8.4

#
# Application base
#
FROM php:${PHP_VERSION}-fpm-alpine AS base
# Install extensions and tools
RUN set -eux \
    && apk update && apk upgrade --no-cache \
    && apk add --no-cache \
      ca-certificates \
    && update-ca-certificates \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# Add production PHP INI overlays (keep extension configs clean in .docker/php)
COPY .docker/php/opcache.ini /usr/local/etc/php/conf.d/
COPY .docker/php/zz-overwrites.ini /usr/local/etc/php/conf.d/
COPY .docker/php-fpm.d/docker.conf /usr/local/etc/php-fpm.d/
ENV APP_ENV=production
# Source code location
WORKDIR /app
EXPOSE 9000


FROM base AS development
# Development-specific settings and tools only
ENV APP_ENV=development
COPY .docker/php/zz-development.ini /usr/local/etc/php/conf.d/
# Defining XDG Base Directories for composer
ENV XDG_CONFIG_HOME=/data/.config
ENV XDG_CACHE_HOME=/data/.cache
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
FROM base AS vendor-builder
COPY composer.json ./composer.json
COPY composer.lock ./composer.lock
COPY public ./public
COPY src ./src
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --no-progress --no-ansi --no-scripts --no-dev --classmap-authoritative --optimize-autoloader


#
# Application (production)
#
FROM base AS production
COPY --from=vendor-builder /app/public ./public
COPY --from=vendor-builder /app/src ./src
COPY --from=vendor-builder /app/vendor ./vendor
COPY resources ./resources
USER www-data

#
# Node + curl for MCP conformance (npx) and scripts (dev only)
#
FROM node:22-alpine AS node
RUN apk add --no-cache curl
WORKDIR /app

#
# MCP Inspector v2 + an OS keyring (dev only)
#
# Pinned by digest: `:latest` moved to the v2.0.0 rewrite on 2026-07-28. v2 keeps
# per-server secrets in an OS keychain, which the upstream image cannot reach in a
# container — see .docker/inspector-entrypoint.sh for the failure and the fix.
#
FROM ghcr.io/modelcontextprotocol/inspector:2.0.0@sha256:69bc9110a70afbdd5137af4cbd30636e889b9ce16776f1f3226d1aa8fcd1acb7 AS inspector
USER root
RUN set -eux \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
      dbus-x11 \
      gnome-keyring \
      libsecret-1-0 \
    && rm -rf /var/lib/apt/lists/*
COPY .docker/inspector-entrypoint.sh /usr/local/bin/inspector-entrypoint
RUN chmod +x /usr/local/bin/inspector-entrypoint
USER node
ENTRYPOINT ["/usr/local/bin/inspector-entrypoint"]
CMD ["--web"]
