FROM php:7.0.6-apache

RUN a2enmod headers
RUN a2enmod rewrite

RUN usermod -u 1000 www-data

WORKDIR /var/www/html

VOLUME ["/var/www/html/config"]

COPY public /var/www/html/public
COPY app /var/www/html/app
COPY scripts /var/www/html/scripts

COPY webconf-example/apache2-alias.conf.example /etc/apache2/sites-available/cakebox.conf

RUN a2ensite cakebox

RUN /etc/init.d/apache2 restart