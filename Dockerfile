# Sử dụng PHP 8.2 với Apache
FROM php:8.2-apache

# Cài đặt system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libzip-dev \
    zip \
    unzip \
    redis-tools \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Thiết lập working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Cài đặt PHP dependencies
RUN composer install --optimize-autoloader --no-dev --no-scripts

# Copy toàn bộ source code
COPY . .

# Copy .env.production thành .env (theo yêu cầu)
RUN cp .env.production .env

# Regenerate autoload sau khi copy helper files
RUN composer dump-autoload --optimize

# Thiết lập permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Cấu hình Apache
RUN a2enmod rewrite

# Tạo Apache virtual host configuration
RUN echo '<VirtualHost *:80>' > /etc/apache2/sites-available/000-default.conf && \
    echo '    ServerAdmin webmaster@localhost' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    DocumentRoot /var/www/html/public' >> /etc/apache2/sites-available/000-default.conf && \
    echo '' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    <Directory /var/www/html/public>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        AllowOverride All' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        Require all granted' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    </Directory>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    ErrorLog /var/log/apache2/error.log' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    CustomLog /var/log/apache2/access.log combined' >> /etc/apache2/sites-available/000-default.conf && \
    echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf

# Tạo entrypoint script để chạy Laravel optimization khi container start
RUN echo '#!/bin/bash' > /usr/local/bin/entrypoint.sh && \
    echo 'set -e' >> /usr/local/bin/entrypoint.sh && \
    echo '' >> /usr/local/bin/entrypoint.sh && \
    echo '# Ensure proper permissions' >> /usr/local/bin/entrypoint.sh && \
    echo 'chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache' >> /usr/local/bin/entrypoint.sh && \
    echo '' >> /usr/local/bin/entrypoint.sh && \
    echo '# Chạy Laravel optimization' >> /usr/local/bin/entrypoint.sh && \
    echo 'echo "Running Laravel optimization..."' >> /usr/local/bin/entrypoint.sh && \
    echo 'php artisan config:cache' >> /usr/local/bin/entrypoint.sh && \
    echo 'php artisan route:cache' >> /usr/local/bin/entrypoint.sh && \
    echo 'php artisan view:cache' >> /usr/local/bin/entrypoint.sh && \
    echo 'echo "Laravel optimization completed"' >> /usr/local/bin/entrypoint.sh && \
    echo '' >> /usr/local/bin/entrypoint.sh && \
    echo '# Start Apache' >> /usr/local/bin/entrypoint.sh && \
    echo 'exec apache2-foreground' >> /usr/local/bin/entrypoint.sh && \
    chmod +x /usr/local/bin/entrypoint.sh

# Cấu hình PHP cho production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Expose port
EXPOSE 80

# Sử dụng entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
