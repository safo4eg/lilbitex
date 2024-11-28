FROM php:8.3.0-cli

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    nano \
    supervisor \
    default-libmysqlclient-dev \
    && docker-php-ext-install pdo pdo_mysql bcmath

RUN mkdir -p "/etc/supervisor/logs"

COPY supervisord.conf /etc/supervisor/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c",  "/etc/supervisor/supervisord.conf"]
