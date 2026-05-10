FROM php:8.4-fpm


RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

#Extensões pra banco de dados
RUN docker-php-ext-install pdo_pgsql pgsql pdo mbstring exif pcntl bcmath gd zip

#Instala redis
RUN pecl install redis && docker-php-ext-enable redis

# Instala o Composer (Copiando da imagem oficial)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


WORKDIR /var/www/html


RUN chown -R www-data:www-data /var/www/html


USER www-data

EXPOSE 9000
CMD ["php-fpm"]
