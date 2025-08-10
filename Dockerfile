FROM php:8.2-apache

# Enable Apache mod_rewrite (optional for frameworks)
RUN a2enmod rewrite

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Optional: Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy your project files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Run Composer if needed
RUN composer install || true

EXPOSE 80
