FROM php:7.4-apache

# Omeka-S web publishing platform for digital heritage collections (https://omeka.org/s/)
# Initial maintainer: Oldrich Vykydal (o1da) - Klokan Technologies GmbH  
MAINTAINER Eric Dodemont <eric.dodemont@skynet.be>

RUN a2enmod rewrite

ENV DEBIAN_FRONTEND noninteractive
RUN apt-get -qq update && apt-get -qq -y upgrade
RUN apt-get -qq update && apt-get -qq -y --no-install-recommends install \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    libjpeg-dev \
    libmemcached-dev \
    zlib1g-dev \
    imagemagick \
    libmagickwand-dev \
	wget

#Install Composer, which we need for Solr search
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install the PHP extensions we need
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
RUN docker-php-ext-install -j$(nproc) iconv pdo pdo_mysql mysqli gd
RUN pecl install imagick && docker-php-ext-enable imagick 

# Add the Omeka-S PHP code
RUN wget -P /var/www/ https://github.com/omeka/omeka-s/releases/download/v3.0.2/omeka-s-3.0.2.zip
RUN unzip -q /var/www/omeka-s-3.0.2.zip -d /var/www/ \
&&  rm /var/www/omeka-s-3.0.2.zip \
&&  rm -rf /var/www/html/ \
&&  mv /var/www/omeka-s/ /var/www/html/

COPY ./imagemagick-policy.xml /etc/ImageMagick/policy.xml
COPY ./.htaccess /var/www/html/.htaccess

# Add some Omeka modules
COPY ./omeka-s-3.0-modules.zip /var/www/html/
RUN rm -rf /var/www/html/modules/ \
&&  unzip -q /var/www/html/omeka-s-3.0-modules.zip -d/var/www/html/modules/ \
&&  rm /var/www/html/omeka-s-3.0-modules.zip

# Set up the Solr and Search modules
RUN cd /var/www/html/modules/Search && composer install --no-dev 
RUN cd /var/www/html/modules/SearchSolr && composer install --no-dev 

# Add some themes
RUN wget -P /var/www/html/themes/ https://github.com/omeka/theme-thedaily/releases/download/v1.5/theme-thedaily-v1.5.zip
RUN wget -P /var/www/html/themes/ https://github.com/omeka-s-themes/cozy/releases/download/v1.5.0/theme-cozy-v1.5.0.zip
RUN wget -P /var/www/html/themes/ https://github.com/DigitalGrinnell/centerrow/archive/refs/heads/master.zip
RUN unzip -q /var/www/html/themes/theme-thedaily-v1.5.zip -d /var/www/html/themes/ \
&&  unzip -q /var/www/html/themes/master.zip -d /var/www/html/themes/ \
&&  unzip -q /var/www/html/themes/theme-cozy-v1.5.0.zip -d /var/www/html/themes/ \
&&  rm /var/www/html/themes/theme-thedaily-v1.5.zip /var/www/html/themes/master.zip /var/www/html/themes/theme-cozy-v1.5.0.zip

# Create one volume for files and config
RUN mkdir -p /var/www/html/volume/config/ && mkdir -p /var/www/html/volume/files/
COPY ./database.ini /var/www/html/volume/config/
RUN rm /var/www/html/config/database.ini \
&& ln -s /var/www/html/volume/config/database.ini /var/www/html/config/database.ini \
&& rm -Rf /var/www/html/files/ \
&& ln -s /var/www/html/volume/files/ /var/www/html/files \
&& chown -R www-data:www-data /var/www/html/ \
&& chmod 600 /var/www/html/volume/config/database.ini \
&& chmod 600 /var/www/html/.htaccess

VOLUME /var/www/html/volume/

CMD ["apache2-foreground"]
