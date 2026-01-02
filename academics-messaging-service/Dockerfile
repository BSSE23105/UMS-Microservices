FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN pecl install -o -f redis \
  && rm -rf /tmp/pear \
  && docker-php-ext-enable redis

RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . .

RUN sed -ri 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf

RUN printf '%s\n' \
'<VirtualHost *:8080>' \
'  ServerName localhost' \
'  DocumentRoot /var/www/html/public' \
'  <Directory /var/www/html/public>' \
'    AllowOverride All' \
'    Require all granted' \
'  </Directory>' \
'</VirtualHost>' \
> /etc/apache2/sites-available/000-default.conf \
&& a2ensite 000-default

COPY ./php/conf.d/session.ini /usr/local/etc/php/conf.d/zz-session.ini

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080
CMD ["apache2-foreground"]
