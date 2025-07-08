FROM php:8.2-apache-bullseye

# Instalar extensões do sistema necessárias
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql

# (Opcional) Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Criar a pasta uploads com permissões apropriadas
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads