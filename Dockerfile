FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite for routing / .htaccess
RUN a2enmod rewrite

# Disable extra MPMs that cause conflicts in container environments, and ensure prefork is enabled
RUN a2dismod mpm_event mpm_worker || true
RUN a2enmod mpm_prefork

# Copy API source files to Apache public directory
COPY ./api /var/www/html/api

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (Apache default)
EXPOSE 80
