FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite for routing / .htaccess
RUN a2enmod rewrite

# Copy API source files to Apache public directory
COPY ./api /var/www/html/api

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (Apache default)
EXPOSE 80

# Copy and set up entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Start via entrypoint
CMD ["/entrypoint.sh"]
