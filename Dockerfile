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
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) iconv pdo pdo_mysql mysqli gd
RUN pecl install imagick && docker-php-ext-enable imagick && pecl install solr && docker-php-ext-enable solr

# Add the Omeka-S PHP code
RUN wget -P /var/www/ https://github.com/omeka/omeka-s/releases/download/v3.1.2/omeka-s-3.1.2.zip
RUN unzip -q /var/www/omeka-s-3.1.2.zip -d /var/www/ \
&&  rm /var/www/omeka-s-3.1.2.zip \
&&  rm -rf /var/www/html/ \
&&  mv /var/www/omeka-s/ /var/www/html/

COPY ./imagemagick-policy.xml /etc/ImageMagick/policy.xml
COPY ./.htaccess /var/www/html/.htaccess

# Add some Omeka modules
RUN wget -P /var/www/html/modules/ https://github.com/HBLL-Collection-Development/omeka-s-any-cloud/releases/download/v2.0.0/AnyCloudv2.0.0.zip \
&& unzip -q /var/www/html/modules/AnyCloudv2.0.0.zip -d/var/www/html/modules \
&& rm /var/www/html/modules/AnyCloudv2.0.0.zip \
&& wget -P /var/www/html/modules/ https://github.com/Libnamic/Omeka-S-GoogleAnalytics/releases/download/v1.3.1/GoogleAnalytics-1.3.1.zip \
&& wget -P /var/www/html/modules/ https://gitlab.com/Daniel-KM/Omeka-S-module-EUCookieBar/-/archive/3.4.4/Omeka-S-module-EUCookieBar-3.4.4.zip \
&& unzip -q /var/www/html/modules/GoogleAnalytics-1.3.1.zip -d/var/www/html/modules \
&& rm /var/www/html/modules/GoogleAnalytics-1.3.1.zip \
&& unzip -q /var/www/html/modules/Omeka-S-module-EUCookieBar-3.4.4.zip -d/var/www/html/modules \
&& mv /var/www/html/modules/Omeka-S-module-EUCookieBar-3.4.4/ /var/www/html/modules/EUCookieBar/ \
&& rm /var/www/html/modules/Omeka-S-module-EUCookieBar-3.4.4.zip \
&& wget -P /var/www/html/modules/ https://github.com/omeka-s-modules/ItemCarouselBlock/archive/refs/heads/master.zip \
&& unzip -q /var/www/html/modules/master.zip -d/var/www/html/modules \
&& mv /var/www/html/modules/ItemCarouselBlock-master/ /var/www/html/modules/ItemCarouselBlock/ \
&& rm /var/www/html/modules/master.zip \
&& wget -P /var/www/html/modules/ https://github.com/Daniel-KM/Omeka-S-module-BulkEdit/releases/download/3.4.18/BulkEdit-3.4.18.zip \
&& unzip -q /var/www/html/modules/BulkEdit-3.4.18.zip -d/var/www/html/modules \
&& rm /var/www/html/modules/BulkEdit-3.4.18.zip \
&& wget -P /var/www/html/modules https://github.com/Daniel-KM/Omeka-s-module-BulkExport/releases/download/3.4.20/BulkExport-3.4.20.zip \
&& unzip -q /var/www/html/modules/BulkExport-3.4.20.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/BulkExport-3.4.20.zip \ 
&& wget -P /var/www/html/modules/ https://github.com/omeka-s-modules/CSVImport/releases/download/v2.3.2/CSVImport-2.3.2.zip \
&& unzip -q /var/www/html/modules/CSVImport-2.3.2.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/CSVImport-2.3.2.zip \
&& wget -P /var/www/html/modules/ https://github.com/omeka-s-modules/CustomVocab/releases/download/v1.5.0/CustomVocab-1.5.0.zip \
&& unzip -q /var/www/html/modules/CustomVocab-1.5.0.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/CustomVocab-1.5.0.zip \
&& wget -P /var/www/html/modules/ https://github.com/Daniel-KM/Omeka-S-module-EasyAdmin/releases/download/3.4.15/EasyAdmin-3.4.15.zip \
&& unzip -q /var/www/html/modules/EasyAdmin-3.4.15.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/EasyAdmin-3.4.15.zip \
&& wget -P /var/www/html/modules/ https://github.com/omeka-s-modules/FileSideload/releases/download/v1.6.1/FileSideload-1.6.1.zip \
&& unzip -q /var/www/html/modules/FileSideload-1.6.1.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/FileSideload-1.6.1.zip \
&& wget -P /var/www/html/modules/ https://github.com/Daniel-KM/Omeka-S-module-Common/archive/a765af0613d71e95177762d84b018f58a98a0e74.zip \
&& unzip -q /var/www/html/modules/a765af0613d71e95177762d84b018f58a98a0e74.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/a765af0613d71e95177762d84b018f58a98a0e74.zip \
&& mv /var/www/html/modules/Omeka-S-module-Common-a765af0613d71e95177762d84b018f58a98a0e74 /var/www/html/modules/Generic \
&& wget -P /var/www/html/modules/ https://github.com/Daniel-KM/Omeka-S-module-Log/releases/download/3.3.12.17/Log-3.3.12.17.zip \
&& unzip -q /var/www/html/modules/Log-3.3.12.17.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/Log-3.3.12.17.zip \
&& wget -P /var/www/html/modules/ https://github.com/omeka-s-modules/Mapping/releases/download/v1.6.0/Mapping-1.6.0.zip \
&& unzip -q /var/www/html/modules/Mapping-1.6.0.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/Mapping-1.6.0.zip \
&& wget -P /var/www/html/modules/ https://github.com/zerocrates/PdfEmbedS/releases/download/v1.2.1/PdfEmbed-1.2.1.zip \
&& unzip -q /var/www/html/modules/PdfEmbed-1.2.1.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/PdfEmbed-1.2.1.zip \
&& wget -P /var/www/html/modules/ https://github.com/Daniel-KM/Omeka-S-module-Reference/releases/download/3.4.47/Reference-3.4.47.zip \
&& unzip -q /var/www/html/modules/Reference-3.4.47.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/Reference-3.4.47.zip \
&& wget -P /var/www/html/modules/ https://github.com/biblibre/omeka-s-module-Search/releases/download/v0.15.4/Search-0.15.4.zip \
&& unzip -q /var/www/html/modules/Search-0.15.4.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/Search-0.15.4.zip \
&& wget -P /var/www/html/modules/  https://github.com/biblibre/omeka-s-module-Solr/releases/download/v0.14.0/Solr-0.14.0.zip \
&& unzip -q /var/www/html/modules/Solr-0.14.0.zip -d /var/www/html/modules \
&& rm /var/www/html/modules/Solr-0.14.0.zip

