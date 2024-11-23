FROM php:8.3-fpm

WORKDIR /var/www

# Устанавливаем необходимые пакеты для работы с PHP и зависимостями
RUN apt-get update && apt-get install -y \
    bash \
    curl \
    zip \
    unzip \
    tzdata \
    libzip-dev \
    libpq-dev \
    libssl-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpng-dev \
    libsodium-dev \
    libgmp-dev \
    && apt-get clean

# Устанавливаем необходимые PHP расширения
RUN docker-php-ext-install pdo_mysql bcmath gd zip gmp opcache

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Создаём пользователя и группу www для приложения Laravel
RUN addgroup --gid 1000 www && adduser --uid 1000 --ingroup www --shell /bin/sh --disabled-password --home /home/www www

# Копируем содержимое текущего каталога в рабочую директорию
COPY . /var/www
COPY --chown=www:www . /var/www

# Меняем пользователя на www
USER www

# Открываем порт 9000 для PHP-FPM
EXPOSE 9000

# Запуск PHP-FPM
CMD ["php-fpm"]
