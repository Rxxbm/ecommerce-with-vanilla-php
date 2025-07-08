FROM php:8.2-apache-bullseye

# 1. Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql zip

# 2. Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# 3. Instalar o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Criar estrutura de diretórios
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

# 5. Definir diretório de trabalho
WORKDIR /var/www/html

# 6. Criar estrutura de pastas
RUN mkdir -p src

# 7. Copiar APENAS os arquivos do composer primeiro (para cache eficiente)
COPY src/composer.json src/composer.lock ./src/

# 8. Instalar dependências
RUN cd src && composer install --no-dev --optimize-autoloader --no-interaction

# 9. Copiar todo o resto do projeto
COPY . .

# Habilita .env (opcional, se quiser carregar via PHP)
RUN echo "<?php require 'vendor/autoload.php'; \
         \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); \
         \$dotenv->load(); ?>" > /var/www/html/prepend.php

# 10. Ajustar permissões
RUN chown -R www-data:www-data /var/www/html