# Add some themes
RUN wget -P /var/www/html/themes/ https://github.com/omeka/theme-thedaily/releases/download/v1.5/theme-thedaily-v1.5.zip
RUN wget -P /var/www/html/themes/ https://github.com/omeka-s-themes/cozy/releases/download/v1.5.0/theme-cozy-v1.5.0.zip
RUN wget -P /var/www/html/themes/ https://github.com/DigitalGrinnell/centerrow/archive/9467e276bd959665cebe48c2d8bd48dc93663fda.zip
RUN wget -P /var/www/html/themes/ https://github.com/DigitalGrinnell/centerrow/archive/refs/heads/generic.zip
RUN unzip -q /var/www/html/themes/theme-thedaily-v1.5.zip -d /var/www/html/themes/ \
&&  unzip -q /var/www/html/themes/9467e276bd959665cebe48c2d8bd48dc93663fda.zip -d /var/www/html/themes/ \
&& mv /var/www/html/themes/centerrow-9467e276bd959665cebe48c2d8bd48dc93663fda /var/www/html/themes/centerrow-master \
&&  unzip -q /var/www/html/themes/generic.zip -d /var/www/html/themes/ \
&&  unzip -q /var/www/html/themes/theme-cozy-v1.5.0.zip -d /var/www/html/themes/ \
&&  rm /var/www/html/themes/theme-thedaily-v1.5.zip /var/www/html/themes/9467e276bd959665cebe48c2d8bd48dc93663fda.zip /var/www/html/themes/theme-cozy-v1.5.0.zip /var/www/html/themes/generic.zip

COPY ./robots.txt /var/www/html/
COPY ./google4399bb9e69fcbe34.html /var/www/html/
COPY ./set-up-database.sh /usr/local/
RUN chown -R www-data:www-data /var/www/html/ \
#&& chmod 600 /var/www/html/config/database.ini \
&& chmod 600 /var/www/html/.htaccess

CMD ["/usr/local/set-up-database.sh"]
#CMD ["apache2-foreground"]
