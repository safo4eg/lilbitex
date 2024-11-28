FROM php:8.3.0-cli

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    cron \
    nano \
    default-libmysqlclient-dev \
    && docker-php-ext-install pdo pdo_mysql bcmath

RUN echo "* * * * * root /usr/local/bin/php /var/www/artisan schedule:run >> /var/log/cron.log 2>&1" >> /etc/crontab
RUN touch /var/log/cron.log

CMD ["cron", "-f"]
