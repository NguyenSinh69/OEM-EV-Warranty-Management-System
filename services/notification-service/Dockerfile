FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    mariadb-client \
    supervisor \
    cron

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Create required directories
RUN mkdir -p /var/www/logs /var/www/storage /var/www/tmp /var/www/scripts

# Create queue worker script
COPY <<EOF /var/www/scripts/queue-worker.php
#!/usr/bin/env php
<?php
require_once __DIR__ . '/../src/bootstrap.php';

use App\Services\QueueService;

\$queueService = new QueueService();

echo "Notification Queue worker started at " . date('Y-m-d H:i:s') . "\n";

while (true) {
    try {
        \$result = \$queueService->processQueue(10);
        
        if (\$result['processed'] > 0) {
            echo "[" . date('Y-m-d H:i:s') . "] Processed " . \$result['processed'] . " notifications\n";
        }
        
        // Clean old items periodically
        \$queueService->scheduleCleanup();
        
        // Sleep for 30 seconds before next batch
        sleep(30);
        
    } catch (Exception \$e) {
        echo "[ERROR " . date('Y-m-d H:i:s') . "] " . \$e->getMessage() . "\n";
        sleep(60); // Wait longer on error
    }
}
EOF

# Create supervisor configuration
COPY <<EOF /etc/supervisor/conf.d/notification-queue.conf
[program:notification-queue]
command=php /var/www/scripts/queue-worker.php
directory=/var/www
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/www/logs/queue.log
stderr_logfile=/var/www/logs/queue-error.log
EOF

# Create startup script
COPY <<EOF /var/www/start.sh
#!/bin/bash
# Start supervisor for background queue processing
supervisord -c /etc/supervisor/supervisord.conf -n &

# Start PHP development server
exec php -S 0.0.0.0:8005 -t public
EOF

# Set permissions
RUN chmod +x /var/www/scripts/queue-worker.php && \
    chmod +x /var/www/start.sh && \
    chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 777 /var/www/logs

# Change current user to www-data
USER www-data

# Expose port 8005
EXPOSE 8005

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:8005/api/health || exit 1

# Start services
CMD ["/var/www/start.sh"]