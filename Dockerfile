# Usa la imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instala las extensiones necesarias para Slim
RUN docker-php-ext-install pdo pdo_mysql

# Copia el proyecto al contenedor
COPY . /var/www/html

# Configura permisos para el directorio
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Habilita el módulo de reescritura de Apache (necesario para Slim)
RUN a2enmod rewrite

# Configura el virtual host para Slim
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Expone el puerto 80
EXPOSE 80

# Inicia Apache en modo foreground
CMD ["apache2-foreground"]