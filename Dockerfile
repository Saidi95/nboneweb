# ---------- Étape 1 : Builder (Composer + dépendances) ----------
FROM php:8.2-cli AS builder

RUN apt-get update && apt-get install -y git unzip libicu-dev libzip-dev libonig-dev libxml2-dev && \
    docker-php-ext-install intl pdo pdo_mysql zip opcache mbstring xml && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --no-progress

COPY . /app
RUN php bin/console cache:clear --env=prod --no-debug

# ---------- Étape 2 : Image finale Apache + PHP ----------
FROM php:8.2-apache

RUN apt-get update && apt-get install -y libicu-dev libzip-dev libonig-dev libxml2-dev unzip && \
    docker-php-ext-install intl pdo pdo_mysql zip opcache mbstring xml && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
WORKDIR /var/www/html
COPY . /var/www/html/
COPY --from=builder /app/vendor /var/www/html/vendor
COPY --from=builder /app/var/cache /var/www/html/var/cache
RUN chown -R www-data:www-data /var/www/html

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf && \
    echo "<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>" > /etc/apache2/conf-available/symfony.conf && \
    a2enconf symfony

EXPOSE 80
CMD ["apache2-foreground"]
