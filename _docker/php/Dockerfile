FROM php:8.3-fpm-alpine

WORKDIR /var/www

RUN apk --no-cache add \
    bash \
    curl \
    libpng-dev \
    libzip-dev \
    libpq-dev \
    zip unzip\
    tzdata


RUN docker-php-ext-install pdo_mysql bcmath gd zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Создаём пользователя и группу www для приложения Laravel
RUN addgroup --gid 1000 www && adduser --uid 1000 --ingroup www --shell /bin/sh --disabled-password --home /home/www www

# Копируем содержимое текущего каталога в рабочую директорию
COPY . /var/www
COPY --chown=www:www . /var/www

# Меняем пользователя на www
USER www

EXPOSE 9000

# Запуск PHP-FPM
CMD ["php-fpm"]