FROM php:8.2-apache

# Install required extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

COPY ./public /var/www/html
COPY ./src /var/www/html/src

EXPOSE 80
