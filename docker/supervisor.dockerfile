FROM php:8.1.16-fpm-alpine

RUN docker-php-ext-install pdo pdo_mysql

RUN apk update && apk add supervisor

RUN mkdir -p "/etc/supervisor/logs"

COPY ./docker/supervisor/conf.d/supervisord.conf /etc/supervisor/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c",  "/etc/supervisor/supervisord.conf"]
