FROM php:8.2-apache
# Instala e ativa a extensão mysqli para o banco de dados
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
COPY . /var/www/html/
RUN a2enmod rewrite
