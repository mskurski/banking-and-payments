ARG PLATFORM=linux/amd64


# Builder images
FROM --platform=$PLATFORM composer/composer:2-bin AS composer
# --- Builder images


FROM --platform=$PLATFORM php:8.3-fpm-alpine AS app-webserver
ENV ROOT_PROJECT=/app
WORKDIR ${ROOT_PROJECT}

RUN apk add --no-cache --update \
    nginx=1.26.2-r0 \
    supervisor=4.2.5-r5 \
    icu-dev \
    linux-headers

RUN set -eux; \
    docker-php-ext-configure intl; \
    docker-php-ext-configure pcntl --enable-pcntl; \
    docker-php-ext-install pdo pdo_mysql intl pcntl sockets opcache;

COPY --chown=nobody ./docker/php/config/nginx.conf /etc/nginx/nginx.conf
COPY --chown=nobody ./docker/php/config/fpm-pool.conf /usr/local/etc/php-fpm.conf
COPY --chown=nobody ./docker/php/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN mkdir -p /etc/nginx/custom.server.d && chmod 0755 /etc/nginx/custom.server.d && chown  nobody.nobody /etc/nginx/custom.server.d
RUN mkdir -p /etc/nginx/custom.http.d && chmod 0755 /etc/nginx/custom.http.d && chown nobody.nobody /etc/nginx/custom.http.d

RUN ln -s /usr/bin/php83 /usr/bin/php
RUN chown -R  nobody.nobody ${ROOT_PROJECT} /run /var/lib/nginx /var/log/nginx
USER nobody
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

EXPOSE 8080


FROM app-webserver AS development
USER root

# default values of app user
ARG USER_ID=1000
ARG GROUP_ID=1000

# create app user, OSX default users are on group 20 wich is dialout in linux/alpine
# busybox addgroup has no -o option that allows group creation with same id's, doing it manually
RUN echo "app:x:$GROUP_ID:" >> /etc/group
RUN adduser -u $USER_ID -S app -G app nogroup

# add user to nobody group
RUN adduser app nobody
RUN chown -R app:app /run /app

RUN set -eux; \
  apk add --no-cache --update linux-headers ${PHPIZE_DEPS}; \
  pecl install xdebug && docker-php-ext-enable xdebug; \
  pecl install pcov && docker-php-ext-enable pcov

RUN chown -R app:app /var/lib/nginx /var/log/nginx

# adding composer
RUN apk add --no-cache --update \
    git \
    unzip

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME /tmp
COPY --from=composer --link /composer /usr/bin/composer
# --- adding composer

USER app
