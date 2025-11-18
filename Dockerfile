# ====== Étape 1 : Composer (construction des dépendances PHP) ======
FROM composer:2 AS composer

WORKDIR /app

# On copie les fichiers de définition des dépendances
COPY composer.json composer.lock ./

# Installation des dépendances, sans les dev (phpunit, etc.) pour l'image de prod
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# ====== Étape 2 : PHP + Apache ======
FROM php:8.3-apache

# Installation des extensions nécessaires à MySQL & co
RUN apt-get update && apt-get install -y \
        libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Document root d'Apache (index.php est à la racine du projet)
ENV APACHE_DOCUMENT_ROOT=/var/www/html

WORKDIR /var/www/html

# On copie TOUT le projet (sans ce qui est ignoré par .dockerignore)
COPY . .

# On copie le vendor généré par l'étape Composer
COPY --from=composer /app/vendor /var/www/html/vendor

# Permissions (simple mais efficace)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]