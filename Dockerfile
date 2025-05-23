FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

# (Opcjonalnie) Włącz mod_rewrite, jeśli używasz .htaccess
RUN a2enmod rewrite
