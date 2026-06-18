FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable mod_rewrite
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo '<Directory /var/www/html/public>' >> /etc/apache2/apache2.conf
RUN echo '    Options Indexes FollowSymLinks' >> /etc/apache2/apache2.conf
RUN echo '    AllowOverride All' >> /etc/apache2/apache2.conf
RUN echo '    Require all granted' >> /etc/apache2/apache2.conf
RUN echo '</Directory>' >> /etc/apache2/apache2.conf

# Set DocumentRoot to public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-dev

COPY . .

EXPOSE 80

CMD ["apache2-foreground"]