FROM docker.pkg.github.com/danskernesdigitalebibliotek/ddb-base-images-k8s/php7.3-fpm-base AS APP_BUILDER
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ARG APP_VERSION="feature/infrastructure"
ENV APP_PATH=/var/www/html \
    FILEPATH=infrastructure/docker/cover-service-faktor-export

# Move site into the container.
ADD https://github.com/danskernesdigitalebibliotek/ddb-cover-service-faktor-export/archive/${APP_VERSION}.tar.gz /tmp/app.tar
RUN tar -zxf /tmp/app.tar --strip-components=1 -C ${APP_PATH} \
    && rm /tmp/app.tar

# Add composer in from the official composer image (also alpine).
COPY --from=composer:1.9.1 /usr/bin/composer /usr/bin/composer

# Install mongodb php extension
RUN apk --update add --no-cache --virtual .build-deps autoconf g++ make \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && pecl clear-cache \
    && apk del .build-deps

WORKDIR ${APP_PATH}

## Install assets, which requires a HACK as redis is not available (should be removed later on).
RUN APP_ENV=prod composer install --no-dev -o --no-scripts --classmap-authoritative \
    && cp config/packages/cache.yaml /tmp \
    && awk '!/system: cache.adapter.redis/' config/packages/cache.yaml > temp && mv temp config/packages/cache.yaml \
    && APP_ENV=prod REDIS_CACHE_SYSTEM_BACKEND=cache.adapter.system composer docker-build \
    && cp -f /tmp/cache.yaml config/packages/cache.yaml \
    && composer clear-cache

####
## Build main application image.
####
FROM docker.pkg.github.com/danskernesdigitalebibliotek/ddb-base-images-k8s/php7.3-fpm-base
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ENV APP_PATH=/var/www/html \
    FILEPATH=infrastructure/docker/cover-service-faktor-export

# Install the application.
COPY --from=APP_BUILDER ${APP_PATH} ${APP_PATH}
RUN chown -R www-data:www-data ${APP_PATH}/var \
    && mkdir -p ${APP_PATH}/config/secrets \
    && chown -R www-data:www-data ${APP_PATH}/config/secrets \
    && rm -rf ${APP_PATH}/infrastructure

# Download prometheus php-fpm export.
COPY --from=hipages/php-fpm_exporter /php-fpm_exporter /usr/local/bin/php-fpm_exporter

# Copy configuration.
COPY ${FILEPATH}/etc/ /etc/

# Install configuration template handler
ADD https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 /usr/local/bin/confd
RUN chmod +x /usr/local/bin/confd

COPY ${FILEPATH}/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD [ "docker-entrypoint.sh" ]