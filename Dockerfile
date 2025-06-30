FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Set index.php as default page
RUN echo "DirectoryIndex index.php index.html" > /etc/apache2/conf-enabled/dir.conf

# Copy all project files into Apache's public folder
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Install MySQLi for PHP to work with your DB
RUN docker-php-ext-install mysqli
