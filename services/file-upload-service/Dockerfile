FROM php:8.1-apache

# Install extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY public/ /var/www/html/

# Create uploads directory with proper permissions
RUN mkdir -p /var/www/uploads && \
    chmod 755 /var/www/uploads && \
    chown -R www-data:www-data /var/www/uploads

# Apache configuration
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/docker-php.conf && \
    a2enconf docker-php

EXPOSE 80

CMD ["apache2-foreground"]
