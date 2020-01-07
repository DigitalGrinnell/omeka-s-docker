FROM php:7.3-apache
# MAINTAINER Jonas Strassel <jo.strassel@gmail.com>
MAINTAINER Mark McFate <mcfatem@grinnell.edu>
## Install git ant and java
ARG version=2.0.2
RUN apt-get update && \
    apt-get -y install --no-install-recommends \
    git-core \
    apt-utils \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    libmemcached-dev \
    zlib1g-dev \
    imagemagick \
    libxml2-dev \
    libcurl4-gnutls-dev 
## Install php-extensions
RUN pecl install mcrypt-1.0.2
RUN docker-php-ext-enable mcrypt

RUN pecl install solr
RUN docker-php-ext-enable solr

RUN docker-php-ext-install -j$(nproc) iconv pdo pdo_mysql gd
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/

## Clone omeka-s - @TODO: replace zips with `git clone` eventually!
RUN rm -rf /var/www/html/*
ADD https://github.com/omeka/omeka-s/releases/download/v${version}/omeka-s-${version}.zip /tmp/omeka-s.zip
RUN unzip -d /tmp/ /tmp/omeka-s.zip && mv /tmp/omeka-s/* /var/www/html/ && rm -rf /tmp/omeka-s*
ADD https://raw.githubusercontent.com/omeka/omeka-s/develop/.htaccess.dist /var/www/html/.htaccess

## enable the rewrite module of apache
RUN a2enmod rewrite
## Create a default php.ini (Change this to `php.ini-production` when you are ready!)
COPY files/php.ini-development /usr/local/etc/php/php.ini

## Clone all the Omeka-S Modules
RUN cd /var/www/html/modules && curl "https://api.github.com/users/omeka-s-modules/repos?page=$PAGE&per_page=100" | grep -e 'git_url*' | cut -d \" -f 4 | xargs -L1 git clone

## Get the two modules we need that are missed by the above

ADD https://github.com/Daniel-KM/Omeka-S-module-Search/archive/master.zip /tmp/search.zip
RUN unzip -d /tmp/ /tmp/search.zip && mv /tmp/Omeka-S-module-Search-master /var/www/html/modules/Search && rm -rf /tmp/search.zip && rm -rf /tmp/Omeka-S-module*
ADD https://github.com/Daniel-KM/Omeka-S-module-Solr/archive/master.zip /tmp/solr.zip
RUN unzip -d /tmp/ /tmp/solr.zip && mv /tmp/Omeka-S-module-Solr-master /var/www/html/modules/Solr && rm -rf /tmp/solr.zip && rm -rf /tmp/Omeka-S-module*

## Clone all the Omeka-S Themes
RUN cd /var/www/html/themes && rm -r default && curl "https://api.github.com/users/omeka-s-themes/repos?page=$PAGE&per_page=100" | grep -e 'git_url*' | cut -d \" -f 4 | xargs -L1 git clone

## Get the themes for WMI and the Physics Instrument Museum
ADD https://github.com/DigitalGrinnell/centerrow/archive/master.zip /tmp/centerrow-master.zip
RUN unzip -d /tmp/ /tmp/centerrow-master.zip && mv /tmp/centerrow-master /var/www/html/themes/centerrow-master && rm -rf /tmp/centerrow*
ADD https://github.com/DigitalGrinnell/centerrow/archive/generic.zip /tmp/centerrow-generic.zip
RUN unzip -d /tmp/ /tmp/centerrow-generic.zip && mv /tmp/centerrow-generic /var/www/html/themes/centerrow-generic && rm -rf /tmp/centerrow*

## copy over the database and the apache config
COPY ./files/database.ini /var/www/html/config/database.ini
COPY ./files/apache-config.conf /etc/apache2/sites-enabled/000-default.conf
## set the file-rights
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R +w /var/www/html/files
## Expose the Port we'll provide Omeka on
EXPOSE 80
