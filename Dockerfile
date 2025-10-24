# Temel imaj: PHP + Apache
FROM php:8.2-apache

# Gerekli paketleri yükle
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    pkg-config \
    && docker-php-ext-install pdo pdo_sqlite

# Apache mod_rewrite etkinleştirme
RUN a2enmod rewrite

# Çalışma dizini
WORKDIR /var/www/html

# Proje dosyalarını container içine kopyala
COPY . /var/www/html

# Dosya izinlerini ayarlama
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Port 80'i açma
EXPOSE 80

# Apache'yi foreground’da çalıştırma
CMD ["apache2-foreground"]
