FROM php:8.2-apache-bullseye

# MSSQL-Treiber und Tools installieren
RUN apt-get update && apt-get install -y \
    gnupg2 curl apt-transport-https unixodbc-dev gcc g++ make autoconf libc-dev pkg-config libssl-dev unzip lsb-release \
    && curl https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > /etc/apt/trusted.gpg.d/microsoft.gpg \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 \
    && pecl install pdo_sqlsrv sqlsrv \
    && docker-php-ext-enable pdo_sqlsrv sqlsrv

# Apache Rewrite aktivieren
RUN a2enmod rewrite

# Projektdateien kopieren
COPY . /var/www/html/

# WICHTIG FÜR AZURE:
EXPOSE 80
CMD ["apache2-foreground"]
