# Use an official PHP runtime with Apache
FROM php:8.2-apache

# Install system dependencies and PostgreSQL drivers
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite and set the document root
RUN a2enmod rewrite
RUN echo "DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Set the DocumentRoot to your public folder
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Set the working directory
WORKDIR /var/www/html

# Copy the application files into the container
COPY . .

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]