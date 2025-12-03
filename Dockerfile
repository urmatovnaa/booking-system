FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    librabbitmq-dev \
    libssh-dev

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

RUN pecl install amqp && docker-php-ext-enable amqp

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/project

COPY . .

RUN chown -R www-data:www-data /var/www/project

CMD ["php-fpm"]