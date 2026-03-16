FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite

# Install PHP extensions
RUN docker-php-ext-install mysqli

# Disable PHP opcache for development (to see changes immediately)
RUN echo "opcache.enable=0" >> /usr/local/etc/php/conf.d/opcache-disable.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy and enable Apache configuration
COPY config/apache.conf /etc/apache2/sites-available/000-default.conf

# Create necessary directories
RUN mkdir -p /var/log/apache2

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/src && \
    chmod -R 755 /var/www/html/config && \
    chmod -R 755 /var/www/html/database

EXPOSE 80

CMD ["apache2-foreground"]
