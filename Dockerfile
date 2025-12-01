FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    build-essential \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

RUN git clone https://github.com/phpredis/phpredis.git /usr/src/php/ext/redis \
    && docker-php-ext-install redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# User
RUN groupadd -g 1000 www \
    && useradd -u 1000 -ms /bin/bash -g www www

COPY . /var/www/project
RUN chown -R www:www /var/www/project

USER www
WORKDIR /var/www/project

EXPOSE 9000
CMD ["php-fpm"]