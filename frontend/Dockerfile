FROM php:8.1-apache

# Cài đặt extension MySQL cơ bản
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Bật mod_rewrite cho Apache
RUN a2enmod rewrite

# Copy cấu hình Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Copy toàn bộ source code
COPY . /var/www/html/

# Thiết lập quyền
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Expose port 80
EXPOSE 80