ARG PHP_BUILD_VERSION
ARG PHP_TEST_VERSION

# Composer on correct PHP version
FROM php:${PHP_BUILD_VERSION}-cli as build

ARG BUILD_ROOT_PATH

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN apt-get update
RUN apt-get install -y zip unzip curl git
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR ${BUILD_ROOT_PATH}
COPY . ./


FROM php:${PHP_TEST_VERSION}-cli as test

ARG BUILD_ROOT_PATH

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN pecl install xdebug-3.2.1
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install posix

WORKDIR ${BUILD_ROOT_PATH}
COPY --from=build ${BUILD_ROOT_PATH} ${BUILD_ROOT_PATH}


# Install PHP dev dependencies
FROM build as vendor-dev

ARG BUILD_ROOT_PATH

WORKDIR ${BUILD_ROOT_PATH}
RUN COMPOSER_DISCARD_CHANGES=true composer install
