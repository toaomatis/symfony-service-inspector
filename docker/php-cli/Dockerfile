FROM composer:latest

ENV USER=docker
ENV UID=1000
ENV GID=1000

RUN apk update && \
    apk upgrade && \
    apk add --no-cache \
        autoconf \
        build-base

RUN pecl install xdebug-2.9.6 && \
    docker-php-ext-enable xdebug

COPY php.ini /usr/local/etc/php/php-cli.ini

COPY bashrc /root/.bashrc

RUN adduser \
    --disabled-password \
    --gecos "" \
    --uid "$UID" \
    "$USER"

COPY bashrc /home/$USER/.bashrc

RUN chown -R $USER:$USER /home/$USER

USER $USER
# Install hirak/prestissimo for regular user
RUN composer global require hirak/prestissimo