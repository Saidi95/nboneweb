FROM php:8.2-apache

# Installer extensions PHP nécessaires
RUN apt-get update && apt-get install -y libicu-dev libzip-dev libonig-dev libxml2-dev unzip git && \
    docker-php-ext-install intl pdo pdo_mysql zip opcache mbstring xml && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# Définir le dossier de travail
WORKDIR /var/www/html

# Copier le code source
COPY . /var/www/html/

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Permissions correctes pour Apache
RUN chown -R www-data:www-data /var/www/html

# Configurer Apache pour servir Symfony depuis /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf && \
    echo "<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>" > /etc/apache2/conf-available/symfony.conf && \
    a2enconf symfony

# Exposer le port 80
EXPOSE 80

# Commande de démarrage
CMD ["apache2-foreground"]
