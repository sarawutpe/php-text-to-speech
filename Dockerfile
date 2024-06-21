# Use the official PHP image as a base
FROM php:7.4-apache

# Copy application files to the default web directory
COPY . /var/www/html/

# Set the working directory
WORKDIR /var/www/html

# Install dependencies if composer.json exists
COPY composer.json composer.lock ./
RUN if [ -f composer.json ]; then \
      curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
      composer install; \
    fi

# Expose port 80
EXPOSE 80

# Start the Apache server
CMD ["apache2-foreground"]
