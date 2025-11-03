# Utilise une image officielle PHP avec Apache
FROM php:8.2-apache

# Installe les dépendances système et PHP nécessaires
RUN apt-get update && apt-get install -y 
    git unzip libicu-dev libzip-dev libonig-dev libxml2-dev 
    && docker-php-ext-install intl pdo pdo_mysql zip opcache mbstring xml 
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Active mod_rewrite pour Symfony
RUN a2enmod rewrite

# Définit le dossier de travail
WORKDIR /var/www/html

# Copie le code source
COPY . /var/www/html/

# Ajuste les permissions pour Apache
RUN chown -R www-data:www-data /var/www/html

# Installe Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure Apache pour servir Symfony depuis /public
RUN echo "<Directory /var/www/html/public>
    AllowOverride All
</Directory>" > /etc/apache2/conf-available/symfony.conf; a2enconf symfony

# Expose le port 80
EXPOSE 80

# Commande de démarrage
CMD ["apache2-foreground"]
