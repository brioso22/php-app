FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql \
 && echo 'FallbackResource /index.php' > /etc/apache2/conf-enabled/fallback.conf \
 && echo 'expose_php = On' > /usr/local/etc/php/conf.d/expose.ini
COPY index.php db.php /var/www/html/
EXPOSE 80
CMD ["sh", "-c", "php /var/www/html/db.php; exec apache2-foreground"]
