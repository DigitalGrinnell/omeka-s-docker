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
	libcurl4-gnutls-dev \
	wget

# Install the PHP extensions we need
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
RUN docker-php-ext-install -j$(nproc) iconv pdo pdo_mysql mysqli gd
RUN pecl install imagick && docker-php-ext-enable imagick && pecl install solr && docker-php-ext-enable solr

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
&&  rm /var/www/html/omeka-s-3.0-modules.zip \
&& wget -P /var/www/html/modules/ https://github.com/HBLL-Collection-Development/omeka-s-any-cloud/releases/download/v2.0.0/AnyCloudv2.0.0.zip \
&& unzip -q /var/www/html/modules/AnyCloudv2.0.0.zip -d/var/www/html/modules \
&& wget -P /var/www/html/modules/ https://github.com/Libnamic/Omeka-S-GoogleAnalytics/releases/download/v1.2.1/LibnamicOmekaSGA.zip \
&& wget -P /var/www/html/modules/ https://gitlab.com/Daniel-KM/Omeka-S-module-EUCookieBar/-/archive/3.3.4.3/Omeka-S-module-EUCookieBar-3.3.4.3.zip \
&& unzip -q /var/www/html/modules/LibnamicOmekaSGA.zip -d/var/www/html/modules \
&& rm /var/www/html/modules/LibnamicOmekaSGA.zip \
&& mv /var/www/html/modules/LibnamicOmekaSGA/GoogleAnalyticsconfig /var/www/html/modules/LibnamicOmekaSGA/config \
&& mv /var/www/html/modules/LibnamicOmekaSGA/GoogleAnalyticssrc /var/www/html/modules/LibnamicOmekaSGA/src \
&& mv /var/www/html/modules/LibnamicOmekaSGA/GoogleAnalyticsModule.php /var/www/html/modules/LibnamicOmekaSGA/Module.php \
&& unzip -q /var/www/html/modules/Omeka-S-module-EUCookieBar-3.3.4.3.zip -d/var/www/html/modules \
&& rm /var/www/html/modules/Omeka-S-module-EUCookieBar-3.3.4.3.zip

# Add some themes
RUN wget -P /var/www/html/themes/ https://github.com/omeka/theme-thedaily/releases/download/v1.5/theme-thedaily-v1.5.zip
RUN wget -P /var/www/html/themes/ https://github.com/omeka-s-themes/cozy/releases/download/v1.5.0/theme-cozy-v1.5.0.zip
RUN wget -P /var/www/html/themes/ https://github.com/DigitalGrinnell/centerrow/archive/refs/heads/master.zip
RUN wget -P /var/www/html/themes/ https://github.com/DigitalGrinnell/centerrow/archive/refs/heads/generic.zip
RUN unzip -q /var/www/html/themes/theme-thedaily-v1.5.zip -d /var/www/html/themes/ \
&&  unzip -q /var/www/html/themes/master.zip -d /var/www/html/themes/ \
&&  unzip -q /var/www/html/themes/generic.zip -d /var/www/html/themes/ \
&&  unzip -q /var/www/html/themes/theme-cozy-v1.5.0.zip -d /var/www/html/themes/ \
&&  rm /var/www/html/themes/theme-thedaily-v1.5.zip /var/www/html/themes/master.zip /var/www/html/themes/theme-cozy-v1.5.0.zip /var/www/html/themes/generic.zip

COPY ./set-up-database.sh /usr/local/
RUN chown -R www-data:www-data /var/www/html/ \
#&& chmod 600 /var/www/html/config/database.ini \
&& chmod 600 /var/www/html/.htaccess

CMD ["/usr/local/set-up-database.sh"]
#CMD ["apache2-foreground"]
